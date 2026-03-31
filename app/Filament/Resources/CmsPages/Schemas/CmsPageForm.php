<?php

namespace App\Filament\Resources\CmsPages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Seite')
                    ->schema([
                        TextInput::make('title')
                            ->label('Seitentitel')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->label('URL-Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('z. B. impressum, widerruf, datenschutz'),
                        RichEditor::make('body')
                            ->label('Inhalt')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
