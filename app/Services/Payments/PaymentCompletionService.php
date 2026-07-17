<?php

namespace App\Services\Payments;

use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\Inventory\StockService;
use App\Support\ShopErrorLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentCompletionService
{
    public function markPaidAndNotify(Order $order, string $provider, ?string $externalPaymentId = null): void
    {
        $paymentData = is_array($order->payment_data) ? $order->payment_data : [];
        $alreadyPaid = $order->payment_status === 'paid';

        $paymentData['provider'] = $provider;
        if ($externalPaymentId !== null && $externalPaymentId !== '') {
            $paymentData['external_payment_id'] = $externalPaymentId;
        }

        if (! $alreadyPaid) {
            app(StockService::class)->commitOrderStock($order);

            $order->forceFill([
                'status' => $order->status === 'pending' ? 'processing' : $order->status,
                'payment_status' => 'paid',
                'payment_id' => $externalPaymentId ?: $order->payment_id,
                'payment_data' => $paymentData,
            ])->save();
            $order->refresh();
        } else {
            $order->forceFill(['payment_data' => $paymentData])->save();
        }

        // sendNow: OrderConfirmed/AdminOrderNotification implementieren ShouldQueue —
        // Mail::send() würde sonst bei QUEUE_CONNECTION=database ohne Worker nie zustellen,
        // und customer_confirmation_sent_at würde fälschlich gesetzt.
        $customerEmail = $order->customerEmail();
        if (filled($customerEmail) && empty($paymentData['customer_confirmation_sent_at'])) {
            try {
                Mail::to($customerEmail)->sendNow(new OrderConfirmed($order));
                $paymentData['customer_confirmation_sent_at'] = now()->toIso8601String();
            } catch (Throwable $e) {
                ShopErrorLogger::report('checkout.place_order.customer_mail_failed', $e, [
                    'order_id' => $order->id,
                    'email' => $customerEmail,
                    'provider' => $provider,
                ]);
                Log::channel('checkout_stack')->error('payment.completed.customer_mail_failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'provider' => $provider,
                    'message' => $e->getMessage(),
                ]);
                report($e);
            }
        }

        $adminEmail = $this->resolveShopOrderNotificationEmail();
        if (filled($adminEmail) && empty($paymentData['admin_notification_sent_at'])) {
            try {
                Mail::to($adminEmail)->sendNow(new AdminOrderNotification($order));
                $paymentData['admin_notification_sent_at'] = now()->toIso8601String();
            } catch (Throwable $e) {
                ShopErrorLogger::report('checkout.place_order.admin_mail_failed', $e, [
                    'order_id' => $order->id,
                    'email' => $adminEmail,
                    'provider' => $provider,
                ]);
                Log::channel('checkout_stack')->error('payment.completed.admin_mail_failed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'provider' => $provider,
                    'message' => $e->getMessage(),
                ]);
                report($e);
            }
        }

        $order->forceFill(['payment_data' => $paymentData])->save();

        app(CartService::class)->clear();

        Log::channel('checkout_stack')->info('payment.completed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'provider' => $provider,
            'external_payment_id' => $externalPaymentId,
            'customer_confirmation_sent' => ! empty($paymentData['customer_confirmation_sent_at']),
            'admin_notification_sent' => ! empty($paymentData['admin_notification_sent_at']),
        ]);
    }

    private function resolveShopOrderNotificationEmail(): ?string
    {
        $fromSetting = Setting::get('order_notification_email');
        if (is_string($fromSetting) && $fromSetting !== '' && filter_var($fromSetting, FILTER_VALIDATE_EMAIL)) {
            return $fromSetting;
        }

        $fallback = (string) config('shop.order_notification_email');

        return filter_var($fallback, FILTER_VALIDATE_EMAIL) ? $fallback : null;
    }
}
