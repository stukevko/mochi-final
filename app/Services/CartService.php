<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductImageUrl;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'cart';

    /**
     * Rohdaten nur: product_id, variant_id?, quantity (keine Preise aus dem Client).
     *
     * @return array<string, array{product_id: int, variant_id: ?int, quantity: int}>
     */
    public function getRawLines(): array
    {
        return $this->migrateAndPersist(Session::get(self::SESSION_KEY, []));
    }

    /**
     * Summe der Mengen aus der Session — ohne Produkt-DB-Auflösung (für Badge / Header).
     */
    public function getTotalLineQuantity(): int
    {
        return (int) collect($this->getRawLines())->sum(
            fn (array $line): int => max(1, (int) ($line['quantity'] ?? 1))
        );
    }

    /**
     * Zeilen mit Preisen/ Namen immer aus der Datenbank aufgelöst.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getContent(): array
    {
        $resolved = [];
        $persist = [];

        foreach ($this->getRawLines() as $line) {
            $row = $this->resolveLine($line);
            if ($row === null) {
                continue;
            }
            $key = $this->makeKey($row);
            $resolved[$key] = $row;
            $persist[$key] = [
                'product_id' => $row['product_id'],
                'variant_id' => $row['variant_id'],
                'quantity' => $row['quantity'],
            ];
        }

        $this->replaceCart($persist);

        return $resolved;
    }

    public function add(int $productId, ?int $variantId = null, int $quantity = 1): void
    {
        $productId = max(1, $productId);
        $quantity = max(1, $quantity);

        $product = Product::query()
            ->where('is_active', true)
            ->find($productId);

        if (! $product) {
            return;
        }

        $variant = null;
        if ($variantId !== null) {
            $variant = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->find($variantId);

            if (! $variant) {
                return;
            }
        }

        if ($product->has_variants && $variant === null) {
            return;
        }

        if ($variant) {
            if ($variant->stock < 1) {
                return;
            }
        } elseif ((int) $product->stock < 1) {
            return;
        }

        $cart = $this->getRawLines();
        $key = $this->makeKey(['product_id' => $productId, 'variant_id' => $variantId]);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
            $resolvedMerge = $this->resolveLine($cart[$key]);
            if ($resolvedMerge === null) {
                unset($cart[$key]);
            } else {
                $cart[$key]['quantity'] = $resolvedMerge['quantity'];
            }
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
            $resolvedNew = $this->resolveLine($cart[$key]);
            if ($resolvedNew === null) {
                unset($cart[$key]);
            } else {
                $cart[$key]['quantity'] = $resolvedNew['quantity'];
            }
        }

        $this->replaceCart($cart);
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        $cart = $this->getRawLines();
        $key = $this->makeKey([
            'product_id' => $productId,
            'variant_id' => $variantId,
        ]);
        unset($cart[$key]);
        $this->replaceCart($cart);
    }

    public function updateQuantity(int $productId, ?int $variantId, int $quantity): void
    {
        $cart = $this->getRawLines();
        $key = $this->makeKey([
            'product_id' => $productId,
            'variant_id' => $variantId,
        ]);
        if (! isset($cart[$key])) {
            return;
        }

        $candidate = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => max(1, $quantity),
        ];
        $resolved = $this->resolveLine($candidate);
        if ($resolved === null) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity'] = $resolved['quantity'];
        }

        $this->replaceCart($cart);
    }

    public function getTotal(): float
    {
        return (float) collect($this->getContent())->sum(
            fn (array $item): float => ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1))
        );
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>|null
     */
    protected function resolveLine(array $line): ?array
    {
        $productId = (int) ($line['product_id'] ?? 0);
        $variantId = isset($line['variant_id']) ? (int) $line['variant_id'] : null;
        $quantity = max(1, (int) ($line['quantity'] ?? 1));

        if ($productId < 1) {
            return null;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->find($productId);

        if (! $product) {
            return null;
        }

        $variant = null;
        if ($variantId !== null) {
            $variant = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->with('attributeValues')
                ->find($variantId);

            if (! $variant) {
                return null;
            }
        }

        if ($product->has_variants && $variant === null) {
            return null;
        }

        if ($variant) {
            $quantity = min($quantity, max(0, (int) $variant->stock));
            $unitPrice = (float) $variant->current_price;
            $variantName = $variant->name;
            $image = ProductImageUrl::sanitize($variant->image) ?? ProductImageUrl::sanitize($product->images[0] ?? null);
        } else {
            $quantity = min($quantity, max(0, (int) $product->stock));
            $unitPrice = (float) $product->current_price;
            $variantName = '';
            $image = ProductImageUrl::sanitize($product->images[0] ?? null);
        }

        if ($quantity < 1) {
            return null;
        }

        return [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'name' => $product->name,
            'variant_name' => $variantName,
            'price' => $unitPrice,
            'image' => $image,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $cart
     * @return array<string, array{product_id: int, variant_id: ?int, quantity: int}>
     */
    protected function migrateAndPersist(array $cart): array
    {
        $migrated = [];
        foreach ($cart as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId < 1) {
                continue;
            }

            $variantId = isset($line['variant_id']) && $line['variant_id'] !== '' && $line['variant_id'] !== null
                ? (int) $line['variant_id']
                : null;
            $quantity = max(1, (int) ($line['quantity'] ?? 1));

            $norm = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
            $key = $this->makeKey($norm);
            if (isset($migrated[$key])) {
                $migrated[$key]['quantity'] += $quantity;
            } else {
                $migrated[$key] = $norm;
            }
        }

        Session::put(self::SESSION_KEY, $migrated);

        return $migrated;
    }

    /**
     * @param  array<string, array{product_id: int, variant_id: ?int, quantity: int}>  $cart
     */
    protected function replaceCart(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    protected function makeKey(array $item): string
    {
        $variantId = $item['variant_id'] ?? null;
        if ($variantId !== null) {
            return 'v'.(int) $variantId;
        }

        return 'p'.(int) ($item['product_id'] ?? 0);
    }
}
