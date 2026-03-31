<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShopCheckStorageCommand extends Command
{
    protected $signature = 'shop:check-storage';

    protected $description = 'Prüft Schreibrechte für storage/, bootstrap/cache und den public/storage-Link (Uploads)';

    public function handle(): int
    {
        $this->info('Speicherpfade und Rechte …');
        $this->newLine();

        $paths = [
            'storage/app' => storage_path('app'),
            'storage/app/public' => storage_path('app/public'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/cache/data' => storage_path('framework/cache/data'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $ok = true;

        foreach ($paths as $label => $path) {
            if (! is_dir($path)) {
                $ok = false;
                $this->error("Fehlt (Verzeichnis): {$label} → {$path}");

                continue;
            }

            if (! is_writable($path)) {
                $ok = false;
                $this->error("Nicht beschreibbar: {$label} → {$path}");

                continue;
            }

            $this->line("OK: {$label}");
        }

        $this->newLine();
        $linkTarget = public_path('storage');

        if (is_link($linkTarget)) {
            $this->line('OK: public/storage ist ein Symlink');
        } elseif (is_dir($linkTarget)) {
            $this->warn('public/storage ist ein Ordner (kein Symlinks) — ok, falls beabsichtigt');
        } else {
            $this->warn('public/storage fehlt — für Datei-Uploads: php artisan storage:link');
        }

        $this->newLine();

        if ($ok) {
            $this->info('Alle geprüften Pfade sind vorhanden und beschreibbar.');

            return self::SUCCESS;
        }

        $this->error('Mindestens ein Pfad fehlt oder ist nicht beschreibbar (Webserver-User / chmod).');

        return self::FAILURE;
    }
}
