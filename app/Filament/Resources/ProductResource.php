<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Support\MoneyFormatter;
use Filament\Forms;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string | \UnitEnum | null $navigationGroup = '🛍️ Management';

    protected static ?string $modelLabel = 'Produkt';

    protected static ?string $pluralModelLabel = 'Produkte';

    protected static ?int $navigationSort = 10;

    protected static ?int $globalSearchSort = 1;

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'slug'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Allgemein')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Produktname')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),

                                Forms\Components\TextInput::make('slug')
                                    ->label('URL-Slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\Textarea::make('short_description')
                                    ->label('Kurzbeschreibung')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('description')
                                    ->label('Beschreibung')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Section::make('Bilder')
                            ->schema([
                                Forms\Components\FileUpload::make('images')
                                    ->label('Produktbilder')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->directory('products')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Varianten')
                            ->schema([
                                Forms\Components\Toggle::make('has_variants')
                                    ->label('Hat Varianten')
                                    ->helperText('Aktivieren Sie dies, wenn das Produkt in verschiedenen Varianten (z.B. Größen, Farben) verfügbar ist.')
                                    ->reactive(),

                                Forms\Components\Placeholder::make('variants_info')
                                    ->label('')
                                    ->content('Varianten können nach dem Speichern des Produkts im Bearbeitungsmodus verwaltet werden.')
                                    ->visible(fn ($get) => $get('has_variants')),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Preis & Lager')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Preis')
                                    ->required()
                                    ->numeric()
                                    ->prefix((string) \App\Models\Setting::get('currency_symbol', '€'))
                                    ->minValue(0),

                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Angebotspreis')
                                    ->numeric()
                                    ->prefix((string) \App\Models\Setting::get('currency_symbol', '€'))
                                    ->minValue(0)
                                    ->lt('price'),

                                Forms\Components\TextInput::make('sku')
                                    ->label('Artikelnummer (SKU)')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                Forms\Components\TextInput::make('stock')
                                    ->label('Lagerbestand')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->hidden(fn ($get) => (bool) $get('has_variants')),
                            ]),

                        Section::make('Organisation')
                            ->schema([
                                Forms\Components\Select::make('category_id')
                                    ->label('Kategorie')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required(),
                                    ]),
                            ]),

                        Section::make('Status')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Hervorgehoben')
                                    ->helperText('Wird auf der Startseite angezeigt'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Bild')
                    ->circular()
                    ->stacked()
                    ->limit(1),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Angebot')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('filament_stock_level')
                    ->label('Lager')
                    ->sortable(false)
                    ->badge()
                    ->color(function ($record) {
                        $n = $record->filament_stock_level;

                        return match (true) {
                            $n === 0 => 'danger',
                            $n < 10 => 'warning',
                            default => 'success',
                        };
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('has_variants')
                    ->label('Varianten')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Hervorgehoben'),

                Tables\Filters\TernaryFilter::make('has_variants')
                    ->label('Hat Varianten'),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Niedriger Lagerbestand')
                    ->query(fn ($query) => $query->where('stock', '<', 10)->where('stock', '>', 0)),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Ausverkauft')
                    ->query(fn ($query) => $query->where('stock', 0)),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'variants']);
    }
}
