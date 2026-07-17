<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\CartService;
use App\Services\Payments\PaymentProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SumUpCheckoutRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_sumup_checkout_includes_redirect_url_for_hosted_checkout(): void
    {
        config([
            'services.sumup.token' => 'test-sumup-token',
            'services.sumup.merchant_code' => 'MC123456',
        ]);

        Http::fake([
            'api.sumup.com/v0.1/checkouts' => Http::response([
                'id' => 'sumup-checkout-abc',
                'hosted_checkout_url' => 'https://checkout.sumup.com/pay/sumup-checkout-abc',
            ], 201),
        ]);

        $order = $this->createPendingSumUpOrder();

        $url = app(PaymentProviderService::class)->createSumUpCheckoutUrl($order);

        $this->assertSame('https://checkout.sumup.com/pay/sumup-checkout-abc', $url);

        Http::assertSent(function ($request) use ($order): bool {
            if ($request->url() !== 'https://api.sumup.com/v0.1/checkouts') {
                return false;
            }

            $payload = $request->data();
            $returnUrl = $payload['redirect_url'] ?? null;

            if (! is_string($returnUrl) || ($payload['return_url'] ?? null) !== $returnUrl) {
                return false;
            }

            if (($payload['hosted_checkout']['enabled'] ?? false) !== true) {
                return false;
            }

            if (! str_contains($returnUrl, '/checkout/return/sumup/'.$order->id)
                || ! str_contains($returnUrl, 'signature=')) {
                return false;
            }

            $signatureRequest = \Illuminate\Http\Request::create($returnUrl, 'GET');

            return $signatureRequest->hasValidSignatureWhileIgnoring(['checkout_id']);
        });
    }

    public function test_sumup_return_redirects_to_signed_success_page_when_paid(): void
    {
        Mail::fake();

        config([
            'services.sumup.token' => 'test-sumup-token',
            'services.sumup.merchant_code' => 'MC123456',
        ]);

        $order = $this->createPendingSumUpOrder([
            'payment_data' => ['sumup_checkout_id' => 'sumup-checkout-abc'],
        ]);

        Http::fake([
            'api.sumup.com/v0.1/checkouts/*' => Http::response([
                'checkout_reference' => $order->order_number,
                'status' => 'PAID',
                'amount' => 79.00,
                'currency' => 'EUR',
                'transaction_id' => 'tx-123',
            ]),
        ]);

        $response = $this->get(URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => 'sumup', 'order' => $order->id],
        ).'&checkout_id=sumup-checkout-abc');

        $expectedSuccessUrl = URL::temporarySignedRoute(
            'checkout.success',
            now()->addDays(7),
            ['orderNumber' => $order->order_number],
        );

        $response->assertRedirect($expectedSuccessUrl);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        Mail::assertSent(\App\Mail\OrderConfirmed::class);
        Mail::assertNotQueued(\App\Mail\OrderConfirmed::class);
        $this->assertNotEmpty(data_get($order->payment_data, 'customer_confirmation_sent_at'));
    }

    public function test_sumup_return_clears_cart_when_paid(): void
    {
        Mail::fake();

        config([
            'services.sumup.token' => 'test-sumup-token',
            'services.sumup.merchant_code' => 'MC123456',
        ]);

        session()->put('cart', [
            'p1' => ['product_id' => 1, 'variant_id' => null, 'quantity' => 2],
        ]);

        $order = $this->createPendingSumUpOrder([
            'payment_data' => ['sumup_checkout_id' => 'sumup-checkout-abc'],
        ]);

        Http::fake([
            'api.sumup.com/v0.1/checkouts/*' => Http::response([
                'checkout_reference' => $order->order_number,
                'status' => 'PAID',
                'amount' => 79.00,
                'currency' => 'EUR',
                'transaction_id' => 'tx-789',
            ]),
        ]);

        $this->get(URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => 'sumup', 'order' => $order->id],
        ).'&checkout_id=sumup-checkout-abc')
            ->assertRedirect();

        $this->assertSame([], app(CartService::class)->getRawLines());
    }

    public function test_sumup_return_retries_when_checkout_still_pending(): void
    {
        Mail::fake();

        config([
            'services.sumup.token' => 'test-sumup-token',
            'services.sumup.merchant_code' => 'MC123456',
        ]);

        $order = $this->createPendingSumUpOrder([
            'payment_data' => ['sumup_checkout_id' => 'sumup-checkout-abc'],
        ]);

        $pendingPayload = [
            'checkout_reference' => $order->order_number,
            'status' => 'PENDING',
            'amount' => 79.00,
            'currency' => 'EUR',
        ];
        $paidPayload = [
            'checkout_reference' => $order->order_number,
            'status' => 'PAID',
            'amount' => 79.00,
            'currency' => 'EUR',
            'transaction_id' => 'tx-456',
        ];

        Http::fake([
            'api.sumup.com/v0.1/checkouts/*' => Http::sequence()
                ->push($pendingPayload)
                ->push($paidPayload),
        ]);

        $response = $this->get(URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => 'sumup', 'order' => $order->id],
        ).'&checkout_id=sumup-checkout-abc');

        $expectedSuccessUrl = URL::temporarySignedRoute(
            'checkout.success',
            now()->addDays(7),
            ['orderNumber' => $order->order_number],
        );

        $response->assertRedirect($expectedSuccessUrl);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
    }

    private function createPendingSumUpOrder(array $overrides = []): Order
    {
        $order = new Order;

        $order->forceFill(array_merge([
            'order_number' => 'ORD-260715-DD2B',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'sumup',
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
