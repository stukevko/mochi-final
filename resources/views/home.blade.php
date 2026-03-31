@extends('layouts.app')

@section('title', $siteName.' · Hub')

@push('promo')
    @include('partials.event-ticker', ['nextEvent' => $nextEvent])
@endpush

@section('content')
    <section
        class="relative overflow-hidden border-b border-white/[0.07] bg-gradient-to-b from-[#0b1120]/45 via-transparent to-[#040712]/40"
        @if ($heroBackgroundUrl) style="background-image: linear-gradient(180deg, rgba(4,7,18,0.75) 0%, rgba(11,17,32,0.55) 45%, rgba(4,7,18,0.82) 100%), url('{{ $heroBackgroundUrl }}'); background-size: cover; background-position: center;" @endif
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_-10%,rgba(255,122,31,0.08),transparent_55%)]"
        ></div>
        <div class="relative">
            <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20 lg:py-24">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-14 xl:gap-20">
                    <div class="flex flex-col lg:pr-2">
                        @if ($heroLogoUrl)
                            <img
                                src="{{ $heroLogoUrl }}"
                                alt="{{ $siteName }}"
                                class="mb-8 h-12 w-auto max-w-[12rem] object-contain opacity-90 sm:mb-10 sm:h-14"
                            >
                        @endif
                        <h1 class="font-display text-4xl font-extrabold uppercase leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-[2.65rem] xl:text-6xl">
                            {{ $heroHeadline }}
                        </h1>
                        <a
                            href="{{ $heroVisitStoreUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mochi-cta mochi-cta-pulse mt-10 w-fit !px-8 !py-3.5 !text-sm font-extrabold sm:!text-base"
                        >
                            Besuch uns im Laden
                        </a>
                    </div>

                    <div class="relative mx-auto w-full max-w-lg lg:mx-0 lg:max-w-none">
                        <div
                            class="pointer-events-none absolute left-1/2 top-[46%] z-0 w-[min(128%,24rem)] -translate-x-1/2 -translate-y-1/2 aspect-square"
                            aria-hidden="true"
                        >
                            <div class="mochi-hero-renner-orb"></div>
                        </div>
                        <div
                            class="mochi-hero-shop-card relative z-10 overflow-hidden rounded-3xl border border-white/20 bg-white/[0.08] p-6 shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_32px_80px_-36px_rgba(255,122,31,0.38),0_28px_72px_-32px_rgba(0,0,0,0.92)] backdrop-blur-2xl sm:p-8"
                        >
                            @if (! empty($featuredProduct['is_placeholder']))
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-mochi-muted/90">
                                    Shop-Highlight
                                </p>
                            @elseif (! empty($featuredProduct['id']))
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-mochi-muted/90">
                                    ID {{ $featuredProduct['id'] }}
                                </p>
                            @else
                                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-mochi-muted/90">
                                    Shop-Highlight
                                </p>
                            @endif
                            <div class="relative mx-auto mt-4 flex min-h-[16rem] items-center justify-center sm:min-h-[18rem]">
                                <div
                                    class="pointer-events-none absolute left-1/2 top-1/2 h-[min(92%,20rem)] w-[min(92%,20rem)] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(255,180,90,0.5)_0%,rgba(255,122,31,0.28)_38%,transparent_70%)] blur-3xl"
                                    aria-hidden="true"
                                ></div>
                                <div
                                    class="pointer-events-none absolute left-1/2 top-1/2 aspect-square w-[min(82%,17rem)] -translate-x-1/2 -translate-y-1/2"
                                    aria-hidden="true"
                                >
                                    <div class="mochi-hero-neon-ring"></div>
                                </div>
                                <div
                                    class="mochi-hero-product-float relative z-10 flex max-h-[min(22rem,52vh)] w-full items-center justify-center px-1"
                                >
                                    @if (! empty($featuredProduct['image_url']))
                                        <img
                                            src="{{ $featuredProduct['image_url'] }}"
                                            alt=""
                                            class="max-h-[min(22rem,52vh)] w-auto max-w-full object-contain drop-shadow-[0_24px_48px_rgba(0,0,0,0.65)]"
                                            loading="eager"
                                        >
                                    @elseif (! empty($featuredProduct['show_community_token_art']))
                                        <div class="mochi-token-holo-wrap relative w-full max-w-[17.5rem] overflow-hidden rounded-2xl">
                                            <span class="mochi-token-holo-sheen" aria-hidden="true"></span>
                                            @include('partials.community-token-card-artwork')
                                        </div>
                                    @else
                                        <div
                                            class="flex h-48 w-48 items-center justify-center rounded-3xl bg-gradient-to-br from-mochi-accent/30 to-mochi-surface-2 font-display text-6xl font-black text-white/85"
                                            aria-hidden="true"
                                        >
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($featuredProduct['title'], 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <p class="relative z-10 mt-5 text-center font-display text-base font-extrabold leading-snug text-white sm:text-lg">
                                {{ $featuredProduct['title'] }}
                            </p>
                            @if (! empty($featuredProduct['description']))
                                <p class="relative z-10 mt-3 text-center text-[13px] leading-relaxed text-mochi-muted sm:text-sm">
                                    {{ $featuredProduct['description'] }}
                                </p>
                            @endif
                            @if (! empty($featuredProduct['price']))
                                <p class="relative z-10 mt-3 text-center font-display text-2xl font-black tabular-nums text-mochi-coral sm:text-3xl">
                                    {{ $featuredProduct['price'] }}
                                </p>
                            @endif
                            <a
                                href="{{ $featuredProduct['url'] }}"
                                @if (! empty($featuredProduct['internal_shop_product']))
                                    wire:navigate.hover
                                @elseif (\Illuminate\Support\Str::startsWith((string) $featuredProduct['url'], ['http://', 'https://']))
                                    target="_blank"
                                    rel="noopener noreferrer"
                                @endif
                                class="relative z-10 mt-6 flex w-full items-center justify-center rounded-full bg-gradient-to-r from-mochi-accent to-mochi-accent-strong px-6 py-3.5 text-center text-sm font-extrabold text-[#0b0f16] shadow-[0_12px_40px_-12px_rgba(255,122,31,0.55)] transition hover:-translate-y-0.5"
                            >
                                {{ ! empty($featuredProduct['internal_shop_product']) ? 'Zum Artikel' : 'Zum Shop' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section
        class="mochi-section-watermark border-b border-white/[0.06] bg-white/[0.02] backdrop-blur-md"
        aria-label="Vorteile"
    >
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <div
                class="mochi-section-inner grid grid-cols-2 gap-x-4 gap-y-6 sm:flex sm:flex-wrap sm:items-start sm:justify-between sm:gap-6 lg:gap-8"
            >
                @foreach ($benefitTiles as $tile)
                    <div
                        class="flex flex-col items-center gap-2.5 rounded-2xl border border-white/12 bg-[#060a10]/70 px-3 py-4 text-center shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] backdrop-blur-xl sm:flex-1 sm:min-w-0 sm:max-w-[11rem] sm:gap-3 sm:border-white/10 sm:bg-[#080d14]/50 sm:py-5"
                    >
                        <span class="mochi-benefit-icon-metallic">
                            @include('partials.benefit-icon', ['name' => $tile['icon'], 'compact' => true])
                        </span>
                        <p
                            class="max-w-[11rem] text-[11px] font-extrabold leading-tight tracking-tight text-white sm:text-xs"
                        >
                            {{ $tile['title'] }}
                        </p>
                        @if (! empty($tile['body']))
                            <p class="max-w-[11rem] text-[9px] font-medium leading-snug text-mochi-muted/95 sm:text-[10px]">
                                {{ $tile['body'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mochi-section-watermark mx-auto max-w-6xl bg-transparent px-4 pb-8 pt-14 sm:px-6">
        <div class="mochi-section-inner flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <h2 class="font-display text-2xl font-black tracking-tight text-white sm:text-3xl">Nächste Events</h2>
            <a
                href="{{ route('events.calendar') }}"
                class="shrink-0 rounded-full border border-mochi-accent/40 bg-mochi-accent/10 px-5 py-2 text-sm font-bold text-mochi-accent backdrop-blur-md transition hover:border-mochi-accent/55 hover:bg-mochi-accent/15"
            >
                Kalender →
            </a>
        </div>
        @if ($upcomingEvents->isEmpty())
            <div
                class="mochi-section-inner mt-6 rounded-2xl border border-dashed border-white/15 bg-gradient-to-br from-mochi-surface/40 to-[#080d14]/50 px-6 py-12 text-center backdrop-blur-xl"
            >
                <p class="font-display text-lg font-bold text-white">Noch keine Events in Sicht</p>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-mochi-muted">
                    Aktuell planen wir neue Abenteuer für euch – schau bald wieder vorbei!
                </p>
            </div>
        @else
            <div
                class="mochi-section-inner mt-6 flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth overscroll-x-contain py-2 pb-8 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            >
                @php
                    $weekdayDe2 = ['SO', 'MO', 'DI', 'MI', 'DO', 'FR', 'SA'];
                @endphp
                @foreach ($upcomingEvents as $ev)
                    <a
                        href="{{ route('events.show', $ev) }}"
                        class="mochi-home-snap-card group flex min-w-[14rem] max-w-[16rem] flex-1 snap-start flex-col overflow-hidden rounded-2xl border border-white/14 bg-white/[0.06] shadow-[0_12px_40px_-28px_rgba(0,0,0,0.85)] backdrop-blur-xl sm:min-w-[15.5rem]"
                    >
                        <div class="relative h-[8rem] w-full shrink-0 overflow-hidden sm:h-36">
                            @if ($ev->image_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ev->image_path) }}"
                                    alt=""
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.06]"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @else
                                @php
                                    $evCc = $ev->gameTypeCalendarColors();
                                @endphp
                                <div
                                    class="flex h-full w-full items-center justify-center px-3 text-center"
                                    style="background: linear-gradient(155deg, {{ $evCc['border'] }}55 0%, rgb(8 13 20) 78%);"
                                >
                                    <span class="font-display text-xs font-extrabold uppercase tracking-wide text-white/95">
                                        {{ $ev->gameTypeLabel() ?: 'Event' }}
                                    </span>
                                </div>
                            @endif
                            <div
                                class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-t from-[#05080d]/80 via-transparent to-transparent"
                            ></div>
                            <span
                                class="absolute left-2.5 top-2.5 z-[2] inline-flex w-fit max-w-[calc(100%-1.25rem)] items-center rounded-lg bg-gradient-to-r from-mochi-accent to-mochi-accent-strong px-2.5 py-1 font-display text-[11px] font-extrabold tabular-nums uppercase tracking-wide text-[#0b0f16]"
                            >
                                {{ $weekdayDe2[(int) $ev->starts_at->format('w')] }} {{ $ev->starts_at->format('d.m.') }}.
                            </span>
                        </div>
                        <div class="flex flex-1 flex-col px-3.5 py-3">
                            <p class="line-clamp-2 font-display text-sm font-extrabold leading-snug text-white">
                                {{ $ev->title }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mochi-section-watermark mx-auto max-w-6xl bg-transparent px-4 pb-24 sm:px-6 sm:pb-28">
        <div class="mochi-section-inner flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <h2 class="font-display text-2xl font-black tracking-tight text-white sm:text-3xl">News</h2>
            <a
                href="{{ route('posts.index') }}"
                class="shrink-0 rounded-full border border-mochi-accent/40 bg-mochi-accent/10 px-5 py-2 text-sm font-bold text-mochi-accent backdrop-blur-md transition hover:border-mochi-accent/55 hover:bg-mochi-accent/15"
            >
                Alle →
            </a>
        </div>

        <div
            class="mochi-section-inner mt-6 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth overscroll-x-contain py-2 pb-8 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
            @php
                $weekdayDeNews = ['SO', 'MO', 'DI', 'MI', 'DO', 'FR', 'SA'];
            @endphp
            @forelse ($latestPosts as $post)
                <a
                    href="{{ route('posts.show', $post) }}"
                    class="group mochi-collectible-card mochi-home-snap-card flex w-[min(82vw,17.5rem)] shrink-0 snap-start flex-col rounded-2xl border border-white/10 bg-white/[0.04] backdrop-blur-xl sm:w-[17.5rem]"
                >
                    <div class="relative min-h-[8rem] flex-[4] overflow-hidden rounded-t-2xl sm:min-h-[8.5rem]">
                        @include('partials.post-card-thumbnail', [
                            'post' => $post,
                            'class' => '!aspect-auto h-full min-h-[8rem] w-full rounded-none ring-0 sm:min-h-[8.5rem]',
                        ])
                        @if ($post->published_at)
                            <span
                                class="absolute left-2.5 top-2.5 z-[2] inline-flex w-fit max-w-[calc(100%-1.25rem)] items-center rounded-lg bg-gradient-to-r from-mochi-accent to-mochi-accent-strong px-2.5 py-1 font-display text-[11px] font-extrabold tabular-nums uppercase tracking-wide text-[#0b0f16]"
                            >
                                {{ $weekdayDeNews[(int) $post->published_at->format('w')] }} {{ $post->published_at->format('d.m.') }}.
                            </span>
                        @endif
                        <div
                            class="pointer-events-none absolute inset-0 z-[1] bg-gradient-to-t from-[#05080d]/85 via-transparent to-transparent"
                        ></div>
                    </div>
                    <div
                        class="flex flex-[1] flex-col justify-center rounded-b-2xl border-t border-white/10 px-3 py-2 backdrop-blur-md"
                    >
                        <h3 class="line-clamp-2 font-display text-[11px] font-bold leading-snug text-white group-hover:text-mochi-coral sm:text-xs">
                            {{ $post->title }}
                        </h3>
                        <time
                            class="sr-only"
                            datetime="{{ $post->published_at?->toIso8601String() }}"
                        >
                            {{ $post->published_at?->format('d.m.Y') }}
                        </time>
                    </div>
                </a>
            @empty
                <a
                    href="{{ route('posts.index') }}"
                    class="group mochi-collectible-card mochi-home-snap-card flex w-full max-w-md shrink-0 snap-start flex-col overflow-hidden rounded-2xl border border-white/12 bg-white/[0.05] text-left shadow-[0_16px_48px_-32px_rgba(0,0,0,0.9)] backdrop-blur-xl sm:w-[min(92vw,24rem)]"
                >
                    <div class="relative h-[8rem] w-full shrink-0 overflow-hidden bg-gradient-to-br from-mochi-accent/35 via-[#0f1724] to-[#040712]">
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#05080d]/80 via-transparent to-transparent"
                        ></div>
                        <span
                            class="absolute left-2.5 top-2.5 z-[2] inline-flex items-center rounded-lg bg-gradient-to-r from-mochi-accent to-mochi-accent-strong px-2.5 py-1 font-display text-[11px] font-extrabold uppercase tracking-wide text-[#0b0f16]"
                        >
                            MOCHI
                        </span>
                    </div>
                    <div class="border-b border-white/10 bg-mochi-accent/10 px-4 py-2">
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-mochi-accent">Vorschau</p>
                    </div>
                    <div class="space-y-3 px-4 py-5">
                        <h3 class="font-display text-sm font-extrabold leading-snug text-white group-hover:text-mochi-coral sm:text-base">
                            {{ config('mochicards.home_news_fallback_title') }}
                        </h3>
                        <p class="text-[13px] leading-relaxed text-mochi-muted">
                            {{ config('mochicards.home_news_fallback_body') }}
                        </p>
                        <span class="inline-flex text-xs font-bold text-mochi-accent">Alle News ansehen →</span>
                    </div>
                </a>
            @endforelse
        </div>
    </section>
@endsection
