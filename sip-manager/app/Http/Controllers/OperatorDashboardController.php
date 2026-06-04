<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\CallQueue;
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

        $missed = CallLog::where('started_at', '>=', $since)
            ->where('disposition', 'NO ANSWER')
            ->where('direction', 'inbound')
            ->where($touched)
            ->orderByDesc('started_at')
            ->get(['id', 'src', 'src_name', 'started_at']);

        $callbacks = CallLog::where('src', $ext)
            ->where('disposition', 'ANSWERED')
            ->where('direction', 'outbound')
            ->where('started_at', '>=', $since)
            ->get(['dst', 'started_at']);

        $tail9 = function ($s) {
            $d = preg_replace('/\D/', '', (string) $s);
            return strlen($d) >= 9 ? substr($d, -9) : $d;
        };

        $resolvedAt = [];
        foreach ($callbacks as $cb) {
            $k = $tail9($cb->dst);
            if (!isset($resolvedAt[$k]) || $resolvedAt[$k] < $cb->started_at) {
                $resolvedAt[$k] = $cb->started_at;
            }
        }

        $seen = [];
        $result = [];
        foreach ($missed as $m) {
            $k = $tail9($m->src);
            if ($k === '' || isset($seen[$k])) continue;
            $seen[$k] = true;
            if (isset($resolvedAt[$k]) && $resolvedAt[$k] > $m->started_at) continue;
            $result[] = [
                'id'     => $m->id,
                'number' => $m->src,
                'name'   => $m->src_name,
                'time'   => $m->started_at->format('d/m H:i'),
            ];
        }

        return response()->json(['missed' => $result]);
    }

    public function calls(Request $request)
    {
        $line = auth()->user()->sipLine;
        if (!$line) return redirect()->route('operator.dashboard');

        $ext = $line->extension;

        $query = CallLog::where(fn($q) => $q->where('src', $ext)->orWhere('dst', $ext));

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

        return view('operator.calls', compact('logs', 'line'));
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
