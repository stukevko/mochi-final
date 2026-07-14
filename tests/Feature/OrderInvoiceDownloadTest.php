<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrderInvoiceDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_order_invoice_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $order = $this->createOrderWithItem();

        $response = $this->actingAs($admin)->get(route('admin.orders.invoice', $order));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'rechnung-'.$order->order_number.'.pdf',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_non_admin_cannot_download_admin_invoice(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $order = $this->createOrderWithItem();

        $this->actingAs($user)->get(route('admin.orders.invoice', $order))->assertForbidden();
    }

    public function test_signed_checkout_invoice_route_returns_pdf(): void
    {
        $order = $this->createOrderWithItem();

        session(['completed_order_id' => $order->id]);

        $url = URL::temporarySignedRoute(
            'checkout.invoice',
            now()->addHour(),
            ['orderNumber' => $order->order_number],
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unsigned_checkout_invoice_route_is_rejected(): void
    {
        $order = $this->createOrderWithItem();

        $this->get(route('checkout.invoice', ['orderNumber' => $order->order_number]))
            ->assertForbidden();
    }

    public function test_order_success_page_shows_invoice_download_link(): void
    {
        $order = $this->createOrderWithItem();

        session(['completed_order_id' => $order->id]);

        $url = URL::temporarySignedRoute(
            'checkout.success',
            now()->addHour(),
            ['orderNumber' => $order->order_number],
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertSee('Rechnung herunterladen', false);
        $response->assertSee('checkout/success/'.$order->order_number.'/invoice', false);
    }

    private function createOrderWithItem(): Order
    {
        $order = new Order;
        $order->forceFill([
            'order_number' => 'ORD-260715-TEST',
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'sumup',
            'subtotal' => 10.08,
            'tax' => 1.92,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 12.00,
            'billing_address' => [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max@example.test',
                'street' => 'Musterstraße 1',
                'zip' => '67346',
                'city' => 'Speyer',
                'country' => 'DE',
            ],
            'shipping_address' => [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max@example.test',
                'street' => 'Musterstraße 1',
                'zip' => '67346',
                'city' => 'Speyer',
                'country' => 'DE',
            ],
            'currency' => 'EUR',
        ])->save();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Testprodukt',
            'variant_name' => 'Standard',
            'sku' => 'TEST-001',
            'quantity' => 1,
            'unit_price' => 12.00,
            'total_price' => 12.00,
        ]);

        return $order->refresh()->load('items');
    }
}
