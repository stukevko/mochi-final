<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class ShopBranding
{
    /** Standard-Logo (helle Schrift, für die dunkle Seite). */
    public const LOGO_PLACEHOLDER = 'images/logo.svg';

    /** Standard-Logo für helle Flächen (Rechnung/PDF) — PNG, weil DomPDF SVG-Verläufe nicht zuverlässig darstellt. */
    public const LOGO_ON_LIGHT = 'images/logo-on-light.png';

    public static function displayName(): string
    {
        $name = Setting::get('shop_name');

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        return (string) config('mochicards.site_name', config('app.name', 'Shop'));
    }

    public static function logoUrl(): string
    {
        $uploaded = Setting::get('logo_path');
        if (is_string($uploaded) && $uploaded !== '' && Storage::disk('public')->exists($uploaded)) {
            return (string) SecureUrl::upgrade(Storage::disk('public')->url($uploaded));
        }

        $site = SiteSetting::query()->first();
        $hero = $site?->hero_logo_path;
        if (is_string($hero) && $hero !== '' && Storage::disk('public')->exists($hero)) {
            return (string) SecureUrl::upgrade(Storage::disk('public')->url($hero));
        }

        return (string) SecureUrl::upgrade(asset(self::LOGO_PLACEHOLDER));
    }

    /**
     * Absoluter Dateipfad für eingebettete Bilder in DomPDF.
     */
    public static function logoPathForPdf(): ?string
    {
        $uploaded = Setting::get('logo_path');
        if (is_string($uploaded) && $uploaded !== '') {
            $path = Storage::disk('public')->path($uploaded);
            if (is_file($path)) {
                return $path;
            }
        }

        $site = SiteSetting::query()->first();
        $hero = $site?->hero_logo_path;
        if (is_string($hero) && $hero !== '') {
            $path = Storage::disk('public')->path($hero);
            if (is_file($path)) {
                return $path;
            }
        }

        $placeholder = public_path(self::LOGO_ON_LIGHT);

        return is_file($placeholder) ? $placeholder : null;
    }

    public static function usesPlaceholderLogo(): bool
    {
        $uploaded = Setting::get('logo_path');
        if (is_string($uploaded) && $uploaded !== '' && Storage::disk('public')->exists($uploaded)) {
            return false;
        }

        $site = SiteSetting::query()->first();
        $hero = $site?->hero_logo_path;
        if (is_string($hero) && $hero !== '' && Storage::disk('public')->exists($hero)) {
            return false;
        }

        return true;
    }
}
