<?php

namespace App\Filament\Widgets;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Order;
use App\Support\MoneyFormatter;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SmartPulseStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -100;

    protected ?string $heading = 'Live-Pulse';

    protected ?string $description = 'Die wichtigsten Shop- und Kalenderzahlen für heute.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $revenueToday = (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereDate('created_at', today())
            ->sum('total');

        $openOrders = Order::query()
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $upcomingEvents = Event::query()
            ->where('status', EventStatus::Active)
            ->whereBetween('starts_at', [now(), now()->addDays(7)])
            ->count();

        return [
            Stat::make('Umsatz heute', MoneyFormatter::format($revenueToday))
                ->description('Bezahlte Bestellungen heute')
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Offene Bestellungen', (string) $openOrders)
                ->description('Pending + Processing')
                ->icon(Heroicon::OutlinedArchiveBox),
            Stat::make('Anstehende Events (7 Tage)', (string) $upcomingEvents)
                ->description('Aktive Termine der nächsten Woche')
                ->icon(Heroicon::OutlinedCalendarDays),
        ];
    }
}

