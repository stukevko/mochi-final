<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    @php
        $breadcrumbLd = \App\Support\BreadcrumbJsonLd::forProduct($product);
    @endphp
    <script type="application/ld+json">@json($breadcrumbLd)</script>

    @php
        $heroImg = $this->selectedVariant?->safeImageUrl() ?? $product->safeImageUrl();
        $sku = (string) ($this->selectedVariant?->sku ?? $product->sku ?? $product->id);
        $jsonLd = \App\Support\ProductStructuredData::productWithOffer(
            $product,
            (float) $this->displayPrice,
            (int) $this->displayStock,
            $heroImg,
            $sku,
        );
    @endphp

    {{-- Structured Data: Google Rich Results (Preis, Währung, Verfügbarkeit, Marke) --}}
    <script type="application/ld+json">@json($jsonLd)</script>
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
        <div>
            <div wire:transition="product-image" class="overflow-hidden rounded-3xl border border-white/10 bg-[#080c12]/40 shadow-none backdrop-blur-sm">
                @php
                    $heroImg = $this->selectedVariant?->safeImageUrl() ?? $product->safeImageUrl();
                @endphp
                @if ($heroImg)
                    <img
                        wire:key="hero-{{ $product->id }}-{{ $this->selectedVariantId ?? 'none' }}"
                        src="{{ $heroImg }}"
                        alt="{{ $product->name }}"
                        class="aspect-square h-full w-full object-cover object-center transition duration-500 hover:scale-[1.03]"
                        fetchpriority="high"
                        decoding="async"
                        sizes="(max-width: 1024px) 100vw, 50vw"
                        width="800"
                        height="800"
                    >
                @else
                    <div class="flex aspect-square items-center justify-center bg-white/5">
                        <span class="rounded-full border border-white/15 bg-[#040712]/80 px-3 py-1 text-xs font-medium uppercase tracking-wide text-mochi-muted">No Image</span>
                    </div>
                @endif
            </div>
        </div>

        @php
            $initialSelections = collect($this->selectedAttributes)
                ->mapWithKeys(fn ($v, $k) => [(string) $k => (int) $v])
                ->all();
        @endphp
        <div
            class="flex flex-col lg:sticky lg:top-24 lg:self-start"
            x-data="{
                selectedAttributes: @js($initialSelections),
                requiredCount: {{ (int) $this->groupedAttributes->count() }},
                isComplete() {
                    return this.requiredCount === 0 || Object.keys(this.selectedAttributes).length === this.requiredCount;
                },
                pick(attrId, valueId) {
                    this.selectedAttributes[String(attrId)] = Number(valueId);
                },
                selected(attrId, valueId) {
                    return Number(this.selectedAttributes[String(attrId)] ?? 0) === Number(valueId);
                },
             }"
        >
            @if ($product->category)
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-mochi-muted">
                    {{ $product->category->name }}
                </p>
            @endif

            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-white">
                {{ $product->name }}
            </h1>

            <div wire:transition="product-meta" class="transition-opacity duration-200 ease-out">
                <div class="mt-5 flex items-end gap-3">
                    <span class="text-3xl font-semibold tabular-nums text-mochi-coral">
                        {{ $this->formatPrice($this->displayPrice) }}
                    </span>
                    @if ($this->compareAtPrice)
                        <span class="pb-1 text-sm text-mochi-muted line-through">
                            {{ $this->formatPrice($this->compareAtPrice) }}
                        </span>
                    @endif
                </div>

                <p class="mt-3 text-sm {{ $this->displayStock > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    @if ($this->displayStock > 0)
                        Auf Lager ({{ $this->displayStock }} verfügbar)
                    @else
                        Nicht auf Lager
                    @endif
                </p>
            </div>

            @if ($product->has_variants && $this->groupedAttributes->isNotEmpty() && ! $this->selectedVariant)
                <p class="mt-2 text-sm text-mochi-muted">
                    Bitte wähle eine Variante, um den finalen Preis und die Verfügbarkeit zu sehen.
                </p>
            @endif

            @if ($product->short_description)
                <p class="mt-6 text-base text-mochi-muted">
                    {{ $product->short_description }}
                </p>
            @endif

            @if ($this->groupedAttributes->isNotEmpty())
                <div class="mt-8 space-y-6 border-t border-white/10 pt-8">
                    @foreach ($this->groupedAttributes as $attribute)
                        <fieldset wire:key="attr-field-{{ $attribute['id'] }}">
                            <legend class="text-sm font-medium text-white">{{ $attribute['name'] }}</legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($attribute['values'] as $value)
                                    <button
                                        type="button"
                                        x-on:click="pick({{ (int) $attribute['id'] }}, {{ (int) $value->id }})"
                                        wire:key="attr-{{ $attribute['id'] }}-val-{{ $value->id }}"
                                        :class="selected({{ (int) $attribute['id'] }}, {{ (int) $value->id }}) ? 'border-mochi-accent bg-mochi-accent/20 text-white shadow-[0_0_0_1px_rgba(255,122,31,0.45)] ring-2 ring-mochi-accent/30' : 'border-white/20 text-mochi-text hover:border-mochi-accent/40 hover:bg-white/5'"
                                        class="inline-flex min-h-[44px] min-w-10 items-center justify-center rounded-full border px-4 py-2 text-sm font-medium transition-all duration-150 ease-out will-change-transform focus:outline-none focus:ring-2 focus:ring-mochi-accent/40 focus:ring-offset-2 focus:ring-offset-[#040712] active:scale-[0.97]"
                                    >
                                        {{ $value->value }}
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 border-t border-white/10 pt-8">
                <button
                    type="button"
                    x-bind:disabled="{{ $product->has_variants ? '!isComplete()' : 'false' }}"
                    wire:click="addToCart(selectedAttributes)"
                    wire:loading.attr="disabled"
                    wire:target="addToCart"
                    @disabled(! $product->has_variants && ! $this->canAddToCart)
                    class="mochi-gradient-button mochi-gradient-button-sm inline-flex w-full min-h-[48px] items-center justify-center gap-2 px-6 py-3 disabled:pointer-events-none disabled:opacity-45"
                >
                    <svg wire:loading wire:target="addToCart" class="h-5 w-5 shrink-0 animate-spin text-[#0b0f16]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="addToCart">
                        @if ($product->has_variants && $this->groupedAttributes->isNotEmpty())
                            <span x-show="!isComplete()">Variante wählen</span>
                            <span x-show="isComplete()">In den Warenkorb</span>
                        @else
                            In den Warenkorb
                        @endif
                    </span>
                    <span wire:loading wire:target="addToCart">Wird hinzugefügt…</span>
                </button>

                @if ($product->has_variants && $this->groupedAttributes->isNotEmpty())
                    <p class="mt-2 text-xs text-mochi-muted" x-show="!isComplete()">
                        Wähle alle Varianten aus, dann kannst du den Artikel hinzufügen.
                    </p>
                @endif

                @if ($message)
                    <p class="mt-3 text-sm {{ str_contains($message, 'hinzugefügt') ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ $message }}
                    </p>
                @endif
            </div>

            <div class="mt-10 space-y-3">
                <div x-data="{ open: true }" class="overflow-hidden rounded-2xl border border-white/10 bg-[#080c12]/40 backdrop-blur-sm">
                    <button type="button" class="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-semibold text-white" @click="open = !open">
                        <span>Produktdetails</span>
                        <span x-text="open ? '−' : '+'"></span>
                    </button>
                    <div x-show="open" x-transition.opacity.duration.200ms class="border-t border-white/10 px-5 py-4">
                        @if (filled($product->description))
                            <div class="prose prose-invert prose-sm max-w-none leading-6 text-mochi-muted">
                                {!! $product->description !!}
                            </div>
                        @else
                            <p class="text-sm leading-6 text-mochi-muted">Keine weiteren Details verfügbar.</p>
                        @endif
                    </div>
                </div>

                <div x-data="{ open: false }" class="overflow-hidden rounded-2xl border border-white/10 bg-[#080c12]/40 backdrop-blur-sm">
                    <button type="button" class="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-semibold text-white" @click="open = !open">
                        <span>Versand &amp; Rückgabe</span>
                        <span x-text="open ? '−' : '+'"></span>
                    </button>
                    <div x-show="open" x-transition.opacity.duration.200ms class="border-t border-white/10 px-5 py-4 text-sm text-mochi-muted">
                        Versand in 1–3 Werktagen. 30 Tage Rückgabe. Support antwortet in der Regel innerhalb von 24 Stunden.
                    </div>
                </div>

                <div x-data="{ open: false }" class="overflow-hidden rounded-2xl border border-white/10 bg-[#080c12]/40 backdrop-blur-sm">
                    <button type="button" class="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-semibold text-white" @click="open = !open">
                        <span>Material &amp; Pflege</span>
                        <span x-text="open ? '−' : '+'"></span>
                    </button>
                    <div x-show="open" x-transition.opacity.duration.200ms class="border-t border-white/10 px-5 py-4 text-sm text-mochi-muted">
                        Hochwertige Materialauswahl. Pflegehinweise findest du auf dem Etikett oder erhältst sie vom Support.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
