<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TtsController extends Controller
{
    private string $piperBin = '/opt/piper/piper/piper';
    private string $model = '/opt/piper/models/fr_FR-siwis-medium.onnx';
    private string $cacheDir = '/var/spool/asterisk/tts_cache';

    private array $voices = [
        'siwis'   => ['id' => 'fr_FR-siwis-medium', 'label' => 'Sophie (Femme)'],
        'jessica' => ['id' => 'fr_FR-upmc-medium', 'speaker' => 0, 'label' => 'Jessica (Femme)'],
        'pierre'  => ['id' => 'fr_FR-upmc-medium', 'speaker' => 1, 'label' => 'Pierre (Homme)'],
        'tom'     => ['id' => 'fr_FR-tom-medium', 'label' => 'Tom (Homme)'],
    ];

    public function voices()
    {
        return response()->json($this->voices);
    }

    public function preview(Request $request)
    {
        $text = $request->input('text', '');
        $voice = $request->input('voice', 'siwis');
        if (empty(trim($text))) {
            return response()->json(['error' => 'Aucun texte'], 400);
        }

        $voiceCfg = $this->voices[$voice] ?? $this->voices['siwis'];
        $voiceModel = $voiceCfg['id'];
        $speakerId = $voiceCfg['speaker'] ?? null;
        $modelPath = "/opt/piper/models/{$voiceModel}.onnx";

        $hash = md5("{$voiceModel}:" . ($speakerId ?? '') . ":{$text}");
        $wavFile = "{$this->cacheDir}/{$hash}_preview.wav";

        // Generate if not cached
        if (!file_exists($wavFile)) {
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0755, true);
            }

            $cmd = [$this->piperBin, '--model', $modelPath, '--output_file', $wavFile];
            if ($speakerId !== null) {
                $cmd[] = '--speaker';
                $cmd[] = (string) $speakerId;
            }

            $process = proc_open(
                $cmd,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes
            );

            if (!is_resource($process)) {
                return response()->json(['error' => 'Impossible de lancer Piper'], 500);
            }

            fwrite($pipes[0], $text);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            if (!file_exists($wavFile)) {
                return response()->json(['error' => 'Echec de la synthese'], 500);
            }
        }

        return response()->file($wavFile, [
            'Content-Type' => 'audio/wav',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
