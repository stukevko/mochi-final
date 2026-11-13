<?php

namespace App\Livewire\Shop;

use App\Services\CartService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Zähler am Warenkorb-Tab der mobilen App-Leiste.
 */
class CartBadge extends Component
{
    #[On('cartUpdated')]
    public function refreshCounter(): void
    {
        unset($this->itemCount);
    }

    #[Computed]
    public function itemCount(): int
    {
        return app(CartService::class)->getTotalLineQuantity();
    }

    public function render()
    {
        return <<<'BLADE'
            <span class="app-tab-badge" @if($this->itemCount < 1) hidden @endif aria-label="{{ $this->itemCount }} Artikel im Warenkorb">
                {{ $this->itemCount > 99 ? '99+' : $this->itemCount }}
            </span>
        BLADE;
    }
}
