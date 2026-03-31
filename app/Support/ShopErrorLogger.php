<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class ShopErrorLogger
{
    /**
     * Loggt kritische Fehler in eine dedizierte Datei (shop-errors.log).
     *
     * @param  array<string, mixed>  $context
     */
    public static function report(string $event, Throwable $e, array $context = []): void
    {
        $context = array_merge($context, [
            'event' => $event,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);

        Log::channel('shop_errors')->error('Shop error', $context);
    }
}

