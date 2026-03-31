<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;

final class BreadcrumbJsonLd
{
    public static function shopCategoryUrl(string $slug): string
    {
        return url('/shop?'.http_build_query(['category' => $slug]));
    }

    /**
     * Kette von der Wurzel bis zur angegebenen Kategorie (parent-Beziehung muss geladen sein).
     *
     * @return array<int, Category>
     */
    public static function categoryChainRootToLeaf(Category $category): array
    {
        $chain = [];
        $current = $category;
        while ($current) {
            array_unshift($chain, $current);
            $current = $current->parent;
        }

        return $chain;
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $crumbs
     */
    public static function toBreadcrumbListSchema(array $crumbs): array
    {
        $elements = [];
        foreach ($crumbs as $i => $crumb) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    public static function forProduct(Product $product): array
    {
        $crumbs = [
            ['name' => 'Startseite', 'url' => url('/')],
            ['name' => 'Shop', 'url' => route('shop')],
        ];

        if ($product->category) {
            foreach (self::categoryChainRootToLeaf($product->category) as $cat) {
                $crumbs[] = [
                    'name' => $cat->name,
                    'url' => self::shopCategoryUrl($cat->slug),
                ];
            }
        }

        $crumbs[] = [
            'name' => $product->name,
            'url' => route('product.show', $product->slug),
        ];

        return self::toBreadcrumbListSchema($crumbs);
    }

    public static function forShop(?Category $category): array
    {
        $crumbs = [
            ['name' => 'Startseite', 'url' => url('/')],
            ['name' => 'Shop', 'url' => route('shop')],
        ];

        if ($category) {
            foreach (self::categoryChainRootToLeaf($category) as $cat) {
                $crumbs[] = [
                    'name' => $cat->name,
                    'url' => self::shopCategoryUrl($cat->slug),
                ];
            }
        }

        return self::toBreadcrumbListSchema($crumbs);
    }
}
