<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOperator
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Admin impersonating an operator is allowed
        if (session()->has('impersonate_admin_id')) {
            return $next($request);
        }

        if ($user?->role !== 'operator') {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
