<?php

namespace App\Http\Middleware;

use App\Services\WebCronRunner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Ersatz für den Server-Cronjob auf normalem Webspace: Nach dem Ausliefern der Seite
 * werden fällige Hintergrundaufgaben erledigt (höchstens einmal pro Minute).
 */
class RunWebCron
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('shop.web_cron')) {
            return;
        }

        try {
            app(WebCronRunner::class)->run();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
