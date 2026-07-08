<?php

namespace App\Models;

use App\Support\StorefrontLayoutCache;
use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => StorefrontLayoutCache::forget());
        static::deleted(fn () => StorefrontLayoutCache::forget());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'title',
        'slug',
        'body',
    ];
}
