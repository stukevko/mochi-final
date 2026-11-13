<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentCompletionService;
use App\Services\Payments\PaymentProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Rückkehr des Kunden von der SumUp-Bezahlseite (signierte URL).
 */
class PaymentCheckoutController extends Controller
{
    public function returnFromProvider(Request $request, string $provider, Order $order, PaymentProviderService $providers, PaymentCompletionService $completion): RedirectResponse
    {
        if (! $order->isPaid()) {
            // Nur die bei der Bestellung gespeicherte checkout_id zählt — nie eine aus der URL.
            $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
            $checkoutId = (string) ($paymentData['sumup_checkout_id'] ?? '');

            if ($checkoutId !== '') {
                $verification = $providers->verifySumUpCheckoutWithRetry($checkoutId, $order);

                if ($verification['paid']) {
                    $completion->markPaidAndNotify($order, 'sumup', $verification['transaction_id']);
                } else {
                    Log::channel('checkout_stack')->warning('payment.return.sumup.not_paid', [
                        'order_id' => $order->id,
                        'checkout_id' => $checkoutId,
                        'status' => $verification['status'],
                    ]);

                    // Endgültig gescheitert → Reservierung sofort freigeben, damit ein neuer Versuch klappt.
                    if (in_array(strtoupper((string) $verification['status']), ['FAILED', 'EXPIRED'], true)) {
                        $completion->cancelUnpaid($order, 'sumup:'.strtolower((string) $verification['status']));
                    }
                }
            }

            $order->refresh();
        }

        if (! $order->isPaid()) {
            return redirect()
                ->route('checkout')
                ->with('payment_error', 'Die Zahlung wurde nicht abgeschlossen. Es wurde nichts abgebucht — bitte versuche es erneut.');
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
}
