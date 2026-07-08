<x-mail::message>
# Versandbestätigung

deine Bestellung **{{ $order->order_number }}** wurde versendet.

@if ($order->shipping_carrier)
**Versanddienstleister:** {{ \App\Models\Order::SHIPPING_CARRIER_LABELS[$order->shipping_carrier] ?? $order->shipping_carrier }}
@endif

@if ($order->tracking_number)
**Sendungsnummer:** {{ $order->tracking_number }}
@endif

@if ($url = $order->getTrackingUrl())
<x-mail::button :url="$url">
Sendung verfolgen
</x-mail::button>
@endif

Die Rechnung findest du erneut im PDF-Anhang.

Danke,<br>
{{ \App\Models\Setting::get('shop_name', config('app.name')) }}
</x-mail::message>
