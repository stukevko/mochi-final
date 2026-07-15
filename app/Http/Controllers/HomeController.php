<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Post;
use App\Models\Product;
use App\Models\ShopLink;
use App\Models\SiteSetting;
use App\Support\BenefitTileIcons;
use App\Support\MoneyFormatter;
use App\Support\SecureUrl;
use App\Support\ShopBranding;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $nextEvent = Event::query()->upcomingActive()->orderBy('starts_at')->first();

        $upcomingEvents = Event::query()
            ->upcomingActive()
            ->orderBy('starts_at')
            ->limit(4)
            ->get();

        $latestPosts = Post::query()
            ->published()
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $settings = SiteSetting::current();

        $heroLogoUrl = $settings->hero_logo_path
            && Storage::disk('public')->exists($settings->hero_logo_path)
            ? Storage::disk('public')->url($settings->hero_logo_path)
            : ShopBranding::logoUrl();

        $heroBackgroundUrl = $settings->hero_background_path
            ? Storage::disk('public')->url($settings->hero_background_path)
            : null;

        $heroHeadline = trim((string) ($settings->hero_headline ?? '')) !== ''
            ? (string) $settings->hero_headline
            : (string) config('mochicards.home_hero_headline_default');

        $fromSettings = trim((string) ($settings->hero_visit_store_url ?? ''));
        $heroVisitStoreUrl = $fromSettings !== ''
            ? $fromSettings
            : trim((string) config('mochicards.default_visit_store_url'));

        $shopBaseUrl = $settings->shop_cta_url
            ?: ShopLink::query()->orderBy('sort_order')->value('url')
            ?: config('mochicards.default_shop_url');
        if ($shopBaseUrl === null || trim((string) $shopBaseUrl) === '') {
            $shopBaseUrl = route('shop');
        }

        $featuredProduct = $this->resolveFeaturedProduct($settings, $shopBaseUrl);
        $benefitTiles = $this->resolveBenefitTiles($settings);

        return view('home', [
            'nextEvent' => $nextEvent,
            'upcomingEvents' => $upcomingEvents,
            'latestPosts' => $latestPosts,
            'heroLogoUrl' => $heroLogoUrl,
            'heroBackgroundUrl' => $heroBackgroundUrl,
            'heroHeadline' => $heroHeadline,
            'heroVisitStoreUrl' => $heroVisitStoreUrl,
            'featuredProduct' => $featuredProduct,
            'benefitTiles' => $benefitTiles,
        ]);
    }

    /**
     * @return array{id: ?string, title: string, description: string, price: ?string, url: string, image_url: ?string, is_placeholder?: bool, show_community_token_art?: bool, internal_shop_product?: bool}
     */
    private function resolveFeaturedProduct(SiteSetting $settings, string $shopBaseUrl): array
    {
        $defaultTitle = (string) config('mochicards.home_featured_default_title');
        $defaultDescription = (string) config('mochicards.home_featured_default_description');

        $linkedProduct = $this->resolveFeaturedShopProduct($settings);
        $url = trim((string) $settings->featured_product_url);

        if ($linkedProduct !== null) {
            $title = $linkedProduct->name;
            $description = trim(strip_tags((string) ($linkedProduct->short_description ?? '')));
            if ($description === '') {
                $description = trim((string) ($settings->featured_product_description ?? '')) ?: $defaultDescription;
            }
            $price = MoneyFormatter::format((float) $linkedProduct->current_price);
            $id = $linkedProduct->sku !== null && $linkedProduct->sku !== ''
                ? (string) $linkedProduct->sku
                : (string) $linkedProduct->id;

            $imageUrl = $linkedProduct->safeImageUrl(0);
            if ($imageUrl === null) {
                $imageUrl = $this->resolveFeaturedImageUrlFromPath($settings->featured_product_image_path);
            }

            return [
                'id' => $id,
                'title' => $title,
                'description' => $description,
                'price' => $price,
                'url' => route('product.show', ['slug' => $linkedProduct->slug], false),
                'image_url' => $imageUrl,
                'is_placeholder' => false,
                'show_community_token_art' => $imageUrl === null,
                'internal_shop_product' => true,
            ];
        }

        if ($url === '') {
            return [
                'id' => null,
                'title' => $defaultTitle,
                'description' => $defaultDescription,
                'price' => null,
                'url' => $shopBaseUrl,
                'image_url' => null,
                'is_placeholder' => true,
                'show_community_token_art' => true,
            ];
        }

        $title = trim((string) $settings->featured_product_title) ?: $defaultTitle;
        $description = trim((string) ($settings->featured_product_description ?? ''));
        if ($description === '') {
            $description = $defaultDescription;
        }

        $imageUrl = $this->resolveFeaturedImageUrlFromPath($settings->featured_product_image_path);

        return [
            'id' => $settings->featured_product_id ? trim((string) $settings->featured_product_id) : null,
            'title' => $title,
            'description' => $description,
            'price' => $settings->featured_product_price ? trim((string) $settings->featured_product_price) : null,
            'url' => $url,
            'image_url' => $imageUrl,
            'show_community_token_art' => $imageUrl === null,
        ];
    }

    private function resolveFeaturedShopProduct(SiteSetting $settings): ?Product
    {
        $raw = $settings->featured_product_id;
        if (! is_string($raw)) {
            return null;
        }
        $key = trim($raw);
        if ($key === '') {
            return null;
        }

        $query = Product::query()->where('is_active', true);

        if (ctype_digit($key)) {
            $byId = (clone $query)->whereKey((int) $key)->first();
            if ($byId !== null) {
                return $byId;
            }
        }

        return (clone $query)->where('sku', $key)->first();
    }

    private function resolveFeaturedImageUrlFromPath(?string $imagePath): ?string
    {
        if (! is_string($imagePath) || trim($imagePath) === '') {
            return null;
        }
        $imagePath = trim($imagePath);
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return SecureUrl::upgrade($imagePath);
        }
        if (Storage::disk('public')->exists($imagePath)) {
            return Storage::disk('public')->url($imagePath);
        }
        if (is_file(public_path($imagePath))) {
            return asset($imagePath);
        }

        return null;
    }

    /**
     * @return array<int, array{title: string, body: string, icon: string}>
     */
    private function resolveBenefitTiles(SiteSetting $settings): array
    {
        $defaults = [
            ['title' => 'Blitzversand', 'body' => 'In 24h bei dir (DE)', 'icon' => 'bolt'],
            ['title' => 'Community', 'body' => 'Dein TCG-Wohnzimmer in Speyer', 'icon' => 'heart'],
            ['title' => 'Turniere', 'body' => 'Wöchentliche Locals & Cups', 'icon' => 'trophy'],
            ['title' => 'SumUp', 'body' => 'Sicher online & im Laden bezahlen', 'icon' => 'lock'],
        ];

        $out = [];
        $config = $settings->benefitTilesConfig();

        foreach (range(0, 3) as $idx) {
            $title = $config[$idx]['title'] ?: $defaults[$idx]['title'];
            $bodyRaw = $config[$idx]['body'] ?? null;
            $body = is_string($bodyRaw) ? trim($bodyRaw) : '';
            if ($body === '') {
                $body = $defaults[$idx]['body'];
            }
            $iconKey = $config[$idx]['icon'] ?? BenefitTileIcons::defaultForIndex($idx);
            if (! array_key_exists($iconKey, BenefitTileIcons::options())) {
                $iconKey = $defaults[$idx]['icon'];
            }
            $out[] = [
                'title' => $title,
                'body' => $body,
                'icon' => $iconKey,
            ];
        }

        return $out;
    }
}
