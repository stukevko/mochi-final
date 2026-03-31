<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class ShopGenerateMerchantHandbookCommand extends Command
{
    protected $signature = 'shop:merchant-handbook {--output=docs/Shop-Haendlerhandbuch.pdf : Relative path from project root}';

    protected $description = 'Erzeugt das ausführliche Händler-Handbuch als PDF (Dummy-sicher)';

    public function handle(): int
    {
        $relativeOutput = (string) $this->option('output');
        $outputPath = base_path($relativeOutput);
        $outputDirectory = dirname($outputPath);

        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        $pdf = Pdf::loadView('pdf.merchant-handbook', [
            'generatedAt' => now()->format('d.m.Y H:i'),
            'appName' => (string) config('app.name', 'Shop'),
        ])->setPaper('a4', 'portrait');

        file_put_contents($outputPath, $pdf->output());

        $this->info('Händler-Handbuch als PDF erstellt: '.$relativeOutput);
        $this->line('Ausführliche Markdown-Fassung: docs/HAENDLER-HANDBUCH.md');

        return self::SUCCESS;
    }
}
