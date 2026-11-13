<?php

namespace App\Http\Controllers;

use App\Support\ShopBranding;
use Illuminate\Http\JsonResponse;

/**
 * Web-App-Manifeste: Shop und Admin lassen sich auf Handy/Tablet/Desktop
 * „installieren“ und starten dann ohne Browser-Leiste wie eine native App.
 */
class WebAppManifestController extends Controller
{
    private const BACKGROUND = '#040712';

    public function storefront(): JsonResponse
    {
        $name = $this->shopName();

        return $this->manifest([
            'id' => '/',
            'name' => $name,
            'short_name' => mb_strimwidth($name, 0, 12, ''),
            'description' => 'Shop, Events und News von '.$name.'.',
            'start_url' => '/?source=app',
            'scope' => '/',
            'shortcuts' => [
                ['name' => 'Shop', 'url' => '/shop?source=app', 'icons' => $this->shortcutIcon()],
                ['name' => 'Events', 'url' => '/events?source=app', 'icons' => $this->shortcutIcon()],
                ['name' => 'Warenkorb', 'url' => '/cart?source=app', 'icons' => $this->shortcutIcon()],
            ],
        ]);
    }

    public function admin(): JsonResponse
    {
        return $this->manifest([
            'id' => '/admin',
            'name' => 'Mochi Hub – '.$this->shopName(),
            'short_name' => 'Mochi Hub',
            'description' => 'Bestellungen, Produkte, Events und Inhalte verwalten.',
            'start_url' => '/admin',
            'scope' => '/admin',
            'shortcuts' => [
                ['name' => 'Bestellungen', 'url' => '/admin/orders', 'icons' => $this->shortcutIcon()],
                ['name' => 'Produkte', 'url' => '/admin/products', 'icons' => $this->shortcutIcon()],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function manifest(array $overrides): JsonResponse
    {
        $data = array_merge([
            'lang' => str_replace('_', '-', app()->getLocale()),
            'dir' => 'ltr',
            'display' => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
            'orientation' => 'any',
            'background_color' => self::BACKGROUND,
            'theme_color' => self::BACKGROUND,
            'categories' => ['shopping', 'entertainment'],
            'icons' => [
                ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], $overrides);

        return response()
            ->json($data, 200, [
                'Content-Type' => 'application/manifest+json; charset=UTF-8',
                'Cache-Control' => 'public, max-age=3600',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function shopName(): string
    {
        try {
            return ShopBranding::displayName();
        } catch (\Throwable) {
            return (string) config('app.name', 'Shop');
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function shortcutIcon(): array
    {
        return [['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png']];
    }
}
