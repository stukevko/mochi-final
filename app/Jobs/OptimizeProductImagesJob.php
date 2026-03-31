<?php

namespace App\Jobs;

use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OptimizeProductImagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $productId,
    ) {}

    public function handle(ProductObserver $observer): void
    {
        $product = Product::query()->find($this->productId);
        if ($product === null) {
            return;
        }

        $observer->optimizeImagesForProduct($product);
    }
}
