<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // CSP nur in Produktion: vermeidet Verwechslung mit fehlendem CSS (z. B. veraltetes npm build)
        // und erleichtert lokales Debugging. Andere Security-Header gelten weiter überall.
        if (app()->environment('production')) {
            $viteDev = $this->viteDevelopmentOrigin();

            $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'blob:'];
            $styleSrc = ["'self'", "'unsafe-inline'", 'https:', 'https://fonts.bunny.net'];
            $connectSrc = ["'self'", 'https:', 'ws:', 'wss:'];

            if ($viteDev !== null) {
                $scriptSrc[] = $viteDev;
                $styleSrc[] = $viteDev;
                $connectSrc[] = $viteDev;
            }

            $csp = [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "img-src 'self' data: https: blob:",
                "font-src 'self' data: https:",
                'connect-src '.implode(' ', $connectSrc),
                'script-src '.implode(' ', $scriptSrc),
                'style-src '.implode(' ', $styleSrc),
            ];
            if ($request->secure()) {
                $csp[] = 'upgrade-insecure-requests';
            }
            $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        }

        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }

    /**
     * Wenn `npm run dev` läuft, zeigt public/hot auf die Vite-Origin (z. B. http://127.0.0.1:5173).
     * Ohne diese Quellen blockiert die CSP alle Module/Styles vom Dev-Server.
     */
    private function viteDevelopmentOrigin(): ?string
    {
        if (! Vite::isRunningHot()) {
            return null;
        }

        $raw = @file_get_contents(Vite::hotFile());
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $url = trim($raw, " \t\n\r\0\x0B/");
        if ($url === '' || ! str_starts_with($url, 'http')) {
            return null;
        }

        return $url;
    }
}
