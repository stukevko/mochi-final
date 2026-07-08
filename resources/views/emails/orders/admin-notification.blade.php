<x-mail::message>
# Neue Bestellung

Es ist eine neue Bestellung eingegangen.

**Bestellnummer:** {{ $order->order_number }}  
**Kunde:** {{ trim(($order->billing_address['first_name'] ?? '').' '.($order->billing_address['last_name'] ?? '')) ?: ($order->user?->name ?? '—') }}  
**E-Mail:** {{ $order->customerEmail() ?? '—' }}  
**Gesamtbetrag:** {{ \App\Support\MoneyFormatter::format($order->total) }}  
**Zahlungsstatus:** {{ $order->payment_status_label }}

<x-mail::button :url="$adminUrl">
Bestellung im Admin öffnen
</x-mail::button>

Danke,<br>
{{ config('app.name') }}
</x-mail::message>
