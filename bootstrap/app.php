<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('shop:reconcile-payments')->everyFiveMinutes()->withoutOverlapping(10);
        $schedule->command('shop:sumup-sync-sales')->everyFiveMinutes()->withoutOverlapping(10);
        $schedule->command('backup:clean')->dailyAt('02:45');
        $schedule->command('backup:run --only-db')->dailyAt('03:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Hinter nginx/CDN: X-Forwarded-Proto für $request->secure() / URL-Generierung.
        // TRUSTED_PROXIES steuerbar, damit die App auch OHNE Reverse-Proxy (Shared Hosting,
        // `php artisan serve`, Docker ohne Proxy) sicher läuft: dort "127.0.0.1" oder leer setzen,
        // sonst könnten Clients per X-Forwarded-For ihre IP fälschen (Rate-Limits umgehen).
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', '*'));
        $middleware->trustProxies(
            at: $trustedProxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $trustedProxies)))),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->web(
            append: [
                \App\Http\Middleware\BlockStorefrontDuringMaintenance::class,
                \App\Http\Middleware\LogUnauthenticatedAdminAccess::class,
            ],
            prepend: [
                \App\Http\Middleware\ForceHttps::class,
            ],
        );
        $middleware->append(\App\Http\Middleware\StaticAssetCacheHeaders::class);
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/payment/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $e, Request $request): ?Response {
            if (config('app.debug') || $request->expectsJson()) {
                return null;
            }

            if (! $e instanceof \Illuminate\Database\QueryException) {
                return null;
            }

            $message = strtolower($e->getMessage());
            $patterns = ['connection', 'refused', 'timed out', 'could not connect', 'server has gone away', 'lost connection', 'broken pipe', 'database is locked'];

            foreach ($patterns as $fragment) {
                if (str_contains($message, $fragment)) {
                    return response()->view('errors.503', [], Response::HTTP_SERVICE_UNAVAILABLE);
                }
            }

            return null;
        });
    })->create();
