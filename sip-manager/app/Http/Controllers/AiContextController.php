<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiContextController extends Controller
{
    private string $baseDir = 'ai-context';

    private function basePath(string $sub = ''): string
    {
        // Storage::path resolves the correct disk root (private/ on Laravel 13)
        return \Illuminate\Support\Facades\Storage::path($this->baseDir . ($sub ? "/{$sub}" : ''));
    }

    public function index(Request $request)
    {
        $folders = $this->getFolders();
        $currentFolder = $request->input('folder', '');

        $dir = $currentFolder
            ? "{$this->baseDir}/{$currentFolder}"
            : $this->baseDir;

        $files = $this->getFiles($dir);
        $totalSize = array_sum(array_column($files, 'size'));

        return view('ai-context.index', compact('folders', 'files', 'totalSize', 'currentFolder'));
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'folder_name' => 'required|string|max:60|regex:/^[a-zA-Z0-9_-]+$/',
        ]);

        $path = storage_path("app/{$this->baseDir}/{$data['folder_name']}");
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        ActivityLog::log('Dossier RAG cree', $data['folder_name'], 'success');

        return back()->with('success', "Dossier \"{$data['folder_name']}\" cree.");
    }

    public function destroyFolder(string $folder)
    {
        $path = storage_path("app/{$this->baseDir}/{$folder}");
        if (is_dir($path)) {
            // Delete all files inside
            foreach (glob("{$path}/*") as $f) {
                unlink($f);
            }
            rmdir($path);
        }

        ActivityLog::log('Dossier RAG supprime', $folder, 'warning');

        return redirect()->route('ai-context.index')->with('success', "Dossier \"{$folder}\" supprime.");
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048',
            'folder' => 'nullable|string|max:60',
        ]);

        $file = $request->file('file');
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $folder = $request->input('folder', '');

        $dir = $folder ? "{$this->baseDir}/{$folder}" : $this->baseDir;
        Storage::putFileAs($dir, $file, $name);

        ActivityLog::log('Contexte AI uploade', ($folder ? "{$folder}/" : '') . $name, 'success');

        return back()->with('success', "Fichier \"{$name}\" uploade.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string|max:100|regex:/^[a-zA-Z0-9._-]+$/',
            'content' => 'required|string',
            'folder' => 'nullable|string|max:60',
        ]);

        $name = str_ends_with($data['filename'], '.txt') || str_ends_with($data['filename'], '.md')
            ? $data['filename']
            : $data['filename'] . '.txt';

        $folder = $data['folder'] ?? '';
        $dir = $folder ? "{$this->baseDir}/{$folder}" : $this->baseDir;

        Storage::put("{$dir}/{$name}", $data['content']);

        ActivityLog::log('Contexte AI cree', ($folder ? "{$folder}/" : '') . $name, 'success');

        return back()->with('success', "Fichier \"{$name}\" cree.");
    }

    public function edit(string $filename)
    {
        // filename can be "folder/file.txt" or "file.txt"
        $path = $this->basePath($filename);

        if (!file_exists($path)) {
            return response()->json(['error' => 'Fichier introuvable'], 404);
        }

        return response()->json([
            'name' => basename($filename),
            'content' => file_get_contents($path),
        ]);
    }

    public function update(Request $request, string $filename)
    {
        $data = $request->validate(['content' => 'required|string']);

        Storage::put("{$this->baseDir}/{$filename}", $data['content']);

        ActivityLog::log('Contexte AI modifie', $filename, 'info');

        return back()->with('success', "Fichier mis a jour.");
    }

    public function destroy(string $filename)
    {
        Storage::delete("{$this->baseDir}/{$filename}");

        ActivityLog::log('Contexte AI supprime', $filename, 'warning');

        return back()->with('success', "Fichier supprime.");
    }

    // --- API for builder ---

    public function apiFolders()
    {
        return response()->json($this->getFolders());
    }

    // --- Helpers ---

    private function getFolders(): array
    {
        $path = $this->basePath();
        $folders = [];

        if (is_dir($path)) {
            foreach (scandir($path) as $item) {
                if ($item === '.' || $item === '..') continue;
                if (is_dir("{$path}/{$item}")) {
                    $files = glob("{$path}/{$item}/*.{txt,md}", GLOB_BRACE);
                    $size = array_sum(array_map('filesize', $files ?: []));
                    $folders[] = [
                        'name' => $item,
                        'files' => count($files ?: []),
                        'size' => $size,
                    ];
                }
            }
        }

        usort($folders, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $folders;
    }

    private function getFiles(string $dir): array
    {
        $path = Storage::path($dir);
        $files = [];

        if (is_dir($path)) {
            foreach (glob("{$path}/*.{txt,md}", GLOB_BRACE) as $f) {
                $files[] = [
                    'name' => basename($f),
                    'size' => filesize($f),
                    'modified' => filemtime($f),
                    'preview' => mb_substr(file_get_contents($f), 0, 200),
                    'lines' => substr_count(file_get_contents($f), "\n") + 1,
                ];
            }
        }

        usort($files, fn($a, $b) => $b['modified'] - $a['modified']);
        return $files;
    }
}
