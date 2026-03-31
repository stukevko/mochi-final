<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Setting $setting): void {
            self::forgetCacheKey($setting->key);
        });

        static::deleted(function (Setting $setting): void {
            self::forgetCacheKey($setting->key);
        });
    }

    protected static function forgetCacheKey(string $key): void
    {
        Cache::forget("setting.{$key}");
        Cache::forget("setting.v2.{$key}");
    }

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Holt einen Einstellungswert
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Nur Arrays/Skalare im App-Cache — niemals Eloquent/Collection/Closure (unserialize-Fallen).
        $payload = Cache::rememberForever("setting.v2.{$key}", function () use ($key) {
            $row = self::query()->where('key', $key)->first();

            if (! $row) {
                return null;
            }

            return [
                'value' => $row->value,
                'type' => $row->type,
            ];
        });

        if ($payload === null) {
            return $default;
        }

        return self::castValue($payload['value'], $payload['type'] ?? 'text');
    }

    /**
     * Setzt einen Einstellungswert
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $storedValue = self::prepareValue($value, $type);

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
            ]
        );

        self::forgetCacheKey($key);
    }

    /**
     * Castet den Wert in den richtigen Typ
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode($value, true),
            'encrypted' => Crypt::decryptString($value),
            default => $value,
        };
    }

    /**
     * Bereitet den Wert für die Speicherung vor
     */
    protected static function prepareValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'json' => json_encode($value),
            'encrypted' => Crypt::encryptString($value),
            default => (string) $value,
        };
    }
}
