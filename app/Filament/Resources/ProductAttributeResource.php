<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductAttributeResource\Pages;
use App\Filament\Resources\ProductAttributeResource\RelationManagers;
use App\Models\ProductAttribute;
use Filament\Forms;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductAttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string | \UnitEnum | null $navigationGroup = '🛍️ Management';

    protected static ?string $modelLabel = 'Attribut';

    protected static ?string $pluralModelLabel = 'Produkt-Attribute';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Attribut')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('z.B. "Größe", "Farbe", "Material"')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Section::make('Werte')
                    ->schema([
                        Forms\Components\Repeater::make('values')
                            ->label('Attribut-Werte')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('value')
                                    ->label('Wert')
                                    ->required()
                                    ->helperText('z.B. "S", "M", "L" oder "Rot", "Blau"'),

                                Forms\Components\ColorPicker::make('color_code')
                                    ->label('Farbcode')
                                    ->helperText('Nur für Farb-Attribute'),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sortierung')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Wert hinzufügen')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn ($state) => $state['value'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('values_count')
                    ->label('Werte')
                    ->counts('values')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('values.value')
                    ->label('Verfügbare Werte')
                    ->badge()
                    ->separator(', ')
                    ->limit(5),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProductAttributes::route('/'),
            'create' => Pages\CreateProductAttribute::route('/create'),
            'edit' => Pages\EditProductAttribute::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['values']);
    }
}
