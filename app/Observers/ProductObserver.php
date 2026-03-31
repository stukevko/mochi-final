<?php

namespace App\Observers;

use App\Jobs\OptimizeProductImagesJob;
use App\Models\Product;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

class ProductObserver
{
    private const int MAX_WIDTH = 800;

    private const int WEBP_QUALITY = 85;

    public function saved(Product $product): void
    {
        if (! $product->wasChanged('images')) {
            return;
        }

        $images = $product->images;
        if (! is_array($images) || $images === []) {
            return;
        }

        OptimizeProductImagesJob::dispatch($product->id);
    }

    public function optimizeImagesForProduct(Product $product): void
    {
        $images = $product->images;
        if (! is_array($images) || $images === []) {
            return;
        }

        $disk = Storage::disk('public');
        $updated = $images;
        $changed = false;

        foreach ($updated as $index => $raw) {
            if (! is_string($raw)) {
                continue;
            }

            $replacement = $this->optimizePath(trim($raw), $disk);
            if ($replacement === null) {
                continue;
            }

            if ($replacement !== $raw) {
                $updated[$index] = $replacement;
                $changed = true;
            }
        }

        if (! $changed) {
            return;
        }

        Product::withoutEvents(function () use ($product, $updated): void {
            $product->update(['images' => array_values($updated)]);
        });
    }

    private function optimizePath(string $path, Filesystem $disk): ?string
    {
        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return null;
        }

        if (str_starts_with($path, '/storage/')) {
            $relative = ltrim(substr($path, strlen('/storage/')), '/');
        } elseif (str_starts_with($path, '/')) {
            return null;
        } else {
            $relative = ltrim($path, '/');
        }

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        if (! $disk->exists($relative)) {
            return null;
        }

        $absolute = $disk->path($relative);
        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

        if (in_array($extension, ['svg', 'gif'], true)) {
            return null;
        }

        $dir = dirname($relative);
        $filename = pathinfo($relative, PATHINFO_FILENAME);
        $webpRelative = ($dir === '.' || $dir === '' ? '' : $dir.'/').$filename.'.webp';

        try {
            $manager = ImageManager::gd();
            $image = $manager->read($absolute)->scaleDown(width: self::MAX_WIDTH);
            $encoded = $image->toWebp(quality: self::WEBP_QUALITY);
            $encoded->save($disk->path($webpRelative));

            if ($webpRelative !== $relative && $disk->exists($relative)) {
                $disk->delete($relative);
            }

            return $webpRelative;
        } catch (Throwable $e) {
            Log::warning('product.image_optimize_failed', [
                'path' => $relative,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
