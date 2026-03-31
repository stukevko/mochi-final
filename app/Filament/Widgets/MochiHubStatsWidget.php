<?php

namespace App\Filament\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Post;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MochiHubStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -50;

    protected ?string $heading = 'Mochi Hub auf einen Blick';

    protected ?string $description = 'Live-Zahlen aus der Datenbank — wie die öffentliche Seite.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $activeEvents = Event::query()->where('status', EventStatus::Active)->count();
        $upcoming = Event::query()->upcomingActive()->count();
        $publishedPosts = Post::query()->published()->count();

        $latest = Post::query()
            ->published()
            ->orderByDesc('published_at')
            ->first();

        return [
            Stat::make('Events (aktiv)', $activeEvents)
                ->description('Sichtbar im Kalender & Feed')
                ->icon(Heroicon::OutlinedCalendarDays),
            Stat::make('Anstehende Termine', $upcoming)
                ->description('Ab jetzt, Status aktiv')
                ->icon(Heroicon::OutlinedClock),
            Stat::make('News & Journal', $publishedPosts)
                ->description('Veröffentlichte Beiträge')
                ->icon(Heroicon::OutlinedNewspaper),
            Stat::make('Letzte News', $latest?->title ?? '—')
                ->description($latest?->published_at?->diffForHumans() ?? 'Noch keine Beiträge')
                ->icon(Heroicon::OutlinedSparkles),
        ];
    }
}
