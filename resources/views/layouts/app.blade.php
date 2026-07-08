{{--
    ╔═══════════════════════════════════════════════════════════╗
    ║  · MOCHI CARDS CMS — Front-Layout                         ║
    ║  · Craft / Kevko — Cursor build — 2026                    ║
    ╚═══════════════════════════════════════════════════════════╝
--}}
<!DOCTYPE html>
<html class="min-h-full bg-[#040712]" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('mochicards.site_name'))</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml" sizes="any">
        @stack('meta')
        @include('partials.consent-config', ['consentLayout' => 'storefront'])
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('head')
    </head>

    <body
        x-data="{ cartDrawerOpen: false }"
        x-on:open-cart-drawer.window="cartDrawerOpen = true"
        x-on:keydown.escape.window="cartDrawerOpen = false"
        class="relative min-h-screen bg-gradient-to-b from-[#0b1120] to-[#040712] font-sans text-gray-100 antialiased mochi-page-gradient"
    >
        @include('partials.background-atmosphere')

        <div class="nav-progress" aria-hidden="true"></div>

        <div class="relative z-10 flex min-h-screen flex-col">
            @stack('promo')

            <header id="mochi-site-header" class="mochi-header sticky top-0 z-40">
                <div
                    class="relative mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:gap-4 sm:px-6"
                >
                    <div class="relative flex min-w-0 flex-1 items-center justify-center md:flex-none md:justify-start">
                        <a
                            href="{{ route('home') }}"
                            class="mochi-brand-link inline-flex max-w-[calc(100%-6.75rem)] items-center justify-center md:max-w-none md:justify-start"
                            aria-label="{{ $shopDisplayName ?? $siteName }}"
                        >
                            <img
                                src="{{ $shopLogoUrl ?? asset('images/mochi-logo-placeholder.png') }}"
                                alt="{{ $shopDisplayName ?? $siteName }}"
                                class="h-9 w-auto max-w-[11rem] object-contain sm:h-10 {{ ($shopLogoIsPlaceholder ?? true) ? 'opacity-95' : '' }}"
                                width="176"
                                height="40"
                            >
                        </a>
                        <a
                            href="{{ route('shop') }}"
                            class="absolute right-10 inline-flex shrink-0 items-center justify-center rounded-lg border border-white/15 p-2 text-white/90 transition hover:border-mochi-accent/40 hover:bg-white/5 hover:text-mochi-coral md:hidden"
                            aria-label="Shop"
                        >
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.25 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75.375a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                                />
                            </svg>
                        </a>
                        <button
                            type="button"
                            id="mochi-nav-toggle"
                            class="absolute right-0 inline-flex shrink-0 items-center justify-center rounded-lg border border-white/15 p-2 text-white/90 transition hover:border-white/30 hover:bg-white/5 md:hidden"
                            aria-controls="mochi-site-nav"
                            aria-expanded="false"
                            aria-label="Menü öffnen"
                        >
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>
                    <nav
                        id="mochi-site-nav"
                        class="mochi-site-nav hidden w-full flex-col gap-2 text-sm font-medium text-gray-100 md:flex md:w-auto md:flex-row md:flex-wrap md:items-center md:gap-x-2 md:gap-y-2 md:pr-1"
                    >
                        <a
                            href="{{ route('shop') }}"
                            class="order-first mochi-cta mochi-cta-pulse !px-4 !py-2 !text-xs sm:!text-sm md:!hidden"
                        >
                            Shop
                        </a>
                        <a
                            href="{{ route('events.index') }}"
                            @class([
                                'rounded-full px-3 py-1.5 transition',
                                request()->routeIs('events.index', 'events.show')
                                    ? 'bg-mochi-accent/15 font-semibold text-mochi-accent ring-1 ring-mochi-accent/35'
                                    : 'text-white/90 hover:bg-white/10 hover:text-white',
                            ])
                        >
                            Events
                        </a>
                        <a
                            href="{{ route('posts.index') }}"
                            @class([
                                'rounded-full px-3 py-1.5 transition',
                                request()->routeIs('posts.index', 'posts.show')
                                    ? 'bg-mochi-accent/15 font-semibold text-mochi-accent ring-1 ring-mochi-accent/35'
                                    : 'text-white/90 hover:bg-white/10 hover:text-white',
                            ])
                        >
                            News
                        </a>
                        <a
                            href="{{ route('contact') }}"
                            @class([
                                'rounded-full px-3 py-1.5 transition',
                                request()->routeIs('contact')
                                    ? 'bg-mochi-accent/15 font-semibold text-mochi-accent ring-1 ring-mochi-accent/35'
                                    : 'text-white/90 hover:bg-white/10 hover:text-white',
                            ])
                        >
                            Kontakt
                        </a>
                        <a
                            href="{{ route('shop') }}"
                            @class([
                                'rounded-full px-3 py-1.5 transition',
                                request()->routeIs('shop', 'product.show', 'cart', 'checkout')
                                    ? 'bg-mochi-accent/15 font-semibold text-mochi-accent ring-1 ring-mochi-accent/35'
                                    : 'text-white/90 hover:bg-white/10 hover:text-white',
                            ])
                        >
                            Shop
                        </a>
                        <div class="flex items-center gap-1 md:pl-1">
                            <livewire:shop.cart-icon />
                        </div>
                    </nav>
                </div>
            </header>

            <main class="relative flex-1 bg-transparent text-gray-100">
                @yield('content')
            </main>

            <footer class="border-t border-white/10 bg-[#080c12] text-mochi-muted">
                <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                    <div
                        class="flex flex-col items-stretch gap-6 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
                    >
                        <div
                            class="flex flex-col items-center gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-start sm:gap-x-6 sm:gap-y-2"
                        >
                            <div class="flex flex-wrap justify-center gap-x-4 gap-y-1.5 text-[10px] leading-snug text-mochi-muted/90 sm:justify-start">
                                @foreach ($footerLegalLinks as $link)
                                    <a href="{{ $link['url'] }}" class="transition hover:text-mochi-text">{{ $link['label'] }}</a>
                                @endforeach
                                <button
                                    type="button"
                                    onclick="window.openCookieSettings?.()"
                                    class="transition hover:text-mochi-text"
                                >
                                    Cookies
                                </button>
                            </div>
                            <div class="inline-flex shrink-0 items-center justify-center sm:justify-start">
                                @include('partials.social-icons')
                            </div>
                        </div>
                        <p class="text-center text-[10px] text-mochi-muted/70 sm:text-right">
                            © {{ date('Y') }} {{ $siteName }}
                        </p>
                    </div>
                </div>
            </footer>

            @include('partials.instagram-feed')
        </div>

        <div
            x-show="cartDrawerOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 bg-[#040712]/65 backdrop-blur-sm"
            @click="cartDrawerOpen = false"
            style="display: none;"
        ></div>
        <aside
            x-show="cartDrawerOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed right-0 top-0 z-[60] flex h-full w-full max-w-sm flex-col border-l border-white/10 bg-[#080c12]/95 text-mochi-text shadow-[-12px_0_48px_-12px_rgba(0,0,0,0.85)] backdrop-blur-xl"
            style="display: none;"
            x-cloak
        >
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                <p class="text-sm font-semibold text-white">Warenkorb</p>
                <button
                    type="button"
                    @click="cartDrawerOpen = false"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-mochi-muted transition hover:bg-white/5 hover:text-white"
                    aria-label="Schließen"
                >
                    ✕
                </button>
            </div>
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <livewire:shop.cart-drawer lazy />
            </div>
        </aside>

        <div
            id="global-shop-toast"
            role="status"
            aria-live="polite"
            aria-atomic="true"
            class="pointer-events-none fixed right-4 top-24 z-[95] max-w-sm sm:top-20 md:top-16"
        >
            <div
                id="global-shop-toast-inner"
                class="rounded-xl border border-white/15 bg-[#080c12] px-4 py-3 text-sm font-medium text-white shadow-lg"
            ></div>
        </div>

        @stack('scripts')
        @include('partials.cookie-consent')
        @livewireScripts
    </body>
</html>
