<?php

namespace App\Filament\Resources\ShopLinks;

use App\Filament\Resources\ShopLinks\Pages\CreateShopLink;
use App\Filament\Resources\ShopLinks\Pages\EditShopLink;
use App\Filament\Resources\ShopLinks\Pages\ListShopLinks;
use App\Filament\Resources\ShopLinks\Schemas\ShopLinkForm;
use App\Filament\Resources\ShopLinks\Tables\ShopLinksTable;
use App\Models\ShopLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShopLinkResource extends Resource
{
    protected static ?string $model = ShopLink::class;

    protected static string|UnitEnum|null $navigationGroup = '⚙️ Konfiguration';

    protected static ?int $navigationSort = 130;

    protected static ?string $modelLabel = 'Link';

    protected static ?string $pluralModelLabel = 'Externe Links';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTopRightOnSquare;

    public static function form(Schema $schema): Schema
    {
        return ShopLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShopLinksTable::configure($table);
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
            'index' => ListShopLinks::route('/'),
            'create' => CreateShopLink::route('/create'),
            'edit' => EditShopLink::route('/{record}/edit'),
        ];
    }
}
