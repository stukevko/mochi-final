<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setzt lange Cache-Header, wenn Laravel die Antwort liefert (z. B. /storage/* hinter der App).
 * In Produktion sollten Nginx/Apache statische Dateien direkt ausliefern; dort zusätzlich expires konfigurieren.
 *
 * Sicherheit: Früher bekam JEDE BinaryFileResponse (also auch Downloads wie Rechnungen)
 * "public, immutable" — Proxies/CDNs hätten personenbezogene Dateien cachen können.
 * Jetzt nur noch echte statische Pfade und nur für Gäste-unabhängige Assets.
 */
class StaticAssetCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $response;
        }

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        if ($this->shouldAttachLongCache($request)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        return $response;
    }

    private function shouldAttachLongCache(Request $request): bool
    {
        if ($request->is('build/*', 'fonts/*')) {
            return true;
        }

        $path = ltrim($request->path(), '/');

        if ($request->is('storage/*')
            && preg_match('/\.(?:css|js|mjs|woff2?|ttf|otf|ico|png|jpe?g|gif|webp|svg|avif)$/i', $path)) {
            return true;
        }

        return false;
    }
}
