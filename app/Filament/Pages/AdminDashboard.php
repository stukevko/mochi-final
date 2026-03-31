<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\OpenContactRequestsWidget;
use App\Filament\Widgets\RecentCommerceAndEventsWidget;
use App\Filament\Widgets\SmartPulseStatsWidget;
use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

class AdminDashboard extends Dashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = '🏠 Home';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string|Htmlable
    {
        return 'Mochi Admin';
    }

    /**
     * @return array<class-string<\Filament\Widgets\Widget> | \Filament\Widgets\WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            SmartPulseStatsWidget::class,
            OpenContactRequestsWidget::class,
            RecentCommerceAndEventsWidget::class,
        ];
    }
}
