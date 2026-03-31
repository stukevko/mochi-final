<div class="flex h-full min-h-0 flex-col">
    <div class="flex-1 overflow-y-auto px-5 py-4">
        @if ($this->items === [])
            <div class="rounded-xl border border-dashed border-white/15 bg-[#040712]/60 px-4 py-8 text-center backdrop-blur-sm">
                <p class="text-sm font-medium text-mochi-text">Dein Warenkorb ist leer.</p>
                <a
                    href="{{ route('shop') }}"
                    wire:navigate
                    class="mochi-gradient-button mochi-gradient-button-sm mt-4 inline-flex min-h-[42px] items-center justify-center px-5"
                >
                    Jetzt shoppen
                </a>
            </div>
        @else
            <ul class="space-y-4">
                @foreach ($this->items as $item)
                    <li class="flex items-start gap-3">
                        <div class="h-14 w-14 shrink-0 rounded-xl border border-white/10 bg-white/5"></div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-white">{{ $item['name'] }}</p>
                            @if (! empty($item['variant_name']))
                                <p class="mt-0.5 text-xs text-mochi-muted">{{ $item['variant_name'] }}</p>
                            @endif
                            <p class="mt-1 text-xs text-mochi-muted">Menge: {{ (int) $item['quantity'] }}</p>
                        </div>
                        <p class="text-sm font-semibold text-mochi-coral">
                            {{ \App\Support\MoneyFormatter::format(((float) $item['price']) * ((int) $item['quantity'])) }}
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="shrink-0 border-t border-white/10 bg-[#040712]/40 px-5 py-4 backdrop-blur-sm">
        <div class="mb-3 flex items-center justify-between text-sm">
            <span class="text-mochi-muted">Zwischensumme</span>
            <span class="font-semibold text-white">{{ \App\Support\MoneyFormatter::format($this->total) }}</span>
        </div>
        <div class="grid grid-cols-1 gap-2">
            <a href="{{ route('checkout') }}" wire:navigate class="mochi-gradient-button mochi-gradient-button-sm w-full justify-center">
                Direkt zur Kasse
            </a>
            <a
                href="{{ route('cart') }}"
                wire:navigate
                class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-white/15 bg-white/[0.04] px-4 py-2.5 text-sm font-semibold text-mochi-text transition hover:border-mochi-accent/35 hover:bg-mochi-accent/10"
            >
                Zum Warenkorb
            </a>
        </div>
    </div>
</div>
