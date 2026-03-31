<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestPostsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Neueste News')
            ->description('Die drei zuletzt veröffentlichten Beiträge — Klick öffnet die Bearbeitung.')
            ->query(
                Post::query()
                    ->published()
                    ->orderByDesc('published_at')
                    ->limit(3),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->limit(50),
                TextColumn::make('published_at')
                    ->label('Veröffentlicht')
                    ->dateTime('d.m.Y H:i'),
                TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? ''),
            ])
            ->recordUrl(fn (Post $record): string => PostResource::getUrl('edit', ['record' => $record]));
    }
}
