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
    ];

    protected function casts(): array
    {
        return [
            'background_animations' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return Cache::rememberForever('site_setting.current.v1', function (): self {
            return static::query()->first()
                ?? static::query()->create([
                    'instagram_url' => config('mochicards.instagram_url'),
                    'background_animations' => true,
                ]);
        });
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('site_setting.current.v1');
            \App\Support\StorefrontLayoutCache::forget();
        });

        static::deleted(function (): void {
            Cache::forget('site_setting.current.v1');
            \App\Support\StorefrontLayoutCache::forget();
        });
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
}
