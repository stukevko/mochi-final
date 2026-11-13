<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\SumUpSale;
use App\Models\User;
use App\Services\SumUp\SumUpSalesSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Laden (SumUp-Kasse) und Shop teilen sich einen Bestand.
 */
class SumUpSalesSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sumup.token' => 'test-sumup-token',
            'services.sumup.merchant_code' => 'MC123456',
        ]);
        Setting::flushMemo();
    }

    public function test_first_run_only_sets_the_starting_point(): void
    {
        Http::fake();
        $product = $this->product('Booster', 'MC-001', 10);

        $stats = app(SumUpSalesSync::class)->run();

        $this->assertTrue($stats['started']);
        $this->assertSame(10, (int) $product->fresh()->stock);
        Http::assertNothingSent();
    }

    public function test_store_sales_reduce_shop_stock_exactly_once(): void
    {
        $bySku = $this->product('Booster Display', 'MC-001', 10);
        $byName = $this->product('Kartenhüllen Schwarz', null, 5);
        Setting::set('sumup_sales_synced_until', now()->subHour()->toIso8601String(), 'text', 'sumup');

        $this->fakeSumUp([
            'sale-1' => $this->sale('sale-1', [
                $this->line('Booster Display (Kasse)', 2, 'MC-001', 'item-a', 'var-a'),
                $this->line('kartenhüllen schwarz', 1),
                ['id' => 'fee', 'type' => 'FEE', 'name' => 'Versand', 'quantity' => 1, 'price' => ['unit_amount' => 490, 'total_gross_amount_final' => 490]],
            ]),
        ]);

        $sync = app(SumUpSalesSync::class);
        $first = $sync->run();
        $second = $sync->run();

        $this->assertSame(1, $first['sales']);
        $this->assertSame(2, $first['matched']);
        $this->assertSame(0, $second['sales']);

        $this->assertSame(8, (int) $bySku->fresh()->stock);
        $this->assertSame(4, (int) $byName->fresh()->stock);
        // SumUp-ID wird gemerkt → künftig sicherer Treffer.
        $this->assertSame('item-a', $bySku->fresh()->sumup_item_id);
        $this->assertSame(1, SumUpSale::query()->count());

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sales/search')
            && $request->hasHeader('User-Agent')
            && ! in_array('ONLINE_PAYMENTS', $request['sales_channels'], true));
    }

    public function test_unknown_store_item_becomes_inactive_draft(): void
    {
        Setting::set('sumup_sales_synced_until', now()->subHour()->toIso8601String(), 'text', 'sumup');

        $this->fakeSumUp([
            'sale-2' => $this->sale('sale-2', [$this->line('Neues Spiel', 1, 'NEU-9', 'item-new', 'var-new', 2499)]),
        ]);

        $stats = app(SumUpSalesSync::class)->run();

        $draft = Product::query()->where('sku', 'NEU-9')->firstOrFail();
        $this->assertSame(1, $stats['drafts']);
        $this->assertFalse((bool) $draft->is_active);
        $this->assertSame('24.99', $draft->price);
        $this->assertSame('item-new', $draft->sumup_item_id);
        $this->assertSame(0, (int) $draft->stock);
    }

    public function test_store_return_puts_item_back_and_stock_never_goes_negative(): void
    {
        $product = $this->product('Booster', 'MC-001', 1);
        Setting::set('sumup_sales_synced_until', now()->subHour()->toIso8601String(), 'text', 'sumup');

        $return = $this->line('Booster', 1, 'MC-001');
        $return['price']['total_gross_amount_final'] = -500;

        $this->fakeSumUp([
            'sale-3' => $this->sale('sale-3', [$this->line('Booster', 3, 'MC-001')]),
            'sale-4' => $this->sale('sale-4', [$return]),
        ]);

        app(SumUpSalesSync::class)->run();

        // 1 − 3 → 0 (nie negativ), Rückgabe +1.
        $this->assertSame(1, (int) $product->fresh()->stock);
    }

    public function test_admin_page_and_command_work(): void
    {
        Http::fake();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/sumup-abgleich')
            ->assertOk()
            ->assertSee('Ein Bestand für Laden und Shop')
            ->assertSee('Artikel aus SumUp übernehmen');

        $this->artisan('shop:sumup-sync-sales')->assertSuccessful();
        $this->assertNotNull(app(SumUpSalesSync::class)->lastSyncedAt());
    }

    private function product(string $name, ?string $sku, int $stock): Product
    {
        return Product::query()->create([
            'name' => $name,
            'slug' => 'p-'.uniqid(),
            'price' => 5.00,
            'sku' => $sku,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $sales
     */
    private function fakeSumUp(array $sales): void
    {
        Http::fake(function ($request) use ($sales) {
            $url = $request->url();

            if (str_ends_with($url, '/sales/search')) {
                return Http::response(['items' => array_map(fn ($id) => ['id' => $id], array_keys($sales))]);
            }

            foreach ($sales as $id => $sale) {
                if (str_ends_with($url, '/sales/'.$id)) {
                    return Http::response($sale);
                }
            }

            return Http::response([], 404);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function sale(string $id, array $items): array
    {
        return [
            'id' => $id,
            'currency' => ['code' => 'EUR', 'scale' => 2],
            'state' => 'CLOSED',
            'source' => ['channel' => 'POS'],
            'price' => ['gross_amount' => 1000],
            'closed_at' => now()->subMinutes(10)->toIso8601String(),
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function line(string $name, int $quantity, ?string $sku = null, ?string $itemId = null, ?string $variantId = null, int $unit = 500): array
    {
        $references = [];
        if ($sku) {
            $references['sku'] = $sku;
        }
        if ($itemId) {
            $references['catalog_reference'] = 'urn:sumup:pos:variant:MC123456:'.$itemId.($variantId ? ':'.$variantId : '');
        }

        return [
            'id' => 'line-'.uniqid(),
            'type' => 'CATALOG',
            'name' => $name,
            'quantity' => $quantity,
            'price' => ['unit_amount' => $unit, 'total_gross_amount_final' => $unit * $quantity],
            'references' => $references,
        ];
    }
}
