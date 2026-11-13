<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\SumUpSale;
use App\Services\SumUp\SumUpCatalogCsvImporter;
use App\Services\SumUp\SumUpSalesSync;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;
use Throwable;
use UnitEnum;

/**
 * Laden (SumUp-Kasse) und Online-Shop als eine Einheit:
 * Artikel aus SumUp übernehmen und Ladenverkäufe vom Shop-Bestand abziehen.
 */
class SumUpSync extends Page
{
    use WithFileUploads;

    protected static ?string $slug = 'sumup-abgleich';

    protected static string|UnitEnum|null $navigationGroup = '🛍️ Management';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Kasse & Shop (SumUp)';

    protected static ?string $title = 'Kasse & Shop abgleichen';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected string $view = 'filament.pages.sumup-sync';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $csv = null;

    public bool $createMissing = true;

    public bool $overwriteStock = false;

    public function syncSalesNow(SumUpSalesSync $sync): void
    {
        if (! $sync->isEnabled()) {
            Notification::make()->warning()->title('SumUp ist nicht eingerichtet')
                ->body('SUMUP_TOKEN und SUMUP_MERCHANT_CODE müssen in der .env stehen.')->send();

            return;
        }

        try {
            $stats = $sync->run();
        } catch (Throwable $e) {
            report($e);
            Notification::make()->danger()->title('Abgleich fehlgeschlagen')->body($e->getMessage())->send();

            return;
        }

        Notification::make()->success()
            ->title($stats['started'] ? 'Abgleich gestartet' : 'Abgleich fertig')
            ->body($stats['started']
                ? 'Ab jetzt werden Ladenverkäufe automatisch vom Shop-Bestand abgezogen.'
                : sprintf('%d neue Ladenverkäufe, %d Positionen verbucht, %d neue Entwürfe.', $stats['sales'], $stats['matched'], $stats['drafts']))
            ->send();
    }

    public function importCatalog(SumUpCatalogCsvImporter $importer): void
    {
        $this->validate([
            'csv' => ['required', 'file', 'max:10240', 'mimes:csv,txt'],
        ], [], ['csv' => 'CSV-Datei']);

        try {
            $stats = $importer->import($this->csv->getRealPath(), $this->createMissing, $this->overwriteStock, false);
        } catch (Throwable $e) {
            report($e);
            Notification::make()->danger()->title('Import fehlgeschlagen')->body($e->getMessage())->send();

            return;
        } finally {
            $this->reset('csv');
        }

        $notification = Notification::make()
            ->title('Artikel übernommen')
            ->body(sprintf(
                '%d neu, %d aktualisiert, %d übersprungen%s',
                $stats['created'], $stats['updated'], $stats['skipped'],
                $stats['errors'] !== [] ? ' — '.count($stats['errors']).' Fehler: '.implode(' | ', array_slice($stats['errors'], 0, 3)) : '.',
            ));

        ($stats['errors'] !== [] ? $notification->warning() : $notification->success())->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $sync = app(SumUpSalesSync::class);

        return [
            'enabled' => $sync->isEnabled(),
            'lastSyncedAt' => $sync->lastSyncedAt(),
            'recentSales' => SumUpSale::query()->orderByDesc('closed_at')->limit(15)->get(),
            'drafts' => Product::query()
                ->where('is_active', false)
                ->whereNotNull('sumup_item_id')
                ->where('short_description', 'like', 'Automatisch aus SumUp%')
                ->orderByDesc('id')
                ->limit(20)
                ->get(['id', 'name', 'sku', 'price']),
            'withoutSku' => Product::query()->where('is_active', true)->whereNull('sku')->whereNull('sumup_item_id')->count(),
        ];
    }
}
