<?php

namespace App\Livewire\Shop;

use App\Models\Order;
use App\Support\MoneyFormatter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class OrderSuccess extends Component
{
    #[Locked]
    public string $orderNumber;

    public ?Order $order = null;

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;

        $order = Order::query()
            ->with(['items' => fn ($q) => $q->orderBy('id')])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $flashedId = session('completed_order_id');
        if ($flashedId !== null && (int) $flashedId !== (int) $order->id) {
            abort(404);
        }

        $this->order = $order;
    }

    public function formatMoney(float|string $amount): string
    {
        return MoneyFormatter::format((float) $amount);
    }

    public function render()
    {
        return view('livewire.shop.order-success');
    }
}
