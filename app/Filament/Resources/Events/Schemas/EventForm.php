<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventRecurrence;
use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Models\Event;
use App\Services\Events\RecurringEventService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
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
                            ->native(false)
                            ->helperText('Bei Serien: erster Termin der Wiederholung.'),
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

                Section::make('Wiederholung')
                    ->description('Serie wird als einzelne Termine angelegt (max. '.RecurringEventService::MAX_OCCURRENCES.'). Ideal für wöchentliche Drafts & Spieltische.')
                    ->schema([
                        Placeholder::make('series_child_hint')
                            ->label('')
                            ->content(function (?Event $record): HtmlString {
                                if (! $record?->isSeriesChild()) {
                                    return new HtmlString('');
                                }

                                $parent = $record->parent;
                                $label = $parent
                                    ? e($parent->title).' ('.e(optional($parent->starts_at)->format('d.m.Y H:i')).')'
                                    : 'Serie';

                                return new HtmlString(
                                    '<p class="text-sm text-gray-500 dark:text-gray-400">'
                                    .'Dieser Termin gehört zur Serie <strong>'.$label.'</strong>. '
                                    .'Änderungen gelten nur für diesen einzelnen Termin (z. B. Ausfall verschieben).'
                                    .'</p>'
                                );
                            })
                            ->visible(fn (?Event $record): bool => (bool) $record?->isSeriesChild())
                            ->columnSpanFull(),

                        Select::make('recurrence')
                            ->label('Wiederholung')
                            ->options(collect(EventRecurrence::cases())->mapWithKeys(
                                fn (EventRecurrence $r) => [$r->value => $r->label()]
                            ))
                            ->default(EventRecurrence::None->value)
                            ->required()
                            ->live()
                            ->native(false)
                            ->disabled(fn (?Event $record): bool => (bool) $record?->isSeriesChild())
                            ->dehydrated(fn (?Event $record): bool => ! $record?->isSeriesChild())
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? 'Bereits erzeugte Termine bleiben bestehen. Neue Serien bitte über „Event erstellen“ anlegen.'
                                : null),

                        DatePicker::make('recurrence_until')
                            ->label('Wiederholen bis')
                            ->native(false)
                            ->visible(fn (Get $get, ?Event $record): bool => ! $record?->isSeriesChild()
                                && EventRecurrence::tryFrom((string) $get('recurrence'))?->isRepeating())
                            ->required(fn (Get $get, ?Event $record): bool => ! $record?->isSeriesChild()
                                && EventRecurrence::tryFrom((string) $get('recurrence'))?->isRepeating())
                            ->minDate(fn (Get $get) => $get('starts_at') ?: now())
                            ->helperText('Letzter möglicher Termin (inkl.). Max. '.RecurringEventService::MAX_OCCURRENCES.' Termine.'),

                        Toggle::make('sync_series_content')
                            ->label('Titel, Text, Preis & Status auf zukünftige Serien-Termine übernehmen')
                            ->default(false)
                            ->dehydrated(false)
                            ->visible(fn (string $operation, ?Event $record): bool => $operation === 'edit'
                                && (bool) $record?->isSeriesRoot())
                            ->helperText('Verschiebt keine Daten — nur Inhalte (Titel, Beschreibung, Preis, Spielart, Bild, Status, Farbe).'),
                    ])
                    ->columns(2)
                    ->collapsed(fn (string $operation, ?Event $record): bool => $operation === 'edit'
                        && ! $record?->isSeriesRoot()
                        && ! $record?->isSeriesChild()),
            ]);
    }
}
