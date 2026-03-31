<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Str;

final class ProductSeo
{
    /**
     * @return array{
     *   title: string,
     *   metaDescription: string,
     *   ogTitle: string,
     *   ogDescription: string,
     *   ogImage: string|null,
     *   ogUrl: string,
     *   canonical: string
     * }
     */
    public static function layoutData(Product $product): array
    {
        $description = $product->short_description ?: $product->description;
        $plain = $description ? strip_tags((string) $description) : $product->name;
        $metaDescription = Str::limit(trim(preg_replace('/\s+/u', ' ', $plain) ?? ''), 160, '');

        $safeImg = $product->safeImageUrl(0);
        $ogImage = $safeImg !== null ? self::absoluteMediaUrl($safeImg) : null;

        $app = config('app.name', 'Shop');
        $currency = strtoupper((string) Setting::get('currency', 'EUR'));

        return [
            'title' => $product->name.' – '.$app,
            'metaDescription' => $metaDescription,
            'ogTitle' => $product->name,
            'ogDescription' => $metaDescription,
            'ogImage' => $ogImage,
            'ogUrl' => route('product.show', $product->slug),
            'canonical' => route('product.show', $product->slug),
            'ogType' => 'product',
            'productOg' => [
                'amount' => number_format((float) $product->current_price, 2, '.', ''),
                'currency' => $currency,
            ],
        ];
    }

    public static function absoluteMediaUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return url($url);
    }
}
