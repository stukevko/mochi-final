<?php

namespace App\Filament\Resources\PaymentGatewayResource\Pages;

use App\Filament\Resources\PaymentGatewayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPaymentGateways extends ListRecords
{
    protected static string $resource = PaymentGatewayResource::class;

    public function getSubheading(): string | Htmlable | null
    {
        return 'Schritt für Schritt: Zahlart auswählen oder bearbeiten → Zugangsdaten eintragen (oder unten im Expertenfeld) → „Verbindung prüfen“ → aktiv schalten. Die Seite „Server-Keys“ betrifft nur die .env-Datei auf dem Server.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Neue Zahlart')
                ->tooltip('Nur nötig, wenn Ihre Agentur eine zusätzliche Methode anlegt. Üblicherweise reichen die vorhandenen Einträge.')
                ->modalHeading('Zahlart anlegen')
                ->modalDescription('Wählen Sie den passenden Anbieter-Typ. Den internen Code nur ändern, wenn Sie wissen, welche Buchstaben das Checkout erwarte.'),
        ];
    }
}
