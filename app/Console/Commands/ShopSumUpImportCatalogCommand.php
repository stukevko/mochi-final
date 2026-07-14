<?php

namespace App\Console\Commands;

use App\Services\SumUp\SumUpCatalogCsvImporter;
use Illuminate\Console\Command;
use Throwable;

class ShopSumUpImportCatalogCommand extends Command
{
    protected $signature = 'shop:sumup-import-catalog
                            {file : Pfad zur SumUp CSV-Exportdatei}
                            {--create : Fehlende Produkte anlegen}
                            {--no-update-stock : Bestand aus CSV nicht überschreiben}
                            {--dry-run : Nur simulieren, nichts speichern}';

    protected $description = 'Importiert Produkte aus einem SumUp CSV-Export (Semikolon) per SKU/ProductId';

    public function handle(SumUpCatalogCsvImporter $importer): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error("Datei nicht gefunden: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $create = (bool) $this->option('create');
        $updateStock = ! (bool) $this->option('no-update-stock');

        if ($dryRun) {
            $this->warn('Dry-Run: Es werden keine Änderungen gespeichert.');
        }

        try {
            $stats = $importer->import($path, $create, $updateStock, $dryRun);
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
            $this->newLine();
            $this->error('Import mit Fehlern beendet.');

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry-Run erfolgreich.' : 'SumUp-Katalogimport abgeschlossen.');

        return self::SUCCESS;
    }
}
