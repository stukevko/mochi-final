<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SumUp meldet Statusänderungen eines Checkouts (Server-zu-Server).
 *
 * Der Inhalt der Meldung wird NICHT vertraut: Wir nehmen nur die Checkout-ID,
 * suchen die passende Bestellung und fragen den echten Status bei SumUp ab.
 * Eine gefälschte Meldung kann deshalb nichts als „bezahlt“ markieren.
 */
class PaymentWebhookController extends Controller
{
    public function sumup(Request $request, PaymentCompletionService $completion): JsonResponse
    {
        $checkoutId = trim((string) ($request->input('id') ?? $request->input('checkout_id') ?? ''));

        if ($checkoutId === '' || strlen($checkoutId) > 100) {
            return response()->json(['ok' => true]);
        }

        $order = Order::query()
            ->where('payment_method', 'sumup')
            ->where('payment_data->sumup_checkout_id', $checkoutId)
            ->first();

        $result = null;
        if ($order) {
            try {
                $result = $completion->reconcileSumUp($order);
            } catch (Throwable $e) {
                report($e);

                // 5xx → SumUp versucht es später erneut.
                return response()->json(['ok' => false], 503);
            }
        }

        Log::channel('payments')->info('payment.webhook.received', [
            'provider' => 'sumup',
            'event_type' => (string) $request->input('event_type', 'unknown'),
            'order_id' => $order?->id,
            'result' => $result,
        ]);

        return response()->json(['ok' => true]);
    }
}
