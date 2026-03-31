<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HeroSection extends Model
{
    /** Nur Array-Payloads cachen (kein Model/Collection im Cache). */
    public const CACHE_KEY_HOME = 'hero_section.home.v2';

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY_HOME);
            Cache::forget('hero_section.home.v1');
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY_HOME);
            Cache::forget('hero_section.home.v1');
        });

        static::saving(function (HeroSection $hero): void {
            if ($hero->cta_label === null || $hero->cta_label === '') {
                $hero->cta_label = 'Jetzt entdecken';
            }
            if ($hero->cta_url === null || $hero->cta_url === '') {
                $hero->cta_url = '/shop';
            }
        });
    }

    protected $fillable = [
        'slug',
        'headline',
        'subheadline',
        'background_image',
        'cta_label',
        'cta_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Aktiver Home-Hero (Slug „home“) als flaches Array — sicher für Cache::rememberForever.
     *
     * @return array<string, mixed>|null
     */
    public static function homePayload(): ?array
    {
        return Cache::rememberForever(self::CACHE_KEY_HOME, function (): ?array {
            $row = self::query()
                ->where('slug', 'home')
                ->where('is_active', true)
                ->first();

            if ($row === null) {
                return null;
            }

            return [
                'headline' => $row->headline,
                'subheadline' => $row->subheadline,
                'cta_label' => $row->cta_label,
                'cta_url' => $row->cta_url,
                'background_image' => $row->background_image,
            ];
        });
    }

    public static function forgetHomeCache(): void
    {
        Cache::forget(self::CACHE_KEY_HOME);
        Cache::forget('hero_section.home.v1');
    }
}
