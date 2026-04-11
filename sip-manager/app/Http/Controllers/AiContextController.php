<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiContextController extends Controller
{
    private string $dir = 'ai-context';

    public function index()
    {
        $files = [];
        $path = storage_path("app/{$this->dir}");

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

        $totalSize = array_sum(array_column($files, 'size'));

        return view('ai-context.index', compact('files', 'totalSize'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048|mimes:txt,md,text',
        ]);

        $file = $request->file('file');
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

        Storage::putFileAs($this->dir, $file, $name);

        ActivityLog::log('Contexte AI uploade', $name, 'success');

        return back()->with('success', "Fichier \"{$name}\" uploade.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string|max:100|regex:/^[a-zA-Z0-9._-]+$/',
            'content' => 'required|string',
        ]);

        $name = str_ends_with($data['filename'], '.txt') || str_ends_with($data['filename'], '.md')
            ? $data['filename']
            : $data['filename'] . '.txt';

        Storage::put("{$this->dir}/{$name}", $data['content']);

        ActivityLog::log('Contexte AI cree', $name, 'success');

        return back()->with('success', "Fichier \"{$name}\" cree.");
    }

    public function edit(string $filename)
    {
        $path = storage_path("app/{$this->dir}/{$filename}");

        if (!file_exists($path)) {
            return back()->with('error', 'Fichier introuvable.');
        }

        $content = file_get_contents($path);

        return response()->json([
            'name' => $filename,
            'content' => $content,
        ]);
    }

    public function update(Request $request, string $filename)
    {
        $data = $request->validate([
            'content' => 'required|string',
        ]);

        Storage::put("{$this->dir}/{$filename}", $data['content']);

        ActivityLog::log('Contexte AI modifie', $filename, 'info');

        return back()->with('success', "Fichier \"{$filename}\" mis a jour.");
    }

    public function destroy(string $filename)
    {
        Storage::delete("{$this->dir}/{$filename}");

        ActivityLog::log('Contexte AI supprime', $filename, 'warning');

        return back()->with('success', "Fichier \"{$filename}\" supprime.");
    }
}
