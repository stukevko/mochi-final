<?php

namespace App\Livewire\Shop;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\Inventory\StockService;
use App\Services\Payments\PaymentCompletionService;
use App\Services\Payments\PaymentProviderService;
use App\Services\TurnstileVerifier;
use App\Support\MoneyFormatter;
use App\Support\ShippingCalculator;
use App\Support\ShopErrorLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Throwable;

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

    public string $payment_method = 'sumup';

    public bool $accepted_legal = false;

    public string $turnstileToken = '';

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
            'accepted_legal' => 'accepted',
        ]);

        $rateKey = 'checkout-place-order:'.sha1((string) (request()->ip() ?? 'unknown'));

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->addError(
                'rate_limit',
                'Zu viele Bestellversuche. Bitte in '.RateLimiter::availableIn($rateKey).' Sekunden erneut versuchen.',
            );

            return null;
        }

        // Auch fehlgeschlagene Turnstile-Versuche zählen — sonst Bypass des Limits.
        RateLimiter::hit($rateKey, 60);

        if (! TurnstileVerifier::verify($this->turnstileToken, request()->ip())) {
            $this->addError('turnstileToken', 'Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden und erneut versuchen.');

            return null;
        }

        // Vorheriger, nicht abgeschlossener Versuch dieses Kunden: erst klären, sonst blockiert
        // dessen Reservierung den neuen Versuch (z. B. beim letzten Exemplar).
        $previousId = session('pending_order_id');
        if ($previousId) {
            $previous = Order::query()->find($previousId);
            if ($previous && $previous->payment_method === 'sumup' && $previous->status === 'pending') {
                try {
                    $completion = app(PaymentCompletionService::class);
                    $state = $completion->reconcileSumUp($previous, clearCart: true);

                    if ($state === 'paid') {
                        session()->forget('pending_order_id');
                        $this->dispatch('cartUpdated');

                        return redirect()->temporarySignedRoute(
                            'checkout.success',
                            now()->addDays(7),
                            ['orderNumber' => $previous->order_number],
                        );
                    }

                    if ($state === 'pending') {
                        $completion->cancelUnpaid($previous, 'superseded_by_new_attempt');
                    }
                } catch (Throwable $e) {
                    report($e);
                }
            }
            session()->forget('pending_order_id');
        }

        // Preise kommen ausschließlich aus getContent() → Datenbank; keine Request-/Session-Preise vertrauen.
        $cartItems = $cartService->getContent();
        if ($cartItems === []) {
            $this->addError('cart', 'Dein Warenkorb ist leer.');

            return null;
        }

        $this->payment_method = 'sumup';
        if ($this->paymentMethods === []) {
            $this->addError('payment_method', 'Online-Zahlung ist noch nicht eingerichtet. Bitte kontaktiere uns.');

            return null;
        }

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
                    'terms_accepted_at' => now(),
                ])->save();
                $order->refresh();

                foreach ($cartItems as $item) {
                    $quantity = (int) ($item['quantity'] ?? 1);
                    $productId = (int) ($item['product_id'] ?? 0);
                    $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;

                    /** @var Product|null $product */
                    $product = Product::query()->where('is_active', true)->lockForUpdate()->find($productId);
                    /** @var ProductVariant|null $variant */
                    $variant = $variantId
                        ? ProductVariant::query()
                            ->where('is_active', true)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
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
                    } elseif ((int) $product->stock < $quantity) {
                        throw new \RuntimeException("Produkt {$item['name']} ist nicht ausreichend auf Lager.");
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

                // Alle Beträge brutto; Netto/MwSt. werden aus der Gesamtsumme (Ware + Versand) herausgerechnet.
                $shippingCost = ShippingCalculator::costFor($grossSubtotal);
                $grossTotal = round($grossSubtotal + $shippingCost, 2);
                $netTotal = round($grossTotal / ($netDivisor > 0 ? $netDivisor : 1), 2);

                $order->forceFill([
                    'subtotal' => $netTotal,
                    'tax' => round($grossTotal - $netTotal, 2),
                    'shipping_cost' => $shippingCost,
                    'total' => $grossTotal,
                ])->save();

                // Ware sofort reservieren (Bestand abziehen). Wird nicht bezahlt, gibt
                // shop:reconcile-payments sie nach 30 Minuten wieder frei → kein Überverkauf.
                app(StockService::class)->commitOrderStock($order);

                return $order;
            });
        } catch (Throwable $e) {
            ShopErrorLogger::report('checkout.place_order.failed', $e, [
                'email' => self::maskEmail($this->email),
                'ip' => request()->ip(),
            ]);
            Log::channel('checkout_stack')->error('checkout.place_order.failed', [
                'email' => self::maskEmail($this->email),
                'ip' => request()->ip(),
                'message' => $e->getMessage(),
            ]);
            report($e);
            $this->addError('cart', 'Die Bestellung konnte nicht abgeschlossen werden. Bitte versuche es erneut oder kontaktiere uns.');

            return null;
        }

        session(['pending_order_id' => $order->id]);

        try {
            return redirect()->away(app(PaymentProviderService::class)->createSumUpCheckoutUrl($order));
        } catch (Throwable $e) {
            ShopErrorLogger::report('checkout.place_order.payment_provider_failed', $e, [
                'order_id' => $order->id ?? null,
                'provider' => 'sumup',
            ]);
            Log::channel('checkout_stack')->error('checkout.place_order.payment_provider_failed', [
                'order_id' => $order->id ?? null,
                'provider' => 'sumup',
                'message' => $e->getMessage(),
            ]);
            report($e);

            // Bezahlseite nicht erreichbar → Bestellung verwerfen und Ware sofort wieder freigeben.
            try {
                app(PaymentCompletionService::class)->cancelUnpaid($order, 'sumup:checkout_create_failed');
            } catch (Throwable $cancelError) {
                report($cancelError);
            }

            $this->addError('payment_method', 'Die Bezahlseite ist gerade nicht erreichbar. Es wurde nichts abgebucht — bitte versuche es in ein paar Minuten erneut.');

            return null;
        }
    }

    public function getItemsProperty(): array
    {
        return app(CartService::class)->getContent();
    }

    public function getGoodsTotalProperty(): float
    {
        return (float) app(CartService::class)->getTotal();
    }

    public function getShippingCostProperty(): float
    {
        return ShippingCalculator::costFor($this->goodsTotal);
    }

    public function getTotalProperty(): float
    {
        return round($this->goodsTotal + $this->shippingCost, 2);
    }

    /**
     * Verkauf ausschließlich über SumUp (Karte, Apple Pay, Google Pay — je nach SumUp-Konto).
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function getPaymentMethodsProperty(): array
    {
        if (! app(PaymentProviderService::class)->isSumUpConfigured()) {
            return [];
        }

        return [['code' => 'sumup', 'name' => 'Sicher bezahlen mit SumUp']];
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

    /**
     * DSGVO: keine vollständigen Kunden-E-Mails in Log-Dateien.
     */
    protected static function maskEmail(?string $email): string
    {
        $email = (string) $email;
        $at = strpos($email, '@');
        if ($at === false) {
            return $email === '' ? '' : '***';
        }

        return substr($email, 0, min(2, $at)).'***'.substr($email, $at);
    }

    protected function addressPayload(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => self::maskEmail($this->email),
            'phone' => $this->phone,
            'street' => $this->street,
            'zip' => $this->zip,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }

    public function render()
    {
        return view('livewire.shop.checkout-page');
    }
}
