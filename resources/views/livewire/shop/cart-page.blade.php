<div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div
        wire:loading.flex
        wire:target="increment,decrement,remove,clear"
        class="fixed inset-0 z-40 items-center justify-center bg-[#040712]/70 backdrop-blur-sm"
    >
        <div
            class="flex items-center gap-2 rounded-xl border border-white/10 bg-[#080c12]/95 px-5 py-3 text-sm font-medium text-white shadow-lg backdrop-blur-xl"
        >
            <svg class="h-5 w-5 animate-spin text-mochi-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Warenkorb wird aktualisiert…
        </div>
    </div>

    <div class="mb-8 flex items-end justify-between">
        <div>
            <h1 class="font-display text-2xl font-semibold tracking-tight text-white sm:text-3xl">Warenkorb</h1>
            <p class="mt-2 text-sm text-mochi-muted">Prüfe deine Artikel und passe die Menge an.</p>
        </div>
        @if (count($this->items) > 0)
            <button
                wire:click="clear"
                class="rounded-xl border border-white/15 px-4 py-2 text-sm text-mochi-muted transition hover:border-mochi-accent/35 hover:bg-white/5 hover:text-white"
            >
                Alles entfernen
            </button>
        @endif
    </div>
    @if ($feedbackMessage !== '')
        <div class="mb-6 rounded-xl border border-amber-500/35 bg-amber-500/10 px-4 py-3 text-sm text-amber-50">
            {{ $feedbackMessage }}
        </div>
    @endif

    @if (count($this->items) === 0)
        <div class="rounded-2xl border border-dashed border-white/15 bg-[#080c12]/40 p-12 text-center shadow-none backdrop-blur-xl">
            <p class="text-lg font-semibold text-white">Dein Warenkorb ist leer.</p>
            <p class="mt-2 text-sm text-mochi-muted">Entdecke Produkte und lege sie in den Warenkorb.</p>
            <a
                href="{{ route('shop') }}"
                wire:navigate
                class="mochi-gradient-button mochi-gradient-button-sm mt-4 inline-flex min-h-11 items-center justify-center px-6"
            >
                Zurück zum Shop
            </a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-[#080c12]/40 shadow-none backdrop-blur-xl lg:col-span-2">
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-white/10">
                        <thead class="bg-white/[0.04]">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-mochi-muted">Produkt</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-mochi-muted">Menge</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-mochi-muted">Preis</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @foreach ($this->items as $item)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="flex gap-3">
                                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-white/5">
                                                @if (! empty($item['image']))
                                                    <img
                                                        src="{{ $item['image'] }}"
                                                        alt="{{ $item['name'] }}"
                                                        loading="lazy"
                                                        decoding="async"
                                                        width="80"
                                                        height="80"
                                                        class="h-full w-full object-cover object-center"
                                                    >
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-white">{{ $item['name'] }}</p>
                                                @if (! empty($item['variant_name']))
                                                    <p class="mt-1 text-xs text-mochi-muted">{{ $item['variant_name'] }}</p>
                                                @endif
                                                <button
                                                    wire:click="remove({{ (int) $item['product_id'] }}, {{ $item['variant_id'] !== null ? (int) $item['variant_id'] : 'null' }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="remove"
                                                    class="mt-2 text-xs font-medium text-mochi-muted transition hover:text-rose-400"
                                                >
                                                    Entfernen
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="inline-flex items-center rounded-xl border border-white/15">
                                            <button
                                                wire:click="decrement({{ (int) $item['product_id'] }}, {{ $item['variant_id'] !== null ? (int) $item['variant_id'] : 'null' }})"
                                                wire:loading.attr="disabled"
                                                wire:target="decrement"
                                                class="min-h-[42px] min-w-[42px] rounded-l-xl px-3 text-base font-semibold text-mochi-text transition hover:bg-white/5"
                                            >
                                                -
                                            </button>
                                            <span class="min-w-12 px-2 text-center text-sm font-semibold text-white">{{ (int) $item['quantity'] }}</span>
                                            <button
                                                wire:click="increment({{ (int) $item['product_id'] }}, {{ $item['variant_id'] !== null ? (int) $item['variant_id'] : 'null' }})"
                                                wire:loading.attr="disabled"
                                                wire:target="increment"
                                                class="min-h-[42px] min-w-[42px] rounded-r-xl px-3 text-base font-semibold text-mochi-text transition hover:bg-white/5"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <p class="text-xs text-mochi-muted">{{ $this->formatPrice((float) $item['price']) }} / Stück</p>
                                        <p class="mt-1 text-sm font-semibold text-mochi-coral">
                                            {{ $this->formatPrice(((float) $item['price']) * ((int) $item['quantity'])) }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    @foreach ($this->items as $item)
                        <article class="rounded-xl border border-white/10 bg-white/[0.03] p-3">
                            <div class="flex gap-3">
                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-white/5">
                                    @if (! empty($item['image']))
                                        <img
                                            src="{{ $item['image'] }}"
                                            alt="{{ $item['name'] }}"
                                            loading="lazy"
                                            decoding="async"
                                            width="80"
                                            height="80"
                                            class="h-full w-full object-cover object-center"
                                        >
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-white">{{ $item['name'] }}</p>
                                    @if (! empty($item['variant_name']))
                                        <p class="mt-1 text-xs text-mochi-muted">{{ $item['variant_name'] }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-mochi-muted">{{ $this->formatPrice((float) $item['price']) }} / Stück</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="inline-flex items-center rounded-xl border border-white/15">
                                    <button
                                        wire:click="decrement({{ (int) $item['product_id'] }}, {{ $item['variant_id'] !== null ? (int) $item['variant_id'] : 'null' }})"
                                        wire:loading.attr="disabled"
                                        wire:target="decrement"
                                        class="min-h-[42px] min-w-[42px] rounded-l-xl px-3 text-base font-semibold text-mochi-text transition hover:bg-white/5"
                                    >
                                        -
                                    </button>
                                    <span class="min-w-12 px-2 text-center text-sm font-semibold text-white">{{ (int) $item['quantity'] }}</span>
                                    <button
                                        wire:click="increment({{ (int) $item['product_id'] }}, {{ $item['variant_id'] !== null ? (int) $item['variant_id'] : 'null' }})"
                                        wire:loading.attr="disabled"
                                        wire:target="increment"
                                        class="min-h-[42px] min-w-[42px] rounded-r-xl px-3 text-base font-semibold text-mochi-text transition hover:bg-white/5"
                                    >
                                        +
                                    </button>
                                </div>
                                <p class="text-sm font-semibold text-mochi-coral">
                                    {{ $this->formatPrice(((float) $item['price']) * ((int) $item['quantity'])) }}
                                </p>
                            </div>
                            <button
                                wire:click="remove({{ (int) $item['product_id'] }}, {{ $item['variant_id'] !== null ? (int) $item['variant_id'] : 'null' }})"
                                wire:loading.attr="disabled"
                                wire:target="remove"
                                class="mt-3 text-xs font-medium text-mochi-muted transition hover:text-rose-400"
                            >
                                Entfernen
                            </button>
                        </article>
                    @endforeach
                </div>
            </div>

            <div wire:loading.grid wire:target="increment,decrement,remove,clear" class="grid gap-3 lg:col-span-2">
                @for ($i = 0; $i < 2; $i++)
                    <div class="rounded-xl border border-white/10 bg-[#080c12]/40 p-4">
                        <div class="h-4 w-2/3 animate-pulse rounded bg-white/10"></div>
                        <div class="mt-3 h-4 w-1/2 animate-pulse rounded bg-white/10"></div>
                        <div class="mt-4 h-10 w-40 animate-pulse rounded-xl bg-white/5"></div>
                    </div>
                @endfor
            </div>

            <aside class="h-fit rounded-2xl border border-white/10 bg-[#080c12]/50 p-5 backdrop-blur-xl">
                <h3 class="text-base font-semibold text-white">Zusammenfassung</h3>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-mochi-muted">Zwischensumme</span>
                    <span class="font-semibold text-white">{{ $this->formatPrice($this->total) }}</span>
                </div>
                <a
                    href="{{ route('checkout') }}"
                    wire:navigate
                    class="mochi-gradient-button mochi-gradient-button-sm mt-6 inline-flex w-full items-center justify-center min-h-12"
                >
                    Zur Kasse
                </a>
            </aside>
        </div>
    @endif
</div>
