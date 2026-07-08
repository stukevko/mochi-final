<div class="mx-auto max-w-6xl px-4 py-8 text-mochi-text sm:px-6 sm:py-12" x-data="{ mobileFiltersOpen: false }">
    @php
        $breadcrumbLd = \App\Support\BreadcrumbJsonLd::forShop($this->currentCategory);
    @endphp
    <script type="application/ld+json">@json($breadcrumbLd)</script>

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="font-display text-3xl font-black tracking-tight text-white sm:text-4xl">
            @if($saleOnly)
                Sale &amp; Angebote
            @else
                {{ $this->currentCategory?->name ?? 'Alle Produkte' }}
            @endif
        </h1>
        @if($saleOnly)
            <p class="mt-2 text-sm font-medium text-mochi-accent">Nur reduzierte Artikel</p>
        @elseif($this->currentCategory?->description)
            <p class="mt-2 text-sm text-mochi-muted">{{ $this->currentCategory->description }}</p>
        @endif
        <div class="mt-2">
            @include('partials.price-legal-hint', ['compact' => true])
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-4 lg:gap-8">
        <!-- Sidebar Filters -->
        <aside class="hidden lg:block">
            <div class="mochi-card mochi-card-hover sticky top-24 space-y-6 p-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-white">Suche</label>
                    <div class="relative mt-2">
                        <input type="text" 
                               id="search"
                               wire:model.live.debounce.300ms="search"
                               placeholder="Produkte suchen..."
                               class="block w-full rounded-xl border border-white/10 bg-white/5 py-2.5 pl-10 pr-3 text-white ring-0 placeholder:text-mochi-muted focus:border-mochi-accent/40 focus:outline-none focus:ring-2 focus:ring-mochi-accent/25 sm:text-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div>
                    <h3 class="text-sm font-medium text-white">Kategorien</h3>
                    <ul class="mt-3 space-y-2">
                        <li>
                            <button type="button" wire:click="$set('categorySlug', null)"
                                    class="flex min-h-11 w-full items-center justify-between rounded-xl px-3 py-2 text-sm transition {{ !$categorySlug ? 'bg-mochi-accent/25 font-semibold text-white ring-1 ring-mochi-accent/35' : 'text-mochi-muted hover:bg-white/5 hover:text-white' }}">
                                <span>Alle Produkte</span>
                                    <span class="{{ !$categorySlug ? 'text-white/80' : 'text-mochi-muted' }}">
                                    {{ $this->totalActiveProductCount }}
                                </span>
                            </button>
                        </li>
                        @foreach($this->categories as $category)
                            <li>
                                <button type="button" wire:click="$set('categorySlug', '{{ $category->slug }}')"
                                        class="flex min-h-11 w-full items-center justify-between rounded-xl px-3 py-2 text-sm transition {{ $categorySlug === $category->slug ? 'bg-mochi-accent/25 font-semibold text-white ring-1 ring-mochi-accent/35' : 'text-mochi-muted hover:bg-white/5 hover:text-white' }}">
                                    <span>{{ $category->name }}</span>
                                    <span class="{{ $categorySlug === $category->slug ? 'text-white/80' : 'text-mochi-muted' }}">
                                        {{ $category->products_count }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm font-medium text-mochi-text transition hover:border-mochi-accent/25">
                        <input type="checkbox" wire:model.live="saleOnly" class="h-4 w-4 rounded border-white/20 bg-transparent text-mochi-accent focus:ring-mochi-accent/40">
                        <span>Nur Sale</span>
                    </label>
                </div>

                <!-- Clear Filters -->
                @if($categorySlug || $search || $sortBy !== 'newest' || $saleOnly)
                    <button type="button" wire:click="clearFilters"
                            class="flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-white/15 px-4 py-2 text-sm font-medium text-mochi-muted transition hover:bg-white/5 hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                        Filter zurücksetzen
                    </button>
                @endif
            </div>
        </aside>

        <!-- Product Grid Area -->
        <div class="lg:col-span-3">
            <!-- Mobile Filter + Sort Bar -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Mobile Search: sticky unter dem Header, Daumen-freundlich -->
                <div class="relative sticky top-16 z-30 -mx-4 border-b border-white/10 bg-[#080c12]/85 px-4 py-3 backdrop-blur-md lg:hidden">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Suchen..."
                           class="block min-h-11 w-full rounded-xl border border-white/10 bg-white/5 py-2.5 pl-11 pr-3 text-white ring-0 placeholder:text-mochi-muted focus:border-mochi-accent/35 focus:outline-none focus:ring-2 focus:ring-mochi-accent/25 sm:text-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center pl-0">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Results count -->
                <p class="text-sm text-mochi-muted">
                    <span class="font-medium">{{ $this->products->total() }}</span> Produkte gefunden
                </p>
                <button type="button" @click="mobileFiltersOpen = true" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/15 px-4 py-2 text-sm font-medium text-mochi-muted transition hover:bg-white/5 hover:text-white lg:hidden">
                    Filter
                </button>

                <!-- Sort Dropdown -->
                <select wire:model.live="sortBy"
                        class="min-h-11 rounded-xl border border-white/10 bg-white/5 py-2 pl-3 pr-8 text-sm text-white focus:border-mochi-accent/35 focus:outline-none focus:ring-2 focus:ring-mochi-accent/25">
                    <option value="newest">Neueste zuerst</option>
                    <option value="price_asc">Preis: niedrig → hoch</option>
                    <option value="price_desc">Preis: hoch → niedrig</option>
                    <option value="name_asc">Name: A → Z</option>
                    <option value="name_desc">Name: Z → A</option>
                </select>
            </div>

            <label class="mb-6 flex cursor-pointer items-center gap-2 lg:hidden">
                <input type="checkbox" wire:model.live="saleOnly" class="h-4 w-4 rounded border-white/20 text-mochi-accent focus:ring-mochi-accent/40">
                <span class="text-sm font-medium text-mochi-text">Nur Sale</span>
            </label>

            <!-- Product Grid -->
            <div wire:loading.flex wire:target="search,categorySlug,sortBy,saleOnly,clearFilters" class="mb-6 items-center gap-2 rounded-xl border border-mochi-accent/25 bg-mochi-accent/10 px-3 py-2 text-sm text-mochi-accent-strong">
                <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-mochi-accent"></span>
                Produkte werden aktualisiert…
            </div>

            @if($this->categoryIsEmpty)
                <div class="col-span-full flex flex-col items-center justify-center rounded-3xl border border-dashed border-white/15 bg-gradient-to-br from-mochi-surface/35 to-[#080d14]/45 px-6 py-16 text-center backdrop-blur-xl">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-mochi-muted">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <h3 class="mt-5 font-display text-lg font-bold text-white">Hier arbeiten wir am Sortiment</h3>
                    <p class="mt-2 max-w-md text-sm text-mochi-muted">
                        Diese Kategorie ist im Moment leer. Schau bald wieder vorbei oder stöbere in anderen Kategorien.
                    </p>
                    <a href="{{ route('shop') }}" wire:navigate.hover class="mochi-cta mt-6 !min-h-11 !px-6">
                        Bald wieder verfügbar — zum Shop
                    </a>
                </div>
            @elseif($this->products->count() > 0)
                {{-- Kein zweites Skeleton-Grid: wire:loading + wire:loading.remove überlappt bei Navigate/Hydration → leere „Geisterkarten“ über den echten Produkten. --}}
                <div
                    class="grid grid-cols-2 gap-4 transition-opacity duration-200 sm:gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                    wire:loading.class="pointer-events-none opacity-40"
                    wire:target="search,categorySlug,sortBy,saleOnly,clearFilters"
                >
                    @foreach($this->products as $product)
                        @php
                            $neon = $product->storefrontNeonAccent();
                        @endphp
                        <article
                            wire:key="product-{{ $product->id }}"
                            class="mochi-collectible-card group flex h-full flex-col overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-none backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:shadow-[0_16px_48px_-28px_rgba(0,0,0,0.85)]"
                            style="border-color: {{ $neon['border'] }}66; box-shadow: {{ $neon['shadow'] }};"
                        >
                            <div class="relative p-2 pb-0">
                                <a
                                    href="{{ url('/product/'.$product->slug) }}"
                                    wire:navigate.hover
                                    class="relative block aspect-square overflow-hidden rounded-2xl bg-mochi-surface-2"
                                >
                                    @if($product->is_on_sale)
                                        <span
                                            class="absolute left-3 top-3 z-10 rounded-full bg-rose-500/90 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white ring-1 ring-white/20"
                                        >
                                            Sale
                                        </span>
                                    @endif
                                    @if($gridImg = $product->safeImageUrl())
                                        <img
                                            src="{{ $gridImg }}"
                                            alt="{{ $product->name }}"
                                            class="h-full w-full object-cover object-center transition duration-500 group-hover:scale-[1.04]"
                                            loading="lazy"
                                            decoding="async"
                                            sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 20vw"
                                            width="600"
                                            height="600"
                                        >
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-mochi-muted">
                                            {{ $product->category?->name ?? 'Mochi' }}
                                        </div>
                                    @endif
                                </a>
                            </div>

                            <div class="flex min-h-0 flex-1 flex-col px-4 pb-4 pt-2">
                                @if($product->category)
                                    <p class="line-clamp-1 text-[10px] font-semibold uppercase tracking-wide text-mochi-muted">
                                        {{ $product->category->name }}
                                    </p>
                                @endif

                                <h3 class="mt-1 min-h-[2.5rem] text-sm font-bold leading-snug text-white">
                                    <a
                                        href="{{ url('/product/'.$product->slug) }}"
                                        wire:navigate.hover
                                        class="line-clamp-2 transition hover:text-mochi-accent"
                                    >
                                        {{ $product->name }}
                                    </a>
                                </h3>

                                @if($product->has_variants && ($product->variants_count ?? 0) > 0)
                                    <p class="mt-1 line-clamp-1 text-[10px] text-mochi-muted">
                                        {{ $product->variants_count }} Varianten
                                    </p>
                                @endif

                                <div class="mt-3 flex flex-wrap items-baseline gap-2">
                                    <span class="text-lg font-black tabular-nums text-white">
                                        {{ $this->formatPrice($product->current_price) }}
                                    </span>
                                    @if($product->is_on_sale)
                                        <span class="text-xs text-mochi-muted line-through">
                                            {{ $this->formatPrice($product->price) }}
                                        </span>
                                    @endif
                                </div>

                                <a
                                    href="{{ url('/product/'.$product->slug) }}"
                                    wire:navigate.hover
                                    class="mochi-cta-muted mt-auto !min-h-10 !px-4 !py-2 !text-xs"
                                >
                                    Details
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($this->products->hasMorePages())
                    <div class="mt-8 flex flex-col items-center gap-3">
                        <button type="button"
                                wire:click="loadMore"
                                wire:loading.attr="disabled"
                                wire:target="loadMore"
                                class="inline-flex min-h-11 items-center justify-center rounded-full border border-white/15 bg-white/5 px-6 text-sm font-semibold text-white transition hover:border-mochi-accent/35 hover:bg-mochi-accent/10 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="loadMore">Weitere Produkte laden</span>
                            <span wire:loading wire:target="loadMore" class="inline-flex items-center gap-2">
                                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Lädt…
                            </span>
                        </button>
                        <p class="text-center text-xs text-mochi-muted">
                            {{ $this->products->count() }} von {{ $this->products->total() }} angezeigt
                        </p>
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-white/15 bg-white/[0.03] py-16 backdrop-blur-xl">
                    <svg class="h-12 w-12 text-mochi-muted" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <h3 class="mt-4 font-display text-lg font-bold text-white">Keine Produkte gefunden</h3>
                    <p class="mt-1 text-sm text-mochi-muted">
                        Versuche deine Suchkriterien zu ändern oder alle Filter zurückzusetzen.
                    </p>
                    @if($categorySlug || $search || $sortBy !== 'newest' || $saleOnly)
                        <button wire:click="clearFilters"
                                class="mochi-cta mt-4 !min-h-11 !px-5 !text-sm">
                            Filter zurücksetzen
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div x-show="mobileFiltersOpen" x-transition.opacity class="drawer-backdrop lg:hidden" @click="mobileFiltersOpen = false"></div>
    <aside x-show="mobileFiltersOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="drawer-panel drawer-panel-motion lg:hidden" x-cloak>
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-4">
            <p class="text-sm font-semibold text-white">Filter</p>
            <button type="button" @click="mobileFiltersOpen = false" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-mochi-muted transition hover:bg-white/5 hover:text-white" aria-label="Schließen">✕</button>
        </div>
        <div class="space-y-5 p-4">
            <div>
                <label class="block text-sm font-medium text-mochi-text">Suche</label>
                <input type="text" wire:model.live.debounce.300ms="search" class="mochi-input mt-2 block min-h-11 w-full rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <h3 class="text-sm font-medium text-mochi-text">Kategorien</h3>
                <div class="mt-2 space-y-2">
                    <button wire:click="$set('categorySlug', null)" @click="mobileFiltersOpen = false" class="flex min-h-11 w-full items-center rounded-xl border border-white/10 px-3 py-2 text-left text-sm text-mochi-muted transition hover:bg-white/5 hover:text-white">Alle Produkte</button>
                    @foreach($this->categories as $category)
                        <button type="button" wire:click="$set('categorySlug', '{{ $category->slug }}')" @click="mobileFiltersOpen = false" class="flex min-h-11 w-full items-center rounded-xl border border-white/10 px-3 py-2 text-left text-sm text-mochi-muted transition hover:bg-white/5 hover:text-white">{{ $category->name }}</button>
                    @endforeach
                </div>
            </div>
            <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-white/10 px-3 py-2 text-sm text-mochi-text">
                <input type="checkbox" wire:model.live="saleOnly" class="h-5 w-5 shrink-0 rounded border-white/20 bg-[#040712] text-mochi-accent focus:ring-mochi-accent/40">
                Nur Sale
            </label>
        </div>
    </aside>
</div>
