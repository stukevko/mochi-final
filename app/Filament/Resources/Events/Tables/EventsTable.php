<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label('Start')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('game_type')
                    ->label('Spielart')
                    ->formatStateUsing(fn (mixed $state, Event $record): string => $record->gameTypeLabel())
                    ->badge()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (?EventStatus $state) => $state?->label())
                    ->badge()
                    ->color(fn (?EventStatus $state) => match ($state) {
                        EventStatus::Active => 'success',
                        EventStatus::Archived => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                SelectFilter::make('game_type')
                    ->label('Spielart')
                    ->options(collect(GameType::casesForSelect())->mapWithKeys(fn (GameType $g) => [$g->value => $g->label()])),
                SelectFilter::make('status')
                    ->options(collect(EventStatus::cases())->mapWithKeys(fn (EventStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
