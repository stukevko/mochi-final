<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShopFlushApplicationCacheCommand extends Command
{
    protected $signature = 'shop:flush-app-cache';

    protected $description = 'Leert den Laravel-Applikationscache (Treiber: database, redis, …) — z. B. nach Cache-Key-Änderungen oder Altlasten';

    public function handle(): int
    {
        $this->warn('Leert alle Einträge des konfigurierten Cache-Stores (nicht config/route/view).');
        $this->call('cache:clear');
        $this->newLine();
        $this->info('Fertig. Settings-, Hero- und Nav-Einträge werden beim nächsten Request neu aufgebaut (rememberForever).');

        return self::SUCCESS;
    }
}
