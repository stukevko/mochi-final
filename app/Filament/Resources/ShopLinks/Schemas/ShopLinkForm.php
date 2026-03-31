<?php

namespace App\Filament\Resources\ShopLinks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShopLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Externer Link')
                    ->schema([
                        TextInput::make('label')
                            ->label('Bezeichnung')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('url')
                            ->label('URL')
                            ->url()
                            ->required()
                            ->maxLength(2048),
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
