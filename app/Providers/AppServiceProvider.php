<?php

namespace App\Providers;

use App\Models\CmsPage;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SiteSetting;
use App\Observers\ProductObserver;
use App\Support\FeaturedProductImageOptimizer;
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

        if (Schema::hasTable('settings')) {
            View::composer('pages.legal-impressum', function ($view): void {
                $html = Setting::get('legal_impressum');
                $view->with('legalHtml', is_string($html) && $html !== '' ? $html : '<p class="text-mochi-muted">Bitte den Impressumstext im Admin unter <strong>Einstellungen</strong> pflegen (<code>legal_impressum</code>).</p>');
            });
            View::composer('pages.legal-agb', function ($view): void {
                $html = Setting::get('legal_agb');
                $view->with('legalHtml', is_string($html) && $html !== '' ? $html : '<p class="text-mochi-muted">Bitte die AGB im Admin pflegen (<code>legal_agb</code>).</p>');
            });
            View::composer('pages.legal-datenschutz', function ($view): void {
                $html = Setting::get('legal_privacy');
                $view->with('legalHtml', is_string($html) && $html !== '' ? $html : '<p class="text-mochi-muted">Bitte die Datenschutzerklärung im Admin pflegen (<code>legal_privacy</code>).</p>');
            });
            View::composer('pages.legal-widerruf', function ($view): void {
                $html = Setting::get('legal_widerruf');
                $view->with('legalHtml', is_string($html) && $html !== '' ? $html : '<p class="text-mochi-muted">Bitte den Widerrufstext im Admin pflegen (<code>legal_widerruf</code>).</p>');
            });
        }

        $frontLayoutKeys = [
            'layouts.app',
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
            $slugs = ['impressum', 'widerruf', 'datenschutz'];
            $bySlug = CmsPage::query()->whereIn('slug', $slugs)->get()->keyBy('slug');

            $footerLegalLink = static function (string $slug, string $fallbackLabel, string $fallbackRouteName) use ($bySlug): array {
                $page = $bySlug->get($slug);

                return [
                    'label' => $page ? (string) $page->title : $fallbackLabel,
                    'url' => $page ? route('pages.show', $page) : route($fallbackRouteName),
                ];
            };

            $footerLegalLinks = [
                $footerLegalLink('impressum', 'Impressum', 'legal.impressum'),
                $footerLegalLink('widerruf', 'Widerruf', 'legal.widerruf'),
                $footerLegalLink('datenschutz', 'Datenschutz', 'legal.datenschutz'),
                ['label' => 'AGB', 'url' => route('legal.agb')],
                ['label' => 'Kontakt', 'url' => route('contact')],
                ['label' => 'Shop', 'url' => route('shop')],
            ];

            $settings = SiteSetting::query()->first();
            $instagramUrl = $settings?->instagram_url ?: config('mochicards.instagram_url');
            $tiktokFromSettings = trim((string) ($settings?->tiktok_url ?? ''));
            $tiktokUrl = $tiktokFromSettings !== ''
                ? $tiktokFromSettings
                : trim((string) config('mochicards.tiktok_url'));
            $footerCarouselUrls = [];
            if ($settings) {
                foreach ($settings->footerImagePaths() as $path) {
                    $footerCarouselUrls[] = Storage::disk('public')->url($path);
                }
            }

            $heroLearnMoreUrl = $settings?->hero_learn_more_url ?: route('posts.index');

            $backgroundAnimationsEnabled = $settings ? (bool) ($settings->background_animations ?? true) : true;

            $view->with([
                'footerLegalLinks' => $footerLegalLinks,
                'instagramUrl' => $instagramUrl,
                'tiktokUrl' => $tiktokUrl,
                'footerCarouselUrls' => $footerCarouselUrls,
                'heroLearnMoreUrl' => $heroLearnMoreUrl,
                'backgroundAnimationsEnabled' => $backgroundAnimationsEnabled,
            ]);
        });
    }
}
