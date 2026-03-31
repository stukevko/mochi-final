<?php

namespace App\Filament\Resources;

use App\Enums\GameType;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = '📰 Inhalte';

    protected static bool $isGloballySearchable = false;

    protected static ?string $modelLabel = 'Kategorie';

    protected static ?string $navigationLabel = 'Spielarten';

    protected static ?string $pluralModelLabel = 'Kategorien';

    protected static ?int $navigationSort = 30;

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Allgemein')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL-Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('game_type')
                            ->label('Spielart')
                            ->helperText('Neon-Rand / Glow der Produktkarten im Shop (wie Events).')
                            ->options(collect(GameType::casesForSelect())->mapWithKeys(fn (GameType $g) => [$g->value => $g->label()]))
                            ->searchable()
                            ->nullable()
                            ->placeholder('Neutral (grau)'),

                        Forms\Components\Select::make('parent_id')
                            ->label('Übergeordnete Kategorie')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Keine (Hauptkategorie)'),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Bild & Einstellungen')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Kategoriebild')
                            ->image()
                            ->directory('categories')
                            ->disk('public')
                            ->visibility('public')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sortierung')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Bild')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('game_type')
                    ->label('Spielart')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof GameType) {
                            return $state->label();
                        }
                        if (is_string($state) && $state !== '') {
                            return GameType::tryFrom($state)?->label() ?? $state;
                        }

                        return '—';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Übergeordnet')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produkte')
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sortierung')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Übergeordnete Kategorie')
                    ->relationship('parent', 'name')
                    ->placeholder('Alle'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['parent']);
    }
}
