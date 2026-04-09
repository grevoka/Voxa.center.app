<?php

namespace App\Http\Controllers;

class WebPhoneController extends Controller
{
    public function config()
    {
        $user = auth()->user();
        $line = $user->sipLine;

        if (!$line) {
            return response()->json(['error' => 'No line assigned'], 404);
        }

        return response()->json([
            'extension'  => $line->extension,
            'password'   => $line->decrypted_secret,
            'name'       => $line->name,
            'caller_id'  => $line->caller_id ?? $line->extension,
            'ws_uri'     => $this->getWsUri(),
            'realm'      => request()->getHost(),
        ]);
    }

    private function getWsUri(): string
    {
        // Behind reverse proxy, check X-Forwarded-Proto header
        $isSecure = request()->isSecure()
            || request()->header('X-Forwarded-Proto') === 'https'
            || str_starts_with(config('app.url', ''), 'https');
        $scheme = $isSecure ? 'wss' : 'ws';
        $host = request()->getHost();
        return "{$scheme}://{$host}/ws";
    }
}
