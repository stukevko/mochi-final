<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class ShopGenerateClientManualCommand extends Command
{
    protected $signature = 'shop:generate-client-manual {--output=KevkoShop-Kundenhandbuch.pdf : Relative output path}';

    protected $description = 'Erstellt ein druckbares PDF-Handbuch fuer Shop-Betreiber';

    public function handle(): int
    {
        $relativeOutput = (string) $this->option('output');
        $outputPath = base_path($relativeOutput);
        $outputDirectory = dirname($outputPath);

        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }

        $pdf = Pdf::loadView('pdf.client-manual', [
            'generatedAt' => now()->format('d.m.Y H:i'),
        ])->setPaper('a4', 'portrait');

        file_put_contents($outputPath, $pdf->output());

        $this->info('PDF-Handbuch erstellt: '.$relativeOutput);

        return self::SUCCESS;
    }
}
