<?php

namespace App\Support;

use App\Models\Setting;
use NumberFormatter;

final class MoneyFormatter
{
    public static function format(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);
        $symbol = (string) Setting::get('currency_symbol', '€');
        $currencyCode = (string) Setting::get('currency', 'EUR');
        $locale = str_replace('-', '_', app()->getLocale() ?: 'de_DE');

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($value, $currencyCode);
            if (is_string($formatted) && $formatted !== '') {
                return $formatted;
            }
        }

        return number_format($value, 2, ',', '.').' '.$symbol;
    }
}
