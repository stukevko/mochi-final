<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Payments\PaymentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentCheckoutReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsigned_payment_return_is_rejected(): void
    {
        $order = $this->createPendingOrder('stripe');

        $this->get(route('payment.return', [
            'provider' => 'stripe',
            'order' => $order->id,
        ]))->assertForbidden();
    }

    public function test_stripe_return_does_not_expose_success_when_unpaid(): void
    {
        $order = $this->createPendingOrder('stripe');

        $response = $this->get(URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => 'stripe', 'order' => $order->id],
        ));

        $response->assertRedirect(route('checkout'));
        $response->assertSessionHas('payment_error');

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_paypal_return_does_not_expose_success_when_unpaid(): void
    {
        $order = $this->createPendingOrder('paypal');

        $response = $this->get(URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => 'paypal', 'order' => $order->id],
        ));

        $response->assertRedirect(route('checkout'));
        $response->assertSessionHas('payment_error');

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_sumup_return_does_not_expose_success_when_unpaid(): void
    {
        config([
            'services.sumup.token' => 'test-sumup-token',
            'services.sumup.merchant_code' => 'MC123456',
        ]);

        $order = $this->createPendingOrder('sumup', [
            'payment_data' => ['sumup_checkout_id' => 'sumup-checkout-abc'],
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'api.sumup.com/v0.1/checkouts/*' => \Illuminate\Support\Facades\Http::response([
                'checkout_reference' => $order->order_number,
                'status' => 'PENDING',
                'amount' => 79.00,
                'currency' => 'EUR',
            ]),
        ]);

        $response = $this->get(URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => 'sumup', 'order' => $order->id],
        ).'&checkout_id=sumup-checkout-abc');

        $response->assertRedirect(route('checkout'));
        $response->assertSessionHas('payment_error');

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_stripe_checkout_uses_signed_success_url_with_session_placeholder(): void
    {
        $order = $this->createPendingOrder('stripe');

        $signedBase = app(PaymentProviderService::class)->signedPaymentReturnUrl('stripe', $order);

        $this->assertStringContainsString('signature=', $signedBase);
        $this->assertStringContainsString('/checkout/return/stripe/'.$order->id, $signedBase);

        $withSession = $signedBase.'&session_id={CHECKOUT_SESSION_ID}';
        $this->assertStringContainsString('&session_id={CHECKOUT_SESSION_ID}', $withSession);

        $request = \Illuminate\Http\Request::create(
            str_replace('{CHECKOUT_SESSION_ID}', 'cs_test_123', $withSession),
            'GET',
        );
        $this->assertTrue($request->hasValidSignatureWhileIgnoring(['session_id']));
    }

    private function createPendingOrder(string $paymentMethod, array $overrides = []): Order
    {
        $order = new Order;

        $order->forceFill(array_merge([
            'order_number' => 'ORD-260716-'.strtoupper(substr(md5($paymentMethod.microtime()), 0, 4)),
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $paymentMethod,
            'subtotal' => 79.00,
            'tax' => 0,
            'shipping_cost' => 0,
            'discount' => 0,
            'total' => 79.00,
            'billing_address' => [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max@example.test',
            ],
            'shipping_address' => null,
            'currency' => 'EUR',
        ], $overrides))->save();

        return $order->refresh();
    }
}
