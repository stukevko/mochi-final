<?php

namespace App\Models;

use App\Support\BenefitTileIcons;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'instagram_url',
        'tiktok_url',
        'background_animations',
        'hero_logo_path',
        'hero_background_path',
        'hero_headline',
        'hero_subline',
        'hero_sales_teaser',
        'hero_visit_store_url',
        'featured_product_id',
        'featured_product_title',
        'featured_product_description',
        'featured_product_price',
        'featured_product_url',
        'featured_product_image_path',
        'shop_cta_url',
        'hero_learn_more_url',
        'benefit_1_title',
        'benefit_1_body',
        'benefit_1_icon',
        'benefit_1_image_path',
        'benefit_2_title',
        'benefit_2_body',
        'benefit_2_icon',
        'benefit_2_image_path',
        'benefit_3_title',
        'benefit_3_body',
        'benefit_3_icon',
        'benefit_3_image_path',
        'benefit_4_title',
        'benefit_4_body',
        'benefit_4_icon',
        'benefit_4_image_path',
        'footer_image_1',
        'footer_image_2',
        'footer_image_3',
        'footer_image_4',
        'footer_image_5',
        'footer_image_6',
        'about_page_title',
        'about_hero_subtitle',
        'about_intro',
        'about_story',
        'about_highlight_1_title',
        'about_highlight_1_body',
        'about_highlight_2_title',
        'about_highlight_2_body',
        'about_highlight_3_title',
        'about_highlight_3_body',
        'about_extra_title',
        'about_extra_body',
        'about_instagram_heading',
        'about_gallery_image_1',
        'about_gallery_image_2',
        'about_gallery_image_3',
        'about_gallery_image_4',
        'about_gallery_image_5',
        'about_cta_label',
        'about_cta_url',
        'about_meta_description',
    ];

    protected function casts(): array
    {
        return [
            'background_animations' => 'boolean',
        ];
    }

    private const CURRENT_ID_CACHE_KEY = 'site_setting.current.id.v2';

    public static function current(): self
    {
        $id = Cache::rememberForever(self::CURRENT_ID_CACHE_KEY, function (): int {
            return static::resolveCurrentRow()->getKey();
        });

        $setting = static::query()->find($id);

        if ($setting === null) {
            static::forgetCurrentCache();

            return static::resolveCurrentRow();
        }

        return $setting;
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::forgetCurrentCache();
            \App\Support\StorefrontLayoutCache::forget();
        });

        static::deleted(function (): void {
            static::forgetCurrentCache();
            \App\Support\StorefrontLayoutCache::forget();
        });
    }

    public static function forgetCurrentCache(): void
    {
        Cache::forget(self::CURRENT_ID_CACHE_KEY);
        Cache::forget('site_setting.current.v1');
    }

    private static function resolveCurrentRow(): self
    {
        return static::query()->first()
            ?? static::query()->create([
                'instagram_url' => config('mochicards.instagram_url'),
                'background_animations' => true,
            ]);
    }

    /**
     * @return array<int, string|null> Storage paths relative to public disk
     */
    public function footerImagePaths(): array
    {
        $paths = [];
        for ($i = 1; $i <= 6; $i++) {
            $key = "footer_image_{$i}";
            if ($this->{$key}) {
                $paths[] = $this->{$key};
            }
        }

        return $paths;
    }

    /**
     * @return array<int, array{title: string|null, body: string|null, icon: string, image_path: string|null}>
     */
    public function benefitTilesConfig(): array
    {
        $out = [];
        for ($i = 1; $i <= 4; $i++) {
            $idx = $i - 1;
            $out[] = [
                'title' => $this->{"benefit_{$i}_title"},
                'body' => $this->{"benefit_{$i}_body"},
                'icon' => $this->{"benefit_{$i}_icon"}
                    ?: BenefitTileIcons::defaultForIndex($idx),
                'image_path' => $this->{"benefit_{$i}_image_path"},
            ];
        }

        return $out;
    }

    /**
     * @return array<int, string|null> Storage paths relative to public disk
     */
    public function aboutGalleryImagePaths(): array
    {
        $paths = [];
        for ($i = 1; $i <= 5; $i++) {
            $key = "about_gallery_image_{$i}";
            if ($this->{$key}) {
                $paths[] = $this->{$key};
            }
        }

        return $paths;
    }

    /**
     * @return array<int, array{title: string|null, body: string|null}>
     */
    public function aboutHighlights(): array
    {
        $highlights = [];

        for ($i = 1; $i <= 3; $i++) {
            $title = trim((string) ($this->{"about_highlight_{$i}_title"} ?? ''));
            $body = trim((string) ($this->{"about_highlight_{$i}_body"} ?? ''));

            if ($title === '' && $body === '') {
                continue;
            }

            $highlights[] = [
                'title' => $title !== '' ? $title : null,
                'body' => $body !== '' ? $body : null,
            ];
        }

        return $highlights;
    }
}
