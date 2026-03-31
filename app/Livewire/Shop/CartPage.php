<?php

namespace App\Livewire\Shop;

use App\Services\CartService;
use App\Support\MoneyFormatter;
use Illuminate\Support\Facades\Log;
use Throwable;
use Livewire\Component;

class CartPage extends Component
{
    public string $feedbackMessage = '';

    public function increment(int $productId, ?int $variantId = null): void
    {
        $item = $this->findItem($productId, $variantId);
        if (!$item) {
            return;
        }

        try {
            app(CartService::class)->updateQuantity($productId, $variantId, ((int) $item['quantity']) + 1);
            $this->dispatch('cartUpdated');
            $this->feedbackMessage = '';
        } catch (Throwable $e) {
            Log::channel('checkout_stack')->warning('cart.increment.failed', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'message' => $e->getMessage(),
            ]);
            $this->feedbackMessage = 'Die Menge konnte gerade nicht erhöht werden. Bitte versuche es erneut.';
        }
    }

    public function decrement(int $productId, ?int $variantId = null): void
    {
        $item = $this->findItem($productId, $variantId);
        if (!$item) {
            return;
        }

        $newQuantity = ((int) $item['quantity']) - 1;
        try {
            if ($newQuantity < 1) {
                app(CartService::class)->remove($productId, $variantId);
            } else {
                app(CartService::class)->updateQuantity($productId, $variantId, $newQuantity);
            }

            $this->dispatch('cartUpdated');
            $this->feedbackMessage = '';
        } catch (Throwable $e) {
            Log::channel('checkout_stack')->warning('cart.decrement.failed', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'message' => $e->getMessage(),
            ]);
            $this->feedbackMessage = 'Die Menge konnte gerade nicht reduziert werden. Bitte versuche es erneut.';
        }
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        try {
            app(CartService::class)->remove($productId, $variantId);
            $this->dispatch('cartUpdated');
            $this->feedbackMessage = '';
        } catch (Throwable $e) {
            Log::channel('checkout_stack')->warning('cart.remove.failed', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'message' => $e->getMessage(),
            ]);
            $this->feedbackMessage = 'Der Artikel konnte gerade nicht entfernt werden. Bitte versuche es erneut.';
        }
    }

    public function clear(): void
    {
        try {
            app(CartService::class)->clear();
            $this->dispatch('cartUpdated');
            $this->feedbackMessage = '';
        } catch (Throwable $e) {
            Log::channel('checkout_stack')->warning('cart.clear.failed', [
                'message' => $e->getMessage(),
            ]);
            $this->feedbackMessage = 'Der Warenkorb konnte gerade nicht geleert werden. Bitte versuche es erneut.';
        }
    }

    public function getItemsProperty(): array
    {
        return app(CartService::class)->getContent();
    }

    public function getTotalProperty(): float
    {
        return app(CartService::class)->getTotal();
    }

    public function formatPrice(float $price): string
    {
        return MoneyFormatter::format($price);
    }

    protected function findItem(int $productId, ?int $variantId = null): ?array
    {
        foreach ($this->items as $item) {
            if ((int) ($item['product_id'] ?? 0) !== $productId) {
                continue;
            }

            $itemVariantId = $item['variant_id'] ?? null;
            if (($itemVariantId === null && $variantId === null) || (int) $itemVariantId === (int) $variantId) {
                return $item;
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.shop.cart-page');
    }
}
