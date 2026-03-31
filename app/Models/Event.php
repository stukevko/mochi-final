<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Models\Concerns\HasGameTypeDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
            'price' => 'decimal:2',
            'game_type' => GameType::class,
            'status' => EventStatus::class,
        ];
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
