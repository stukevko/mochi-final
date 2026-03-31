<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kürzere Session-Lebensdauer nur für Filament-Routen (Inaktivität in Minuten).
 */
class ConfigureFilamentSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $minutes = (int) config('filament.session_lifetime', 120);
        if ($minutes > 0) {
            config(['session.lifetime' => $minutes]);
        }

        return $next($request);
    }
}
