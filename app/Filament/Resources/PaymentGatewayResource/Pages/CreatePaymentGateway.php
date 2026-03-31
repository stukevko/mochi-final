<?php

namespace App\Filament\Resources\PaymentGatewayResource\Pages;

use App\Concerns\MapsMerchantPaymentFields;
use App\Filament\Resources\PaymentGatewayResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentGateway extends CreateRecord
{
    use MapsMerchantPaymentFields;

    protected static string $resource = PaymentGatewayResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mapPaymentGatewayFormDataBeforeSave($data);
    }
}
