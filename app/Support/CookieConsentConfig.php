<?php

namespace App\Support;

use App\Services\TurnstileVerifier;

/**
 * Konfiguration für consent-gesteuerte optionale Dienste (Maps).
 * Schriftarten werden self-hosted über Vite geladen — kein Consent nötig.
 */
final class CookieConsentConfig
{
    /**
     * @return array{
     *     turnstile: bool,
     *     turnstileSiteKey: ?string
     * }
     */
    public static function forLayout(string $layout): array
    {
        if ($layout === 'shop') {
            return [
                'turnstile' => false,
                'turnstileSiteKey' => null,
            ];
        }

        return [
            'turnstile' => TurnstileVerifier::siteKey() !== null,
            'turnstileSiteKey' => TurnstileVerifier::siteKey(),
        ];
    }
}
