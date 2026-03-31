<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * One-Piece Easter Egg: unauthenticated hits on Filament admin entry URLs.
 */
class LogUnauthenticatedAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldLog($request)) {
            Log::channel('impel_down')->info('admin.guest_access', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $next($request);
    }

    protected function shouldLog(Request $request): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if (Filament::auth()->check()) {
            return false;
        }

        return $request->is('admin', 'admin/', 'admin/login', 'admin/login/*');
    }
}
