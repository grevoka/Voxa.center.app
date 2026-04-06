<?php

namespace App\Http\Controllers;

use App\Models\AudioFile;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AudioController extends Controller
{
    private string $soundsDir = '/var/lib/asterisk/sounds/custom';
    private string $mohDir    = '/var/lib/asterisk/moh';

    public function index()
    {
        $sounds = AudioFile::where('category', 'sound')->orderBy('name')->get();
        $moh    = AudioFile::where('category', 'moh')->orderBy('name')->get();
        $mohClasses = $moh->pluck('moh_class')->unique()->filter()->values();

        return view('audio.index', compact('sounds', 'moh', 'mohClasses'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file'     => 'required|file|mimes:wav,mp3,ogg,flac,m4a,aac|max:20480',
            'name'     => 'required|string|max:100',
            'category' => 'required|in:sound,moh',
            'moh_class' => 'nullable|string|max:50|regex:/^[a-z0-9_-]+$/',
        ]);

        $file = $request->file('file');
        $category = $request->input('category');
        $mohClass = $request->input('moh_class', 'default');

        // Generate safe filename
        $baseName = Str::slug($request->input('name')) . '-' . Str::random(6);
        $wavName  = $baseName . '.wav';

        // Save uploaded file to temp
        $tmpUpload = $file->store('uploads', 'local');
        $tmpPath   = storage_path("app/{$tmpUpload}");

        // Convert to Asterisk-compatible WAV (8kHz, 16-bit, mono PCM)
        $targetDir = $category === 'moh' ? $this->mohDir : $this->soundsDir;
        $targetPath = "{$targetDir}/{$wavName}";

        $convertResult = $this->convertToAsteriskWav($tmpPath, $targetPath);

        // Clean up temp
        @unlink($tmpPath);

        if (!$convertResult['success']) {
            return back()->with('error', 'Conversion echouee : ' . $convertResult['error']);
        }

        // Get duration
        $duration = $this->getAudioDuration($targetPath);
        $fileSize = file_exists($targetPath) ? filesize($targetPath) : 0;

        $audio = AudioFile::create([
            'name'          => $request->input('name'),
            'original_name' => $file->getClientOriginalName(),
            'filename'      => $wavName,
            'category'      => $category,
            'moh_class'     => $category === 'moh' ? $mohClass : null,
            'duration'      => $duration,
            'file_size'     => $fileSize,
            'format'        => 'wav',
            'created_by'    => auth()->id(),
        ]);

        // Reload MOH if category is moh
        if ($category === 'moh') {
            $this->reloadMoh();
        }

        ActivityLog::log('Audio uploade', "{$audio->name} ({$audio->category})", 'success', $audio);

        return back()->with('success', "Fichier « {$audio->name} » uploade et converti en WAV 8kHz mono.");
    }

    public function destroy(AudioFile $audio)
    {
        $name = $audio->name;
        $path = $audio->getFilePath();

        // Delete file from disk
        if (file_exists($path)) {
            @unlink($path);
        }

        $audio->delete();

        ActivityLog::log('Audio supprime', $name, 'warning');

        return back()->with('success', "Fichier « {$name} » supprime.");
    }

    public function play(AudioFile $audio)
    {
        $path = $audio->getFilePath();

        if (!file_exists($path)) {
            abort(404, 'Fichier non trouve');
        }

        return response()->file($path, [
            'Content-Type' => 'audio/wav',
            'Content-Disposition' => 'inline; filename="' . $audio->filename . '"',
        ]);
    }

    /**
     * API: list audio files for builder dropdowns.
     */
    public function api(Request $request)
    {
        $category = $request->input('category', 'sound');
        $files = AudioFile::where('category', $category)
            ->orderBy('name')
            ->get(['id', 'name', 'filename', 'duration', 'moh_class']);

        return response()->json($files->map(fn($f) => [
            'id'       => $f->id,
            'name'     => $f->name,
            'ref'      => $f->getAsteriskRef(),
            'duration' => $f->duration,
            'moh_class' => $f->moh_class,
        ]));
    }

    /**
     * Convert any audio to Asterisk-compatible WAV: PCM 16-bit, 8000Hz, mono.
     */
    private function convertToAsteriskWav(string $input, string $output): array
    {
        // Try sox first (better quality for telephony)
        $soxCmd = sprintf(
            'sox %s -r 8000 -c 1 -b 16 -e signed-integer %s 2>&1',
            escapeshellarg($input),
            escapeshellarg($output)
        );

        exec($soxCmd, $soxOut, $soxCode);

        if ($soxCode === 0 && file_exists($output)) {
            // Fix permissions
            @chmod($output, 0644);
            return ['success' => true];
        }

        // Fallback to ffmpeg
        $ffCmd = sprintf(
            'ffmpeg -y -i %s -ar 8000 -ac 1 -acodec pcm_s16le %s 2>&1',
            escapeshellarg($input),
            escapeshellarg($output)
        );

        exec($ffCmd, $ffOut, $ffCode);

        if ($ffCode === 0 && file_exists($output)) {
            @chmod($output, 0644);
            return ['success' => true];
        }

        $error = implode("\n", array_merge($soxOut, $ffOut));
        Log::error('Audio conversion failed', ['sox' => $soxCode, 'ffmpeg' => $ffCode, 'output' => $error]);

        return ['success' => false, 'error' => $error];
    }

    private function getAudioDuration(string $path): ?int
    {
        // Try soxi
        $cmd = sprintf('soxi -D %s 2>/dev/null', escapeshellarg($path));
        $result = trim(shell_exec($cmd) ?? '');
        if (is_numeric($result)) {
            return (int) round((float) $result);
        }

        // Try ffprobe
        $cmd = sprintf(
            'ffprobe -v quiet -show_entries format=duration -of csv="p=0" %s 2>/dev/null',
            escapeshellarg($path)
        );
        $result = trim(shell_exec($cmd) ?? '');
        if (is_numeric($result)) {
            return (int) round((float) $result);
        }

        return null;
    }

    private function reloadMoh(): void
    {
        exec('sudo /usr/sbin/asterisk -rx "moh reload" 2>&1', $out, $code);
        if ($code !== 0) {
            Log::warning('MOH reload failed', ['output' => implode("\n", $out)]);
        }
    }
}
