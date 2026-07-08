<?php

namespace App\Providers;

use App\Models\CmsPage;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SiteSetting;
use App\Observers\ProductObserver;
use App\Support\FeaturedProductImageOptimizer;
use App\Support\ShopBranding;
use App\Support\ShopTypography;
use App\Support\StorefrontLayoutCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https', false)) {
            URL::forceScheme('https');
        }

        $assetUrl = config('app.asset_url');
        if (is_string($assetUrl) && $assetUrl !== '') {
            Vite::useAssetUrl($assetUrl);
        }

        if (Schema::hasTable('products')) {
            Product::observe(ProductObserver::class);
        }

        SiteSetting::saved(function (SiteSetting $setting): void {
            if (! $setting->wasChanged('featured_product_image_path')) {
                return;
            }

            $path = $setting->featured_product_image_path;
            if (is_string($path) && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))) {
                return;
            }

            FeaturedProductImageOptimizer::optimize($path);
        });

        View::share('siteName', config('mochicards.site_name'));

        View::composer('pages.legal-impressum', fn ($view) => $view->with(
            'legalHtml',
            self::legalHtmlWithCmsFallback(
                'legal_impressum',
                'impressum',
                'Bitte den Impressumstext im Admin unter <strong>Konfiguration → Rechtstexte</strong> pflegen.'
            )
        ));
        View::composer('pages.legal-agb', fn ($view) => $view->with(
            'legalHtml',
            self::legalHtmlWithCmsFallback(
                'legal_agb',
                null,
                'Bitte die AGB im Admin unter <strong>Konfiguration → Rechtstexte</strong> pflegen.'
            )
        ));
        View::composer('pages.legal-datenschutz', fn ($view) => $view->with(
            'legalHtml',
            self::legalHtmlWithCmsFallback(
                'legal_privacy',
                'datenschutz',
                'Bitte die Datenschutzerklärung im Admin unter <strong>Konfiguration → Rechtstexte</strong> pflegen.'
            )
        ));
        View::composer('pages.legal-widerruf', fn ($view) => $view->with(
            'legalHtml',
            self::legalHtmlWithCmsFallback(
                'legal_widerruf',
                'widerruf',
                'Bitte den Widerrufstext im Admin unter <strong>Konfiguration → Rechtstexte</strong> pflegen.'
            )
        ));

        $frontLayoutKeys = [
            'layouts.app',
            'components.layouts.app',
            'home',
            'events.index',
            'events.calendar',
            'events.show',
            'posts.index',
            'posts.show',
            'pages.show',
            'pages.shop',
            'pages.cart',
            'pages.checkout',
            'pages.checkout-success',
            'pages.product',
            'pages.service',
            'pages.legal-impressum',
            'pages.legal-agb',
            'pages.legal-datenschutz',
            'pages.legal-widerruf',
        ];

        View::composer($frontLayoutKeys, function ($view) {
            $data = Cache::remember(StorefrontLayoutCache::KEY, now()->addMinutes(10), function (): array {
                $slugs = ['impressum', 'widerruf', 'datenschutz'];
                $bySlug = CmsPage::query()->whereIn('slug', $slugs)->get()->keyBy('slug');

                $footerLegalLink = static function (string $slug, string $fallbackLabel, string $fallbackRouteName) use ($bySlug): array {
                    $page = $bySlug->get($slug);

                    return [
                        'label' => $page ? (string) $page->title : $fallbackLabel,
                        'url' => $page ? route('pages.show', $page) : route($fallbackRouteName),
                    ];
                };

                $settings = SiteSetting::current();
                $instagramUrl = $settings->instagram_url ?: config('mochicards.instagram_url');
                $tiktokFromSettings = trim((string) ($settings->tiktok_url ?? ''));
                $tiktokUrl = $tiktokFromSettings !== ''
                    ? $tiktokFromSettings
                    : trim((string) config('mochicards.tiktok_url'));
                $footerCarouselUrls = [];
                foreach ($settings->footerImagePaths() as $path) {
                    $footerCarouselUrls[] = Storage::disk('public')->url($path);
                }

                return [
                    'footerLegalLinks' => [
                        $footerLegalLink('impressum', 'Impressum', 'legal.impressum'),
                        $footerLegalLink('widerruf', 'Widerruf', 'legal.widerruf'),
                        $footerLegalLink('datenschutz', 'Datenschutz', 'legal.datenschutz'),
                        ['label' => 'AGB', 'url' => route('legal.agb')],
                        ['label' => 'Kontakt', 'url' => route('contact')],
                        ['label' => 'Shop', 'url' => route('shop')],
                    ],
                    'instagramUrl' => $instagramUrl,
                    'tiktokUrl' => $tiktokUrl,
                    'footerCarouselUrls' => $footerCarouselUrls,
                    'heroLearnMoreUrl' => $settings->hero_learn_more_url ?: route('posts.index'),
                    'backgroundAnimationsEnabled' => (bool) ($settings->background_animations ?? true),
                    'shopDisplayName' => ShopBranding::displayName(),
                    'shopLogoUrl' => ShopBranding::logoUrl(),
                    'shopLogoIsPlaceholder' => ShopBranding::usesPlaceholderLogo(),
                    'shopFontFamily' => ShopTypography::normalizeFamily(
                        (string) (Setting::get('font_family') ?: ShopTypography::DEFAULT_FAMILY)
                    ),
                ];
            });

            $view->with($data);
        });
    }

    private static function legalHtmlWithCmsFallback(string $settingKey, ?string $cmsSlug, string $placeholder): string
    {
        if (Schema::hasTable('settings')) {
            $html = Setting::get($settingKey);
            if (is_string($html) && trim($html) !== '') {
                return $html;
            }
        }

        if ($cmsSlug !== null && Schema::hasTable('cms_pages')) {
            $page = CmsPage::query()->where('slug', $cmsSlug)->first();
            if ($page && filled($page->body)) {
                return (string) $page->body;
            }
        }

        return '<p class="text-mochi-muted">'.$placeholder.'</p>';
    }
}
