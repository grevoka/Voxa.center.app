<?php

namespace App\Http\Controllers;

use App\Models\WidgetToken;
use App\Services\WidgetGuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetCallController extends Controller
{
    public function __construct(
        private WidgetGuestService $guestService,
    ) {}

    /**
     * Public API: provision a guest WebRTC endpoint for a widget visitor.
     * No authentication required — validated by token + domain.
     */
    public function config(string $token, Request $request): JsonResponse
    {
        // 1. Find token
        $widget = WidgetToken::where('token', $token)
            ->where('enabled', true)
            ->first();

        if (!$widget) {
            return response()->json(['error' => 'Invalid or disabled token'], 403);
        }

        // 2. Domain verification
        $origin = $request->header('Origin') ?: $request->header('Referer');
        if (!$widget->isValidForDomain($origin)) {
            return response()->json(['error' => 'Domain not allowed'], 403);
        }

        // 3. Concurrent call limit
        $activeGuests = $this->guestService->countActiveGuests($widget);
        if ($activeGuests >= $widget->max_concurrent) {
            return response()->json(['error' => 'Too many concurrent calls'], 429);
        }

        // 4. Provision ephemeral guest endpoint
        try {
            $guest = $this->guestService->provisionGuest($widget);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }

        // 5. TURN credentials (same logic as WebPhoneController)
        $turnSecret = env('TURN_SECRET', 'voxacenterturn2026');
        $turnTtl = 86400;
        $turnUser = (time() + $turnTtl) . ':' . $guest['endpoint_id'];
        $turnPass = base64_encode(hash_hmac('sha1', $turnUser, $turnSecret, true));
        $host = $request->getHost();

        // 6. Usage stats
        $widget->increment('call_count');
        $widget->update(['last_used_at' => now()]);

        // 7. Return config
        return response()->json([
            'endpoint'    => $guest['endpoint_id'],
            'password'    => $guest['password'],
            'ws_uri'      => $this->getWsUri($request),
            'realm'       => $host,
            'dial_target' => 'widget-' . $widget->id,
            'ice_servers'  => [
                ['urls' => 'stun:stun.l.google.com:19302'],
                [
                    'urls' => [
                        "turn:{$host}:3478?transport=udp",
                        "turn:{$host}:3478?transport=tcp",
                        "turns:{$host}:5349?transport=tcp",
                    ],
                    'username' => $turnUser,
                    'credential' => $turnPass,
                ],
            ],
        ]);
    }

    private function getWsUri(Request $request): string
    {
        $isSecure = $request->isSecure()
            || $request->header('X-Forwarded-Proto') === 'https'
            || str_starts_with(config('app.url', ''), 'https');
        $scheme = $isSecure ? 'wss' : 'ws';
        $host = $request->getHost();
        return "{$scheme}://{$host}/ws";
    }
}
