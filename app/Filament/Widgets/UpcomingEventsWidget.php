<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingEventsWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Nächste Events (so wie auf der Website)')
            ->description('Die drei nächsten aktiven Termine — Klick öffnet die Bearbeitung.')
            ->query(
                Event::query()
                    ->upcomingActive()
                    ->orderBy('starts_at')
                    ->limit(3),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Event'),
                TextColumn::make('starts_at')
                    ->label('Beginn')
                    ->dateTime('d.m.Y, H:i'),
                TextColumn::make('game_type')
                    ->label('Spiel')
                    ->formatStateUsing(fn (mixed $state, Event $record): string => $record->gameTypeLabel()),
            ])
            ->recordUrl(fn (Event $record): string => EventResource::getUrl('edit', ['record' => $record]));
    }
}
