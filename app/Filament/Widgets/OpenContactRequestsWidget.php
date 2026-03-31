<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpenContactRequestsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -95;

    protected ?string $heading = 'Kontakt';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $open = ContactMessage::openCount();

        return [
            Stat::make('Offene Kontaktanfragen', (string) $open)
                ->description('Alle außer „Erledigt“')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color($open > 0 ? 'warning' : 'success')
                ->url(ContactMessageResource::getUrl('index')),
        ];
    }
}
