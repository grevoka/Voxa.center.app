<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = null;

        // 1. Session (set by lang switch)
        if (session()->has('locale')) {
            $locale = session('locale');
        }
        // 2. User preference (DB)
        elseif (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }

        // 3. Default
        $locale = $locale ?? config('app.locale', 'fr');

        if (in_array($locale, ['fr', 'en'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
