<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (?PostType $state) => $state?->label())
                    ->badge()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Veröffentlicht')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Live')
                    ->boolean(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options(collect(PostType::cases())->mapWithKeys(fn (PostType $t) => [$t->value => $t->label()])),
                SelectFilter::make('post_category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name'),
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
