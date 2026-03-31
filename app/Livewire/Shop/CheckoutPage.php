<?php

namespace App\Livewire\Shop;

use App\Mail\AdminOrderNotification;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\Payments\PaymentProviderService;
use App\Support\MoneyFormatter;
use App\Support\ShopErrorLogger;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;
use Livewire\Component;

class CheckoutPage extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $street = '';
    public string $zip = '';
    public string $city = '';
    public string $country = 'DE';
    public string $notes = '';
    public string $payment_method = 'invoice';

    public function mount(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->first_name = (string) ($user->name ?? '');
            $this->email = (string) ($user->email ?? '');
        }
    }

    public function placeOrder(CartService $cartService): mixed
    {
        $this->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'street' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'city' => 'required|string|max:120',
            'country' => 'required|string|size:2',
            'notes' => 'nullable|string|max:2000',
            'payment_method' => 'required|string|max:50',
        ]);

        // Preise kommen ausschließlich aus getContent() → Datenbank; keine Request-/Session-Preise vertrauen.
        $cartItems = $cartService->getContent();
        if ($cartItems === []) {
            $this->addError('cart', 'Dein Warenkorb ist leer.');
            return null;
        }

        $rateKey = 'checkout-place-order:'.sha1((string) (request()->ip() ?? 'unknown'));

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->addError(
                'rate_limit',
                'Zu viele Bestellversuche. Bitte in '.RateLimiter::availableIn($rateKey).' Sekunden erneut versuchen.',
            );

            return null;
        }

        RateLimiter::hit($rateKey, 60);

        try {
            $order = DB::transaction(function () use ($cartItems) {
                $grossSubtotal = 0.0;
                $taxRate = $this->getTaxRate();
                $netDivisor = 1 + ($taxRate / 100);
                $paymentStatus = 'pending';

                $order = new Order;
                $order->forceFill([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => Auth::id(),
                    'status' => 'pending',
                    'payment_status' => $paymentStatus,
                    'payment_method' => $this->payment_method,
                    'subtotal' => 0,
                    'tax' => 0,
                    'shipping_cost' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'billing_address' => $this->addressPayload(),
                    'shipping_address' => $this->addressPayload(),
                    'notes' => $this->notes ?: null,
                    'currency' => (string) Setting::get('currency', 'EUR'),
                ])->save();
                $order->refresh();

                foreach ($cartItems as $item) {
                    $quantity = (int) ($item['quantity'] ?? 1);
                    $productId = (int) ($item['product_id'] ?? 0);
                    $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;

                    /** @var Product|null $product */
                    $product = Product::query()->where('is_active', true)->find($productId);
                    /** @var ProductVariant|null $variant */
                    $variant = $variantId
                        ? ProductVariant::query()
                            ->where('is_active', true)
                            ->with('attributeValues.attribute')
                            ->find($variantId)
                        : null;

                    if (! $product) {
                        throw new \RuntimeException('Ein Produkt aus dem Warenkorb ist nicht mehr verfügbar.');
                    }

                    if ($variant && (int) $variant->product_id !== (int) $product->id) {
                        throw new \RuntimeException('Ungültige Warenkorb-Zeile (Variante passt nicht zum Produkt).');
                    }

                    if ($variant) {
                        if ($variant->stock < $quantity) {
                            throw new \RuntimeException("Variante {$item['name']} ist nicht ausreichend auf Lager.");
                        }
                        $variant->decrement('stock', $quantity);
                    } elseif ($product) {
                        if ((int) $product->stock < $quantity) {
                            throw new \RuntimeException("Produkt {$item['name']} ist nicht ausreichend auf Lager.");
                        }
                        $product->decrement('stock', $quantity);
                    }

                    $unitPrice = $variant
                        ? (float) $variant->current_price
                        : (float) $product->current_price;

                    $declaredUnit = isset($item['price']) ? round((float) $item['price'], 2) : null;
                    if ($declaredUnit !== null && abs($declaredUnit - round($unitPrice, 2)) > 0.01) {
                        throw new \RuntimeException('Preisabweichung — bitte Warenkorb leeren und neu füllen.');
                    }

                    $lineTotal = $unitPrice * $quantity;
                    $grossSubtotal += $lineTotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId ?: null,
                        'product_variant_id' => $variantId,
                        'product_name' => $product->name,
                        'variant_name' => $variant ? (string) $variant->name : '',
                        'sku' => $variant?->sku ?? $product?->sku,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ]);
                }

                $netSubtotal = round($grossSubtotal / ($netDivisor > 0 ? $netDivisor : 1), 2);
                $taxAmount = round($grossSubtotal - $netSubtotal, 2);

                $order->forceFill([
                    'subtotal' => $netSubtotal,
                    'tax' => $taxAmount,
                    'total' => round($grossSubtotal, 2),
                ])->save();

                return $order;
            });
        } catch (Throwable $e) {
            ShopErrorLogger::report('checkout.place_order.failed', $e, [
                'email' => $this->email,
                'ip' => request()->ip(),
            ]);
            Log::channel('checkout_stack')->error('checkout.place_order.failed', [
                'email' => $this->email,
                'ip' => request()->ip(),
                'message' => $e->getMessage(),
            ]);
            report($e);
            $this->addError('cart', 'Die Bestellung konnte nicht abgeschlossen werden. Bitte versuche es erneut oder kontaktiere uns.');

            return null;
        }

        if ($this->isOnlinePaymentMethod($this->payment_method)) {
            try {
                $providerService = app(PaymentProviderService::class);
                $checkoutUrl = match ($this->payment_method) {
                    'paypal' => $providerService->createPayPalCheckoutUrl($order),
                    'sumup' => $providerService->createSumUpCheckoutUrl($order),
                    default => $providerService->createStripeCheckoutUrl($order),
                };

                return redirect()->away($checkoutUrl);
            } catch (Throwable $e) {
                ShopErrorLogger::report('checkout.place_order.payment_provider_failed', $e, [
                    'order_id' => $order->id ?? null,
                    'provider' => $this->payment_method,
                ]);
                Log::channel('checkout_stack')->error('checkout.place_order.payment_provider_failed', [
                    'order_id' => $order->id ?? null,
                    'provider' => $this->payment_method,
                    'message' => $e->getMessage(),
                ]);
                report($e);
                $this->addError('payment_method', 'Zahlung fehlgeschlagen, bitte probiere eine andere Methode.');

                return null;
            }
        }

        return $this->completeOfflineOrder($order, $cartService);
    }

    public function getItemsProperty(): array
    {
        return app(CartService::class)->getContent();
    }

    public function getTotalProperty(): float
    {
        return app(CartService::class)->getTotal();
    }

    public function getPaymentMethodsProperty(): array
    {
        $gateways = PaymentGateway::query()->active()->get(['code', 'name'])->toArray();

        if ((bool) Setting::get('prepayment_enabled', false)) {
            $gateways[] = ['code' => 'prepayment', 'name' => 'Vorkasse / Überweisung'];
        }

        $preferred = [
            'card' => 'Kreditkarte (Stripe)',
            'klarna' => 'Klarna (Stripe)',
            'stripe' => 'Stripe Checkout',
            'paypal' => 'PayPal',
            'sumup' => 'SumUp',
            'prepayment' => 'Vorkasse / Überweisung',
            'invoice' => 'Kauf auf Rechnung',
        ];

        $normalized = [];
        foreach ($gateways as $gateway) {
            $code = (string) ($gateway['code'] ?? '');
            if ($code === '') {
                continue;
            }

            $normalized[$code] = [
                'code' => $code,
                'name' => (string) ($gateway['name'] ?? ($preferred[$code] ?? strtoupper($code))),
            ];
        }

        foreach ($preferred as $code => $label) {
            if (! isset($normalized[$code])) {
                $normalized[$code] = ['code' => $code, 'name' => $label];
            }
        }

        return array_values($normalized);
    }

    public function formatPrice(float $price): string
    {
        return MoneyFormatter::format($price);
    }

    public function getTaxRate(): float
    {
        return max(0.0, (float) Setting::get('tax_rate', 19));
    }

    public function getCurrencySymbol(): string
    {
        return (string) Setting::get('currency_symbol', '€');
    }

    public function getNetSubtotalProperty(): float
    {
        $gross = $this->getTotalProperty();
        $divisor = 1 + ($this->getTaxRate() / 100);

        return round($gross / ($divisor > 0 ? $divisor : 1), 2);
    }

    public function getTaxAmountProperty(): float
    {
        return round($this->getTotalProperty() - $this->netSubtotal, 2);
    }

    public function getPrepaymentBankDataProperty(): array
    {
        return [
            'holder' => (string) Setting::get('prepayment_bank_account_holder', ''),
            'bank' => (string) Setting::get('prepayment_bank_name', ''),
            'iban' => (string) Setting::get('prepayment_iban', ''),
            'bic' => (string) Setting::get('prepayment_bic', ''),
        ];
    }

    /**
     * E-Mail für Shop-Benachrichtigungen: Setting `order_notification_email`, sonst `config('shop.order_notification_email')`.
     */
    public static function resolveShopOrderNotificationEmail(): ?string
    {
        $fromSetting = Setting::get('order_notification_email');
        if (is_string($fromSetting) && $fromSetting !== '' && filter_var($fromSetting, FILTER_VALIDATE_EMAIL)) {
            return $fromSetting;
        }

        $fallback = config('shop.order_notification_email');
        if (is_string($fallback) && $fallback !== '' && filter_var($fallback, FILTER_VALIDATE_EMAIL)) {
            return $fallback;
        }

        return null;
    }

    protected function addressPayload(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }

    protected function sendOrderMail(string $recipient, MailableContract $mail): void
    {
        if ($mail instanceof ShouldQueue && $this->canQueueMail()) {
            Mail::to($recipient)->later(now()->addSeconds(2), $mail);

            return;
        }

        Mail::to($recipient)->send($mail);
    }

    protected function canQueueMail(): bool
    {
        $queueConnection = (string) config('queue.default', 'sync');

        return $queueConnection !== '' && strtolower($queueConnection) !== 'sync';
    }

    protected function isOnlinePaymentMethod(string $method): bool
    {
        return in_array($method, ['stripe', 'card', 'klarna', 'paypal', 'sumup'], true);
    }

    protected function completeOfflineOrder(Order $order, CartService $cartService): mixed
    {
        try {
            $cartService->clear();
        } catch (Throwable $e) {
            ShopErrorLogger::report('checkout.place_order.clear_cart_failed', $e, [
                'order_id' => $order->id ?? null,
                'email' => $this->email,
            ]);
            Log::channel('checkout_stack')->error('checkout.place_order.clear_cart_failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => $e->getMessage(),
            ]);
            report($e);
        }

        $this->dispatch('cartUpdated');
        session()->flash('completed_order_id', $order->id);

        $mailDriver = (string) config('mail.default');
        $mailHints = [];

        $confirmEmail = data_get($order->billing_address, 'email');
        if (is_string($confirmEmail) && filter_var($confirmEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                $this->sendOrderMail($confirmEmail, new OrderConfirmed($order));
            } catch (Throwable $e) {
                ShopErrorLogger::report('checkout.place_order.customer_mail_failed', $e, [
                    'order_id' => $order->id ?? null,
                    'email' => $confirmEmail,
                ]);
                report($e);
                $mailHints[] = 'Die Bestellbestätigung per E-Mail konnte nicht gesendet werden. Wir haben deine Bestellung trotzdem erhalten.';
            }
        } else {
            $mailHints[] = 'Keine gültige E-Mail in der Bestellung — Bestellbestätigung wurde nicht versendet.';
        }

        $shopNotify = self::resolveShopOrderNotificationEmail();
        if ($shopNotify !== null) {
            try {
                $this->sendOrderMail($shopNotify, new AdminOrderNotification($order));
            } catch (Throwable $e) {
                ShopErrorLogger::report('checkout.place_order.admin_mail_failed', $e, [
                    'order_id' => $order->id ?? null,
                    'email' => $shopNotify,
                ]);
                report($e);
                $mailHints[] = 'Die interne Bestellbenachrichtigung konnte nicht gesendet werden.';
            }
        }

        if ($mailDriver === 'log') {
            $mailHints[] = 'Hinweis: MAIL_MAILER=log — E-Mails landen nur in storage/logs/laravel.log, nicht im Posteingang.';
        }

        if ($mailHints !== []) {
            session()->flash('order_mail_hints', $mailHints);
        }

        return redirect()->temporarySignedRoute(
            'checkout.success',
            now()->addDays(7),
            ['orderNumber' => $order->order_number],
        );
    }

    public function render()
    {
        return view('livewire.shop.checkout-page');
    }
}
