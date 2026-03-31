<?php

namespace App\Livewire\Shop;

use App\Services\CartService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CartIcon extends Component
{
    #[On('cartUpdated')]
    public function refreshCounter(): void
    {
        unset($this->itemCount);
    }

    #[Computed]
    public function itemCount(): int
    {
        /** @var CartService $cartService */
        $cartService = app(CartService::class);

        return $cartService->getTotalLineQuantity();
    }

    public function render()
    {
        return view('livewire.shop.cart-icon');
    }
}
