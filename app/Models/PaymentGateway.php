<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'is_test_mode',
        'config',
        'sort_order',
        'min_amount',
        'max_amount',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
        'config' => 'encrypted:array',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    protected $hidden = [
        'config', // Sensible Daten nicht in API-Responses
    ];

    /**
     * Holt einen Konfigurationswert
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return ($this->config ?? [])[$key] ?? $default;
    }

    /**
     * Setzt einen Konfigurationswert (gesamtes config-JSON ist als encrypted:array gespeichert).
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
    }

    /**
     * Aktive Payment Gateways
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
