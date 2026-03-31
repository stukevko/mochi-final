<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\BreadcrumbJsonLd;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $entries = [];

        $entries[] = [
            'loc' => url('/'),
            'lastmod' => now(),
        ];

        $entries[] = [
            'loc' => route('shop'),
            'lastmod' => now(),
        ];

        foreach (['legal.impressum', 'legal.agb', 'legal.datenschutz', 'legal.widerruf', 'service', 'contact'] as $routeName) {
            $entries[] = [
                'loc' => route($routeName),
                'lastmod' => now(),
            ];
        }

        Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->select(['slug', 'updated_at'])
            ->lazyById(200)
            ->each(function (Product $product) use (&$entries): void {
                $entries[] = [
                    'loc' => route('product.show', $product->slug),
                    'lastmod' => $product->updated_at ?? now(),
                ];
            });

        Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->select(['slug', 'updated_at'])
            ->lazyById(200)
            ->each(function (Category $category) use (&$entries): void {
                $entries[] = [
                    'loc' => BreadcrumbJsonLd::shopCategoryUrl($category->slug),
                    'lastmod' => $category->updated_at ?? now(),
                ];
            });

        return response()
            ->view('sitemap', ['entries' => $entries], 200)
            ->header('Content-Type', 'application/xml');
    }
}
