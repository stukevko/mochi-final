<?php

namespace App\Filament\Resources\ShopLinks\Pages;

use App\Filament\Resources\ShopLinks\ShopLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShopLinks extends ListRecords
{
    protected static string $resource = ShopLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
