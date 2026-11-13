<?php

namespace Tests\Feature;

use App\Livewire\Shop\CheckoutPage;
use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\Payments\PaymentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kompletter Verkaufsablauf: Bestellen → Reservieren → SumUp → Bezahlt → Rechnung.
 */
class SumUpOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sumup.token' => 'test-sumup-token',
            'services.sumup.merchant_code' => 'MC123456',
        ]);
        Setting::flushMemo();
    }

    public function test_checkout_offers_only_sumup(): void
    {
        Setting::set('prepayment_enabled', true, 'boolean');

        $methods = Livewire::test(CheckoutPage::class)->instance()->paymentMethods;

        $this->assertSame(['sumup'], array_column($methods, 'code'));
    }

    public function test_placing_an_order_reserves_stock_adds_shipping_and_redirects_to_sumup(): void
    {
        Setting::set('tax_rate', 19, 'number');
        Setting::set('shipping_flat_rate', '4.90', 'number');
        Setting::set('free_shipping_from', '100', 'number');

        $product = $this->createProduct(stock: 1, price: 20.00);
        app(CartService::class)->add($product->id, null, 1);

        Http::fake([
            'api.sumup.com/v0.1/checkouts' => Http::response([
                'id' => 'co-1',
                'hosted_checkout_url' => 'https://checkout.sumup.com/pay/co-1',
            ], 201),
        ]);

        $this->fillCheckout(Livewire::test(CheckoutPage::class))
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertRedirect('https://checkout.sumup.com/pay/co-1');

        $order = Order::query()->firstOrFail();

        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('sumup', $order->payment_method);
        $this->assertEqualsWithDelta(4.90, (float) $order->shipping_cost, 0.001);
        $this->assertEqualsWithDelta(24.90, (float) $order->total, 0.001);
        $this->assertEqualsWithDelta(24.90, (float) $order->subtotal + (float) $order->tax, 0.001);
        $this->assertSame('co-1', $order->payment_data['sumup_checkout_id']);
        $this->assertNull($order->invoice_number);

        // Reserviert: das letzte Exemplar ist sofort weg.
        $this->assertSame(0, (int) $product->fresh()->stock);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.sumup.com/v0.1/checkouts'
            && abs((float) $request['amount'] - 24.90) < 0.001);
    }

    public function test_last_item_cannot_be_ordered_twice(): void
    {
        $product = $this->createProduct(stock: 1, price: 20.00);
        $this->createPendingOrder($product, reserve: true);

        app(CartService::class)->add($product->id, null, 1);
        Http::fake();

        $this->fillCheckout(Livewire::test(CheckoutPage::class))
            ->call('placeOrder')
            ->assertHasErrors(['cart']);

        $this->assertSame(1, Order::query()->count());
    }

    public function test_failed_sumup_checkout_creation_releases_the_reservation(): void
    {
        $product = $this->createProduct(stock: 2, price: 20.00);
        app(CartService::class)->add($product->id, null, 2);

        Http::fake(['api.sumup.com/*' => Http::response(['message' => 'nope'], 500)]);

        $this->fillCheckout(Livewire::test(CheckoutPage::class))
            ->call('placeOrder')
            ->assertHasErrors(['payment_method']);

        $this->assertSame(2, (int) $product->fresh()->stock);
        $this->assertSame('cancelled', Order::query()->firstOrFail()->status);
    }

    public function test_webhook_marks_order_paid_assigns_invoice_number_and_sends_mails(): void
    {
        Mail::fake();
        Setting::set('shop_email', 'laden@example.test', 'text');
        Setting::set('invoice_prefix', 'RE-', 'text');

        $product = $this->createProduct(stock: 5, price: 20.00);
        $first = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-a');
        $second = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-b');

        Http::fake([
            'api.sumup.com/v0.1/checkouts/co-a' => Http::response($this->sumupPayload($first, 'PAID')),
            'api.sumup.com/v0.1/checkouts/co-b' => Http::response($this->sumupPayload($second, 'PAID')),
        ]);

        $this->postJson('/webhooks/payment/sumup', ['event_type' => 'CHECKOUT_STATUS_CHANGED', 'id' => 'co-a'])->assertOk();
        // Doppelte Meldung darf nichts doppelt auslösen.
        $this->postJson('/webhooks/payment/sumup', ['event_type' => 'CHECKOUT_STATUS_CHANGED', 'id' => 'co-a'])->assertOk();
        $this->postJson('/webhooks/payment/sumup', ['event_type' => 'CHECKOUT_STATUS_CHANGED', 'id' => 'co-b'])->assertOk();

        $first->refresh();
        $second->refresh();
        $year = now()->format('Y');

        $this->assertSame('paid', $first->payment_status);
        $this->assertSame('processing', $first->status);
        $this->assertNotNull($first->paid_at);
        $this->assertSame("RE-{$year}-00001", $first->invoice_number);
        $this->assertSame("RE-{$year}-00002", $second->invoice_number);

        // Bestand wurde bei der Reservierung abgezogen — nicht noch einmal bei Zahlung.
        $this->assertSame(3, (int) $product->fresh()->stock);

        Mail::assertQueued(OrderConfirmed::class, 2);
        Mail::assertQueued(AdminOrderNotification::class, 2);
    }

    public function test_forged_webhook_cannot_mark_order_paid(): void
    {
        $product = $this->createProduct(stock: 5, price: 20.00);
        $order = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-a');

        Http::fake([
            'api.sumup.com/v0.1/checkouts/co-a' => Http::response($this->sumupPayload($order, 'PENDING')),
        ]);

        $this->postJson('/webhooks/payment/sumup', ['id' => 'co-a', 'status' => 'PAID'])->assertOk();
        $this->postJson('/webhooks/payment/sumup', ['id' => 'unbekannt', 'status' => 'PAID'])->assertOk();

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_checkout_of_another_order_or_wrong_amount_is_rejected(): void
    {
        $product = $this->createProduct(stock: 5, price: 20.00);
        $order = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-a');

        Http::fake([
            'api.sumup.com/v0.1/checkouts/co-a' => Http::sequence()
                ->push(array_merge($this->sumupPayload($order, 'PAID'), ['checkout_reference' => 'ORD-FREMD']))
                ->push(array_merge($this->sumupPayload($order, 'PAID'), ['amount' => 1.00])),
        ]);

        $completion = app(PaymentCompletionService::class);
        $completion->reconcileSumUp($order);
        $completion->reconcileSumUp($order->fresh());

        $this->assertNotSame('paid', $order->fresh()->payment_status);
    }

    public function test_reconcile_command_releases_abandoned_orders_and_completes_paid_ones(): void
    {
        Mail::fake();

        $product = $this->createProduct(stock: 5, price: 20.00);
        $abandoned = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-x', createdAt: now()->subHour());
        $paid = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-y', createdAt: now()->subMinutes(10));
        $fresh = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-z', createdAt: now()->subMinutes(10));

        Http::fake([
            'api.sumup.com/v0.1/checkouts/co-x' => Http::response($this->sumupPayload($abandoned, 'EXPIRED')),
            'api.sumup.com/v0.1/checkouts/co-y' => Http::response($this->sumupPayload($paid, 'PAID')),
            'api.sumup.com/v0.1/checkouts/co-z' => Http::response($this->sumupPayload($fresh, 'PENDING')),
        ]);

        $this->assertSame(2, (int) $product->fresh()->stock);

        $this->artisan('shop:reconcile-payments')->assertSuccessful();

        $this->assertSame('cancelled', $abandoned->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->payment_status);
        $this->assertSame('pending', $fresh->fresh()->payment_status);

        // Nur die verfallene Bestellung gibt ihre Ware zurück.
        $this->assertSame(3, (int) $product->fresh()->stock);
    }

    public function test_late_payment_after_release_is_still_recorded_as_paid(): void
    {
        Mail::fake();

        $product = $this->createProduct(stock: 1, price: 20.00);
        $order = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-a');

        app(PaymentCompletionService::class)->cancelUnpaid($order, 'test');
        $this->assertSame(1, (int) $product->fresh()->stock);

        app(PaymentCompletionService::class)->markPaidAndNotify($order->fresh(), 'sumup', 'tx-1', clearCart: false);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame(0, (int) $product->fresh()->stock);
    }

    public function test_cancelling_a_paid_order_in_admin_restocks_items(): void
    {
        Mail::fake();

        $product = $this->createProduct(stock: 3, price: 20.00);
        $order = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-a');
        app(PaymentCompletionService::class)->markPaidAndNotify($order, 'sumup', 'tx-1', clearCart: false);
        $this->assertSame(2, (int) $product->fresh()->stock);

        $order->fresh()->forceFill(['status' => 'cancelled'])->save();

        $this->assertSame(3, (int) $product->fresh()->stock);
    }

    public function test_invoice_and_success_page_exist_only_for_paid_orders(): void
    {
        $product = $this->createProduct(stock: 3, price: 20.00);
        $order = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-a');

        $invoiceUrl = URL::temporarySignedRoute('checkout.invoice', now()->addHour(), ['orderNumber' => $order->order_number]);
        $successUrl = URL::temporarySignedRoute('checkout.success', now()->addHour(), ['orderNumber' => $order->order_number]);

        $this->get($invoiceUrl)->assertNotFound();
        $this->get($successUrl)->assertNotFound();

        Mail::fake();
        app(PaymentCompletionService::class)->markPaidAndNotify($order, 'sumup', 'tx-1', clearCart: false);

        $this->get($successUrl)->assertOk()->assertSee('Rechnung herunterladen');
        $this->get($invoiceUrl)->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_pdf_view_contains_legal_fields(): void
    {
        Mail::fake();
        Setting::set('tax_number', '41/123/45678', 'text');
        Setting::set('shop_address', "Maximilianstraße 42\n67346 Speyer", 'textarea');

        $product = $this->createProduct(stock: 3, price: 20.00);
        $order = $this->createPendingOrder($product, reserve: true, checkoutId: 'co-a');
        app(PaymentCompletionService::class)->markPaidAndNotify($order, 'sumup', 'tx-1', clearCart: false);
        $order->refresh();

        $html = view('pdf.invoice', ['order' => $order])->render();

        $this->assertStringContainsString($order->invoice_number, $html);
        $this->assertStringContainsString('Steuernummer: 41/123/45678', $html);
        $this->assertStringContainsString('Rechnungsdatum', $html);
        $this->assertStringContainsString('Leistungsdatum', $html);
        $this->assertStringContainsString('19 % MwSt.', $html);
        $this->assertStringContainsString('Betrag dankend erhalten', $html);
    }

    private function fillCheckout($component)
    {
        return $component
            ->set('first_name', 'Max')
            ->set('last_name', 'Mustermann')
            ->set('email', 'max@example.test')
            ->set('street', 'Musterstraße 1')
            ->set('zip', '67346')
            ->set('city', 'Speyer')
            ->set('country', 'DE')
            ->set('accepted_legal', true);
    }

    private function createProduct(int $stock, float $price): Product
    {
        return Product::query()->create([
            'name' => 'Booster Display',
            'slug' => 'booster-'.uniqid(),
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function createPendingOrder(Product $product, bool $reserve = false, ?string $checkoutId = null, $createdAt = null): Order
    {
        $order = new Order;
        $order->forceFill([
            'order_number' => Order::generateOrderNumber(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'sumup',
            'subtotal' => 16.81,
            'tax' => 3.19,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 20.00,
            'currency' => 'EUR',
            'billing_address' => [
                'first_name' => 'Max', 'last_name' => 'Mustermann', 'email' => 'max@example.test',
                'street' => 'Musterstraße 1', 'zip' => '67346', 'city' => 'Speyer', 'country' => 'DE',
            ],
            'shipping_address' => [
                'first_name' => 'Max', 'last_name' => 'Mustermann', 'email' => 'max@example.test',
                'street' => 'Musterstraße 1', 'zip' => '67346', 'city' => 'Speyer', 'country' => 'DE',
            ],
            'payment_data' => $checkoutId ? ['sumup_checkout_id' => $checkoutId] : null,
        ])->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variant_name' => '',
            'quantity' => 1,
            'unit_price' => 20.00,
            'total_price' => 20.00,
        ]);

        if ($reserve) {
            app(\App\Services\Inventory\StockService::class)->commitOrderStock($order);
        }

        if ($createdAt) {
            $order->forceFill(['created_at' => $createdAt])->save();
        }

        return $order->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function sumupPayload(Order $order, string $status): array
    {
        return [
            'id' => $order->payment_data['sumup_checkout_id'] ?? 'co',
            'checkout_reference' => $order->order_number,
            'status' => $status,
            'amount' => (float) $order->total,
            'currency' => 'EUR',
            'transaction_id' => $status === 'PAID' ? 'tx-'.$order->id : null,
        ];
    }
}
