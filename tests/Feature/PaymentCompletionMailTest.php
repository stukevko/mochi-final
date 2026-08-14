<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Services\Payments\PaymentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentCompletionMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_and_notify_sends_confirmation_synchronously_even_with_database_queue(): void
    {
        Mail::fake();
        config(['queue.default' => 'database']);

        $order = new Order;
        $order->forceFill([
            'order_number' => 'MC-MAIL-SYNC-1',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'sumup',
            'currency' => 'EUR',
            'subtotal' => 10,
            'tax' => 0,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 10,
            'billing_address' => [
                'email' => 'kunde@example.com',
                'first_name' => 'Test',
                'last_name' => 'Kunde',
            ],
            'shipping_address' => null,
            'payment_data' => [],
        ])->save();

        app(PaymentCompletionService::class)->markPaidAndNotify($order->refresh(), 'sumup', 'tx-sync-1');

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        Mail::assertSent(OrderConfirmed::class, fn (OrderConfirmed $mail): bool => $mail->order->is($order));
        Mail::assertNotQueued(OrderConfirmed::class);
        $this->assertNotEmpty(data_get($order->payment_data, 'customer_confirmation_sent_at'));
    }
}
