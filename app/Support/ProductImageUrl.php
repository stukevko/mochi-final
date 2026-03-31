<?php

namespace App\Support;

final class ProductImageUrl
{
    /**
     * Nur erlaubte Bild-URLs ausgeben (keine file://, keine Pfad-Traversal, kein Freiform-Relative zum Server).
     *
     * Shop-Bilder: Produkt-Uploads werden beim Speichern per Observer auf WebP max. 800px Breite optimiert
     * (@see \App\Observers\ProductObserver) — gleiche URL für Grid und Detail; RIO über HTML sizes/width.
     */
    public static function sanitize(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/')) {
            if (str_contains($url, '..') || preg_match('#//+#', $url)) {
                return null;
            }
            if (! str_starts_with($url, '/storage/')) {
                return null;
            }

            return $url;
        }

        // Support DB values like "products/foo.webp" (Filament disk "public").
        if (! str_contains($url, '://')) {
            $path = ltrim($url, '/');
            if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
                return null;
            }

            return '/storage/'.$path;
        }

        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $lower = strtolower($url);
        if (str_starts_with($lower, 'file:') || str_starts_with($lower, 'php:')) {
            return null;
        }

        return $url;
    }
}
