<x-mail::message>
# Bestellbestätigung

vielen Dank für deine Bestellung bei **{{ \App\Models\Setting::get('shop_name', config('app.name')) }}**.

**Bestellnummer:** {{ $order->order_number }}  
**Datum:** {{ $order->created_at?->format('d.m.Y H:i') }}  
@if ((float) $order->shipping_cost > 0)
**Versand:** {{ \App\Support\MoneyFormatter::format($order->shipping_cost) }}  
@endif
**Gesamtbetrag (inkl. MwSt.):** {{ \App\Support\MoneyFormatter::format($order->total) }}  
**Zahlung:** erhalten per SumUp

## Deine Artikel

@foreach ($order->items as $item)
- {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif — {{ (int) $item->quantity }}× {{ \App\Support\MoneyFormatter::format($item->unit_price) }}
@endforeach

Die Rechnung findest du im PDF-Anhang dieser E-Mail.

Es gelten unsere [AGB]({{ route('legal.agb') }}). Informationen zu deinem Widerrufsrecht findest du in der [Widerrufsbelehrung]({{ route('legal.widerruf') }}).

Bei Fragen antworte einfach auf diese Nachricht oder kontaktiere uns unter {{ \App\Models\Setting::get('shop_email', config('mail.from.address')) }}.

Danke,<br>
{{ \App\Models\Setting::get('shop_name', config('app.name')) }}
</x-mail::message>
