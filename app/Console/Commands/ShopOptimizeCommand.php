<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShopOptimizeCommand extends Command
{
    protected $signature = 'shop:optimize';

    protected $description = 'Caches config, routes, views, Blade icons and Filament components (typical production deploy)';

    public function handle(): int
    {
        $this->info('Caching configuration…');
        $this->call('config:cache');

        $this->info('Caching routes…');
        $this->call('route:cache');

        $this->info('Caching Blade views…');
        $this->call('view:cache');

        $this->info('Caching Blade icon sets…');
        $this->call('icons:cache');

        $this->info('Caching Filament components & icons…');
        $this->call('filament:optimize');

        $this->newLine();
        $this->comment('Hinweis: In lokaler Entwicklung bei Konfigurationsänderungen `php artisan optimize:clear` ausführen.');
        $this->comment('App-Cache (Settings/Hero/Nav) bei Altlasten: `php artisan shop:flush-app-cache` — siehe docs/OPCACHE-ANLEITUNG.md');

        return self::SUCCESS;
    }
}
