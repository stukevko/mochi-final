<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shop-Wartung aus dem Admin schaltbar; Admin- und Webhook-Routen bleiben erreichbar.
 */
class BlockStorefrontDuringMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        if (Setting::get('storefront_maintenance', false) === true) {
            return response()
                ->view('pages.storefront-maintenance', [], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }

    protected function shouldBypass(Request $request): bool
    {
        $path = $request->path();

        if ($path === 'up' || $path === 'sitemap.xml' || $path === 'robots.txt') {
            return true;
        }

        if (str_starts_with($path, 'admin')) {
            return true;
        }

        if (str_starts_with($path, 'webhooks/')) {
            return true;
        }

        if (str_starts_with($path, 'livewire/')) {
            return true;
        }

        return false;
    }
}
