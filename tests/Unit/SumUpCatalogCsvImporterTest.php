<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\SumUp\SumUpCatalogCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SumUpCatalogCsvImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_and_updates_products_by_sku(): void
    {
        Product::query()->create([
            'name' => 'Bestehendes Produkt',
            'slug' => 'bestehendes-produkt',
            'price' => 5.00,
            'sku' => 'MC-001',
            'stock' => 2,
            'is_active' => true,
        ]);

        $csvPath = $this->writeTempCsv(<<<'CSV'
ProductId;Name;SKU;Price;Stock
sumup-100;Neues Produkt;MC-002;12,50;7
sumup-001;Bestehendes Produkt;MC-001;9,99;15
CSV);

        $stats = app(SumUpCatalogCsvImporter::class)->import($csvPath, createMissing: true, updateStock: true, dryRun: false);

        $this->assertSame(2, $stats['rows']);
        $this->assertSame(1, $stats['created']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame([], $stats['errors']);

        $existing = Product::query()->where('sku', 'MC-001')->firstOrFail();
        $this->assertSame('sumup-001', $existing->sumup_item_id);
        $this->assertSame('9.99', $existing->price);
        $this->assertSame(15, $existing->stock);

        $created = Product::query()->where('sku', 'MC-002')->firstOrFail();
        $this->assertSame('Neues Produkt', $created->name);
        $this->assertSame('sumup-100', $created->sumup_item_id);
        $this->assertSame(7, $created->stock);
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        $csvPath = $this->writeTempCsv(<<<'CSV'
Name;SKU;Price;Stock
Dry Run Produkt;MC-DRY;4,00;3
CSV);

        $stats = app(SumUpCatalogCsvImporter::class)->import($csvPath, createMissing: true, updateStock: true, dryRun: true);

        $this->assertSame(1, $stats['created']);
        $this->assertDatabaseMissing('products', ['sku' => 'MC-DRY']);
    }

    private function writeTempCsv(string $contents): string
    {
        $path = sys_get_temp_dir().'/sumup-catalog-'.uniqid('', true).'.csv';
        file_put_contents($path, $contents);

        return $path;
    }
}
