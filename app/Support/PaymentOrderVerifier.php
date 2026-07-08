<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

final class PaymentOrderVerifier
{
    public static function amountsMatch(Order $order, float $paidAmount, string $currency): bool
    {
        $expectedTotal = round((float) $order->total, 2);
        $paid = round($paidAmount, 2);
        $expectedCurrency = strtoupper((string) ($order->currency ?: 'EUR'));
        $paidCurrency = strtoupper(trim($currency));

        return abs($expectedTotal - $paid) <= 0.01 && $expectedCurrency === $paidCurrency;
    }

    /**
     * @param  array<string, mixed>  $paypalOrder
     * @return array{external_id: string}|null
     */
    public static function verifyPayPalOrderPayload(array $paypalOrder, Order $order): ?array
    {
        if (strtoupper((string) ($paypalOrder['status'] ?? '')) !== 'COMPLETED') {
            return null;
        }

        $unit = is_array($paypalOrder['purchase_units'][0] ?? null)
            ? $paypalOrder['purchase_units'][0]
            : null;

        if ($unit === null) {
            return null;
        }

        $customId = (int) ($unit['custom_id'] ?? 0);
        if ($customId !== (int) $order->id) {
            self::logMismatch('paypal', $order, 'custom_id mismatch');

            return null;
        }

        $capture = is_array($unit['payments']['captures'][0] ?? null)
            ? $unit['payments']['captures'][0]
            : null;

        if ($capture === null) {
            return null;
        }

        $amount = (float) ($capture['amount']['value'] ?? 0);
        $currency = (string) ($capture['amount']['currency_code'] ?? 'EUR');

        if (! self::amountsMatch($order, $amount, $currency)) {
            self::logMismatch('paypal', $order, 'amount mismatch');

            return null;
        }

        $externalId = (string) ($capture['id'] ?? $paypalOrder['id'] ?? '');

        return $externalId !== '' ? ['external_id' => $externalId] : null;
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    public static function verifyPayPalWebhookResource(array $resource, Order $order): bool
    {
        $customId = (int) (
            data_get($resource, 'purchase_units.0.custom_id')
            ?? data_get($resource, 'custom_id')
            ?? 0
        );

        if ($customId !== (int) $order->id) {
            self::logMismatch('paypal_webhook', $order, 'custom_id mismatch');

            return false;
        }

        $amount = (float) (
            data_get($resource, 'purchase_units.0.amount.value')
            ?? data_get($resource, 'amount.value')
            ?? 0
        );
        $currency = (string) (
            data_get($resource, 'purchase_units.0.amount.currency_code')
            ?? data_get($resource, 'amount.currency_code')
            ?? 'EUR'
        );

        if (! self::amountsMatch($order, $amount, $currency)) {
            self::logMismatch('paypal_webhook', $order, 'amount mismatch');

            return false;
        }

        return true;
    }

    public static function verifyStripeSession(object $session, Order $order): bool
    {
        $metadataOrderId = (int) (($session->metadata->order_id ?? 0));
        if ($metadataOrderId !== (int) $order->id) {
            self::logMismatch('stripe', $order, 'metadata order_id mismatch');

            return false;
        }

        if ((string) ($session->payment_status ?? '') !== 'paid') {
            return false;
        }

        $amountTotal = (int) ($session->amount_total ?? 0);
        $expectedCents = (int) round((float) $order->total * 100);

        if ($amountTotal !== $expectedCents) {
            self::logMismatch('stripe', $order, 'amount_total mismatch');

            return false;
        }

        $currency = strtoupper((string) ($session->currency ?? 'eur'));
        $expectedCurrency = strtolower((string) ($order->currency ?: 'EUR'));

        if ($currency !== $expectedCurrency) {
            self::logMismatch('stripe', $order, 'currency mismatch');

            return false;
        }

        return true;
    }

    private static function logMismatch(string $provider, Order $order, string $reason): void
    {
        Log::channel('checkout_stack')->warning('payment.verification.failed', [
            'provider' => $provider,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'reason' => $reason,
        ]);
    }
}
