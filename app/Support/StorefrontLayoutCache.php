<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class StorefrontLayoutCache
{
    public const KEY = 'storefront.layout.v3';

    public static function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
