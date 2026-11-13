@php
    $coInput = 'mochi-input mt-2 w-full min-h-[52px] rounded-xl px-4 py-3 text-base';
@endphp

<div
    class="relative mx-auto max-w-6xl px-4 pb-32 pt-10 sm:px-6 md:pb-10 lg:px-8 lg:py-14"
    x-on:mochi-turnstile.window="$wire.set('turnstileToken', $event.detail.token)"
>
    <div
        wire:loading.flex
        wire:target="placeOrder"
        class="fixed inset-0 z-[60] items-center justify-center bg-[#040712]/75 backdrop-blur-sm"
    >
        <div class="flex flex-col items-center gap-3 rounded-2xl border border-white/10 bg-[#080c12]/95 px-8 py-6 shadow-2xl backdrop-blur-xl">
            <svg
                class="h-10 w-10 animate-spin text-mochi-accent"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
            <p class="text-sm font-medium text-mochi-text">Bestellung wird übermittelt…</p>
        </div>
    </div>

    <h1 class="font-display text-2xl font-bold tracking-tight text-white sm:text-3xl">Checkout</h1>
    <p class="mt-2 max-w-2xl text-sm text-mochi-muted">
        Prüfe deine Angaben und schließe die Bestellung ab. Die Zahlung erfolgt über den gewählten Zahlungsdienstleister (siehe Datenschutzerklärung).
    </p>

    @if ($errors->has('cart'))
        <div class="mt-6 rounded-xl border border-red-500/35 bg-red-500/10 px-4 py-3 text-sm text-red-100">
            {{ $errors->first('cart') }}
        </div>
    @endif

    @if ($errors->has('rate_limit'))
        <div class="mt-6 rounded-xl border border-amber-500/35 bg-amber-500/10 px-4 py-3 text-sm text-amber-50">
            {{ $errors->first('rate_limit') }}
        </div>
    @endif
    @if (session('payment_error'))
        <div class="mt-6 rounded-xl border border-red-500/35 bg-red-500/10 px-4 py-3 text-sm text-red-100">
            {{ session('payment_error') }}
        </div>
    @endif

    <form id="checkout-form" wire:submit="placeOrder" data-consent-turnstile class="mt-10 lg:grid lg:grid-cols-12 lg:items-start lg:gap-12">
        <div class="space-y-10 lg:col-span-7">
            <section class="mochi-card rounded-2xl border border-white/10 bg-[#080c12]/40 p-6 backdrop-blur-xl sm:p-8">
                <h2 class="text-lg font-semibold text-white">Kontakt &amp; Lieferadresse</h2>
                <p class="mt-1 text-sm text-mochi-muted">Diese Daten verwenden wir für die Bestellbestätigung und den Versand.</p>

                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <label for="co-first" class="block text-sm font-medium text-mochi-text">Vorname</label>
                        <input id="co-first" type="text" wire:model.defer="first_name" autocomplete="given-name" class="{{ $coInput }}" />
                        @error('first_name')
                            <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-1">
                        <label for="co-last" class="block text-sm font-medium text-mochi-text">Nachname</label>
                        <input id="co-last" type="text" wire:model.defer="last_name" autocomplete="family-name" class="{{ $coInput }}" />
                        @error('last_name')
                            <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="co-email" class="block text-sm font-medium text-mochi-text">E-Mail</label>
                        <input id="co-email" type="email" wire:model.defer="email" autocomplete="email" class="{{ $coInput }}" />
                        @error('email')
                            <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="co-phone" class="block text-sm font-medium text-mochi-text">
                            Telefon <span class="font-normal text-mochi-muted">(optional)</span>
                        </label>
                        <input id="co-phone" type="text" wire:model.defer="phone" autocomplete="tel" class="{{ $coInput }}" />
                        @error('phone')
                            <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="relative sm:col-span-2" x-data="checkoutAddressSuggest(@js(route('api.address-suggest')))">
                        <p class="text-sm text-mochi-muted">
                            PLZ eingeben → Ort wird vorgeschlagen; danach Straße tippen → Vorschläge (OpenStreetMap).
                        </p>
                        <div class="mt-4 grid gap-6 sm:grid-cols-2">
                            <div class="relative sm:col-span-1">
                                <label for="co-zip" class="block text-sm font-medium text-mochi-text">PLZ</label>
                                <input
                                    id="co-zip"
                                    type="text"
                                    wire:model.live.debounce.300ms="zip"
                                    autocomplete="postal-code"
                                    @input.debounce.350ms="onZipInput($event)"
                                    class="{{ $coInput }}"
                                />
                                <ul
                                    x-show="zipSuggestions.length > 0"
                                    x-transition
                                    class="absolute z-30 mt-1 max-h-48 w-full overflow-auto rounded-xl border border-white/10 bg-[#080c12] py-1 text-base shadow-xl"
                                    @click.outside="zipSuggestions = []"
                                >
                                    <template x-for="s in zipSuggestions" :key="s.label">
                                        <li
                                            role="button"
                                            tabindex="0"
                                            @click="pickZip(s)"
                                            @keydown.enter.prevent="pickZip(s)"
                                            class="cursor-pointer px-3 py-2.5 text-mochi-text hover:bg-white/5"
                                            x-text="s.label"
                                        ></li>
                                    </template>
                                </ul>
                                @error('zip')
                                    <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="relative sm:col-span-1">
                                <label for="co-city" class="block text-sm font-medium text-mochi-text">Stadt</label>
                                <input
                                    id="co-city"
                                    type="text"
                                    wire:model.live.debounce.300ms="city"
                                    autocomplete="address-level2"
                                    class="{{ $coInput }}"
                                />
                                @error('city')
                                    <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="relative sm:col-span-2">
                                <label for="co-street" class="block text-sm font-medium text-mochi-text">Straße &amp; Hausnummer</label>
                                <input
                                    id="co-street"
                                    type="text"
                                    wire:model.live.debounce.300ms="street"
                                    autocomplete="street-address"
                                    @input.debounce.400ms="onStreetInput($event)"
                                    class="{{ $coInput }}"
                                />
                                <ul
                                    x-show="streetSuggestions.length > 0"
                                    x-transition
                                    class="absolute z-30 mt-1 max-h-48 w-full overflow-auto rounded-xl border border-white/10 bg-[#080c12] py-1 text-base shadow-xl"
                                    @click.outside="streetSuggestions = []"
                                >
                                    <template x-for="s in streetSuggestions" :key="s.label">
                                        <li
                                            role="button"
                                            tabindex="0"
                                            @click="pickStreet(s)"
                                            @keydown.enter.prevent="pickStreet(s)"
                                            class="cursor-pointer px-3 py-2.5 text-mochi-text hover:bg-white/5"
                                            x-text="s.label"
                                        ></li>
                                    </template>
                                </ul>
                                @error('street')
                                    <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="co-country" class="block text-sm font-medium text-mochi-text">Land (ISO-2)</label>
                        <input
                            id="co-country"
                            type="text"
                            wire:model.defer="country"
                            maxlength="2"
                            autocomplete="country"
                            class="{{ $coInput }} max-w-[8rem] uppercase"
                        />
                        @error('country')
                            <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="mt-4 text-xs leading-relaxed text-mochi-muted">
                    Deine Angaben werden zur Bestellabwicklung verarbeitet. Details in der
                    <a href="{{ route('legal.datenschutz') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-mochi-coral underline-offset-2 hover:underline">Datenschutzerklärung</a>.
                </p>
            </section>

            <section class="mochi-card rounded-2xl border border-white/10 bg-[#080c12]/40 p-6 backdrop-blur-xl sm:p-8">
                <h2 class="text-lg font-semibold text-white">Zahlungsart</h2>
                <p class="mt-1 text-sm text-mochi-muted">Die Zahlung läuft über unseren Zahlungsdienstleister SumUp, der eigene Cookies setzen kann.</p>
                @if ($this->paymentMethods !== [])
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-mochi-accent/45 bg-mochi-accent/10 px-4 py-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-mochi-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-mochi-text">Sicher bezahlen mit SumUp</p>
                            <p class="mt-1 text-sm text-mochi-muted">Nach „Zahlungspflichtig bestellen“ leiten wir dich zur Bezahlseite von SumUp weiter. Deine Artikel sind 30&nbsp;Minuten für dich reserviert.</p>
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-xl border border-amber-500/35 bg-amber-500/10 px-4 py-3 text-sm text-amber-50">
                        Online-Zahlung ist noch nicht eingerichtet. Bitte kontaktiere den Laden.
                    </div>
                @endif
                @error('payment_method')
                    <p class="mt-3 text-sm text-red-300">{{ $message }}</p>
                @enderror
            </section>

            <section class="mochi-card rounded-2xl border border-white/10 bg-[#080c12]/40 p-6 backdrop-blur-xl sm:p-8">
                <label for="co-notes" class="block text-lg font-semibold text-white">Notiz zur Bestellung</label>
                <p class="mt-1 text-sm text-mochi-muted">Optional — z.&nbsp;B. Lieferhinweise.</p>
                <textarea
                    id="co-notes"
                    wire:model.defer="notes"
                    rows="4"
                    placeholder="Deine Nachricht an uns…"
                    class="mochi-input mt-4 w-full rounded-xl px-4 py-3 text-base"
                ></textarea>
                @error('notes')
                    <p class="mt-1.5 text-sm text-red-300">{{ $message }}</p>
                @enderror
            </section>
        </div>

        <aside class="mt-10 lg:col-span-5 lg:mt-0">
            <div class="sticky top-24 space-y-6">
                <div class="mochi-card rounded-2xl border border-white/10 bg-[#080c12]/50 p-6 backdrop-blur-xl sm:p-8">
                    <h3 class="text-base font-semibold text-white">Bestellübersicht</h3>
                    <div class="mt-6 space-y-4">
                        @foreach ($this->items as $item)
                            <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-4 last:border-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="font-medium text-mochi-text">{{ $item['name'] }}</p>
                                    @if (! empty($item['variant_name']))
                                        <p class="mt-0.5 text-sm text-mochi-muted">{{ $item['variant_name'] }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-mochi-muted">Menge: {{ (int) $item['quantity'] }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-semibold text-white">
                                    {{ $this->formatPrice(((float) $item['price']) * ((int) $item['quantity'])) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6 border-t border-white/10 pt-6">
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-mochi-muted">Versand</span>
                                <span class="font-medium text-white">{{ $this->shippingCost > 0 ? $this->formatPrice($this->shippingCost) : 'Kostenfrei' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-mochi-muted">Nettosumme</span>
                                <span class="font-medium text-white">{{ $this->formatPrice($this->netSubtotal) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-mochi-muted">MwSt. ({{ number_format($this->getTaxRate(), 2, ',', '.') }}%)</span>
                                <span class="font-medium text-white">{{ $this->formatPrice($this->taxAmount) }}</span>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-base">
                            <span class="font-medium text-mochi-muted">Gesamtsumme (Brutto)</span>
                            <span class="text-lg font-bold text-mochi-coral">{{ $this->formatPrice($this->total) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3">
                    @if (\App\Services\TurnstileVerifier::siteKey())
                        <div class="mb-4 border-b border-white/10 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-mochi-muted">Sicherheitsprüfung</p>
                            <p class="mt-1 text-xs text-mochi-muted">Kurz bestätigen, bevor du die Bestellung abschickst.</p>
                            <div
                                wire:ignore
                                class="cf-turnstile mt-3 flex min-h-[65px] justify-start"
                                data-sitekey="{{ \App\Services\TurnstileVerifier::siteKey() }}"
                                data-callback="mochiTurnstileCallback"
                            ></div>
                            @error('turnstileToken')
                                <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            type="checkbox"
                            wire:model.defer="accepted_legal"
                            class="mt-0.5 h-5 w-5 shrink-0 rounded border-white/20 bg-[#040712] text-mochi-accent focus:ring-mochi-accent/40"
                        />
                        <span class="text-sm text-mochi-muted">
                            Ich habe die
                            <a href="{{ route('legal.agb') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-mochi-coral underline decoration-mochi-coral/40 underline-offset-2 hover:text-white">AGB</a>
                            und die
                            <a href="{{ route('legal.widerruf') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-mochi-coral underline decoration-mochi-coral/40 underline-offset-2 hover:text-white">Widerrufsbelehrung</a>
                            gelesen und akzeptiere sie.
                        </span>
                    </label>
                    @error('accepted_legal')
                        <p class="mt-2 text-sm text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="placeOrder"
                    class="mochi-gradient-button mochi-gradient-button-sm hidden lg:inline-flex"
                >
                    <span wire:loading.remove wire:target="placeOrder">Jetzt zahlungspflichtig bestellen</span>
                    <span wire:loading wire:target="placeOrder" class="inline-flex items-center gap-2">
                        <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Wird gesendet…
                    </span>
                </button>

                <p class="hidden text-center text-xs text-mochi-muted lg:block">
                    Mit Klick auf „Jetzt zahlungspflichtig bestellen“ schließt du einen Kaufvertrag ab. Rechnung erhältst du per E-Mail.
                </p>
            </div>
        </aside>
    </form>

    <div class="checkout-sticky-cta md:hidden">
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-medium uppercase tracking-wide text-mochi-muted">Gesamt</p>
                <p class="truncate text-base font-bold text-white">{{ $this->formatPrice($this->total) }}</p>
            </div>
            <button
                type="submit"
                form="checkout-form"
                wire:loading.attr="disabled"
                wire:target="placeOrder"
                class="mochi-gradient-button mochi-gradient-button-sm inline-flex min-h-12 shrink-0 px-6"
            >
                <span wire:loading.remove wire:target="placeOrder">Jetzt zahlungspflichtig bestellen</span>
                <span wire:loading wire:target="placeOrder" class="inline-flex items-center gap-2">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    …
                </span>
            </button>
        </div>
    </div>
</div>
