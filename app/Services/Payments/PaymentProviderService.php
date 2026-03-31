<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
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
                        'name' => $item->product_name . (filled($item->variant_name) ? ' ('.$item->variant_name.')' : ''),
                    ],
                ],
                'quantity' => (int) $item->quantity,
            ];
        }

        $payload = [
            'mode' => 'payment',
            'payment_method_types' => ['card', 'klarna', 'sofort', 'giropay'],
            'line_items' => $lineItems,
            'success_url' => route('payment.return', ['provider' => 'stripe', 'order' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
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
                    'return_url' => route('payment.return', ['provider' => 'paypal', 'order' => $order->id]),
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

    public function createSumUpCheckoutUrl(Order $order): string
    {
        $token = (string) config('services.sumup.token');
        $merchantCode = (string) config('services.sumup.merchant_code');

        if ($token === '' || $merchantCode === '') {
            throw new RuntimeException('SumUp ist nicht konfiguriert (SUMUP_TOKEN / SUMUP_MERCHANT_CODE fehlen).');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://api.sumup.com/v0.1/checkouts', [
                'checkout_reference' => (string) $order->order_number,
                'amount' => round((float) $order->total, 2),
                'currency' => strtoupper((string) ($order->currency ?: 'EUR')),
                'merchant_code' => $merchantCode,
                'description' => 'Bestellung '.$order->order_number,
                'return_url' => route('payment.return', ['provider' => 'sumup', 'order' => $order->id]),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('SumUp Checkout konnte nicht initialisiert werden.');
        }

        $url = $response->json('checkout_url');
        if (! is_string($url) || $url === '') {
            throw new RuntimeException('SumUp Checkout URL fehlt.');
        }

        return $url;
    }

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
