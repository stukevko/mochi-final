<?php

namespace App\Console\Commands;

use App\Services\SumUp\SumUpSalesSync;
use Illuminate\Console\Command;
use Throwable;

class ShopSumUpSyncSalesCommand extends Command
{
    protected $signature = 'shop:sumup-sync-sales';

    protected $description = 'Zieht Ladenverkäufe aus der SumUp-Kasse vom Shop-Bestand ab (ein Bestand für Laden und Shop).';

    public function handle(SumUpSalesSync $sync): int
    {
        if (! $sync->isEnabled()) {
            $this->comment('SumUp-Abgleich ist aus (SUMUP_SALES_SYNC=false oder SumUp nicht konfiguriert).');

            return self::SUCCESS;
        }

        try {
            $stats = $sync->run();
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($stats['started']) {
            $this->info('Abgleich gestartet. Ab jetzt werden Ladenverkäufe vom Shop-Bestand abgezogen.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Neue Ladenverkäufe: %d · Positionen verbucht: %d · neue Entwürfe: %d',
            $stats['sales'], $stats['matched'], $stats['drafts'],
        ));

        return self::SUCCESS;
    }
}
