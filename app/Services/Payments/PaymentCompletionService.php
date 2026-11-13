<?php

namespace App\Services\Payments;

use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Einziger Ort, an dem eine Bestellung „bezahlt“ oder „verfallen“ wird.
 *
 * Wird von drei Seiten aufgerufen — Rückkehr des Kunden, SumUp-Webhook und
 * dem Abgleich-Job (shop:reconcile-payments). Alle Wege sind idempotent.
 */
class PaymentCompletionService
{
    /** Minuten, die eine unbezahlte Bestellung Ware reserviert (= Gültigkeit des SumUp-Checkouts). */
    public const RESERVATION_MINUTES = 30;

    public function markPaidAndNotify(Order $order, string $provider, ?string $externalPaymentId = null, bool $clearCart = true): void
    {
        // Rückkehr-Seite und Webhook treffen oft gleichzeitig ein → nacheinander abarbeiten.
        Cache::lock('order-payment-'.$order->id, 30)->block(10, function () use ($order, $provider, $externalPaymentId): void {
            $order->refresh();

            $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
            $paymentData['provider'] = $provider;
            if (filled($externalPaymentId)) {
                $paymentData['external_payment_id'] = $externalPaymentId;
            }

            if (! $order->isPaid()) {
                // Geld ist eingegangen: Bestellung MUSS als bezahlt gelten, auch wenn die Reservierung
                // inzwischen verfallen und die Ware weg ist. Dann Hinweis für den Admin statt Fehler.
                try {
                    app(StockService::class)->commitOrderStock($order);
                    $order->refresh();
                    $paymentData = array_merge(is_array($order->payment_data) ? $order->payment_data : [], $paymentData);
                } catch (Throwable $e) {
                    $paymentData['stock_conflict'] = $e->getMessage();
                    Log::channel('checkout_stack')->critical('payment.completed.stock_conflict', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'message' => $e->getMessage(),
                    ]);
                }

                unset($paymentData['auto_cancelled_at']);

                $order->forceFill([
                    'status' => in_array($order->status, ['pending', 'cancelled'], true) ? 'processing' : $order->status,
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'payment_id' => $externalPaymentId ?: $order->payment_id,
                    'payment_data' => $paymentData,
                ])->save();
            } else {
                $order->forceFill(['payment_data' => $paymentData])->save();
            }

            $order->ensureInvoiceNumber();

            $this->notify($order);

            Log::channel('checkout_stack')->info('payment.completed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'provider' => $provider,
                'external_payment_id' => $externalPaymentId,
            ]);
        });

        if ($clearCart) {
            try {
                app(CartService::class)->clear();
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Unbezahlte Bestellung verfallen lassen und reservierte Ware wieder freigeben.
     */
    public function cancelUnpaid(Order $order, string $reason): void
    {
        Cache::lock('order-payment-'.$order->id, 30)->block(10, function () use ($order, $reason): void {
            $order->refresh();

            if ($order->isPaid() || $order->status !== 'pending') {
                return;
            }

            app(StockService::class)->restoreOrderStock($order);
            $order->refresh();

            $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
            $paymentData['auto_cancelled_at'] = now()->toIso8601String();
            $paymentData['auto_cancel_reason'] = $reason;

            $order->forceFill([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'payment_data' => $paymentData,
            ])->save();

            Log::channel('checkout_stack')->info('payment.cancelled_unpaid', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Fragt SumUp nach dem echten Stand und zieht die Bestellung nach.
     *
     * @return 'paid'|'cancelled'|'pending'
     */
    public function reconcileSumUp(Order $order, bool $clearCart = false): string
    {
        if ($order->isPaid()) {
            return 'paid';
        }

        $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
        $checkoutId = (string) ($paymentData['sumup_checkout_id'] ?? '');

        $result = $checkoutId !== ''
            ? app(PaymentProviderService::class)->verifySumUpCheckout($checkoutId, $order)
            : ['paid' => false, 'transaction_id' => null, 'status' => 'MISSING'];

        if ($result['paid']) {
            $this->markPaidAndNotify($order, 'sumup', $result['transaction_id'], $clearCart);

            return 'paid';
        }

        $status = strtoupper((string) ($result['status'] ?? ''));
        $age = $order->created_at?->diffInMinutes(now()) ?? 0;

        $definitelyDead = in_array($status, ['FAILED', 'EXPIRED'], true)
            // Kein Checkout angelegt (Abbruch vor der Weiterleitung) — kurze Schonfrist für laufende Requests.
            || ($status === 'MISSING' && $age >= 3)
            // SumUp meldet weiter „offen“, aber der Checkout ist längst abgelaufen.
            || ($status !== '' && $age >= self::RESERVATION_MINUTES + 5)
            // SumUp dauerhaft nicht erreichbar: Ware nicht ewig blockieren.
            || $age >= 24 * 60;

        if ($definitelyDead) {
            $this->cancelUnpaid($order, 'sumup:'.($status !== '' ? strtolower($status) : 'unreachable'));

            return 'cancelled';
        }

        return 'pending';
    }

    private function notify(Order $order): void
    {
        $paymentData = is_array($order->payment_data) ? $order->payment_data : [];

        // Mailfehler dürfen die Zahlung nie „rückgängig“ wirken lassen — beim nächsten Aufruf wird erneut versucht.
        $customerEmail = $order->customerEmail();
        if (filled($customerEmail) && empty($paymentData['customer_confirmation_sent_at'])) {
            try {
                Mail::to($customerEmail)->send(new OrderConfirmed($order));
                $paymentData['customer_confirmation_sent_at'] = now()->toIso8601String();
            } catch (Throwable $e) {
                report($e);
            }
        }

        $adminEmail = $this->resolveShopOrderNotificationEmail();
        if (filled($adminEmail) && empty($paymentData['admin_notification_sent_at'])) {
            try {
                Mail::to($adminEmail)->send(new AdminOrderNotification($order));
                $paymentData['admin_notification_sent_at'] = now()->toIso8601String();
            } catch (Throwable $e) {
                report($e);
            }
        }

        $order->forceFill(['payment_data' => $paymentData])->save();
    }

    private function resolveShopOrderNotificationEmail(): ?string
    {
        foreach (['order_notification_email', 'shop_email'] as $key) {
            $value = Setting::get($key);
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        $fallback = (string) config('shop.order_notification_email');

        return filter_var($fallback, FILTER_VALIDATE_EMAIL) ? $fallback : null;
    }
}
