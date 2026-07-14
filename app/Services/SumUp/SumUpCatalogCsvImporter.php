<?php

namespace App\Services\SumUp;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class SumUpCatalogCsvImporter
{
    /**
     * @return array{
     *     rows: int,
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     errors: list<string>
     * }
     */
    public function import(string $path, bool $createMissing = false, bool $updateStock = true, bool $dryRun = false): array
    {
        if (! is_readable($path)) {
            throw new RuntimeException("CSV-Datei nicht lesbar: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("CSV-Datei konnte nicht geöffnet werden: {$path}");
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            throw new RuntimeException('CSV-Datei ist leer.');
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $headerRow = fgetcsv($handle, 0, $delimiter);
        if (! is_array($headerRow) || $headerRow === []) {
            fclose($handle);

            throw new RuntimeException('CSV-Header fehlt.');
        }

        $headerMap = $this->mapHeaders($headerRow);
        if (! isset($headerMap['name']) && ! isset($headerMap['sku'])) {
            fclose($handle);

            throw new RuntimeException('CSV enthält weder Name- noch SKU-Spalte (erwartet SumUp-Export mit Semikolon).');
        }

        $stats = [
            'rows' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $syncedAt = Carbon::now();

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $stats['rows']++;
            $lineNumber = $stats['rows'] + 1;
            $data = $this->extractRow($headerMap, $row);

            if ($this->shouldSkipRow($data)) {
                $stats['skipped']++;

                continue;
            }

            if ($data['name'] === '' && $data['sku'] === '') {
                $stats['skipped']++;

                continue;
            }

            try {
                $result = $this->upsertProduct($data, $createMissing, $updateStock, $dryRun, $syncedAt);
                $stats[$result]++;
            } catch (\Throwable $e) {
                $stats['errors'][] = "Zeile {$lineNumber}: {$e->getMessage()}";
            }
        }

        fclose($handle);

        return $stats;
    }

    /**
     * @param  list<string|null>  $headerRow
     * @return array<string, int>
     */
    private function mapHeaders(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $label) {
            $key = $this->normalizeHeader((string) $label);
            if ($key === '') {
                continue;
            }

            $canonical = match ($key) {
                'productid', 'product_id', 'itemid', 'item_id', 'id' => 'sumup_item_id',
                'name', 'productname', 'product', 'product_option', 'productoption', 'title' => 'name',
                'sku', 'articlenumber', 'artikelnummer', 'article_number' => 'sku',
                'price', 'unitprice', 'unit_price', 'priceincltax', 'priceinclvat', 'price_incl_tax', 'salesprice', 'saleprice' => 'price',
                'sale_price', 'discountprice', 'offerprice' => 'sale_price',
                'stock', 'quantity', 'stockquantity', 'stock_quantity', 'inventory', 'qty' => 'stock',
                'description', 'desc', 'longdescription' => 'description',
                'category', 'categoryname', 'category_name' => 'category',
                'type', 'rowtype', 'itemtype' => 'type',
                default => $key,
            };

            if (! isset($map[$canonical])) {
                $map[$canonical] = (int) $index;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $headerMap
     * @param  list<string|null>  $row
     * @return array{
     *     sumup_item_id: string,
     *     name: string,
     *     sku: string,
     *     price: float|null,
     *     sale_price: float|null,
     *     stock: int|null,
     *     description: string,
     *     category: string,
     *     type: string
     * }
     */
    private function extractRow(array $headerMap, array $row): array
    {
        return [
            'sumup_item_id' => $this->cell($headerMap, $row, 'sumup_item_id'),
            'name' => $this->cell($headerMap, $row, 'name'),
            'sku' => $this->cell($headerMap, $row, 'sku'),
            'price' => $this->parsePrice($this->cell($headerMap, $row, 'price')),
            'sale_price' => $this->parsePrice($this->cell($headerMap, $row, 'sale_price')),
            'stock' => $this->parseStock($this->cell($headerMap, $row, 'stock')),
            'description' => $this->cell($headerMap, $row, 'description'),
            'category' => $this->cell($headerMap, $row, 'category'),
            'type' => strtolower($this->cell($headerMap, $row, 'type')),
        ];
    }

    /**
     * @param  array<string, int>  $headerMap
     * @param  list<string|null>  $row
     */
    private function cell(array $headerMap, array $row, string $field): string
    {
        if (! isset($headerMap[$field])) {
            return '';
        }

        $value = $row[$headerMap[$field]] ?? '';

        return trim((string) $value);
    }

    /**
     * @param  array{
     *     sumup_item_id: string,
     *     name: string,
     *     sku: string,
     *     price: float|null,
     *     sale_price: float|null,
     *     stock: int|null,
     *     description: string,
     *     category: string,
     *     type: string
     * }  $data
     */
    private function shouldSkipRow(array $data): bool
    {
        if ($data['type'] === '') {
            return false;
        }

        $skipTypes = ['category', 'menu', 'meal', 'modifier group', 'modifiergroup'];

        return in_array($data['type'], $skipTypes, true);
    }

    /**
     * @param  array{
     *     sumup_item_id: string,
     *     name: string,
     *     sku: string,
     *     price: float|null,
     *     sale_price: float|null,
     *     stock: int|null,
     *     description: string,
     *     category: string,
     *     type: string
     * }  $data
     */
    private function upsertProduct(array $data, bool $createMissing, bool $updateStock, bool $dryRun, Carbon $syncedAt): string
    {
        $product = $this->findExistingProduct($data);

        if (! $product && ! $createMissing) {
            return 'skipped';
        }

        if (! $product && $createMissing) {
            if ($dryRun) {
                return 'created';
            }

            $product = new Product;
            $product->forceFill([
                'name' => $data['name'] !== '' ? $data['name'] : ('SumUp '.$data['sku']),
                'slug' => $this->uniqueSlug($data['name'] !== '' ? $data['name'] : $data['sku']),
                'description' => $data['description'] !== '' ? $data['description'] : null,
                'short_description' => null,
                'price' => $data['price'] ?? 0,
                'sale_price' => $data['sale_price'],
                'sku' => $data['sku'] !== '' ? $data['sku'] : null,
                'stock' => $updateStock ? ($data['stock'] ?? 0) : 0,
                'category_id' => $this->resolveCategoryId($data['category']),
                'is_active' => true,
                'is_featured' => false,
                'has_variants' => false,
                'sumup_item_id' => $data['sumup_item_id'] !== '' ? $data['sumup_item_id'] : null,
                'sumup_synced_at' => $syncedAt,
            ])->save();

            return 'created';
        }

        if (! $product) {
            return 'skipped';
        }

        if ($dryRun) {
            return 'updated';
        }

        $updates = [
            'sumup_synced_at' => $syncedAt,
        ];

        if ($data['sumup_item_id'] !== '') {
            $updates['sumup_item_id'] = $data['sumup_item_id'];
        }

        if ($data['sku'] !== '' && $product->sku === null) {
            $updates['sku'] = $data['sku'];
        }

        if ($data['price'] !== null) {
            $updates['price'] = $data['price'];
        }

        if ($data['sale_price'] !== null) {
            $updates['sale_price'] = $data['sale_price'];
        }

        if ($updateStock && $data['stock'] !== null && ! $product->has_variants) {
            $updates['stock'] = $data['stock'];
        }

        if ($data['description'] !== '' && blank($product->description)) {
            $updates['description'] = $data['description'];
        }

        if ($data['category'] !== '' && $product->category_id === null) {
            $categoryId = $this->resolveCategoryId($data['category']);
            if ($categoryId !== null) {
                $updates['category_id'] = $categoryId;
            }
        }

        $product->forceFill($updates)->save();

        return 'updated';
    }

    /**
     * @param  array{sumup_item_id: string, sku: string}  $data
     */
    private function findExistingProduct(array $data): ?Product
    {
        if ($data['sku'] !== '') {
            $bySku = Product::query()->where('sku', $data['sku'])->first();
            if ($bySku) {
                return $bySku;
            }

            $variant = ProductVariant::query()->where('sku', $data['sku'])->with('product')->first();
            if ($variant?->product) {
                return $variant->product;
            }
        }

        if ($data['sumup_item_id'] !== '') {
            $bySumUp = Product::query()->where('sumup_item_id', $data['sumup_item_id'])->first();
            if ($bySumUp) {
                return $bySumUp;
            }

            $variant = ProductVariant::query()
                ->where('sumup_item_id', $data['sumup_item_id'])
                ->with('product')
                ->first();

            if ($variant?->product) {
                return $variant->product;
            }
        }

        return null;
    }

    private function resolveCategoryId(string $categoryName): ?int
    {
        if ($categoryName === '') {
            return null;
        }

        $slug = Str::slug($categoryName);
        $category = Category::query()
            ->where('slug', $slug)
            ->orWhere('name', $categoryName)
            ->first();

        return $category?->id;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'sumup-product';
        }

        $slug = $base;
        $suffix = 1;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function detectDelimiter(string $line): string
    {
        $semicolons = substr_count($line, ';');
        $commas = substr_count($line, ',');

        return $semicolons >= $commas ? ';' : ',';
    }

    private function normalizeHeader(string $label): string
    {
        $label = trim($label);
        $label = preg_replace('/^\xEF\xBB\xBF/', '', $label) ?? $label;
        $label = strtolower($label);
        $label = preg_replace('/[^a-z0-9]+/', '_', $label) ?? $label;

        return trim($label, '_');
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parsePrice(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(['€', ' '], '', $value);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
        }
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function parseStock(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);
        if (! is_numeric($normalized)) {
            return null;
        }

        return max(0, (int) round((float) $normalized));
    }
}
