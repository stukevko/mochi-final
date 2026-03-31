<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Enums\GameType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stammdaten')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, string $operation) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug((string) $state));
                            }),
                        Toggle::make('allow_slug_edit')
                            ->label('Slug freischalten (öffentliche URL ändert sich)')
                            ->visibleOn('edit')
                            ->default(false)
                            ->live()
                            ->dehydrated(false),
                        TextInput::make('slug')
                            ->label('URL-Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Wird nur beim Anlegen automatisch aus dem Titel erzeugt. Zum Ändern „Slug freischalten“ nutzen — bestehende Links werden ungültig.')
                            ->readOnly(fn (Get $get, string $operation): bool => $operation === 'edit' && ! $get('allow_slug_edit')),
                        RichEditor::make('description')
                            ->label('Beschreibung')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'link',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                        DateTimePicker::make('starts_at')
                            ->label('Datum & Uhrzeit')
                            ->required()
                            ->native(false),
                        TextInput::make('price')
                            ->label('Preis')
                            ->numeric()
                            ->prefix('€')
                            ->placeholder('z. B. 15'),
                        Select::make('game_type')
                            ->label('Spielart')
                            ->options(collect(GameType::casesForSelect())->mapWithKeys(fn (GameType $g) => [$g->value => $g->label()]))
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('game_type_other')
                            ->label('Name der eigenen Spielart')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => $get('game_type') === GameType::Custom->value)
                            ->required(fn (Get $get): bool => $get('game_type') === GameType::Custom->value)
                            ->helperText('Wird in Kalender, Liste und Badges angezeigt.'),
                        ColorPicker::make('calendar_color')
                            ->label('Kalender-Farbe (optional)')
                            ->helperText('Überschreibt die Standardfarbe der Spielart im öffentlichen Kalender — z. B. für Jubiläen oder Sonder-Events.')
                            ->nullable()
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->label('Veranstaltungsbild')
                            ->image()
                            ->disk('public')
                            ->directory('events')
                            ->imageEditor()
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options(collect(EventStatus::cases())->mapWithKeys(fn (EventStatus $s) => [$s->value => $s->label()]))
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }
}
