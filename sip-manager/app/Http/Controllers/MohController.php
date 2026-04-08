<?php

namespace App\Http\Controllers;

use App\Models\MohStream;
use App\Models\MohPlaylist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MohController extends Controller
{
    private string $mohDir = '/var/lib/asterisk/moh';
    private string $mohConf = '/etc/asterisk/musiconhold.conf';

    public function index()
    {
        $files = $this->getMohFiles();
        $currentSource = $this->getCurrentDefault();
        $streams = MohStream::orderBy('name')->get();
        $playlists = MohPlaylist::orderBy('name')->get();
        return view('moh.index', compact('files', 'currentSource', 'streams', 'playlists'));
    }

    /**
     * API: return MOH classes for dropdowns.
     */
    public function api()
    {
        $classes = $this->getMohClasses();

        foreach (MohStream::where('enabled', true)->get() as $stream) {
            $classes[] = [
                'name' => $stream->getMohClassName(),
                'mode' => 'custom',
                'directory' => '',
                'files' => [],
                'display_name' => $stream->display_name ?: $stream->name,
                'is_stream' => true,
            ];
        }

        foreach (MohPlaylist::where('enabled', true)->get() as $playlist) {
            $classes[] = [
                'name' => $playlist->getMohClassName(),
                'mode' => 'playlist',
                'directory' => '',
                'files' => $playlist->files ?? [],
                'display_name' => $playlist->display_name ?: $playlist->name,
                'is_playlist' => true,
            ];
        }

        return response()->json($classes);
    }

    // ── Default source ──

    public function setDefault(Request $request)
    {
        $source = $request->input('source', 'rotation');

        $this->rebuildMohConf("\0", $source);
        $this->reloadMoh();

        $label = match (true) {
            $source === 'rotation' => 'Rotation automatique de tous les fichiers',
            str_starts_with($source, 'file:') => 'Fichier: ' . str_replace(['_', '-'], ' ', substr($source, 5)),
            str_starts_with($source, 'playlist:') => 'Playlist: ' . (MohPlaylist::find(substr($source, 9))?->display_name ?? substr($source, 9)),
            str_starts_with($source, 'stream:') => 'Stream: ' . (MohStream::find(substr($source, 7))?->display_name ?? substr($source, 7)),
            default => $source,
        };
        return back()->with('success', "Source par defaut: {$label}");
    }

    // ── Streams ──

    public function storeStream(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:80|unique:moh_streams,name|regex:/^[a-zA-Z0-9_-]+$/',
            'display_name' => 'nullable|string|max:150',
            'url'          => 'required|url|max:500',
        ]);
        $data['created_by'] = auth()->id();

        MohStream::create($data);
        $this->rebuildMohConf();
        $this->reloadMoh();

        return back()->with('success', "Flux \"{$data['name']}\" ajoute.");
    }

    public function toggleStream(MohStream $stream)
    {
        $stream->update(['enabled' => !$stream->enabled]);
        $this->rebuildMohConf();
        $this->reloadMoh();

        $label = $stream->enabled ? 'active' : 'desactive';
        return back()->with('success', "Flux \"{$stream->name}\" {$label}.");
    }

    public function destroyStream(MohStream $stream)
    {
        $name = $stream->name;
        $stream->delete();
        $this->rebuildMohConf();
        $this->reloadMoh();
        return back()->with('success', "Flux \"{$name}\" supprime.");
    }

    // ── Playlists ──

    public function storePlaylist(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:80|unique:moh_playlists,name|regex:/^[a-zA-Z0-9_-]+$/',
            'display_name' => 'nullable|string|max:150',
            'files'        => 'required|array|min:1',
            'files.*'      => 'required|string|max:200',
        ]);
        $data['created_by'] = auth()->id();

        MohPlaylist::create($data);
        $this->rebuildMohConf();
        $this->reloadMoh();

        return back()->with('success', "Playlist \"{$data['name']}\" creee.");
    }

    public function updatePlaylist(Request $request, MohPlaylist $playlist)
    {
        $data = $request->validate([
            'display_name' => 'nullable|string|max:150',
            'files'        => 'required|array|min:1',
            'files.*'      => 'required|string|max:200',
        ]);

        $playlist->update($data);
        $this->rebuildMohConf();
        $this->reloadMoh();

        return back()->with('success', "Playlist \"{$playlist->name}\" mise a jour.");
    }

    public function togglePlaylist(MohPlaylist $playlist)
    {
        $playlist->update(['enabled' => !$playlist->enabled]);
        $this->rebuildMohConf();
        $this->reloadMoh();

        $label = $playlist->enabled ? 'activee' : 'desactivee';
        return back()->with('success', "Playlist \"{$playlist->name}\" {$label}.");
    }

    public function destroyPlaylist(MohPlaylist $playlist)
    {
        $name = $playlist->name;
        $playlist->delete();
        $this->rebuildMohConf();
        $this->reloadMoh();
        return back()->with('success', "Playlist \"{$name}\" supprimee.");
    }

    // ── Playback ──

    public function play(string $class, string $file)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $class) || !preg_match('/^[a-zA-Z0-9_.-]+$/', $file)) {
            abort(400);
        }

        $path = $this->mohDir . '/' . $file;
        foreach (['.wav', '.ulaw', '.alaw', '.gsm', '.sln', '.sln16', ''] as $ext) {
            $tryPath = $path . $ext;
            if (file_exists($tryPath)) {
                $mime = str_ends_with($tryPath, '.wav') ? 'audio/wav' : 'application/octet-stream';
                return response()->file($tryPath, ['Content-Type' => $mime]);
            }
        }
        abort(404);
    }

    // ── Private helpers ──

    private function getMohFiles(): array
    {
        $files = [];
        if (!is_dir($this->mohDir)) return $files;

        $audioExts = ['wav', 'ulaw', 'alaw', 'gsm', 'sln', 'sln16'];
        foreach (scandir($this->mohDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, $audioExts)) continue;

            $baseName = pathinfo($entry, PATHINFO_FILENAME);
            $fullPath = $this->mohDir . '/' . $entry;
            $size = filesize($fullPath);
            $displayName = ucfirst(str_replace(['_', '-'], ' ', $baseName));

            $files[] = [
                'name'         => $baseName,
                'file'         => $entry,
                'display_name' => $displayName,
                'ext'          => $ext,
                'size'         => $size,
                'size_human'   => $this->humanFileSize($size),
                'playable'     => $ext === 'wav',
            ];
        }
        usort($files, fn($a, $b) => $a['display_name'] <=> $b['display_name']);
        return $files;
    }

    /**
     * Returns the current default source descriptor, e.g.:
     * "rotation", "file:filename", "playlist:3", "stream:2"
     */
    private function getCurrentDefault(): string
    {
        $file = storage_path('app/moh_default.txt');
        if (file_exists($file)) {
            $val = trim(file_get_contents($file));
            if ($val !== '') return $val;
        }
        return 'rotation';
    }

    private function saveCurrentDefault(string $source): void
    {
        $dir = storage_path('app');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents(storage_path('app/moh_default.txt'), $source);
    }

    private function rebuildMohConf(string $singleFile = "\0", string|null $source = null): void
    {
        // Backward compat: if called with old-style singleFile param
        if ($singleFile !== "\0") {
            // Legacy call from toggle/destroy — keep current source
            $source = $this->getCurrentDefault();
        }
        if ($source === null) {
            $source = $this->getCurrentDefault();
        }

        $this->saveCurrentDefault($source);

        $content = "; musiconhold.conf — auto-generated by Voxa Center\n";
        $content .= "; default-source: {$source}\n";
        $content .= "[general]\n\n";

        // Build [default] section based on source type
        if (str_starts_with($source, 'playlist:')) {
            $playlist = MohPlaylist::find(substr($source, 9));
            if ($playlist && $playlist->enabled) {
                $content .= "[default]\n";
                $content .= "mode=playlist\n";
                $content .= "sort=alpha\n";
                foreach ($playlist->files ?? [] as $file) {
                    $content .= "entry={$this->mohDir}/{$file}\n";
                }
            } else {
                // Playlist deleted/disabled, fallback to rotation
                $content .= "[default]\nmode=files\ndirectory=moh\n";
            }
        } elseif (str_starts_with($source, 'stream:')) {
            $stream = MohStream::find(substr($source, 7));
            if ($stream && $stream->enabled) {
                $content .= "[default]\n";
                $content .= "mode=custom\n";
                $content .= "application=/usr/bin/ffmpeg -i " . $stream->url . " -f s16le -ar 8000 -ac 1 -loglevel quiet pipe:1\n";
                $content .= "format=slin\n";
            } else {
                $content .= "[default]\nmode=files\ndirectory=moh\n";
            }
        } elseif (str_starts_with($source, 'file:')) {
            $file = substr($source, 5);
            $content .= "[default]\n";
            $content .= "mode=playlist\n";
            $content .= "entry={$this->mohDir}/{$file}\n";
            $content .= "sort=alpha\n";
        } else {
            // rotation
            $content .= "[default]\n";
            $content .= "mode=files\n";
            $content .= "directory=moh\n";
        }

        // Add all playlist classes (separate from default)
        foreach (MohPlaylist::where('enabled', true)->get() as $playlist) {
            $content .= "\n" . $playlist->toMohConf($this->mohDir) . "\n";
        }

        // Add all stream classes (separate from default)
        foreach (MohStream::where('enabled', true)->get() as $stream) {
            $content .= "\n" . $stream->toMohConf() . "\n";
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'moh_');
        file_put_contents($tmpFile, $content);

        exec(sprintf(
            'sudo /usr/bin/tee %s < %s > /dev/null 2>&1',
            escapeshellarg($this->mohConf),
            escapeshellarg($tmpFile)
        ), $out, $code);
        unlink($tmpFile);

        if ($code !== 0) {
            Log::error('Failed to write musiconhold.conf', ['code' => $code]);
        }
    }

    private function reloadMoh(): void
    {
        exec('sudo /usr/sbin/asterisk -rx "moh reload" 2>&1', $out, $code);
        if ($code !== 0) {
            Log::warning('MOH reload failed', ['output' => implode("\n", $out)]);
        }
    }

    private function getMohClasses(): array
    {
        $classes = [];
        $output = $this->asteriskCmd('moh show classes');
        $current = null;

        foreach (explode("\n", $output) as $line) {
            if (preg_match('/^Class:\s*(.+)/', $line, $m)) {
                $current = trim($m[1]);
                $classes[$current] = ['name' => $current, 'mode' => '', 'directory' => '', 'files' => []];
            } elseif ($current && preg_match('/^\s*Mode:\s*(.+)/', $line, $m)) {
                $classes[$current]['mode'] = trim($m[1]);
            } elseif ($current && preg_match('/^\s*Directory:\s*(.+)/', $line, $m)) {
                $dir = trim($m[1]);
                if (!str_starts_with($dir, '/')) $dir = '/var/lib/asterisk/' . $dir;
                $classes[$current]['directory'] = $dir;
            }
        }

        $filesOutput = $this->asteriskCmd('moh show files');
        $current = null;
        foreach (explode("\n", $filesOutput) as $line) {
            if (preg_match('/^Class:\s*(.+)/', $line, $m)) {
                $current = trim($m[1]);
            } elseif ($current && preg_match('/^\s*File:\s*(.+)/', $line, $m)) {
                if (isset($classes[$current])) {
                    $classes[$current]['files'][] = basename(trim($m[1]));
                }
            }
        }
        return array_values($classes);
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
        return round($bytes / 1048576, 1) . ' Mo';
    }

    private function asteriskCmd(string $cmd): string
    {
        $output = [];
        exec('sudo /usr/sbin/asterisk -rx ' . escapeshellarg($cmd) . ' 2>&1', $output);
        return implode("\n", $output);
    }
}
