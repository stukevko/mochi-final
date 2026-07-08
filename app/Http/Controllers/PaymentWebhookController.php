<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentCompletionService;
use App\Support\PaymentOrderVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class PaymentWebhookController extends Controller
{
    public function stripe(Request $request, PaymentCompletionService $completion): JsonResponse
    {
        $secret = (string) config('services.stripe.webhook_secret');
        $payload = (string) $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');

        if ($secret === '' || $sigHeader === '') {
            $this->logWebhook('stripe', 'config_or_signature_missing', null);

            return response()->json(['ok' => false, 'message' => 'Stripe webhook not configured'], 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::channel('checkout_stack')->warning('webhook.stripe.invalid_signature', ['message' => $e->getMessage()]);
            $this->logWebhook('stripe', 'invalid_signature', null);

            return response()->json(['ok' => false], 400);
        }

        $type = (string) ($event->type ?? '');
        $loggedOrderId = null;

        if ($type === 'checkout.session.completed') {
            $sessionPayload = (array) $event->data->object;
            $orderId = (int) (($sessionPayload['metadata']['order_id'] ?? $sessionPayload['client_reference_id'] ?? 0));
            $paymentIntent = (string) ($sessionPayload['payment_intent'] ?? '');
            $loggedOrderId = $orderId > 0 ? $orderId : null;

            $order = Order::query()->find($orderId);
            if ($order && $this->verifyStripeSessionPayload($sessionPayload, $order)) {
                $completion->markPaidAndNotify($order, 'stripe', $paymentIntent !== '' ? $paymentIntent : null);
            }
        }

        if ($type === 'payment_intent.succeeded') {
            $intent = (array) $event->data->object;
            $orderId = (int) (($intent['metadata']['order_id'] ?? 0));
            $intentId = (string) ($intent['id'] ?? '');
            $loggedOrderId = $orderId > 0 ? $orderId : null;

            $order = Order::query()->find($orderId);
            if ($order && $this->verifyStripeIntentPayload($intent, $order)) {
                $completion->markPaidAndNotify($order, 'stripe', $intentId !== '' ? $intentId : null);
            }
        }

        $this->logWebhook('stripe', $type !== '' ? $type : 'unknown', $loggedOrderId);

        return response()->json(['ok' => true]);
    }

    public function paypal(Request $request, PaymentCompletionService $completion): JsonResponse
    {
        if (! $this->verifyPayPalSignature($request)) {
            $this->logWebhook('paypal', 'invalid_signature', null);

            return response()->json(['ok' => false], 400);
        }

        $eventType = (string) $request->input('event_type', '');
        $resource = (array) $request->input('resource', []);

        if (in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
            $orderId = (int) (
                data_get($resource, 'purchase_units.0.custom_id')
                ?? data_get($resource, 'custom_id')
                ?? 0
            );
            $externalId = (string) (data_get($resource, 'id') ?? '');

            $order = Order::query()->find($orderId);
            if ($order && PaymentOrderVerifier::verifyPayPalWebhookResource($resource, $order)) {
                $completion->markPaidAndNotify($order, 'paypal', $externalId !== '' ? $externalId : null);
            }

            $this->logWebhook('paypal', $eventType !== '' ? $eventType : 'unknown', $orderId > 0 ? $orderId : null);
        } else {
            $this->logWebhook('paypal', $eventType !== '' ? $eventType : 'unknown', null);
        }

        return response()->json(['ok' => true]);
    }

    private function logWebhook(string $provider, string $eventType, ?int $orderId): void
    {
        Log::channel('payments')->info('payment.webhook.received', [
            'provider' => $provider,
            'event_type' => $eventType,
            'order_id' => $orderId,
        ]);
    }

    private function verifyPayPalSignature(Request $request): bool
    {
        $clientId = (string) config('services.paypal.client_id');
        $secret = (string) config('services.paypal.secret');
        $webhookId = (string) config('services.paypal.webhook_id');

        if ($clientId === '' || $secret === '' || $webhookId === '') {
            return false;
        }

        $baseUrl = (string) config('services.paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $tokenResponse->successful()) {
            return false;
        }

        $token = (string) $tokenResponse->json('access_token', '');
        if ($token === '') {
            return false;
        }

        $verification = Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl.'/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $request->header('paypal-auth-algo'),
                'cert_url' => $request->header('paypal-cert-url'),
                'transmission_id' => $request->header('paypal-transmission-id'),
                'transmission_sig' => $request->header('paypal-transmission-sig'),
                'transmission_time' => $request->header('paypal-transmission-time'),
                'webhook_id' => $webhookId,
                'webhook_event' => $request->all(),
            ]);

        return $verification->successful() && (string) $verification->json('verification_status') === 'SUCCESS';
    }

    /**
     * @param  array<string, mixed>  $sessionPayload
     */
    private function verifyStripeSessionPayload(array $sessionPayload, Order $order): bool
    {
        if ((string) ($sessionPayload['payment_status'] ?? '') !== 'paid') {
            return false;
        }

        $metadataOrderId = (int) ($sessionPayload['metadata']['order_id'] ?? 0);
        if ($metadataOrderId !== (int) $order->id) {
            return false;
        }

        $amountTotal = (int) ($sessionPayload['amount_total'] ?? 0);
        $expectedCents = (int) round((float) $order->total * 100);

        if ($amountTotal !== $expectedCents) {
            return false;
        }

        $currency = strtoupper((string) ($sessionPayload['currency'] ?? ''));

        return PaymentOrderVerifier::amountsMatch($order, (float) $order->total, $currency);
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function verifyStripeIntentPayload(array $intent, Order $order): bool
    {
        $metadataOrderId = (int) ($intent['metadata']['order_id'] ?? 0);
        if ($metadataOrderId !== (int) $order->id) {
            return false;
        }

        $amount = (int) ($intent['amount'] ?? 0);
        $expectedCents = (int) round((float) $order->total * 100);

        if ($amount !== $expectedCents) {
            return false;
        }

        return PaymentOrderVerifier::amountsMatch(
            $order,
            (float) $order->total,
            (string) ($intent['currency'] ?? 'eur'),
        );
    }
}
