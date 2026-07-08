<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Mail\OrderShipped;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_paid')
                ->label('Als bezahlt markieren')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->payment_status === 'pending')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['payment_status' => 'paid']);
                    Notification::make()->success()->title('Zahlungsstatus aktualisiert')->send();
                }),

            Actions\Action::make('mark_shipped_notify')
                ->label('Versenden & Mail')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->visible(fn (): bool => ! in_array($this->getRecord()->status, ['shipped', 'delivered', 'cancelled'], true))
                ->modalHeading('Als versendet markieren und Versand-Mail senden')
                ->form([
                    Forms\Components\Select::make('shipping_carrier')
                        ->label('Versanddienst')
                        ->options(Order::shippingCarrierOptions())
                        ->placeholder('Bitte wählen')
                        ->native(false),
                    Forms\Components\TextInput::make('tracking_number')
                        ->label('Sendungsnummer (optional)')
                        ->required(fn ($get) => filled($get('shipping_carrier')) && $get('shipping_carrier') !== 'other')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('custom_tracking_url')
                        ->label('Eigener Tracking-Link')
                        ->url()
                        ->required(fn ($get) => $get('shipping_carrier') === 'other')
                        ->maxLength(500)
                        ->placeholder('https://...')
                        ->visible(fn ($get) => $get('shipping_carrier') === 'other'),
                ])
                ->action(function ($data): void {
                    $record = $this->getRecord();
                    $trackingNumber = isset($data['tracking_number']) ? trim((string) $data['tracking_number']) : '';
                    $carrier = isset($data['shipping_carrier']) ? (string) $data['shipping_carrier'] : null;
                    $customTrackingUrl = isset($data['custom_tracking_url']) ? trim((string) $data['custom_tracking_url']) : '';
                    $record->update([
                        'status' => 'shipped',
                        'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
                        'shipping_carrier' => filled($carrier) ? $carrier : null,
                        'custom_tracking_url' => $customTrackingUrl !== '' ? $customTrackingUrl : null,
                    ]);
                    $to = $record->customerEmail();
                    if ($to !== null && $to !== '') {
                        Mail::to($to)->send(new OrderShipped($record));
                        Notification::make()
                            ->success()
                            ->title('Versendet')
                            ->body('Die Versand-Mail wurde vorbereitet.')
                            ->send();
                    } else {
                        Notification::make()
                            ->warning()
                            ->title('Keine Kunden-E-Mail')
                            ->body('Status wurde auf „Versendet" gesetzt.')
                            ->send();
                    }
                }),
            Actions\Action::make('download_invoice')
                ->label('Rechnung herunterladen')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->loadMissing('items');
                    $pdf = Pdf::loadView('pdf.invoice', ['order' => $record]);

                    return response()->streamDownload(
                        function () use ($pdf): void {
                            echo $pdf->output();
                        },
                        'rechnung-'.$record->order_number.'.pdf'
                    );
                }),

            Actions\EditAction::make(),
        ];
    }
}
