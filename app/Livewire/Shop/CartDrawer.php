<?php

namespace App\Livewire\Shop;

use App\Services\CartService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    #[On('cartUpdated')]
    public function refreshDrawer(): void
    {
        unset($this->items, $this->total);
    }

    #[Computed]
    public function items(): array
    {
        return app(CartService::class)->getContent();
    }

    #[Computed]
    public function total(): float
    {
        return app(CartService::class)->getTotal();
    }

    public function render()
    {
        return view('livewire.shop.cart-drawer');
    }
}
