<?php

namespace App\Services\SumUp;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\SumUpSale;
use App\Services\Payments\PaymentProviderService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Laden und Online-Shop teilen sich EINEN Bestand.
 *
 * Liest die Verkäufe der SumUp-Kasse (Sales API) und zieht jede verkaufte Position
 * vom Shop-Bestand ab. Zuordnung über SumUp-Artikel-ID, dann Artikelnummer (SKU), dann Name.
 * Artikel, die der Shop noch nicht kennt, werden als Entwurf (inaktiv) angelegt.
 *
 * Jeder Verkauf wird genau einmal verbucht (Tabelle sumup_sales).
 */
class SumUpSalesSync
{
    private const CURSOR_KEY = 'sumup_sales_synced_until';

    private const MAX_PAGES = 40;

    public function __construct(private readonly PaymentProviderService $providers) {}

    public function isEnabled(): bool
    {
        return (bool) config('services.sumup.sales_sync_enabled', true) && $this->providers->isSumUpConfigured();
    }

    public function lastSyncedAt(): ?Carbon
    {
        $value = Setting::get(self::CURSOR_KEY);

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }

    /**
     * @return array{sales: int, matched: int, unmatched: int, drafts: int, started: bool}
     */
    public function run(): array
    {
        $stats = ['sales' => 0, 'matched' => 0, 'unmatched' => 0, 'drafts' => 0, 'started' => false];

        $credentials = $this->providers->resolveSumUpCredentials();
        if ($credentials['token'] === '' || $credentials['merchant_code'] === '') {
            throw new RuntimeException('SumUp ist nicht konfiguriert (SUMUP_TOKEN / SUMUP_MERCHANT_CODE fehlen).');
        }

        $runStartedAt = now();
        $cursor = $this->lastSyncedAt();

        // Erster Lauf: nur den Startpunkt merken. Alte Verkäufe stecken bereits im aktuellen Bestand
        // und dürfen nicht nachträglich abgezogen werden.
        if ($cursor === null) {
            Setting::set(self::CURSOR_KEY, $runStartedAt->toIso8601String(), 'text', 'sumup');
            $stats['started'] = true;

            return $stats;
        }

        $base = 'https://api.sumup.com/v1.0/merchants/'.rawurlencode($credentials['merchant_code']).'/sales';
        $channels = (array) config('services.sumup.sales_channels', ['POS']);

        // Überlappung fängt verspätet gemeldete Verkäufe ab; sumup_sales verhindert Doppelbuchungen.
        $body = [
            'start_date' => $cursor->copy()->subHours(6)->utc()->format('Y-m-d\TH:i:s\Z'),
            'sales_channels' => $channels,
            'pagination_limit' => 25,
        ];

        $saleIds = [];
        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $this->client($credentials['token'])->post($base.'/search', $body);

            if (! $response->successful()) {
                throw new RuntimeException('SumUp-Verkäufe konnten nicht geladen werden (HTTP '.$response->status().'). Hat der API-Schlüssel die Berechtigung „sales.read“?');
            }

            foreach ((array) $response->json('items', []) as $row) {
                $id = (string) ($row['id'] ?? '');
                if ($id !== '') {
                    $saleIds[$id] = true;
                }
            }

            $token = (string) ($response->json('pagination_token') ?? '');
            if ($token === '') {
                break;
            }
            $body['pagination_token'] = $token;
        }

        $known = SumUpSale::query()->whereIn('sale_id', array_keys($saleIds))->pluck('sale_id')->all();
        $newIds = array_diff(array_keys($saleIds), $known);

        foreach ($newIds as $saleId) {
            $response = $this->client($credentials['token'])->get($base.'/'.rawurlencode($saleId));
            if (! $response->successful()) {
                // Beim nächsten Lauf erneut versuchen — Cursor bleibt dank Überlappung sicher.
                Log::channel('checkout_stack')->warning('sumup.sales.fetch_failed', ['sale_id' => $saleId, 'status' => $response->status()]);

                continue;
            }

            $result = $this->applySale((array) $response->json());
            if ($result === null) {
                continue;
            }

            $stats['sales']++;
            $stats['matched'] += $result['matched'];
            $stats['unmatched'] += $result['unmatched'];
            $stats['drafts'] += $result['drafts'];
        }

