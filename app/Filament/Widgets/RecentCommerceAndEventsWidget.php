<?php

namespace App\Filament\Widgets;

use App\Enums\EventStatus;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\OrderResource;
use App\Models\Event;
use App\Models\Order;
use App\Support\MoneyFormatter;
use Carbon\CarbonInterface;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class RecentCommerceAndEventsWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-commerce-and-events';

    protected static ?int $sort = -90;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{
     *   latestOrders: array<int, array<string, mixed>>,
     *   latestEvents: array<int, array<string, mixed>>
     * }
     */
    protected function getViewData(): array
    {
        $latestOrders = Order::query()
            ->with(['user:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Order $order): array => $this->orderRow($order))
            ->all();

        $latestEvents = Event::query()
            ->where('status', EventStatus::Active)
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map(fn (Event $event): array => $this->eventRow($event))
            ->all();

        return [
            'latestOrders' => $latestOrders,
            'latestEvents' => $latestEvents,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderRow(Order $order): array
    {
        $paymentStatus = (string) $order->payment_status;
        $paymentVariant = match ($paymentStatus) {
            'paid' => 'paid',
            'pending' => 'pending',
            'failed', 'cancelled' => 'danger',
            'refunded' => 'muted',
            default => 'muted',
        };

        return [
            'number' => (string) $order->order_number,
            'customer_name' => $this->orderCustomerDisplayName($order),
            'status' => (string) $order->status,
            'payment_status' => $paymentStatus,
            'payment_label' => Order::PAYMENT_STATUS_LABELS[$paymentStatus] ?? $paymentStatus,
            'payment_variant' => $paymentVariant,
            'total' => MoneyFormatter::format((float) $order->total),
            'url' => OrderResource::getUrl('edit', ['record' => $order]),
        ];
    }

    private function orderCustomerDisplayName(Order $order): string
    {
        $fromUser = trim((string) ($order->user?->name ?? ''));
        if ($fromUser !== '') {
            return $fromUser;
        }

        $ship = is_array($order->shipping_address) ? $order->shipping_address : [];
        $first = trim((string) ($ship['first_name'] ?? ''));
        $last = trim((string) ($ship['last_name'] ?? ''));
        $name = trim($first.' '.$last);
        if ($name !== '') {
            return $name;
        }

        $bill = is_array($order->billing_address) ? $order->billing_address : [];
        $bFirst = trim((string) ($bill['first_name'] ?? ''));
        $bLast = trim((string) ($bill['last_name'] ?? ''));
        $bName = trim($bFirst.' '.$bLast);

        return $bName !== '' ? $bName : 'Gastbestellung';
    }

    /**
     * @return array<string, mixed>
     */
    private function eventRow(Event $event): array
    {
        $key = $event->getRouteKey();
        $url = $key !== null && $key !== ''
            ? EventResource::getUrl('edit', ['record' => $key])
            : null;

        $colors = $event->calendarFeedColors();

        return [
            'title' => (string) $event->title,
            'url' => $url,
            'dot_color' => (string) ($colors['borderColor'] ?? '#ff7a1f'),
            'date_badge' => $this->eventDateBadge($event->starts_at),
        ];
    }

    private function eventDateBadge(?CarbonInterface $starts): string
    {
        if ($starts === null) {
            return '—';
        }

        /** @var Carbon $starts */
        if ($starts->isToday()) {
            return 'Heute, '.$starts->format('H:i');
        }

        if ($starts->isTomorrow()) {
            return 'Morgen, '.$starts->format('H:i');
        }

        return $starts->locale(app()->getLocale())->translatedFormat('D d.m., H:i');
    }
}
