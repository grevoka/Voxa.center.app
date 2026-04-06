<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InstallController;
use Closure;
use Illuminate\Http\Request;

class CheckInstalled
{
    public function handle(Request $request, Closure $next)
    {
        // If not installed, redirect to installer (unless already on install route)
        if (!InstallController::isInstalled() && !$request->is('install*')) {
            return redirect()->route('install.index');
        }

        // If installed, block access to installer
        if (InstallController::isInstalled() && $request->is('install*')) {
            abort(404);
        }

        return $next($request);
    }
}
