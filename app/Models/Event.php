<?php

namespace App\Models;

use App\Enums\EventRecurrence;
use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Models\Concerns\HasGameTypeDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Event extends Model
{
    use HasGameTypeDisplay;

    public const FEED_CACHE_KEY = 'events.feed.json';

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::FEED_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::FEED_CACHE_KEY));
    }

    protected $fillable = [
        'title',
        'slug',
        'description',
        'starts_at',
        'recurrence',
        'recurrence_until',
        'parent_event_id',
        'price',
        'game_type',
        'game_type_other',
        'calendar_color',
        'image_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'recurrence_until' => 'date',
            'recurrence' => EventRecurrence::class,
            'price' => 'decimal:2',
            'game_type' => GameType::class,
            'status' => EventStatus::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_event_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_event_id');
    }

    public function isSeriesRoot(): bool
    {
        return $this->parent_event_id === null
            && ($this->recurrence?->isRepeating() ?? false);
    }

    public function isSeriesChild(): bool
    {
        return $this->parent_event_id !== null;
    }

    public function seriesRoot(): ?self
    {
        if ($this->isSeriesChild()) {
            return $this->parent;
        }

        return $this->isSeriesRoot() ? $this : null;
    }

    public function scopeUpcomingActive(Builder $query): Builder
    {
        return $query
            ->where('status', EventStatus::Active)
            ->where('starts_at', '>=', now());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Active);
    }

    /**
     * Farben für FullCalendar JSON (Override oder GameType).
     *
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    public function calendarFeedColors(): array
    {
        $hex = $this->calendar_color ? trim((string) $this->calendar_color) : '';
        if ($hex !== '' && ! str_starts_with($hex, '#')) {
            $hex = '#'.$hex;
        }

        if ($hex !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return self::calendarColorsFromHex($hex);
        }

        return $this->game_type?->eventCalendarStyle()
            ?? GameType::defaultEventCalendarStyle();
    }

    /**
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    public static function calendarColorsFromHex(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return GameType::defaultEventCalendarStyle();
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = $r * 0.299 + $g * 0.587 + $b * 0.114;

        return [
            'backgroundColor' => sprintf('rgba(%d, %d, %d, 0.45)', $r, $g, $b),
            'borderColor' => '#'.$hex,
            'textColor' => $luma > 160 ? '#0b1220' : '#f8fafc',
        ];
    }
}
