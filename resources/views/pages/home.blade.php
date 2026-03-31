<x-layouts.app title="Willkommen">
    <!-- Hero Section (Inhalt: Admin → Startseiten-Hero) -->
    @php
        $h = $hero ?? null;
        $heroHeadline = \Illuminate\Support\Arr::get($h, 'headline') ?? 'Shopping, klar und schnell.';
        $heroSub = \Illuminate\Support\Arr::get($h, 'subheadline') ?? 'Entdecke deine Lieblingsprodukte mit einer modernen Store-Experience: bessere Orientierung, schnellere Auswahl, klarer Checkout.';
        $ctaLabel = \Illuminate\Support\Arr::get($h, 'cta_label') ?? 'Jetzt entdecken';
        $ctaUrlRaw = \Illuminate\Support\Arr::get($h, 'cta_url');
        $ctaHref = $ctaUrlRaw
            ? (str_starts_with((string) $ctaUrlRaw, 'http') ? $ctaUrlRaw : url($ctaUrlRaw))
            : url('/shop');
        $bgPath = \Illuminate\Support\Arr::get($h, 'background_image');
        $heroBg = $bgPath
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($bgPath)
            : null;
    @endphp
    <section
        class="relative overflow-hidden bg-gradient-to-br from-blue-50 via-white to-cyan-50 {{ $heroBg ? 'bg-cover bg-center' : '' }}"
        @if($heroBg)
            style="background-image: linear-gradient(rgba(248,250,252,0.92), rgba(255,255,255,0.94)), url('{{ $heroBg }}');"
        @endif
    >
        <div class="absolute -right-16 top-10 h-52 w-52 rounded-full bg-blue-200/40 blur-3xl"></div>
        <div class="absolute -left-16 bottom-6 h-52 w-52 rounded-full bg-cyan-200/40 blur-3xl"></div>
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                <h1 class="font-display text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl md:text-6xl">
                    {{ $heroHeadline }}
                </h1>
                <p class="mt-6 max-w-2xl text-lg text-slate-600">
                    {{ $heroSub }}
                </p>
                <div class="mt-10 flex flex-col items-start gap-4 sm:flex-row">
                    <a href="{{ $ctaHref }}"
                       @if(str_starts_with((string) $ctaHref, url('/'))) wire:navigate @endif
                       class="btn-primary">
                        {{ $ctaLabel }}
                        <svg class="ml-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>

                <div class="mt-8 max-w-xl rounded-2xl border border-white/20 bg-white/70 p-5 shadow-sm backdrop-blur-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-blue-700">Deal endet in</p>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-center sm:grid-cols-4 sm:gap-3" data-deal-timer data-end-hours="18">
                        <div class="min-w-0 rounded-xl bg-slate-900 px-1 py-2.5 text-white sm:px-2 sm:py-3"><p class="font-mono text-lg font-bold tabular-nums sm:text-xl" data-unit="days">00</p><p class="mt-0.5 text-[10px] uppercase leading-tight text-slate-300">Tage</p></div>
                        <div class="min-w-0 rounded-xl bg-slate-900 px-1 py-2.5 text-white sm:px-2 sm:py-3"><p class="font-mono text-lg font-bold tabular-nums sm:text-xl" data-unit="hours">00</p><p class="mt-0.5 text-[10px] uppercase leading-tight text-slate-300">Std</p></div>
                        <div class="min-w-0 rounded-xl bg-slate-900 px-1 py-2.5 text-white sm:px-2 sm:py-3"><p class="font-mono text-lg font-bold tabular-nums sm:text-xl" data-unit="minutes">00</p><p class="mt-0.5 text-[10px] uppercase leading-tight text-slate-300">Min</p></div>
                        <div class="min-w-0 rounded-xl bg-slate-900 px-1 py-2.5 text-white sm:px-2 sm:py-3"><p class="font-mono text-lg font-bold tabular-nums sm:text-xl" data-unit="seconds">00</p><p class="mt-0.5 text-[10px] uppercase leading-tight text-slate-300">Sek</p></div>
                    </div>
                </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-blue-200/30 to-cyan-200/20 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-[2rem] border border-white/20 bg-white/60 p-3 shadow-2xl backdrop-blur-md">
                        @if($heroProduct && $heroProduct->safeImageUrl())
                            <img src="{{ $heroProduct->safeImageUrl() }}" alt="{{ $heroProduct->name }}" class="aspect-[4/5] w-full rounded-[1.4rem] object-cover" />
                        @else
                            <div class="flex aspect-[4/5] w-full items-center justify-center rounded-[1.4rem] bg-slate-100">
                                <span class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-medium uppercase tracking-wide text-slate-500">Premium Product</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    @if($featuredProducts->count() > 0)
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Highlights
                </h2>
                <a href="{{ url('/shop') }}" wire:navigate class="text-sm font-medium text-blue-700 transition hover:text-blue-800">
                    Alle anzeigen →
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach($featuredProducts as $product)
                    <article class="card-elevated group flex h-full flex-col overflow-hidden">
                        <div class="relative p-2 pb-0">
                            <a href="{{ url('/product/' . $product->slug) }}" wire:navigate
                               class="relative block aspect-square overflow-hidden rounded-xl bg-slate-100">
                                @if($product->sale_price)
                                    <span class="absolute left-2 top-2 z-10 rounded-full bg-rose-500 px-2 py-1 text-xs font-semibold text-white shadow-sm">
                                        Sale
                                    </span>
                                @endif
                                @if($homeImg = $product->safeImageUrl())
                                    <img src="{{ $homeImg }}"
                                         alt="{{ $product->name }}"
                                         class="h-full w-full object-cover object-center transition duration-300 group-hover:scale-105"
                                         loading="lazy"
                                         decoding="async"
                                         sizes="(max-width: 640px) 100vw, 25vw"
                                         width="600"
                                         height="600">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-slate-100">
                                        <span class="rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-medium uppercase tracking-wide text-slate-500">No Image</span>
                                    </div>
                                @endif
                            </a>
                        </div>

                        <div class="flex min-h-0 flex-1 flex-col p-4 pt-3">
                            @if($product->category)
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                    {{ $product->category->name }}
                                </p>
                            @endif

                            <h3 class="mt-1 min-h-[2.75rem] text-base font-semibold leading-snug text-slate-900">
                                <a href="{{ url('/product/' . $product->slug) }}" wire:navigate.hover
                                   class="line-clamp-2 transition hover:text-blue-700">
                                    {{ $product->name }}
                                </a>
                            </h3>

                            <div class="mt-2 flex flex-wrap items-baseline gap-2">
                                <span class="text-lg font-bold {{ $product->sale_price ? 'text-rose-600' : 'text-slate-900' }}">
                                    {{ \App\Support\MoneyFormatter::format((float) $product->current_price) }}
                                </span>
                                @if($product->sale_price)
                                    <span class="text-sm text-slate-500 line-through">
                                        {{ \App\Support\MoneyFormatter::format((float) $product->price) }}
                                    </span>
                                @endif
                            </div>

                            <a href="{{ url('/product/' . $product->slug) }}" wire:navigate.hover
                               class="mt-auto inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 max-sm:translate-y-0 max-sm:opacity-100 sm:translate-y-1 sm:opacity-0 sm:duration-300 sm:group-hover:translate-y-0 sm:group-hover:opacity-100">
                                In den Warenkorb
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Categories Section -->
    @if($categories->count() > 0)
        <section class="bg-slate-100/70">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Kategorien
                </h2>

                <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    @foreach($categories as $category)
                        <a href="{{ url('/shop?category=' . $category->slug) }}" wire:navigate
                           class="group relative flex h-64 items-center justify-center overflow-hidden rounded-2xl bg-slate-200 shadow-sm transition duration-300 hover:shadow-lg">
                            @if($catImg = $category->safeImageUrl())
                                <img src="{{ $catImg }}"
                                     alt="{{ $category->name }}"
                                     class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                     loading="lazy"
                                     decoding="async"
                                     sizes="(max-width: 640px) 100vw, 33vw">
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/55 to-slate-900/25"></div>
                            <div class="relative rounded-2xl border border-white/30 bg-white/20 px-6 py-5 text-center shadow-lg backdrop-blur-md">
                                <h3 class="text-2xl font-extrabold tracking-tight text-white">{{ $category->name }}</h3>
                                <p class="mt-1 text-sm font-medium text-white/90">Jetzt entdecken</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
