<?php

namespace App\Http\Controllers;

use App\Models\SipLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class VoicemailController extends Controller
{
    private string $vmBasePath = '/var/spool/asterisk/voicemail/default';

    public function index(Request $request)
    {
        $lines = SipLine::where('voicemail_enabled', true)
            ->orderBy('extension')
            ->get();

        $selectedExt = $request->input('extension', $lines->first()?->extension);
        $messages = $selectedExt ? $this->getMessages($selectedExt) : [];

        return view('voicemail.index', compact('lines', 'selectedExt', 'messages'));
    }

    public function play(string $extension, string $folder, string $file)
    {
        // Sanitize inputs
        if (!preg_match('/^[0-9]+$/', $extension) || !preg_match('/^[a-zA-Z]+$/', $folder) || !preg_match('/^msg[0-9]+$/', $file)) {
            abort(404);
        }

        $path = "{$this->vmBasePath}/{$extension}/{$folder}/{$file}.wav";

        if (!file_exists($path)) {
            abort(404, 'Message non trouve');
        }

        return response()->file($path, [
            'Content-Type' => 'audio/wav',
            'Content-Disposition' => "inline; filename=\"{$file}.wav\"",
        ]);
    }

    public function destroy(string $extension, string $folder, string $file)
    {
        if (!preg_match('/^[0-9]+$/', $extension) || !preg_match('/^[a-zA-Z]+$/', $folder) || !preg_match('/^msg[0-9]+$/', $file)) {
            abort(404);
        }

        $base = "{$this->vmBasePath}/{$extension}/{$folder}/{$file}";

        // Delete all formats (wav, wav49, gsm, txt)
        foreach (['wav', 'wav49', 'gsm', 'WAV', 'txt'] as $ext) {
            $f = "{$base}.{$ext}";
            if (file_exists($f)) {
                @unlink($f);
            }
        }

        return back()->with('success', 'Message supprime.');
    }

    /**
     * Read voicemail messages from the spool directory.
     */
    private function getMessages(string $extension): array
    {
        $messages = [];

        $folders = ['INBOX' => 'Nouveaux', 'Old' => 'Lus'];

        foreach ($folders as $folder => $label) {
            $dir = "{$this->vmBasePath}/{$extension}/{$folder}";
            if (!is_dir($dir)) continue;

            $files = glob("{$dir}/msg*.txt");
            foreach ($files as $txtFile) {
                $msgId = pathinfo($txtFile, PATHINFO_FILENAME); // msg0000
                $meta = $this->parseMessageFile($txtFile);

                // Check if audio file exists
                $hasAudio = file_exists("{$dir}/{$msgId}.wav")
                    || file_exists("{$dir}/{$msgId}.wav49")
                    || file_exists("{$dir}/{$msgId}.WAV");

                if (!$hasAudio && empty($meta)) continue;

                $messages[] = [
                    'id'        => $msgId,
                    'folder'    => $folder,
                    'folder_label' => $label,
                    'callerid'  => $meta['callerid'] ?? 'Inconnu',
                    'origdate'  => $meta['origdate'] ?? '',
                    'origtime'  => isset($meta['origtime']) ? (int) $meta['origtime'] : 0,
                    'duration'  => isset($meta['duration']) ? (int) $meta['duration'] : 0,
                    'has_audio' => $hasAudio,
                ];
            }
        }

        // Sort by origtime descending (newest first)
        usort($messages, fn($a, $b) => $b['origtime'] <=> $a['origtime']);

        return $messages;
    }

    /**
     * Parse Asterisk voicemail msg*.txt metadata file.
     */
    private function parseMessageFile(string $path): array
    {
        if (!file_exists($path)) return [];

        $data = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $data[trim($key)] = trim($value);
            }
        }
        return $data;
    }
}
