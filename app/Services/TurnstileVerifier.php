<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class TurnstileVerifier
{
    public static function secretConfigured(): bool
    {
        return filled(config('services.turnstile.secret'));
    }

    public static function siteKey(): ?string
    {
        $k = config('services.turnstile.site_key');

        return is_string($k) && $k !== '' ? $k : null;
    }

    public static function verify(?string $token, ?string $remoteIp): bool
    {
        if (! self::secretConfigured()) {
            return true;
        }

        if (! is_string($token) || trim($token) === '') {
            return false;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => $remoteIp,
            ]);

        if (! $response->successful()) {
            return false;
        }

        return (bool) ($response->json('success') ?? false);
    }
}
