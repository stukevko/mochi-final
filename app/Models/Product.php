<?php

namespace App\Models;

use App\Enums\GameType;
use App\Support\ProductImageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * $fillable nur für vertrauenswürdige Admin-Pfade (Filament). Öffentliche Mass-Assignment-Endpoints gibt es nicht.
 */
class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'sale_price',
        'sku',
        'stock',
        'category_id',
        'images',
        'is_active',
        'is_featured',
        'has_variants',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'images' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'has_variants' => 'boolean',
    ];

    /**
     * Kategorie des Produkts
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Varianten des Produkts
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Attribut-Typen des Produkts (z.B. Größe, Farbe)
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_product_attributes')
            ->withTimestamps();
    }

    /**
     * Aktueller Preis (Sale-Preis wenn vorhanden)
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Ist das Produkt im Sale?
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }

    /**
     * Erste Bild-URL nur wenn erlaubt (https/http oder /storage/… ohne Traversal).
     */
    public function safeImageUrl(?int $index = 0): ?string
    {
        if (! is_array($this->images) || ! isset($this->images[$index])) {
            return null;
        }

        return ProductImageUrl::sanitize((string) $this->images[$index]);
    }

    /**
     * Ist das Produkt verfügbar (auf Lager)?
     */
    public function getIsInStockAttribute(): bool
    {
        if ($this->has_variants) {
            return $this->variants()->where('stock', '>', 0)->where('is_active', true)->exists();
        }
        return $this->stock > 0;
    }

    /**
     * Für die Admin-Produktliste: bei Varianten der niedrigste Bestand aktiver Varianten („schwächstes Glied“).
     */
    public function getFilamentStockLevelAttribute(): int
    {
        if (! $this->has_variants) {
            return (int) $this->stock;
        }

        if ($this->relationLoaded('variants')) {
            $active = $this->variants->where('is_active', true);
            if ($active->isEmpty()) {
                return 0;
            }

            return (int) ($active->min('stock') ?? 0);
        }

        $min = $this->variants()
            ->where('is_active', true)
            ->min('stock');

        return (int) ($min ?? 0);
    }

    /**
     * @return array{border: string, shadow: string}
     */
    public function storefrontNeonAccent(): array
    {
        $game = $this->category?->game_type;
        if ($game instanceof GameType) {
            return $game->storefrontCardAccent();
        }

        return [
            'border' => 'rgba(148, 163, 184, 0.35)',
            'shadow' => '0 16px 48px -28px rgba(0,0,0,0.85), 0 0 28px -12px rgba(148, 163, 184, 0.25)',
        ];
    }
}
