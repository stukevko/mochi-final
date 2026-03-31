<?php

namespace App\Support;

/**
 * Erlaubte Schriften für Admin + Google Fonts CSS2-URL (nur Allowlist — kein freies URL-Injection).
 */
final class ShopTypography
{
    public const DEFAULT_FAMILY = 'Inter';

    /**
     * Anzeige-Name => Google Fonts „family“-Parameter (Spaces als +).
     *
     * @var array<string, string>
     */
    public const FAMILIES = [
        'Inter' => 'Inter',
        'Montserrat' => 'Montserrat',
        'Poppins' => 'Poppins',
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open+Sans',
        'Lato' => 'Lato',
        'Playfair Display' => 'Playfair+Display',
        'Merriweather' => 'Merriweather',
        'Source Sans 3' => 'Source+Sans+3',
        'DM Sans' => 'DM+Sans',
    ];

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return array_keys(self::FAMILIES);
    }

    public static function normalizeFamily(string $choice): string
    {
        $choice = trim($choice);

        return array_key_exists($choice, self::FAMILIES) ? $choice : self::DEFAULT_FAMILY;
    }

    /**
     * Google Fonts CSS2 Stylesheet URL (400–700).
     */
    public static function googleFontStylesheetHref(string $choice): string
    {
        $slug = self::FAMILIES[self::normalizeFamily($choice)];

        return 'https://fonts.googleapis.com/css2?family='.$slug.':wght@400;500;600;700&display=swap';
    }
}
