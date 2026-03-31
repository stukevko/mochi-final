<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setzt lange Cache-Header, wenn Laravel die Antwort liefert (z. B. /storage/* hinter der App).
 * In Produktion sollten Nginx/Apache statische Dateien direkt ausliefern; dort zusätzlich expires konfigurieren.
 */
class StaticAssetCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET')) {
            return $response;
        }

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        if ($this->shouldAttachLongCache($request, $response)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        return $response;
    }

    private function shouldAttachLongCache(Request $request, Response $response): bool
    {
        if ($request->is('storage/*', 'build/*')) {
            return true;
        }

        if ($response instanceof BinaryFileResponse) {
            return true;
        }

        $path = ltrim($request->path(), '/');
        if (preg_match('/\.(?:css|js|mjs|woff2?|ttf|otf|eot|ico|png|jpe?g|gif|webp|svg|avif)$/i', $path)) {
            return true;
        }

        return false;
    }
}
