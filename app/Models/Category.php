<?php

namespace App\Models;

use App\Enums\GameType;
use App\Support\ProductImageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    /** Nur Arrays cachen (keine Eloquent-Collections im Cache — sonst fehlerhafte Typen nach unserialize). */
    public const CACHE_KEY_NAV_ROOT = 'shop.nav.root_categories.v2';

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY_NAV_ROOT);
            Cache::forget('shop.nav.root_categories.v1');
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY_NAV_ROOT);
            Cache::forget('shop.nav.root_categories.v1');
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'game_type',
        'description',
        'image',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'game_type' => GameType::class,
    ];

    /**
     * Unterkategorien
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Eltern-Kategorie
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function safeImageUrl(): ?string
    {
        if ($this->image === null || $this->image === '') {
            return null;
        }

        return ProductImageUrl::sanitize((string) $this->image);
    }

    /**
     * Produkte in dieser Kategorie
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
