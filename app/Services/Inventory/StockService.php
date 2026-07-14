<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function orderStockAlreadyCommitted(Order $order): bool
    {
        $paymentData = is_array($order->payment_data) ? $order->payment_data : [];

        return ! empty($paymentData['stock_deducted_at']);
    }

    /**
     * Bestand für eine Bestellung abziehen (idempotent).
     */
    public function commitOrderStock(Order $order): void
    {
        if ($this->orderStockAlreadyCommitted($order)) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->refresh();
            $order->loadMissing('items');

            if ($this->orderStockAlreadyCommitted($order)) {
                return;
            }

            foreach ($order->items as $item) {
                $quantity = max(1, (int) $item->quantity);
                $productId = (int) ($item->product_id ?? 0);
                $variantId = $item->product_variant_id ? (int) $item->product_variant_id : null;

                if ($productId < 1) {
                    continue;
                }

                /** @var Product|null $product */
                $product = Product::query()->where('is_active', true)->lockForUpdate()->find($productId);
                if (! $product) {
                    throw new RuntimeException("Produkt #{$productId} ist nicht mehr verfügbar.");
                }

                if ($variantId) {
                    /** @var ProductVariant|null $variant */
                    $variant = ProductVariant::query()
                        ->where('is_active', true)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->find($variantId);

                    if (! $variant) {
                        throw new RuntimeException("Variante #{$variantId} ist nicht mehr verfügbar.");
                    }

                    if ($variant->stock < $quantity) {
                        throw new RuntimeException("Variante {$item->product_name} ist nicht ausreichend auf Lager.");
                    }

                    $variant->decrement('stock', $quantity);

                    continue;
                }

                if ((int) $product->stock < $quantity) {
                    throw new RuntimeException("Produkt {$item->product_name} ist nicht ausreichend auf Lager.");
                }

                $product->decrement('stock', $quantity);
            }

            $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
            $paymentData['stock_deducted_at'] = now()->toIso8601String();
            $order->forceFill(['payment_data' => $paymentData])->save();
        });
    }

    /**
     * Bestand zurückbuchen (z. B. bei Storno).
     */
    public function restoreOrderStock(Order $order): void
    {
        if (! $this->orderStockAlreadyCommitted($order)) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->refresh();
            $order->loadMissing('items');

            if (! $this->orderStockAlreadyCommitted($order)) {
                return;
            }

            foreach ($order->items as $item) {
                $quantity = max(1, (int) $item->quantity);
                $productId = (int) ($item->product_id ?? 0);
                $variantId = $item->product_variant_id ? (int) $item->product_variant_id : null;

                if ($productId < 1) {
                    continue;
                }

                if ($variantId) {
                    ProductVariant::query()
                        ->where('product_id', $productId)
                        ->whereKey($variantId)
                        ->lockForUpdate()
                        ->first()
                        ?->increment('stock', $quantity);

                    continue;
                }

                Product::query()
                    ->whereKey($productId)
                    ->lockForUpdate()
                    ->first()
                    ?->increment('stock', $quantity);
            }

            $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
            unset($paymentData['stock_deducted_at']);
            $order->forceFill(['payment_data' => $paymentData])->save();
        });
    }
}
