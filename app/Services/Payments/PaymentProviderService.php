<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Support\PaymentOrderVerifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class PaymentProviderService
{
    public function createStripeCheckoutUrl(Order $order): string
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            throw new RuntimeException('Stripe ist nicht konfiguriert (STRIPE_SECRET fehlt).');
        }

        $client = new StripeClient($secret);
        $currency = strtolower((string) ($order->currency ?: 'eur'));

        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round(((float) $item->unit_price) * 100),
                    'product_data' => [
                        'name' => $item->product_name.(filled($item->variant_name) ? ' ('.$item->variant_name.')' : ''),
                    ],
                ],
                'quantity' => (int) $item->quantity,
            ];
        }

        // session_id wird nach dem Signieren angehängt und von der signed-Middleware ignoriert.
        $successUrl = $this->signedPaymentReturnUrl('stripe', $order).'&session_id={CHECKOUT_SESSION_ID}';

        $payload = [
            'mode' => 'payment',
            'payment_method_types' => ['card', 'klarna', 'sofort', 'giropay'],
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => route('payment.cancel', ['provider' => 'stripe', 'order' => $order->id]),
            'client_reference_id' => (string) $order->id,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
            ],
            /** @see https://docs.stripe.com/payments/checkout/customization/appearance */
            'appearance' => [
                'theme' => 'night',
            ],
        ];

        try {
            $session = $client->checkout->sessions->create($payload);
        } catch (InvalidRequestException $e) {
            // Fallback for accounts/countries where one of the extra methods is not enabled.
            $payload['payment_method_types'] = ['card'];
            $session = $client->checkout->sessions->create($payload);
        }

        if (! is_string($session->url) || $session->url === '') {
            throw new RuntimeException('Stripe Checkout URL konnte nicht erzeugt werden.');
        }

        return $session->url;
    }

    public function createPayPalCheckoutUrl(Order $order): string
    {
        $accessToken = $this->getPayPalAccessToken();
        $baseUrl = $this->payPalBaseUrl();

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($baseUrl.'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $order->order_number,
                    'custom_id' => (string) $order->id,
                    'amount' => [
                        'currency_code' => strtoupper((string) ($order->currency ?: 'EUR')),
                        'value' => number_format((float) $order->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $this->signedPaymentReturnUrl('paypal', $order),
                    'cancel_url' => route('payment.cancel', ['provider' => 'paypal', 'order' => $order->id]),
                    'brand_name' => config('app.name'),
                    'user_action' => 'PAY_NOW',
                    /* PayPal hosted approval page follows account theme; no official „dark“ API on Orders v2 redirect flow. */
                    'locale' => 'de-DE',
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal Checkout konnte nicht initialisiert werden.');
        }

        $links = $response->json('links', []);
        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'approve' && is_string($link['href'] ?? null)) {
                return $link['href'];
            }
        }

        throw new RuntimeException('PayPal Approval-Link fehlt.');
    }

    /**
     * @return array{token: string, merchant_code: string}
     */
    public function resolveSumUpCredentials(): array
    {
        $token = trim((string) config('services.sumup.token'));
        $merchantCode = trim((string) config('services.sumup.merchant_code'));

        if ($token === '' || $merchantCode === '') {
            $gateway = PaymentGateway::query()
                ->where('code', 'sumup')
                ->first();

            if ($gateway) {
                if ($token === '') {
                    $token = trim((string) (
                        $gateway->getConfigValue('api_key')
                        ?? $gateway->getConfigValue('token')
                        ?? ''
                    ));
                }

                if ($merchantCode === '') {
                    $merchantCode = trim((string) $gateway->getConfigValue('merchant_code'));
                }
            }
        }

        return [
            'token' => $token,
            'merchant_code' => $merchantCode,
        ];
    }

    public function isSumUpConfigured(): bool
    {
        $credentials = $this->resolveSumUpCredentials();

        return $credentials['token'] !== '' && $credentials['merchant_code'] !== '';
    }

    public function createSumUpCheckoutUrl(Order $order): string
    {
        $credentials = $this->resolveSumUpCredentials();

        if ($credentials['token'] === '' || $credentials['merchant_code'] === '') {
            throw new RuntimeException('SumUp ist nicht konfiguriert (SUMUP_TOKEN / SUMUP_MERCHANT_CODE fehlen).');
        }

        $returnUrl = $this->signedPaymentReturnUrl('sumup', $order);

        $response = Http::withToken($credentials['token'])
            ->acceptJson()
            ->post('https://api.sumup.com/v0.1/checkouts', [
                'checkout_reference' => (string) $order->order_number,
                'amount' => round((float) $order->total, 2),
                'currency' => strtoupper((string) ($order->currency ?: 'EUR')),
                'merchant_code' => $credentials['merchant_code'],
                'description' => 'Bestellung '.$order->order_number,
                'return_url' => $returnUrl,
                'redirect_url' => $returnUrl,
                'hosted_checkout' => ['enabled' => true],
            ]);

        if (! $response->successful()) {
            Log::channel('checkout_stack')->error('sumup.checkout.create_failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new RuntimeException('SumUp Checkout konnte nicht initialisiert werden.');
        }

        $checkoutId = (string) ($response->json('id') ?? '');
        $url = $response->json('hosted_checkout_url') ?? $response->json('checkout_url');

        if ($checkoutId !== '') {
            $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
            $paymentData['sumup_checkout_id'] = $checkoutId;
            $order->forceFill(['payment_data' => $paymentData])->save();
        }

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('SumUp Checkout URL fehlt.');
        }

        return $url;
    }

    /**
     * SumUp can redirect back before the checkout status flips to PAID — poll briefly.
     *
     * @return array{paid: bool, transaction_id: string|null, status: string|null}
     */
    public function verifySumUpCheckoutWithRetry(
        string $checkoutId,
        Order $order,
        int $maxAttempts = 5,
        int $delayMs = 500,
    ): array {
        $result = ['paid' => false, 'transaction_id' => null, 'status' => null];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->verifySumUpCheckout($checkoutId, $order);

            if ($result['paid']) {
                return $result;
            }

            $status = strtoupper((string) ($result['status'] ?? ''));
            if ($status !== 'PENDING' || $attempt === $maxAttempts) {
                return $result;
            }

            usleep($delayMs * 1000);
        }

        return $result;
    }

    /**
     * @return array{paid: bool, transaction_id: string|null, status: string|null}
     */
    public function verifySumUpCheckout(string $checkoutId, Order $order): array
    {
        $credentials = $this->resolveSumUpCredentials();

        if ($credentials['token'] === '') {
            return ['paid' => false, 'transaction_id' => null, 'status' => null];
        }

        $response = Http::withToken($credentials['token'])
            ->acceptJson()
            ->get('https://api.sumup.com/v0.1/checkouts/'.urlencode($checkoutId));

        if (! $response->successful()) {
            return ['paid' => false, 'transaction_id' => null, 'status' => null];
        }

        $reference = (string) ($response->json('checkout_reference') ?? '');
        if ($reference !== '' && $reference !== (string) $order->order_number) {
            return ['paid' => false, 'transaction_id' => null, 'status' => (string) ($response->json('status') ?? null)];
        }

        $paidAmount = (float) ($response->json('amount') ?? $response->json('total_amount') ?? 0);
        $currency = (string) ($response->json('currency') ?? $order->currency ?? 'EUR');
        if ($paidAmount > 0 && ! PaymentOrderVerifier::amountsMatch($order, $paidAmount, $currency)) {
            return ['paid' => false, 'transaction_id' => null, 'status' => (string) ($response->json('status') ?? null)];
        }

        $status = strtoupper((string) ($response->json('status') ?? ''));
        $transactionId = (string) (
            $response->json('transaction_id')
            ?? data_get($response->json(), 'transactions.0.id')
            ?? data_get($response->json(), 'transactions.0.transaction_id')
            ?? ''
        );

        $paid = in_array($status, ['PAID', 'SUCCESSFUL', 'SUCCESS'], true);
        if (! $paid) {
            $transactions = $response->json('transactions', []);
            if (is_array($transactions)) {
                foreach ($transactions as $transaction) {
                    $txStatus = strtoupper((string) (is_array($transaction) ? ($transaction['status'] ?? '') : ''));
                    if (in_array($txStatus, ['SUCCESSFUL', 'SUCCESS', 'PAID'], true)) {
                        $paid = true;
                        $transactionId = $transactionId !== ''
                            ? $transactionId
                            : (string) (is_array($transaction) ? ($transaction['id'] ?? $transaction['transaction_id'] ?? '') : '');
                        break;
                    }
                }
            }
        }

        return [
            'paid' => $paid,
            'transaction_id' => $transactionId !== '' ? $transactionId : ($paid ? $checkoutId : null),
            'status' => $status !== '' ? $status : null,
        ];
    }

    /**
     * @return array{external_id: string}|null
     */
    public function captureAndVerifyPayPalOrder(string $paypalOrderId, Order $order): ?array
    {
        $accessToken = $this->getPayPalAccessToken();
        $baseUrl = $this->payPalBaseUrl();

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($baseUrl.'/v2/checkout/orders/'.$paypalOrderId.'/capture');

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return null;
        }

        return PaymentOrderVerifier::verifyPayPalOrderPayload($payload, $order);
    }

    /**
     * @deprecated Use captureAndVerifyPayPalOrder()
     */
    public function capturePayPalOrder(string $paypalOrderId): ?string
    {
        $accessToken = $this->getPayPalAccessToken();
        $baseUrl = $this->payPalBaseUrl();

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post($baseUrl.'/v2/checkout/orders/'.$paypalOrderId.'/capture');

        if (! $response->successful()) {
            return null;
        }

        return (string) ($response->json('id') ?? $paypalOrderId);
    }

    /**
     * Signed return URL; provider-specific query params (session_id, token, …) are ignored by middleware.
     */
    public function signedPaymentReturnUrl(string $provider, Order $order): string
    {
        return URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => $provider, 'order' => $order->id],
        );
    }

    private function getPayPalAccessToken(): string
    {
        $clientId = (string) config('services.paypal.client_id');
        $secret = (string) config('services.paypal.secret');

        if ($clientId === '' || $secret === '') {
            throw new RuntimeException('PayPal ist nicht konfiguriert (PAYPAL_CLIENT_ID / PAYPAL_SECRET fehlen).');
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->payPalBaseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal Access Token konnte nicht abgerufen werden.');
        }

        return (string) $response->json('access_token');
    }

    private function payPalBaseUrl(): string
    {
        $mode = (string) config('services.paypal.mode', 'sandbox');

        return $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
