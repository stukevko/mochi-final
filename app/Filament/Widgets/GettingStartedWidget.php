<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManageBrandingDesign;
use App\Filament\Pages\ManageLegalSettings;
use App\Filament\Pages\ManageMerchantSettings;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Pages\ManageTechnicalSettings;
use App\Filament\Pages\SumUpSync;
use App\Filament\Resources\ProductResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Payments\PaymentProviderService;
use App\Services\SumUp\SumUpSalesSync;
use App\Support\ShopBranding;
use Filament\Widgets\Widget;
use Throwable;

/**
 * „Erste Schritte“: führt Schritt für Schritt durch die Einrichtung und hakt
 * Erledigtes selbst ab. Verschwindet, sobald alles Pflichtige erledigt ist.
 */
class GettingStartedWidget extends Widget
{
    protected string $view = 'filament.widgets.getting-started';

    protected static ?int $sort = -100;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        try {
            return collect((new self)->steps())->contains(fn (array $step): bool => ! $step['done'] && ! $step['optional']);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $steps = $this->steps();
        $required = array_filter($steps, fn (array $s): bool => ! $s['optional']);

        return [
            'steps' => $steps,
            'doneCount' => count(array_filter($required, fn (array $s): bool => $s['done'])),
            'totalCount' => count($required),
        ];
    }

    /**
     * @return list<array{title: string, text: string, url: string, action: string, done: bool, optional: bool}>
     */
    public function steps(): array
    {
        $filled = static fn (string $key): bool => trim(strip_tags((string) Setting::get($key, ''))) !== '';

        $legalDone = $filled('legal_impressum') && $filled('legal_privacy') && $filled('legal_agb') && $filled('legal_widerruf');

        return [
            [
                'title' => 'Shop-Daten und Steuernummer eintragen',
                'text' => 'Name, Adresse und Steuernummer stehen auf jeder Rechnung. Hier stellst du auch die Versandkosten ein.',
                'url' => ManageMerchantSettings::getUrl(),
                'action' => 'Shop-Daten öffnen',
                'done' => $filled('shop_address') && ($filled('tax_number') || $filled('tax_id')),
                'optional' => false,
            ],
            [
                'title' => 'Impressum, Datenschutz, AGB und Widerruf einfügen',
                'text' => 'Ohne diese vier Texte darf ein Online-Shop in Deutschland nicht verkaufen. Einfach die Texte eures Anwalts oder Rechtstext-Anbieters einfügen.',
                'url' => ManageLegalSettings::getUrl(),
                'action' => 'Rechtstexte öffnen',
                'done' => $legalDone,
                'optional' => false,
            ],
            [
                'title' => 'SumUp verbinden',
                'text' => 'Damit Kunden bezahlen können: API-Schlüssel und Händler-Code aus SumUp eintragen und mit dem Test-Knopf prüfen.',
                'url' => ManageTechnicalSettings::getUrl(),
                'action' => 'Zugangsdaten öffnen',
                'done' => app(PaymentProviderService::class)->isSumUpConfigured(),
                'optional' => false,
            ],
            [
                'title' => 'E-Mail-Postfach eintragen',
                'text' => 'Darüber gehen Bestellbestätigungen und Rechnungen an die Kunden. Danach eine Test-Mail schicken.',
                'url' => ManageTechnicalSettings::getUrl(),
                'action' => 'Postfach eintragen',
                'done' => config('mail.default') !== 'log' && filled(config('mail.mailers.smtp.host')) && config('mail.mailers.smtp.host') !== '127.0.0.1',
                'optional' => false,
            ],
            [
                'title' => 'Das erste Produkt anlegen',
                'text' => 'Name, Foto, Preis, Stückzahl — mehr braucht es nicht. Oder alle Artikel auf einmal aus SumUp übernehmen.',
                'url' => ProductResource::getUrl('create'),
                'action' => 'Produkt anlegen',
                'done' => Product::query()->where('is_active', true)->exists(),
                'optional' => false,
            ],
            [
                'title' => 'Eine Testbestellung machen',
                'text' => 'Im Shop einen günstigen Artikel kaufen und bezahlen. Kommen Bestätigungs-Mail und Rechnung an, ist alles bereit.',
                'url' => url('/shop'),
                'action' => 'Zum Shop',
                'done' => Order::query()->where('payment_status', 'paid')->exists(),
                'optional' => false,
            ],
            [
                'title' => 'Kasse und Shop verbinden',
                'text' => 'Verkäufe an der SumUp-Kasse ziehen dann automatisch den Bestand im Shop ab.',
                'url' => SumUpSync::getUrl(),
                'action' => 'Abgleich starten',
                'done' => app(SumUpSalesSync::class)->lastSyncedAt() !== null,
                'optional' => true,
            ],
            [
                'title' => 'Eigenes Logo und Startseite anpassen',
                'text' => 'Ein Logo ist schon dabei. Wer mag, lädt ein eigenes hoch und wählt die Highlight-Karte der Startseite.',
                'url' => ShopBranding::usesPlaceholderLogo() ? ManageBrandingDesign::getUrl() : ManageSiteSettings::getUrl(),
                'action' => 'Anpassen',
                'done' => ! ShopBranding::usesPlaceholderLogo(),
                'optional' => true,
            ],
        ];
    }
}