        Setting::set(self::CURSOR_KEY, $runStartedAt->toIso8601String(), 'text', 'sumup');

        return $stats;
    }

    /**
     * Verbucht einen Kassenverkauf im Shop-Bestand. Gibt null zurück, wenn er schon verbucht war.
     *
     * @param  array<string, mixed>  $sale
     * @return array{matched: int, unmatched: int, drafts: int}|null
     */
    public function applySale(array $sale): ?array
    {
        $saleId = (string) ($sale['id'] ?? '');
        if ($saleId === '') {
            return null;
        }

        return DB::transaction(function () use ($sale, $saleId): ?array {
            if (SumUpSale::query()->where('sale_id', $saleId)->lockForUpdate()->exists()) {
                return null;
            }

            $matched = 0;
            $unmatched = 0;
            $drafts = 0;
            $log = [];

            foreach ((array) ($sale['items'] ?? []) as $line) {
                if (! is_array($line) || strtoupper((string) ($line['type'] ?? '')) !== 'CATALOG') {
                    continue; // Gebühren, Pfand, Gutscheine, freie Beträge: kein Lagerartikel.
                }

                $quantity = (int) round((float) ($line['quantity'] ?? 0));
                if ($quantity < 1) {
                    continue;
                }

                // Rückgabe an der Kasse (negativer Betrag) → Ware kommt zurück in den Bestand.
                $isReturn = (int) data_get($line, 'price.total_gross_amount_final', 0) < 0;
                $delta = $isReturn ? $quantity : -$quantity;

                $ref = $this->parseReferences($line);
                $target = $this->findTarget($ref, (string) ($line['name'] ?? ''));

                $createdDraft = false;
                if ($target === null) {
                    $target = $this->createDraft($line, $ref, $sale);
                    $createdDraft = true;
                    $drafts++;
                    $unmatched++;
                } else {
                    $matched++;
                }

                if (! $createdDraft) {
                    $locked = $target::query()->whereKey($target->getKey())->lockForUpdate()->first();
                    if ($locked) {
                        $locked->forceFill(['stock' => max(0, (int) $locked->stock + $delta)])->save();
                    }
                }

                $log[] = [
                    'name' => Str::limit((string) ($line['name'] ?? ''), 120, ''),
                    'sku' => $ref['sku'],
                    'quantity' => $delta,
                    'target' => class_basename($target).'#'.$target->getKey(),
                    'draft' => $createdDraft,
                ];
            }

            SumUpSale::query()->create([
                'sale_id' => $saleId,
                'channel' => Str::limit((string) data_get($sale, 'source.channel', ''), 40, ''),
                'total_cents' => (int) data_get($sale, 'price.gross_amount', 0),
                'closed_at' => filled($sale['closed_at'] ?? null) ? Carbon::parse((string) $sale['closed_at']) : null,
                'lines_matched' => $matched,
                'lines_unmatched' => $unmatched,
                'lines' => $log,
            ]);

            return ['matched' => $matched, 'unmatched' => $unmatched, 'drafts' => $drafts];
        });
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array{sku: string, item_id: string, variant_id: string}
     */
    private function parseReferences(array $line): array
    {
        $sku = trim((string) data_get($line, 'references.sku', ''));
        $urn = trim((string) data_get($line, 'references.catalog_reference', ''));

        $itemId = '';
        $variantId = '';
        // urn:sumup:pos:variant:{MERCHANT}:{ITEM_ID}:{VARIANT_ID}
        if (preg_match('/^urn:sumup:pos:variant:[^:]+:([^:]+)(?::([^:]+))?$/i', $urn, $m)) {
            $itemId = $m[1];
            $variantId = $m[2] ?? '';
        }

        return ['sku' => $sku, 'item_id' => $itemId, 'variant_id' => $variantId];
    }

    /**
     * @param  array{sku: string, item_id: string, variant_id: string}  $ref
     */
    private function findTarget(array $ref, string $name): Product|ProductVariant|null
    {
        foreach (array_filter([$ref['variant_id'], $ref['item_id']]) as $sumupId) {
            if ($variant = ProductVariant::query()->where('sumup_item_id', $sumupId)->first()) {
                return $variant;
            }
        }

        if ($ref['sku'] !== '') {
            if ($variant = ProductVariant::query()->where('sku', $ref['sku'])->first()) {
                return $variant;
            }
            if ($product = Product::query()->where('sku', $ref['sku'])->first()) {
                return $this->remember($product, $ref);
            }
        }

        foreach (array_filter([$ref['item_id'], $ref['variant_id']]) as $sumupId) {
            if ($product = Product::query()->where('sumup_item_id', $sumupId)->first()) {
                return $product;
            }
        }

        // Letzter Versuch: exakt gleicher Name — nur wenn eindeutig und der Artikel keine Varianten hat.
        $name = trim($name);
        if ($name !== '') {
            $byName = Product::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->where('has_variants', false)->limit(2)->get();
            if ($byName->count() === 1) {
                return $this->remember($byName->first(), $ref);
            }
        }

        return null;
    }

    /**
     * SumUp-ID am Produkt merken → nächstes Mal sicherer Treffer, auch wenn Name/SKU sich ändern.
     *
     * @param  array{sku: string, item_id: string, variant_id: string}  $ref
     */
    private function remember(Product $product, array $ref): Product
    {
        if (blank($product->sumup_item_id) && $ref['item_id'] !== '') {
            $product->forceFill(['sumup_item_id' => $ref['item_id'], 'sumup_synced_at' => now()])->saveQuietly();
        }

        return $product;
    }

    /**
     * In SumUp angelegt, im Shop noch unbekannt → als Entwurf übernehmen (inaktiv, Bestand 0).
     * Im Admin nur noch Bild/Bestand ergänzen und aktivieren.
     *
     * @param  array<string, mixed>  $line
     * @param  array{sku: string, item_id: string, variant_id: string}  $ref
     * @param  array<string, mixed>  $sale
     */
    private function createDraft(array $line, array $ref, array $sale): Product
    {
        $name = trim((string) ($line['name'] ?? '')) ?: 'SumUp-Artikel';
        $scale = (int) data_get($sale, 'currency.scale', 2);
        $price = round(((int) data_get($line, 'price.unit_amount', 0)) / (10 ** $scale), 2);

        $sku = $ref['sku'] !== '' && ! Product::query()->where('sku', $ref['sku'])->exists() ? $ref['sku'] : null;

        $slugBase = Str::slug($name) ?: 'sumup-artikel';
        $slug = $slugBase;
        for ($i = 2; Product::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $slugBase.'-'.$i;
        }

        $product = new Product;
        $product->forceFill([
            'name' => Str::limit($name, 250, ''),
            'slug' => $slug,
            'short_description' => 'Automatisch aus SumUp übernommen — bitte prüfen, Bestand und Bild ergänzen und aktivieren.',
            'price' => $price,
            'sku' => $sku,
            'stock' => 0,
            'is_active' => false,
            'is_featured' => false,
            'has_variants' => false,
            'sumup_item_id' => $ref['item_id'] !== '' ? $ref['item_id'] : null,
            'sumup_synced_at' => now(),
        ])->save();

        return $product;
    }

    private function client(string $token): PendingRequest
    {
        // SumUp verlangt bei der Sales API einen User-Agent (sonst HTTP 412).
        return Http::withToken($token)
            ->acceptJson()
            ->withUserAgent(config('app.name', 'Shop').' shop-sync/1.0')
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 500, throw: false);
    }
}
