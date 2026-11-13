<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Bereinigt Rich-Text aus dem Admin (RichEditor, Rechtstexte, CMS-Seiten) vor der Ausgabe.
 *
 * Verteidigung in der Tiefe: Selbst wenn ein Admin-Konto kompromittiert wird oder
 * fremdes HTML hineinkopiert wird, landen keine <script>, on*-Handler oder javascript:-Links
 * im Shop. Ergebnis wird nach Inhalts-Hash gecacht (kein Parsen pro Request).
 */
final class SafeHtml
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $key = 'safe-html.v1.'.hash('xxh128', $html);

        try {
            // Begrenzte TTL: alte Fassungen bearbeiteter Texte verschwinden von selbst aus dem Cache.
            return Cache::remember($key, now()->addDays(30), fn (): string => self::sanitizer()->sanitize($html));
        } catch (\Throwable) {
            // Cache nicht verfügbar → trotzdem sanitizen, niemals Roh-HTML ausgeben.
            return self::sanitizer()->sanitize($html);
        }
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer !== null) {
            return self::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
            ->allowMediaSchemes(['https', 'http', 'data'])
            ->allowAttribute('class', '*')
            ->allowAttribute('style', '*')
            ->allowAttribute('target', 'a')
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->withMaxInputLength(1_000_000);

        return self::$sanitizer = new HtmlSanitizer($config);
    }
}
