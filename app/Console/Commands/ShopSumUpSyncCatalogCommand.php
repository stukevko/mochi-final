<?php

namespace App\Console\Commands;

use App\Services\SumUp\SumUpCatalogCsvImporter;
use Illuminate\Console\Command;
use Throwable;

class ShopSumUpSyncCatalogCommand extends Command
{
    protected $signature = 'shop:sumup-sync-catalog
                            {--file= : Optionaler CSV-Pfad (sonst SUMUP_CATALOG_CSV_PATH)}
                            {--create : Fehlende Produkte anlegen}
                            {--no-update-stock : Bestand aus CSV nicht überschreiben}';

    protected $description = 'Synchronisiert den Shop-Katalog aus der konfigurierten SumUp CSV-Datei';

    public function handle(SumUpCatalogCsvImporter $importer): int
    {
        $path = (string) ($this->option('file') ?: config('services.sumup.catalog_csv_path', ''));

        if ($path === '') {
            $this->error('Keine CSV konfiguriert. Setze SUMUP_CATALOG_CSV_PATH in .env oder nutze --file=/pfad/export.csv');
            $this->line('SumUp Back Office → Produkte → Export CSV, dann:');
            $this->line('  php artisan shop:sumup-import-catalog /pfad/zum/export.csv --create');

            return self::FAILURE;
        }

        if (! is_file($path)) {
            $this->error("CSV-Datei nicht gefunden: {$path}");

            return self::FAILURE;
        }

        $create = (bool) $this->option('create');
        $updateStock = ! (bool) $this->option('no-update-stock');

        $this->info("Synchronisiere aus: {$path}");

        try {
            $stats = $importer->import($path, $create, $updateStock, false);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrik', 'Anzahl'],
            [
                ['Zeilen gelesen', (string) $stats['rows']],
                ['Neu angelegt', (string) $stats['created']],
                ['Aktualisiert', (string) $stats['updated']],
                ['Übersprungen', (string) $stats['skipped']],
                ['Fehler', (string) count($stats['errors'])],
            ],
        );

        foreach ($stats['errors'] as $error) {
            $this->warn($error);
        }

        if ($stats['errors'] !== []) {
            return self::FAILURE;
        }

        $this->info('SumUp-Katalog-Sync abgeschlossen.');
        $this->comment('Hinweis: SumUp bietet derzeit keine öffentliche Katalog-API — regelmäßig CSV exportieren oder Cron mit shop:sumup-sync-catalog einrichten.');

        return self::SUCCESS;
    }
}
