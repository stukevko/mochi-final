<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Hintergrundaufgaben ohne Server-Cronjob (Webspace). Alles läuft im selben PHP-Prozess —
 * es wird nichts über die Kommandozeile gestartet (dort oft verboten).
 */
class WebCronRunner
{
    public function run(bool $force = false): void
    {
        // Höchstens ein Lauf pro Minute, auch bei vielen gleichzeitigen Besuchern.
        if (! Cache::add('webcron:tick', 1, 55) && ! $force) {
            return;
        }

        @set_time_limit(120);

        $this->migrateAfterUpload();

        if (Cache::add('webcron:five-minutes', 1, 290) || $force) {
            $this->call('shop:reconcile-payments');
            $this->call('shop:sumup-sync-sales');
        }
    }

    /**
     * Neue Version per FTP hochgeladen? Dann fehlende Datenbank-Änderungen automatisch nachziehen.
     */
    private function migrateAfterUpload(): void
    {
        $files = glob(database_path('migrations/*.php')) ?: [];
        $fingerprint = md5(implode('|', array_map('basename', $files)));
        $marker = storage_path('app/schema-version');

        if (is_file($marker) && trim((string) @file_get_contents($marker)) === $fingerprint) {
            return;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('view:clear');
            @file_put_contents($marker, $fingerprint);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function call(string $command): void
    {
        try {
            Artisan::call($command);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
