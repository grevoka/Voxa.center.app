<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WidgetCors
{
    public function handle(Request $request, Closure $next)
    {
        // Handle preflight OPTIONS
        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $request->header('Origin', '*'))
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        $origin = $request->header('Origin', '*');
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');

        return $response;
    }
}
