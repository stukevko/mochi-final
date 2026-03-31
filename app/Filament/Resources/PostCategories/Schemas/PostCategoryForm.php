<?php

namespace App\Filament\Resources\PostCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kategorie')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->label('URL-Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(120)
                            ->helperText('Interner technischer Name in der URL (Kleinbuchstaben, Bindestriche).'),
                        TextInput::make('sort_order')
                            ->label('Reihenfolge')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
