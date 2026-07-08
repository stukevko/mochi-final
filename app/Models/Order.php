<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Order extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('filament.nav.orders_pending_count'));

        static::deleted(fn () => Cache::forget('filament.nav.orders_pending_count'));
    }

    /**
     * @var list<string>
     *
     * Nur vertrauenswürdige Felder für Mass Assignment. Status, Zahlungsfelder und Beträge
     * werden ausschließlich per forceFill in Server-Code / Filament-Hooks gesetzt.
     */
    protected $fillable = [
        'user_id',
        'shipping_carrier',
        'tracking_number',
        'custom_tracking_url',
        'shipping_cost',
        'billing_address',
        'shipping_address',
        'notes',
        'currency',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'payment_data' => 'array',
        'terms_accepted_at' => 'datetime',
    ];

    /**
     * Status-Labels auf Deutsch
     */
    public const STATUS_LABELS = [
        'pending' => 'Ausstehend',
        'processing' => 'In Bearbeitung',
        'shipped' => 'Versendet',
        'delivered' => 'Geliefert',
        'cancelled' => 'Storniert',
        'refunded' => 'Erstattet',
    ];

    /**
     * Zahlungsstatus-Labels auf Deutsch
     */
    public const PAYMENT_STATUS_LABELS = [
        'pending' => 'Ausstehend',
        'paid' => 'Bezahlt',
        'failed' => 'Fehlgeschlagen',
        'refunded' => 'Erstattet',
        'cancelled' => 'Storniert',
    ];

    public const SHIPPING_CARRIER_LABELS = [
        'dhl' => 'DHL',
        'hermes' => 'Hermes',
        'dpd' => 'DPD',
        'gls' => 'GLS',
        'deutsche_post' => 'Deutsche Post',
        'other' => 'Sonstige',
    ];

    /**
     * Der Kunde
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bestellpositionen
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generiert eine neue Bestellnummer
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Status-Label auf Deutsch
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Zahlungsstatus-Label auf Deutsch
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * E-Mail-Adresse des Kunden (Nutzerkonto oder Adressdaten der Bestellung).
     */
    public function customerEmail(): ?string
    {
        $userEmail = $this->user?->email;
        if (filled($userEmail)) {
            return $userEmail;
        }

        $ship = is_array($this->shipping_address) ? $this->shipping_address : [];
        $bill = is_array($this->billing_address) ? $this->billing_address : [];

        $fromShip = $ship['email'] ?? null;
        if (filled($fromShip)) {
            return is_string($fromShip) ? $fromShip : null;
        }

        $fromBill = $bill['email'] ?? null;

        return filled($fromBill) && is_string($fromBill) ? $fromBill : null;
    }

    public static function shippingCarrierOptions(): array
    {
        return self::SHIPPING_CARRIER_LABELS;
    }

    public function getTrackingUrl(): ?string
    {
        $custom = trim((string) ($this->custom_tracking_url ?? ''));
        if ($custom !== '' && filter_var($custom, FILTER_VALIDATE_URL)) {
            return $custom;
        }

        $number = trim((string) ($this->tracking_number ?? ''));
        if ($number === '') {
            return null;
        }

        $encoded = urlencode($number);

        return match ((string) ($this->shipping_carrier ?? '')) {
            'dhl' => "https://www.dhl.de/de/privatkunden/dhl-sendungsverfolgung.html?piececode={$encoded}",
            'hermes' => "https://www.myhermes.de/empfangen/sendungsverfolgung/?trackingnumber={$encoded}",
            'dpd' => "https://tracking.dpd.de/status/de_DE/parcel/{$encoded}",
            'gls' => "https://gls-group.com/DE/de/paketverfolgung?match={$encoded}",
            'deutsche_post' => "https://www.deutschepost.de/sendung/simpleQuery.html?form.sendungsnummer={$encoded}",
            'other' => null,
            default => null,
        };
    }

    public function getInvoiceNumber(): string
    {
        $prefix = trim((string) Setting::get('invoice_prefix', 'INV-'));
        if ($prefix === '') {
            $prefix = 'INV-';
        }

        return $prefix.$this->order_number;
    }

    public function getCurrencySymbol(): string
    {
        return (string) Setting::get('currency_symbol', '€');
    }

    /**
     * Weitere Bestellungen desselben Kunden (nach Account oder E-Mail), aktuelle ausgeschlossen.
     *
     * @return Collection<int, Order>
     */
    public function priorOrdersForSameCustomer(int $limit = 10): Collection
    {
        $email = $this->customerEmail();

        return self::query()
            ->whereKeyNot($this->getKey())
            ->when(
                $this->user_id,
                fn (Builder $q): Builder => $q->where('user_id', $this->user_id),
                function (Builder $q) use ($email): Builder {
                    if (blank($email)) {
                        return $q->whereRaw('1 = 0');
                    }

                    return $q->where(function (Builder $inner) use ($email): void {
                        $inner
                            ->where('billing_address->email', $email)
                            ->orWhere('shipping_address->email', $email);
                    });
                }
            )
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function hasPriorOrdersForSameCustomer(): bool
    {
        $email = $this->customerEmail();

        return self::query()
            ->whereKeyNot($this->getKey())
            ->when(
                $this->user_id,
                fn (Builder $q): Builder => $q->where('user_id', $this->user_id),
                function (Builder $q) use ($email): Builder {
                    if (blank($email)) {
                        return $q->whereRaw('1 = 0');
                    }

                    return $q->where(function (Builder $inner) use ($email): void {
                        $inner
                            ->where('billing_address->email', $email)
                            ->orWhere('shipping_address->email', $email);
                    });
                }
            )
            ->exists();
    }
}
