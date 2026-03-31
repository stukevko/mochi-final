<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\MoneyFormatter;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    
    protected static ?string $title = 'Bestellpositionen';
    
    protected static ?string $modelLabel = 'Position';
    
    protected static ?string $pluralModelLabel = 'Positionen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produkt')
                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('product_name', $product->name);
                                $set('unit_price', $product->current_price);
                                $set('product_variant_id', null);
                            }
                        }
                    }),
                    
                Forms\Components\Select::make('product_variant_id')
                    ->label('Variante')
                    ->options(function ($get) {
                        $productId = $get('product_id');
                        if (!$productId) {
                            return [];
                        }
                        return ProductVariant::where('product_id', $productId)
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($variant) => [
                                $variant->id => $variant->sku . ' - ' . MoneyFormatter::format((float) $variant->price)
                            ]);
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $variant = ProductVariant::find($state);
                            if ($variant) {
                                $set('variant_name', $variant->sku);
                                $set('unit_price', $variant->price);
                            }
                        }
                    }),
                    
                Forms\Components\Hidden::make('product_name'),
                    
                Forms\Components\Hidden::make('variant_name'),
                    
                Forms\Components\TextInput::make('quantity')
                    ->label('Menge')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        $unitPrice = $get('unit_price') ?? 0;
                        $set('total_price', $state * $unitPrice);
                    }),
                    
                Forms\Components\TextInput::make('unit_price')
                    ->label('Stückpreis')
                    ->numeric()
                    ->prefix((string) Setting::get('currency_symbol', '€'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        $quantity = $get('quantity') ?? 1;
                        $set('total_price', $quantity * $state);
                    }),
                    
                Forms\Components\TextInput::make('total_price')
                    ->label('Zwischensumme')
                    ->numeric()
                    ->prefix((string) Setting::get('currency_symbol', '€'))
                    ->disabled()
                    ->dehydrated(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produkt')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Variante')
                    ->placeholder('-'),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Menge')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Stückpreis')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state)),
                    
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Gesamt')
                    ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                    ->summarize(Tables\Columns\Summarizers\Sum::make()
                        ->formatStateUsing(fn ($state) => MoneyFormatter::format((float) $state))
                        ->label('Summe')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateFormDataUsing(function ($data) {
                        if (empty($data['product_name']) && !empty($data['product_id'])) {
                            $product = Product::find($data['product_id']);
                            $data['product_name'] = $product?->name ?? '';
                        }
                        if (empty($data['variant_name']) && !empty($data['product_variant_id'])) {
                            $variant = ProductVariant::find($data['product_variant_id']);
                            $data['variant_name'] = $variant?->sku ?? '';
                        }
                        $data['total_price'] = ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0);
                        return $data;
                    })
                    ->after(function () {
                        $this->updateOrderTotals();
                    }),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->after(function () {
                        $this->updateOrderTotals();
                    }),
                Actions\DeleteAction::make()
                    ->after(function () {
                        $this->updateOrderTotals();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->after(function () {
                            $this->updateOrderTotals();
                        }),
                ]),
            ]);
    }

    protected function updateOrderTotals(): void
    {
        $order = $this->getOwnerRecord();
        $items = $order->items()->get();

        $gross = (float) $items->sum('total_price');
        $taxRatePercent = max(0.0, (float) Setting::get('tax_rate', 19));
        $divisor = 1 + ($taxRatePercent / 100);
        $net = round($gross / ($divisor > 0 ? $divisor : 1), 2);
        $tax = round($gross - $net, 2);
        $total = round($gross + (float) ($order->shipping_cost ?? 0), 2);

        $order->forceFill([
            'subtotal' => $net,
            'tax' => $tax,
            'total' => $total,
        ])->save();
    }
}
