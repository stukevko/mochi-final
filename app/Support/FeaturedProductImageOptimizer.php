<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

final class FeaturedProductImageOptimizer
{
    public const MAX_WIDTH = 1400;

    public static function optimize(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $fullPath = Storage::disk('public')->path($relativePath);
        if (! is_file($fullPath)) {
            return;
        }

        try {
            $manager = ImageManager::gd();
            $image = $manager->read($fullPath);
            $image->scaleDown(width: self::MAX_WIDTH);

            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $encoded = match ($ext) {
                'png' => $image->toPng(),
                'webp' => $image->toWebp(85),
                'gif' => $image->toGif(),
                default => $image->toJpeg(85),
            };
            $encoded->save($fullPath);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
