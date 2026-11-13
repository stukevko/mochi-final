<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Versandkosten (brutto) aus dem Admin: Pauschale + optionale Freigrenze.
 * Standard ohne Einstellung: 0 € (wie bisher „Kostenfrei“).
 */
final class ShippingCalculator
{
    public static function flatRate(): float
    {
        return max(0.0, round((float) str_replace(',', '.', (string) Setting::get('shipping_flat_rate', 0)), 2));
    }

    public static function freeFrom(): float
    {
        return max(0.0, round((float) str_replace(',', '.', (string) Setting::get('free_shipping_from', 0)), 2));
    }

    public static function costFor(float $goodsGross): float
    {
        $rate = self::flatRate();
        if ($rate <= 0 || $goodsGross <= 0) {
            return 0.0;
        }

        $freeFrom = self::freeFrom();

        return ($freeFrom > 0 && $goodsGross >= $freeFrom) ? 0.0 : $rate;
    }
}
