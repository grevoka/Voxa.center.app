<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordingController extends Controller
{
    private string $recordingPath = '/var/spool/asterisk/monitor';

    public function index(Request $request)
    {
        $query = DB::connection('asterisk')
            ->table('cdr')
            ->where('billsec', '>', 0)
            ->orderByDesc('calldate');

        // Filter by operator (extension from channel name)
        if ($request->filled('operator')) {
            $ext = $request->input('operator');
            $query->where(function ($q) use ($ext) {
                $q->where('channel', 'like', "PJSIP/{$ext}-%")
                  ->orWhere('dstchannel', 'like', "PJSIP/{$ext}-%");
            });
        }

        if ($request->filled('direction')) {
            $dir = $request->input('direction');
            if ($dir === 'inbound') {
                $query->where('dcontext', 'like', 'from-trunk%');
            } elseif ($dir === 'outbound') {
                $query->where('dcontext', 'from-internal')
                      ->where('dstchannel', 'like', 'PJSIP/trunk-%');
            } elseif ($dir === 'internal') {
                $query->where('dcontext', 'from-internal')
                      ->where('dstchannel', 'not like', 'PJSIP/trunk-%')
                      ->where('dstchannel', '!=', '');
            }
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('src', 'like', "%{$s}%")
                  ->orWhere('dst', 'like', "%{$s}%")
                  ->orWhere('clid', 'like', "%{$s}%");
            });
        }

        $records = $query->paginate(30)->withQueryString();

        // Check which recordings have audio files
        foreach ($records as $r) {
            $r->has_recording = file_exists("{$this->recordingPath}/{$r->uniqueid}.wav");
            $r->direction = $this->detectDirection($r);
            $r->operator_ext = $this->extractExtension($r);
        }

        $extensions = DB::connection('asterisk')
            ->table('ps_endpoints')
            ->where('id', 'not like', 'trunk-%')
            ->pluck('id');

        return view('recordings.index', compact('records', 'extensions'));
    }

    public function operatorIndex()
    {
        $user = auth()->user();
        $ext = $user->sipLine?->extension;

        if (!$ext) {
            return view('recordings.operator', ['records' => collect(), 'ext' => null]);
        }

        $records = DB::connection('asterisk')
            ->table('cdr')
            ->where('billsec', '>', 0)
            ->where(function ($q) use ($ext) {
                $q->where('channel', 'like', "PJSIP/{$ext}-%")
                  ->orWhere('dstchannel', 'like', "PJSIP/{$ext}-%");
            })
            ->orderByDesc('calldate')
            ->paginate(30);

        foreach ($records as $r) {
            $r->has_recording = file_exists("{$this->recordingPath}/{$r->uniqueid}.wav");
            $r->direction = $this->detectDirection($r);
        }

        return view('recordings.operator', compact('records', 'ext'));
    }

    public function play(string $uniqueid)
    {
        $file = "{$this->recordingPath}/{$uniqueid}.wav";

        if (!file_exists($file)) {
            abort(404, 'Enregistrement introuvable');
        }

        // Security: operator can only play their own recordings
        $user = auth()->user();
        if ($user->isOperator()) {
            $ext = $user->sipLine?->extension;
            $cdr = DB::connection('asterisk')
                ->table('cdr')
                ->where('uniqueid', $uniqueid)
                ->first();

            if (!$cdr || (!str_contains($cdr->channel, "PJSIP/{$ext}-") && !str_contains($cdr->dstchannel, "PJSIP/{$ext}-"))) {
                abort(403, 'Acces refuse');
            }
        }

        return response()->file($file, [
            'Content-Type' => 'audio/wav',
            'Content-Disposition' => "inline; filename=\"{$uniqueid}.wav\"",
        ]);
    }

    public function destroy(string $uniqueid)
    {
        // Sanitize uniqueid to prevent path traversal
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '', $uniqueid);
        $file = "{$this->recordingPath}/{$safe}.wav";

        if (file_exists($file)) {
            // Use sudo rm since files are owned by root (Asterisk runs as root)
            exec(sprintf('sudo rm -f %s 2>&1', escapeshellarg($file)), $out, $code);
            if ($code !== 0) {
                return back()->with('error', 'Impossible de supprimer le fichier.');
            }
        }

        return back()->with('success', 'Enregistrement supprime.');
    }

    private function detectDirection($cdr): string
    {
        if (str_contains($cdr->dcontext, 'from-trunk')) return 'inbound';
        if (str_contains($cdr->dstchannel ?? '', 'trunk-')) return 'outbound';
        return 'internal';
    }

    private function extractExtension($cdr): string
    {
        // Extract extension from PJSIP/1002-00000003
        if (preg_match('/PJSIP\/(\d+)-/', $cdr->channel, $m)) {
            if (!str_starts_with($m[1], 'trunk')) return $m[1];
        }
        if (preg_match('/PJSIP\/(\d+)-/', $cdr->dstchannel ?? '', $m)) {
            return $m[1];
        }
        return $cdr->src;
    }
}
