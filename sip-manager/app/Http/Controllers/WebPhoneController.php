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

        // Generate TURN credentials (time-limited)
        $turnSecret = env('TURN_SECRET', 'voxacenterturn2026');
        $turnTtl = 86400;
        $turnUser = time() + $turnTtl . ':' . $line->extension;
        $turnPass = base64_encode(hash_hmac('sha1', $turnUser, $turnSecret, true));
        $host = request()->getHost();

        // Get available Caller IDs for this user
        $callerIds = $user->availableCallerIds()->map(fn ($c) => [
            'id' => $c->id,
            'number' => $c->number,
            'label' => $c->label,
            'trunk' => $c->trunk?->name,
        ]);

        return response()->json([
            'extension'  => $line->extension,
            'password'   => $line->decrypted_secret,
            'name'       => $line->name,
            'caller_id'  => $line->caller_id ?? $line->extension,
            'caller_ids' => $callerIds,
            'ws_uri'     => $this->getWsUri(),
            'realm'      => $host,
            'ice_servers' => [
                ['urls' => 'stun:stun.l.google.com:19302'],
                [
                    'urls' => "turn:{$host}:3478",
                    'username' => $turnUser,
                    'credential' => $turnPass,
                ],
            ],
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
