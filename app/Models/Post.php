<?php

namespace App\Models;

use App\Enums\GameType;
use App\Enums\PostType;
use App\Models\Concerns\HasGameTypeDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasGameTypeDisplay;
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'title',
        'slug',
        'type',
        'post_category_id',
        'game_type',
        'game_type_other',
        'excerpt',
        'body',
        'cover_image_path',
        'published_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'game_type' => GameType::class,
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
