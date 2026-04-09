<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role !== 'admin' && !session()->has('impersonate_admin_id')) {
            if ($request->user()?->isOperator()) {
                return redirect()->route('operator.dashboard');
            }
            abort(403);
        }

        return $next($request);
    }
}
