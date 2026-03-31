<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductAttributeValue;
use App\Support\MoneyFormatter;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Varianten';

    protected static ?string $modelLabel = 'Variante';

    protected static ?string $pluralModelLabel = 'Varianten';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('Artikelnummer (SKU)')
                    ->maxLength(255),

                Forms\Components\TextInput::make('price')
                    ->label('Preis')
                    ->numeric()
                    ->prefix((string) \App\Models\Setting::get('currency_symbol', '€'))
                    ->helperText('Leer lassen für Hauptprodukt-Preis'),

                Forms\Components\TextInput::make('sale_price')
                    ->label('Angebotspreis')
                    ->numeric()
                    ->prefix((string) \App\Models\Setting::get('currency_symbol', '€')),

                Forms\Components\TextInput::make('stock')
                    ->label('Lagerbestand')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Forms\Components\FileUpload::make('image')
                    ->label('Variantenbild')
                    ->image()
                    ->directory('products/variants')
                    ->disk('public')
                    ->visibility('public'),

                Forms\Components\Select::make('attributeValues')
                    ->label('Attribut-Werte')
                    ->relationship('attributeValues', 'value')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Wählen Sie die Attribut-Werte für diese Variante (z.B. "Rot" + "M")'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Bild')
                    ->circular(),

                Tables\Columns\TextColumn::make('attributeValues.value')
                    ->label('Variante')
                    ->badge()
                    ->separator(', '),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                    ->placeholder('Hauptpreis'),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Lager')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state === 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
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
}
