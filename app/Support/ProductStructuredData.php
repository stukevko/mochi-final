<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Setting;

final class ProductStructuredData
{
    /**
     * Schema.org Product + Offer (Google Merchant / Rich Results).
     *
     * @return array<string, mixed>
     */
    public static function productWithOffer(
        Product $product,
        float $displayPrice,
        int $displayStock,
        ?string $imageUrl,
        string $sku,
    ): array {
        $description = $product->short_description ?: $product->description;
        $plain = $description ? trim(preg_replace('/\s+/u', ' ', strip_tags((string) $description)) ?? '') : '';
        $currency = strtoupper((string) Setting::get('currency', 'EUR'));
        $shopName = (string) Setting::get('shop_name', config('app.name', 'Shop'));

        $availability = $displayStock > 0
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => (string) $product->name,
            'sku' => $sku,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product.show', $product->slug),
                'priceCurrency' => $currency,
                'price' => round($displayPrice, 2),
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
            'brand' => [
                '@type' => 'Brand',
                'name' => $shopName,
            ],
        ];

        if ($plain !== '') {
            $data['description'] = \Illuminate\Support\Str::limit($plain, 5000, '');
        }

        if ($imageUrl !== null && $imageUrl !== '') {
            $data['image'] = ProductSeo::absoluteMediaUrl($imageUrl);
        }

        return $data;
    }
}
