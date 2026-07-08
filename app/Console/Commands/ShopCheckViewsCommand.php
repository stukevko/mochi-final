<?php

namespace App\Console\Commands;

use App\Support\ShopViewIntegrity;
use Illuminate\Console\Command;

class ShopCheckViewsCommand extends Command
{
    protected $signature = 'shop:check-views';

    protected $description = 'Prüft kritische Blade-Views, PDFs und E-Mail-Templates auf Vollständigkeit und Renderbarkeit';

    public function handle(): int
    {
        $allOk = true;

        $this->info('Blade-Views (Existenz)');
        $viewRows = ShopViewIntegrity::checkBladeViews();
        $this->table(
            ['View', 'Status', 'Details'],
            array_map(
                fn (array $row): array => [
                    $row['view'],
                    $row['ok'] ? 'PASS' : 'FAIL',
                    $row['detail'],
                ],
                $viewRows
            )
        );
        $allOk = $allOk && collect($viewRows)->every(fn (array $row): bool => $row['ok']);

        $this->newLine();
        $this->info('Render-Check (PDF, Mails, Sitemap)');
        $surfaceRows = ShopViewIntegrity::checkRenderableSurfaces();
        $this->table(
            ['Oberfläche', 'Status', 'Details'],
            array_map(
                fn (array $row): array => [
                    $row['surface'],
                    $row['ok'] ? 'PASS' : 'FAIL',
                    $row['detail'],
                ],
                $surfaceRows
            )
        );
        $allOk = $allOk && collect($surfaceRows)->every(fn (array $row): bool => $row['ok']);

        $this->newLine();

        if ($allOk) {
            $this->info('Alle kritischen Views und Oberflächen sind in Ordnung.');

            return self::SUCCESS;
        }

        $this->error('Integritäts-Check fehlgeschlagen — fehlende oder nicht renderbare Views beheben.');

        return self::FAILURE;
    }
}
