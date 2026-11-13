<x-mail::message>
# Neue Bestellung

Es ist eine neue Bestellung eingegangen.

**Bestellnummer:** {{ $order->order_number }}  
**Kunde:** {{ trim(($order->billing_address['first_name'] ?? '').' '.($order->billing_address['last_name'] ?? '')) ?: ($order->user?->name ?? '—') }}  
**E-Mail:** {{ $order->customerEmail() ?? '—' }}  
**Gesamtbetrag:** {{ \App\Support\MoneyFormatter::format($order->total) }}  
**Zahlungsstatus:** {{ $order->payment_status_label }}

## Zum Packen

@foreach ($order->items as $item)
- {{ (int) $item->quantity }}× {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif @if($item->sku) · Art.-Nr. {{ $item->sku }}@endif

@endforeach

@php($ship = is_array($order->shipping_address) ? $order->shipping_address : (is_array($order->billing_address) ? $order->billing_address : []))
**Lieferadresse:**  
{{ trim(($ship['first_name'] ?? '').' '.($ship['last_name'] ?? '')) }}  
{{ $ship['street'] ?? '' }}  
{{ trim(($ship['zip'] ?? '').' '.($ship['city'] ?? '')) }}

<x-mail::button :url="$adminUrl">
Bestellung im Admin öffnen
</x-mail::button>

Danke,<br>
{{ config('app.name') }}
</x-mail::message>
