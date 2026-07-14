<?php

namespace App\Services\Payments;

use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        $customerEmail = $order->customerEmail();
        if (filled($customerEmail) && empty($paymentData['customer_confirmation_sent_at'])) {
            Mail::to($customerEmail)->send(new OrderConfirmed($order));
            $paymentData['customer_confirmation_sent_at'] = now()->toIso8601String();
        }

        $adminEmail = $this->resolveShopOrderNotificationEmail();
        if (filled($adminEmail) && empty($paymentData['admin_notification_sent_at'])) {
            Mail::to($adminEmail)->send(new AdminOrderNotification($order));
            $paymentData['admin_notification_sent_at'] = now()->toIso8601String();
        }

        $order->forceFill(['payment_data' => $paymentData])->save();

        app(CartService::class)->clear();

        Log::channel('checkout_stack')->info('payment.completed', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'provider' => $provider,
            'external_payment_id' => $externalPaymentId,
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
