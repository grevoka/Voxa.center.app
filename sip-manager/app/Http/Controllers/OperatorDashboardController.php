<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\CallQueue;
use App\Models\CallerId;
use App\Models\Contact;
use Illuminate\Http\Request;

class OperatorDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $line = $user->sipLine;

        if (!$line) {
            return view('operator.no-line');
        }

        $ext = $line->extension;

        // An "operator-touched" call is any call where this extension appears
        // either as src/dst directly, or in the dial leg (dst_channel = PJSIP/<ext>-...).
        // The latter covers queue-distributed calls whose dst is the inbound number.
        $touched = function ($q) use ($ext) {
            $q->where('src', $ext)
              ->orWhere('dst', $ext)
              ->orWhere('dst_channel', 'LIKE', "PJSIP/{$ext}-%")
              ->orWhere('channel', 'LIKE', "PJSIP/{$ext}-%");
        };
        $today = now()->startOfDay();
        $todayStats = [
            'total'    => CallLog::whereDate('started_at', $today)->where($touched)->count(),
            'answered' => CallLog::whereDate('started_at', $today)->where('disposition', 'ANSWERED')->where($touched)->count(),
            'missed'   => CallLog::whereDate('started_at', $today)->where('disposition', 'NO ANSWER')->where($touched)->count(),
            'outbound' => CallLog::whereDate('started_at', $today)->where('direction', 'outbound')->where('src', $ext)->count(),
            'inbound'  => CallLog::whereDate('started_at', $today)->where('direction', 'inbound')->where($touched)->count(),
        ];

        // Recent calls
        $recentCalls = CallLog::where($touched)
            ->latest('started_at')
            ->take(15)
            ->get();

        // Queue memberships
        $queues = CallQueue::where('enabled', true)->get()->filter(function ($queue) use ($ext) {
            return collect($queue->members ?? [])->contains('extension', $ext);
        });

        // Voicemail count
        $vmPath = "/var/spool/asterisk/voicemail/default/{$ext}/INBOX";
        $vmCount = is_dir($vmPath) ? count(glob("{$vmPath}/msg*.txt")) : 0;

        return view('operator.dashboard', compact('line', 'todayStats', 'recentCalls', 'queues', 'vmCount'));
    }

    /**
     * Resolve a phone number against the contacts directory. Returns the
     * matching prenom/nom or null. Normalisation collapses French prefix
     * variants (0033xx, +33xx, 33xx, 0xx) before comparing.
     */
    public function contactLookup(Request $request)
    {
        $raw = trim((string) $request->input('number', ''));
        if ($raw === '') return response()->json(['contact' => null]);
        $norm = Contact::normalizePhone($raw);
        if (strlen($norm) < 8) return response()->json(['contact' => null]);
        $c = Contact::where('phone_normalized', $norm)->first(['prenom', 'nom']);
        return response()->json([
            'contact' => $c ? ['prenom' => $c->prenom, 'nom' => $c->nom] : null,
        ]);
    }

    /**
     * Strip the French international prefix so a caller "0033647635056" is
     * shown as "0647635056" — matches how humans read local numbers.
     */
    private function trimFrenchPrefix(?string $raw): ?string
    {
        if ($raw === null || $raw === '') return $raw;
        $d = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($d, '0033') && strlen($d) === 13) return '0' . substr($d, 4);
        if (str_starts_with($d, '33')   && strlen($d) === 11) return '0' . substr($d, 2);
        return $raw;
    }

    /**
     * Missed inbound calls that this operator hasn't called back yet.
     * Resolved as soon as an ANSWERED outbound from this operator to the same
     * number (matched on the last 9 digits to absorb 0/+33/0033 variants)
     * appears after the missed call.
     */
    public function missedCalls(Request $request)
    {
        $line = auth()->user()->sipLine;
        if (!$line) return response()->json(['missed' => []]);
        $ext = $line->extension;

        $since = now()->subDays(14);

        $touched = function ($q) use ($ext) {
            $q->where('src', $ext)
              ->orWhere('dst', $ext)
              ->orWhere('dst_channel', 'LIKE', "PJSIP/{$ext}-%")
              ->orWhere('channel', 'LIKE', "PJSIP/{$ext}-%");
        };

        // The operator's DIDs (REIMS, LILLE, …) — normalised so we can match
        // against CDR `dst` regardless of which prefix variant OVH delivered.
        $allowedCidNumbers = auth()->user()->availableCallerIds()->pluck('number');
        $tail9 = function ($s) {
            $d = preg_replace('/\D/', '', (string) $s);
            return strlen($d) >= 9 ? substr($d, -9) : $d;
        };
        $operatorDidTails = $allowedCidNumbers->map(fn ($n) => $tail9($n))->filter()->unique()->values();

        // Two sources of "missed":
        //   (1) the operator's extension was in the dial chain but didn't pick
        //       up (queue timeout, ring no-answer, busy) — the original case
        //   (2) the call landed on one of the operator's DIDs but no human
        //       answered (closed-hour TTS, voicemail-only, hangup) — Asterisk
        //       answered the channel for the announcement so disposition is
        //       ANSWERED, but lastapp won't be Queue or Dial in that case
        $inbound = CallLog::where('started_at', '>=', $since)
            ->where('direction', 'inbound')
            ->orderByDesc('started_at')
            ->get(['id', 'src', 'src_name', 'dst', 'started_at', 'disposition', 'channel', 'dst_channel']);

        $missed = $inbound->filter(function ($c) use ($ext, $operatorDidTails, $tail9) {
            $touched = $c->src === $ext
                || $c->dst === $ext
                || str_starts_with((string) $c->dst_channel, "PJSIP/{$ext}-")
                || str_starts_with((string) $c->channel,     "PJSIP/{$ext}-");
            $onOurDid = $operatorDidTails->contains($tail9($c->dst));
            if (!$touched && !$onOurDid) return false;
            // A human (or forwarded mobile) actually picked up iff the Dial /
            // Queue leg connected to a PJSIP destination and answered. Closed-
            // hour TTS calls have ANSWERED disposition too but never reach a
            // PJSIP dst_channel — those stay in the missed list.
            $humanAnswered = $c->disposition === 'ANSWERED'
                && !empty($c->dst_channel)
                && str_starts_with((string) $c->dst_channel, 'PJSIP/');
            return ! $humanAnswered;
        })->values();

        // Callbacks — ANY operator's ANSWERED outbound to the same caller
        // resolves the missed entry. Filtering by src=ext missed most of them
        // because the outbound CDR carries the caller_id (33352745112, …) in
        // src, never the extension; and when Post 1 picks up + calls back the
        // caller, Post 2 still saw the entry as unresolved.
        $callbacks = CallLog::where('direction', 'outbound')
            ->where('disposition', 'ANSWERED')
            ->where('started_at', '>=', $since)
            ->get(['dst', 'started_at']);

        $resolvedAt = [];
        foreach ($callbacks as $cb) {
            $k = $tail9($cb->dst);
            if (!isset($resolvedAt[$k]) || $resolvedAt[$k] < $cb->started_at) {
                $resolvedAt[$k] = $cb->started_at;
            }
        }

        // Preload contacts for the numbers we'll surface (one query, not N).
        $candidateNorms = $missed->map(fn ($m) => Contact::normalizePhone($m->src))
            ->filter(fn ($n) => strlen($n) >= 8)
            ->unique()
            ->values();
        $contactsByNorm = Contact::whereIn('phone_normalized', $candidateNorms)
            ->get(['prenom', 'nom', 'phone_normalized'])
            ->keyBy('phone_normalized');

        // Map CallerIds (REIMS / LILLE …) by normalised number so we can hint
        // the softphone which signature to pick before dialling back. The
        // operator must own this caller_id (group membership) — otherwise we
        // don't surface it; the softphone falls back to its current selection.
        $cidByNorm = CallerId::whereIn('number', $allowedCidNumbers)
            ->where('is_active', true)
            ->get(['number', 'label'])
            ->keyBy(fn ($c) => Contact::normalizePhone($c->number));

        $seen = [];
        $result = [];
        foreach ($missed as $m) {
            $k = $tail9($m->src);
            if ($k === '' || isset($seen[$k])) continue;
            $seen[$k] = true;
            if (isset($resolvedAt[$k]) && $resolvedAt[$k] > $m->started_at) continue;
            $norm = Contact::normalizePhone($m->src);
            $contact = $contactsByNorm->get($norm);
            // Avoid surfacing our own internal "->LABEL" prefix from CALLERID(name)
            // as if it were the caller's identity — it's the dialed DID label,
            // not the person on the other end.
            $rawName = $m->src_name && !str_starts_with($m->src_name, '->') ? $m->src_name : null;
            $name = $contact ? trim($contact->prenom . ' ' . $contact->nom) : $rawName;
            // Find the CallerId that owns the dialled DID — the softphone
            // will pre-select that signature so the callback goes out on the
            // matching trunk (e.g. LILLE call back → OVH-3 not OVH-2).
            $dstNorm = Contact::normalizePhone($m->dst);
            $cid = $cidByNorm->get($dstNorm);
            // App-wide timezone is UTC, but the operator lives in Europe/Paris.
            // Convert to the local zone before formatting; iso stays UTC-anchored
            // (has offset) so client-side comparisons still work.
            $local = $m->started_at->copy()->setTimezone('Europe/Paris');
            $result[] = [
                'id'         => $m->id,
                'number'     => $this->trimFrenchPrefix($m->src),
                'name'       => $name,
                'time'       => $local->format('d/m H:i'),
                'iso'        => $m->started_at->toIso8601String(),
                'cid_number' => $cid?->number,
                'cid_label'  => $cid?->label,
            ];
        }

        return response()->json(['missed' => $result]);
    }

    public function calls(Request $request)
    {
        $line = auth()->user()->sipLine;
        if (!$line) return redirect()->route('operator.dashboard');

        $ext = $line->extension;

        // Queue-distributed calls only carry the operator's extension in the
        // channel/dst_channel ("PJSIP/<ext>-..."), not in src/dst. Match all
        // four so the journal surfaces inbound queue legs too.
        $query = CallLog::where(function ($q) use ($ext) {
            $q->where('src', $ext)
              ->orWhere('dst', $ext)
              ->orWhere('dst_channel', 'LIKE', "PJSIP/{$ext}-%")
              ->orWhere('channel', 'LIKE', "PJSIP/{$ext}-%");
        });

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('src', 'like', "%{$s}%")->orWhere('dst', 'like', "%{$s}%")->orWhere('src_name', 'like', "%{$s}%"));
        }
        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }
        if ($request->filled('date_from')) {
            $query->where('started_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('started_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->latest('started_at')->paginate(50)->withQueryString();

        // Line label lookup (dst → REIMS / LILLE …) — keyed on the last 9
        // digits so 0033xx / +33xx / 0xx all match. The view uses this to
        // slap a small badge on every inbound row.
        $tail9 = fn ($s) => (function ($x) {
            $d = preg_replace('/\D/', '', (string) $x);
            return strlen($d) >= 9 ? substr($d, -9) : $d;
        })($s);
        $lineBadges = CallerId::where('is_active', true)->get(['number', 'label'])
            ->mapWithKeys(fn ($c) => [$tail9($c->number) => $c->label])
            ->toArray();

        return view('operator.calls', compact('logs', 'line', 'lineBadges'));
    }

    public function voicemail()
    {
        $line = auth()->user()->sipLine;
        if (!$line) return redirect()->route('operator.dashboard');

        $messages = $this->getMessages($line->extension);

        return view('operator.voicemail', compact('messages', 'line'));
    }

    public function playVoicemail(string $folder, string $file)
    {
        $line = auth()->user()->sipLine;
        if (!$line || !preg_match('/^[a-zA-Z]+$/', $folder) || !preg_match('/^msg[0-9]+$/', $file)) {
            abort(404);
        }

        $path = "/var/spool/asterisk/voicemail/default/{$line->extension}/{$folder}/{$file}.wav";
        if (!file_exists($path)) abort(404);

        return response()->file($path, ['Content-Type' => 'audio/wav']);
    }

    public function destroyVoicemail(string $folder, string $file)
    {
        $line = auth()->user()->sipLine;
        if (!$line || !preg_match('/^[a-zA-Z]+$/', $folder) || !preg_match('/^msg[0-9]+$/', $file)) {
            abort(404);
        }

        $base = "/var/spool/asterisk/voicemail/default/{$line->extension}/{$folder}/{$file}";
        foreach (['wav', 'wav49', 'gsm', 'WAV', 'txt'] as $ext) {
            $f = "{$base}.{$ext}";
            if (file_exists($f)) @unlink($f);
        }

        return back()->with('success', 'Message supprime.');
    }

    private function getMessages(string $extension): array
    {
        $messages = [];
        $folders = ['INBOX' => 'Nouveaux', 'Old' => 'Lus'];

        foreach ($folders as $folder => $label) {
            $dir = "/var/spool/asterisk/voicemail/default/{$extension}/{$folder}";
            if (!is_dir($dir)) continue;

            foreach (glob("{$dir}/msg*.txt") as $txtFile) {
                $msgId = pathinfo($txtFile, PATHINFO_FILENAME);
                $meta = $this->parseMsg($txtFile);
                $hasAudio = file_exists("{$dir}/{$msgId}.wav") || file_exists("{$dir}/{$msgId}.WAV");

                if (!$hasAudio && empty($meta)) continue;

                $messages[] = [
                    'id'           => $msgId,
                    'folder'       => $folder,
                    'folder_label' => $label,
                    'callerid'     => $meta['callerid'] ?? 'Inconnu',
                    'origdate'     => $meta['origdate'] ?? '',
                    'origtime'     => isset($meta['origtime']) ? (int)$meta['origtime'] : 0,
                    'duration'     => isset($meta['duration']) ? (int)$meta['duration'] : 0,
                    'has_audio'    => $hasAudio,
                ];
            }
        }

        usort($messages, fn($a, $b) => $b['origtime'] <=> $a['origtime']);
        return $messages;
    }

    private function parseMsg(string $path): array
    {
        if (!file_exists($path)) return [];
        $data = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $data[trim($key)] = trim($value);
            }
        }
        return $data;
    }
}
