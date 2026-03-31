<?php

namespace App\Filament\Resources\ShopLinks\Pages;

use App\Filament\Resources\ShopLinks\ShopLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShopLink extends EditRecord
{
    protected static string $resource = ShopLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
