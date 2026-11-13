@php
    $flat = \App\Support\ShippingCalculator::flatRate();
    $freeFrom = \App\Support\ShippingCalculator::freeFrom();
    $shippingHint = $flat <= 0
        ? ' Versandkostenfrei.'
        : ' zzgl. '.\App\Support\MoneyFormatter::format($flat).' Versand'.($freeFrom > 0 ? ' (frei ab '.\App\Support\MoneyFormatter::format($freeFrom).')' : '').'.';
@endphp
<p class="text-[10px] leading-snug text-mochi-muted/80">
    Alle Preise inkl. gesetzlicher MwSt.{{ ($compact ?? false) ? '' : $shippingHint }}
</p>
