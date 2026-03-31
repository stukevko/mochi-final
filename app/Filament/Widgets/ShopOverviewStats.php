<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Ihr Shop auf einen Blick';

    protected ?string $description = 'Bezahlte Bestellungen ohne Stornierung — Trends helfen beim schnellen Erfassen.';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenueWindow = (float) Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $revenuePrevWindow = (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $totalRevenue = (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $pctChange = $revenuePrevWindow > 0.001
            ? (($revenueWindow - $revenuePrevWindow) / $revenuePrevWindow) * 100
            : null;

        $openOrders = Order::query()
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $sparkline = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $sparkline[] = (float) Order::query()
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $day)
                ->whereNotIn('status', ['cancelled'])
                ->sum('total');
        }

        $trendDescription = $pctChange === null
            ? 'Vorperiode ohne Umsatz — kein Vergleich möglich.'
            : sprintf('%s%.1f %% vs. 30 Tage davor', $pctChange >= 0 ? '+' : '', $pctChange);

        return [
            Stat::make('Gesamtumsatz (bisher)', MoneyFormatter::format($totalRevenue))
                ->description($trendDescription)
                ->descriptionIcon($pctChange !== null && $pctChange < 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart($sparkline),
            Stat::make('Offene Bestellungen', (string) $openOrders)
                ->description('Ausstehend oder in Bearbeitung — bitte zeitnah bearbeiten.')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($openOrders > 0 ? 'warning' : 'gray'),
        ];
    }
}
