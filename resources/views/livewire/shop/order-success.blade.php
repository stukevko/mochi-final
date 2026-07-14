<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <style>
        @keyframes thank-you-check {
            0% { transform: scale(0.4); opacity: 0; }
            55% { transform: scale(1.08); opacity: 1; }
            80% { transform: scale(0.96); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes thank-you-ring {
            0% { transform: scale(0.8); opacity: 0.6; }
            100% { transform: scale(1.35); opacity: 0; }
        }

        .thank-you-icon-wrap {
            animation: thank-you-check 0.65s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .thank-you-ring {
            animation: thank-you-ring 1.2s ease-out 0.15s forwards;
        }
    </style>

    <div class="text-center">
        <div class="relative mx-auto flex h-20 w-20 items-center justify-center">
            <span class="thank-you-ring absolute inset-0 rounded-full bg-emerald-400/25" aria-hidden="true"></span>
            <div
                class="thank-you-icon-wrap relative flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/35"
            >
                <svg class="h-9 w-9" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                        clip-rule="evenodd"
                    />
                </svg>
            </div>
        </div>

        <h1 class="mt-8 font-display text-3xl font-semibold tracking-tight text-white">
            Danke für deine Bestellung!
        </h1>
        <p class="mt-3 text-mochi-muted">
            Wir haben deine Bestellung erhalten und bereiten alles vor.
        </p>
        <p class="mt-2 inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-200">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
            Deine Rechnung wurde dir soeben per E-Mail zugestellt.
        </p>
        @if (session('invoice_error'))
            <p class="mx-auto mt-4 max-w-xl rounded-xl border border-amber-500/35 bg-amber-500/10 px-4 py-3 text-sm text-amber-50">
                {{ session('invoice_error') }}
            </p>
        @endif
        <p class="mt-2 text-sm text-mochi-muted">
            Bestellnummer
            <span class="font-semibold text-white">{{ $order->order_number }}</span>
        </p>
    </div>

    @if (is_array(session('order_mail_hints')) && count(session('order_mail_hints')) > 0)
        <div class="mx-auto mt-8 max-w-xl rounded-xl border border-amber-500/35 bg-amber-500/10 px-4 py-3 text-left text-sm text-amber-50">
            <p class="font-semibold">Hinweise zu E-Mail</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach (session('order_mail_hints') as $hint)
                    <li>{{ $hint }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $addr = $order->shipping_address ?? $order->billing_address ?? [];
    @endphp

    @if ($addr !== [])
        <div class="mt-10 rounded-xl border border-white/10 bg-[#080c12]/50 p-5 backdrop-blur-xl">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-mochi-muted">Lieferadresse</h2>
            <address class="mt-2 not-italic text-sm leading-relaxed text-mochi-text">
                {{ trim(($addr['first_name'] ?? '').' '.($addr['last_name'] ?? '')) }}<br>
                {{ $addr['street'] ?? '' }}<br>
                {{ $addr['zip'] ?? '' }} {{ $addr['city'] ?? '' }}<br>
                {{ $addr['country'] ?? '' }}
                @if (! empty($addr['email']))
                    <br><span class="text-mochi-muted">{{ $addr['email'] }}</span>
                @endif
            </address>
        </div>
    @endif

    <div class="mt-8 overflow-hidden rounded-xl border border-white/10 bg-[#080c12]/50 backdrop-blur-xl">
        <div class="border-b border-white/10 px-5 py-4">
            <h2 class="text-base font-semibold text-white">Bestellübersicht</h2>
            <p class="mt-0.5 text-sm text-mochi-muted">{{ $order->items->count() }} Position(en)</p>
        </div>
        <ul class="divide-y divide-white/10">
            @foreach ($order->items as $item)
                <li class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium text-white">{{ $item->product_name }}</p>
                        @if (filled($item->variant_name))
                            <p class="text-sm text-mochi-muted">{{ $item->variant_name }}</p>
                        @endif
                        <p class="mt-1 text-sm text-mochi-muted">
                            {{ $item->quantity }} × {{ $this->formatMoney($item->unit_price) }}
                        </p>
                    </div>
                    <p class="text-right text-sm font-semibold text-mochi-coral sm:text-base">
                        {{ $this->formatMoney($item->total_price) }}
                    </p>
                </li>
            @endforeach
        </ul>
        <div class="flex items-center justify-between border-t border-white/10 bg-[#040712]/40 px-5 py-4">
            <span class="text-sm font-medium text-mochi-muted">Gesamtbetrag</span>
            <span class="text-lg font-bold text-white">{{ $this->formatMoney($order->total) }}</span>
        </div>
    </div>

    <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
        <a
            href="{{ $this->invoiceDownloadUrl() }}"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/[0.04] px-5 py-2.5 text-sm font-semibold text-mochi-text transition hover:border-mochi-accent/35 hover:bg-mochi-accent/10 sm:w-auto"
        >
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12 2.25a.75.75 0 0 1 .75.75v11.69l3.22-3.22a.75.75 0 1 1 1.06 1.06l-4.5 4.5a.75.75 0 0 1-1.06 0l-4.5-4.5a.75.75 0 1 1 1.06-1.06l3.22 3.22V3a.75.75 0 0 1 .75-.75Zm-9 13.5a.75.75 0 0 1 .75.75v2.25a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5V16.5a.75.75 0 0 1 1.5 0v2.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V16.5a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
            </svg>
            Rechnung herunterladen
        </a>
        <a
            href="{{ route('shop') }}"
            wire:navigate
            class="mochi-gradient-button mochi-gradient-button-sm inline-flex w-full items-center justify-center sm:w-auto"
        >
            Weiter einkaufen
        </a>
        <a
            href="{{ route('home') }}"
            wire:navigate
            class="inline-flex w-full items-center justify-center rounded-xl border border-white/15 bg-white/[0.04] px-5 py-2.5 text-sm font-semibold text-mochi-text transition hover:border-mochi-accent/35 hover:bg-mochi-accent/10 sm:w-auto"
        >
            Zur Startseite
        </a>
    </div>
</div>
