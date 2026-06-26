<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Technischer Hinweis zu .env — bewusst getrennt von „Zahlungen einrichten“.
 */
class PaymentSettingsCheck extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';

    protected static string | UnitEnum | null $navigationGroup = '⚙️ Konfiguration';

    protected static ?string $navigationLabel = 'Server-Keys (.env-Hilfe)';

    protected static ?int $navigationSort = 150;

    protected static ?string $slug = 'payment-settings-check';

    public array $checks = [];

    public bool $showDebugWarning = false;

    public string $shopName = '';

    public function mount(): void
    {
        $sumupToken = (string) config('services.sumup.token', '');
        $sumupMerchantCode = (string) config('services.sumup.merchant_code', '');

        $this->shopName = (string) Setting::get('shop_name', config('app.name', 'Shop'));

        $this->checks = [
            [
                'provider' => 'SumUp Token (.env SUMUP_TOKEN)',
                'configured' => $sumupToken !== '',
                'masked' => $this->maskedValue($sumupToken),
            ],
            [
                'provider' => 'SumUp Merchant Code (.env SUMUP_MERCHANT_CODE)',
                'configured' => $sumupMerchantCode !== '',
                'masked' => $this->maskedValue($sumupMerchantCode),
            ],
        ];

        $this->showDebugWarning = app()->hasDebugModeEnabled();
    }

    public function getTitle(): string
    {
        return 'Server & .env – Zahlungs-Keys';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaView::make('filament.pages.payment-setup-env-status')
                    ->viewData(fn ($livewire): array => [
                        'checks' => $livewire->checks,
                        'showDebugWarning' => $livewire->showDebugWarning,
                        'shopName' => $livewire->shopName,
                    ]),
            ]);
    }

    private function maskedValue(string $value): string
    {
        if ($value === '') {
            return 'Nicht gesetzt';
        }

        $prefix = substr($value, 0, min(6, strlen($value)));
        $suffix = substr($value, -4);

        return $prefix.'…'.$suffix;
    }
}
