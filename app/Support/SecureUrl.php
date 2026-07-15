<?php

namespace App\Support;

/**
 * Normalisiert absolute URLs auf https — verhindert Mixed Content aus DB/CMS-Feldern.
 */
final class SecureUrl
{
    public static function upgrade(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, strlen('http://'));
        }

        return $url;
    }
}
