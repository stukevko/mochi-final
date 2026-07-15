<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentCompletionService;
use App\Services\Payments\PaymentProviderService;
use App\Support\PaymentOrderVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Throwable;

class PaymentCheckoutController extends Controller
{
    public function returnFromProvider(Request $request, string $provider, Order $order, PaymentProviderService $providers, PaymentCompletionService $completion): RedirectResponse
    {
        if ($provider === 'stripe') {
            $sessionId = (string) $request->query('session_id', '');
            $secret = (string) config('services.stripe.secret', '');

            if ($sessionId !== '' && $secret !== '') {
                try {
                    $client = new StripeClient($secret);
                    $session = $client->checkout->sessions->retrieve($sessionId, []);
                    $paymentIntent = (string) ($session->payment_intent ?? '');

                    if (PaymentOrderVerifier::verifyStripeSession($session, $order)) {
                        $completion->markPaidAndNotify($order, 'stripe', $paymentIntent !== '' ? $paymentIntent : null);
                    }
                } catch (Throwable $e) {
                    Log::channel('checkout_stack')->warning('payment.return.stripe.verify_failed', [
                        'order_id' => $order->id,
                        'session_id' => $sessionId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($provider === 'paypal') {
            $paypalOrderId = (string) $request->query('token', '');
            if ($paypalOrderId !== '') {
                $verified = $providers->captureAndVerifyPayPalOrder($paypalOrderId, $order);
                if ($verified !== null) {
                    $completion->markPaidAndNotify($order, 'paypal', $verified['external_id']);
                }
            }
        }

        if ($provider === 'sumup') {
            $checkoutId = (string) $request->query('checkout_id', '');
            if ($checkoutId === '') {
                $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
                $checkoutId = (string) ($paymentData['sumup_checkout_id'] ?? '');
            }

            if ($checkoutId === '') {
                return redirect()
                    ->route('checkout')
                    ->with('payment_error', 'SumUp-Zahlung konnte nicht bestätigt werden. Bitte erneut versuchen oder uns kontaktieren.');
            }

            $verification = $providers->verifySumUpCheckoutWithRetry($checkoutId, $order);

            if (! $verification['paid']) {
                Log::channel('checkout_stack')->warning('payment.return.sumup.not_paid', [
                    'order_id' => $order->id,
                    'checkout_id' => $checkoutId,
                    'status' => $verification['status'],
                ]);

                return redirect()
                    ->route('checkout')
                    ->with('payment_error', 'Die SumUp-Zahlung wurde nicht abgeschlossen. Bitte erneut versuchen.');
            }

            $completion->markPaidAndNotify(
                $order,
                'sumup',
                $verification['transaction_id'],
            );
        }

        $order->refresh();

        // Alle Provider: Success-Seite nur nach verifiziertem paid-Status (kein IDOR über Return-URL).
        if ($order->payment_status !== 'paid') {
            Log::channel('checkout_stack')->warning('payment.return.not_paid', [
                'order_id' => $order->id,
                'provider' => $provider,
                'payment_status' => $order->payment_status,
            ]);

            return redirect()
                ->route('checkout')
                ->with('payment_error', 'Die Zahlung wurde nicht bestätigt. Bitte erneut versuchen oder uns kontaktieren.');
        }

        session()->flash('shop_toast', [
            'message' => 'Zahlung erfolgreich — vielen Dank!',
            'type' => 'success',
        ]);

        return redirect()->temporarySignedRoute(
            'checkout.success',
            now()->addDays(7),
            ['orderNumber' => $order->order_number],
        );
    }

    public function cancelFromProvider(string $provider, Order $order): RedirectResponse
    {
        return redirect()
            ->route('checkout')
            ->with('payment_error', 'Zahlung fehlgeschlagen oder abgebrochen. Bitte probiere eine andere Methode.');
    }
}
