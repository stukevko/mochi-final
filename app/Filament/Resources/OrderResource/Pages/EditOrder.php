<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Order $record */
        $secure = [
            'status' => $data['status'] ?? $record->status,
            'payment_status' => $data['payment_status'] ?? $record->payment_status,
            'payment_method' => $data['payment_method'] ?? $record->payment_method,
        ];
        unset($data['status'], $data['payment_status'], $data['payment_method']);

        $record->update($data);
        $record->forceFill($secure)->save();

        return $record->refresh();
    }
}
