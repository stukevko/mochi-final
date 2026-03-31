<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = Order::generateOrderNumber();
        $data['subtotal'] = 0;
        $data['tax'] = 0;
        $data['total'] = $data['shipping_cost'] ?? 0;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $orderNumber = $data['order_number'] ?? Order::generateOrderNumber();
        $subtotal = (float) ($data['subtotal'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $total = (float) ($data['total'] ?? 0);

        $status = (string) ($data['status'] ?? 'pending');
        $paymentStatus = (string) ($data['payment_status'] ?? 'pending');
        $paymentMethod = (string) ($data['payment_method'] ?? 'invoice');

        unset(
            $data['order_number'],
            $data['subtotal'],
            $data['tax'],
            $data['discount'],
            $data['total'],
            $data['payment_id'],
            $data['payment_data'],
            $data['status'],
            $data['payment_status'],
            $data['payment_method'],
        );

        /** @var Order $record */
        $record = new Order;
        $record->fill($data);
        $record->forceFill([
            'order_number' => $orderNumber,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
        ]);
        $record->save();

        if ($parentRecord = $this->getParentRecord()) {
            return $this->associateRecordWithParent($record, $parentRecord);
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
