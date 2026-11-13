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

        // Lokale Entwicklung/Vorschau ohne TLS: nicht auf https zwingen, sonst laden Logo & Bilder nicht.
        if (preg_match('#^http://(localhost|127\.0\.0\.1|\[::1\])(:\d+)?(/|$)#i', $url)) {
            return $url;
        }

        if (str_starts_with($url, 'http://')) {
            return 'https://'.substr($url, strlen('http://'));
        }

        return $url;
    }
}
