<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Bestellungen (letzte 7 Tage)';

    protected ?string $description = 'Anzahl neuer Bestellungen pro Tag';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = [];
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $labels[] = $day->format('d.m.');
            $counts[] = Order::query()
                ->whereDate('created_at', $day->toDateString())
                ->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Bestellungen',
                    'data' => $counts,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
