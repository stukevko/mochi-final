<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ShopSeedBulkProductsCommand extends Command
{
    protected $signature = 'shop:seed-bulk-products {--count=200 : Number of demo products (max 5000)}';

    protected $description = 'Creates demo products with Picsum placeholder images (stress-test / UX). Requires existing categories (run db:seed first).';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $count = max(1, min(5000, $count));

        $categoryIds = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        if ($categoryIds === []) {
            $this->error('Keine aktiven Kategorien. Bitte zuerst: php artisan migrate --seed');

            return self::FAILURE;
        }

        $this->info("Lege {$count} Demo-Produkte an…");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $suffix = $i.'-'.Str::lower(Str::random(6));
            $name = 'Demo Artikel '.$suffix;
            $slug = 'demo-artikel-'.$suffix;
            $categoryId = $categoryIds[array_rand($categoryIds)];
            $price = round(fake()->randomFloat(2, 4.99, 899.99), 2);
            $onSale = fake()->boolean(25);
            $salePrice = $onSale ? round(fake()->randomFloat(2, 1, max(1, $price - 0.5)), 2) : null;
            if ($salePrice !== null && $salePrice >= $price) {
                $salePrice = round($price * 0.85, 2);
            }

            $seed = rawurlencode(Str::slug($slug));

            Product::query()->create([
                'name' => $name,
                'slug' => $slug,
                'description' => '<p>'.e(fake()->paragraph()).' '.e(fake()->paragraph()).'</p>',
                'short_description' => fake()->sentence(8),
                'price' => $price,
                'sale_price' => $salePrice,
                'sku' => 'DEMO-'.strtoupper(Str::random(10)),
                'stock' => fake()->numberBetween(0, 250),
                'category_id' => $categoryId,
                'images' => [
                    'https://picsum.photos/seed/'.$seed.'/800/800',
                ],
                'is_active' => true,
                'is_featured' => $i % 17 === 0,
                'has_variants' => false,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Fertig.');

        return self::SUCCESS;
    }
}
