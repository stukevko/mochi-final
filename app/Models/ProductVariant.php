<?php

namespace App\Models;

use App\Support\ProductImageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'sale_price',
        'stock',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Das Hauptprodukt
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Attribut-Werte dieser Variante (z.B. "Rot" + "M")
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_variant_attribute_values',
            'product_variant_id',
            'product_attribute_value_id'
        )->withTimestamps();
    }

    public function safeImageUrl(): ?string
    {
        return ProductImageUrl::sanitize($this->image);
    }

    /**
     * Aktueller Preis (Varianten-Preis oder Hauptprodukt-Preis)
     */
    public function getCurrentPriceAttribute(): float
    {
        if ($this->sale_price !== null) {
            return $this->sale_price;
        }
        if ($this->price !== null) {
            return $this->price;
        }
        return $this->product->current_price;
    }

    /**
     * Varianten-Name (z.B. "Rot, M")
     */
    public function getNameAttribute(): string
    {
        return $this->attributeValues->pluck('value')->implode(', ');
    }
}
