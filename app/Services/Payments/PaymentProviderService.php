<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Support\PaymentOrderVerifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class PaymentProviderService
{
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

        $redirectUrl = $this->signedPaymentReturnUrl('sumup', $order);

        $response = Http::withToken($credentials['token'])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post('https://api.sumup.com/v0.1/checkouts', [
                'checkout_reference' => (string) $order->order_number,
                'amount' => round((float) $order->total, 2),
                'currency' => strtoupper((string) ($order->currency ?: 'EUR')),
                'merchant_code' => $credentials['merchant_code'],
                'description' => 'Bestellung '.$order->order_number,
                // redirect_url: dorthin kommt der KUNDE zurück. return_url: Server-zu-Server-Meldung von
                // SumUp (Webhook) — greift auch, wenn der Kunde nach dem Bezahlen den Tab schließt.
                'redirect_url' => $redirectUrl,
                'return_url' => route('webhooks.payment.sumup'),
                // Checkout läuft ab, sobald die Warenreservierung endet → keine Zahlung für freigegebene Ware.
                'valid_until' => now()->addMinutes(PaymentCompletionService::RESERVATION_MINUTES)->toIso8601String(),
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

        try {
            $response = Http::withToken($credentials['token'])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->get('https://api.sumup.com/v0.1/checkouts/'.urlencode($checkoutId));
        } catch (\Throwable $e) {
            Log::channel('checkout_stack')->warning('sumup.checkout.verify_unreachable', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return ['paid' => false, 'transaction_id' => null, 'status' => null];
        }

        if (! $response->successful()) {
            return ['paid' => false, 'transaction_id' => null, 'status' => null];
        }

        $reference = (string) ($response->json('checkout_reference') ?? '');
        // Strikt: Der Checkout muss zu genau dieser Bestellung gehören (keine fremde checkout_id unterschieben).
        if ($reference !== (string) $order->order_number) {
            return ['paid' => false, 'transaction_id' => null, 'status' => (string) ($response->json('status') ?? null)];
        }

        $paidAmount = (float) ($response->json('amount') ?? $response->json('total_amount') ?? 0);
        $currency = (string) ($response->json('currency') ?? $order->currency ?? 'EUR');
        if (! PaymentOrderVerifier::amountsMatch($order, $paidAmount, $currency)) {
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
     * Signierte Rückkehr-URL für den Kunden (SumUp hängt ggf. checkout_id an — wird von der Signaturprüfung ignoriert).
     */
    public function signedPaymentReturnUrl(string $provider, Order $order): string
    {
        return URL::temporarySignedRoute(
            'payment.return',
            now()->addDay(),
            ['provider' => $provider, 'order' => $order->id],
        );
    }
}
