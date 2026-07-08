@props([
    'title' => null,
    'metaDescription' => null,
    'ogType' => 'website',
    'ogTitle' => null,
    'ogDescription' => null,
    'ogImage' => null,
    'ogUrl' => null,
    'canonical' => null,
    'productOg' => null,
])

@php
    $pageTitle = $title ?? config('app.name', 'Shop');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>

    @if(filled($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
    @endif

    @if(filled($canonical))
        <link rel="canonical" href="{{ $canonical }}">
    @endif

    <meta property="og:type" content="{{ $ogType }}">
    @if(is_array($productOg) && filled($productOg['amount'] ?? null) && filled($productOg['currency'] ?? null))
        <meta property="product:price:amount" content="{{ $productOg['amount'] }}">
        <meta property="product:price:currency" content="{{ $productOg['currency'] }}">
    @endif
    <meta property="og:site_name" content="{{ config('app.name', 'Shop') }}">
    <meta property="og:title" content="{{ $ogTitle ?? $pageTitle }}">
    @if(filled($ogDescription ?? null) || filled($metaDescription))
        <meta property="og:description" content="{{ $ogDescription ?? $metaDescription }}">
    @endif
    @if(filled($ogUrl))
        <meta property="og:url" content="{{ $ogUrl }}">
    @endif
    @if(filled($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle ?? $pageTitle }}">
    @if(filled($ogDescription ?? null) || filled($metaDescription))
        <meta name="twitter:description" content="{{ $ogDescription ?? $metaDescription }}">
    @endif
    @if(filled($ogImage))
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    @include('partials.consent-config', ['consentLayout' => 'shop'])

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if(!empty($shopFaviconUrl))
        <link rel="icon" href="{{ $shopFaviconUrl }}">
    @endif

    <style>
        :root {
            --shop-primary: {{ $shopPrimaryColor ?? '#3b82f6' }};
            --primary: var(--shop-primary);
            --shop-font-sans: "{{ $shopFontFamily ?? 'Inter' }}", ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji",
                "Segoe UI Symbol", "Noto Color Emoji";
            --shop-font-display: "{{ $shopFontFamily ?? 'Inter' }}", ui-sans-serif, system-ui, sans-serif;
            --font-main: var(--shop-font-sans);
        }
        .nav-brand-box {
            background-color: var(--shop-primary) !important;
        }
    </style>

    @livewireStyles
</head>
<body class="min-h-screen bg-[#040712] text-mochi-text antialiased" x-data="{ mobileMenuOpen: false, searchOpen: false, cartDrawerOpen: false }" x-on:keydown.escape.window="mobileMenuOpen=false; searchOpen=false; cartDrawerOpen=false" x-on:open-cart-drawer.window="cartDrawerOpen = true">
    <div class="nav-progress" aria-hidden="true"></div>
    <!-- Navbar -->
    <nav class="mochi-header-glass sticky top-0 z-50 border-b border-white/10 transition-all duration-300">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <!-- Logo -->
                <a href="{{ url('/') }}" wire:navigate.hover class="flex items-center gap-2.5">
                    <img
                        src="{{ $shopLogoUrl ?? asset('images/mochi-logo-placeholder.png') }}"
                        alt="{{ $shopDisplayName ?? config('app.name') }}"
                        class="h-8 max-w-[140px] object-contain {{ ($shopLogoIsPlaceholder ?? true) ? 'opacity-95' : '' }}"
                        width="140"
                        height="32"
                        decoding="async"
                        fetchpriority="low"
                    />
                    <span class="text-lg font-semibold tracking-tight text-white">{{ $shopDisplayName ?? config('app.name') }}</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex md:items-center md:gap-8">
                    <a href="{{ url('/') }}" wire:navigate.hover class="text-sm font-medium transition {{ request()->is('/') ? 'text-white' : 'text-mochi-muted hover:text-white' }}">
                        Home
                    </a>
                    <a href="{{ url('/shop') }}" wire:navigate.hover class="text-sm font-medium transition {{ request()->is('shop*') ? 'text-white' : 'text-mochi-muted hover:text-white' }}">
                        Alle Produkte
                    </a>
                    
                    @foreach($navCategories ?? [] as $category)
                        <a href="{{ url('/shop?category=' . \Illuminate\Support\Arr::get($category, 'slug')) }}" wire:navigate.hover
                           class="text-sm font-medium text-mochi-muted transition hover:text-white">
                            {{ \Illuminate\Support\Arr::get($category, 'name') }}
                        </a>
                    @endforeach
                </div>

                <!-- Right side: Cart -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <button type="button"
                       @click="searchOpen = true"
                       class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full border border-transparent text-mochi-muted transition hover:border-white/15 hover:bg-white/5 hover:text-mochi-coral"
                       aria-label="Produkte suchen">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                    </button>
                    <livewire:shop.cart-icon />

                    <!-- Mobile Menu Button -->
                    <button type="button" 
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-white/10 text-mochi-muted transition hover:border-mochi-accent/30 hover:bg-white/5 hover:text-white md:hidden"
                            aria-label="Menü öffnen">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <div x-show="mobileMenuOpen" x-transition.opacity class="drawer-backdrop md:hidden" @click="mobileMenuOpen = false"></div>
    <aside x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="drawer-panel drawer-panel-motion text-mochi-text md:hidden" x-cloak>
        <div class="flex items-center justify-between border-b border-white/10 bg-[#040712]/30 px-4 py-4 backdrop-blur-sm">
            <p class="text-sm font-semibold text-white">Navigation</p>
            <button type="button" @click="mobileMenuOpen = false" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-mochi-muted transition hover:bg-white/5 hover:text-white" aria-label="Menü schließen">✕</button>
        </div>
        <nav class="space-y-1 p-4 text-sm">
            <a href="{{ route('home') }}" wire:navigate @click="mobileMenuOpen = false" class="flex min-h-11 items-center rounded-xl border border-transparent px-3 py-2 text-mochi-text transition hover:border-white/10 hover:bg-white/5">Home</a>
            <a href="{{ route('shop') }}" wire:navigate @click="mobileMenuOpen = false" class="flex min-h-11 items-center rounded-xl border border-transparent px-3 py-2 text-mochi-text transition hover:border-white/10 hover:bg-white/5">Shop</a>
            <a href="{{ route('cart') }}" wire:navigate @click="mobileMenuOpen = false" class="flex min-h-11 items-center rounded-xl border border-transparent px-3 py-2 text-mochi-text transition hover:border-white/10 hover:bg-white/5">Warenkorb</a>
            <a href="{{ url('/admin') }}" @click="mobileMenuOpen = false" class="flex min-h-11 items-center rounded-xl border border-transparent px-3 py-2 text-mochi-muted transition hover:border-mochi-accent/25 hover:bg-mochi-accent/10 hover:text-mochi-coral">Admin</a>
        </nav>
    </aside>

    <div x-show="searchOpen" x-transition.opacity class="drawer-backdrop" @click="searchOpen = false"></div>
    <div x-show="searchOpen" x-transition class="fixed inset-x-0 top-0 z-[70] border-b border-white/10 bg-[#080c12]/92 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] shadow-[0_12px_40px_-16px_rgba(0,0,0,0.75)] backdrop-blur-xl supports-[backdrop-filter]:bg-[#080c12]/85 md:mx-auto md:mt-4 md:max-w-2xl md:rounded-2xl md:border md:border-white/10 md:py-4" x-cloak>
        <div class="mx-auto max-w-2xl">
            <div class="flex min-h-11 items-center gap-3">
                <svg class="h-5 w-5 shrink-0 text-mochi-muted" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                </svg>
                <input id="global-search-input" type="text" placeholder="Produkte suchen…" class="mochi-input min-h-11 w-full rounded-xl px-3 py-2 text-base md:text-sm">
                <button type="button" @click="searchOpen = false" class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-3 text-sm text-mochi-muted transition hover:bg-white/5 hover:text-white">Schließen</button>
            </div>
            <p class="mt-2 text-xs text-mochi-muted">Enter — zur Shop-Suche.</p>
        </div>
    </div>

    <div class="border-b border-white/10 bg-slate-950/45 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center gap-6 overflow-x-auto px-4 py-2 text-xs text-mochi-muted sm:px-6 lg:px-8">
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-emerald-500/80"></span>Käuferschutz</span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-blue-500/80"></span>Sicher bezahlen</span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-violet-500/80"></span>30 Tage Rückgabe</span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap"><span class="h-2 w-2 rounded-full bg-amber-500/80"></span>Support < 24h</span>
        </div>
    </div>

    <!-- Main Content (lw-main: leichte Opacity bei wire:navigate) -->
    <main class="lw-main">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-white/10 bg-[#080c12]/55 backdrop-blur-xl">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
                <!-- Brand -->
                <div class="md:col-span-1">
                    <a href="{{ url('/') }}" wire:navigate.hover class="flex items-center gap-2">
                        @if(!empty($shopLogoUrl))
                            <img src="{{ $shopLogoUrl }}" alt="" class="h-8 max-w-[140px] object-contain" width="140" height="32" decoding="async" fetchpriority="low" />
                        @else
                            <div class="nav-brand-box flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold text-white">
                                {{ strtoupper(\Illuminate\Support\Str::substr($shopDisplayName ?? config('app.name'), 0, 1)) }}
                            </div>
                        @endif
                        <span class="text-lg font-semibold tracking-tight text-white">{{ $shopDisplayName ?? config('app.name') }}</span>
                    </a>
                    <p class="mt-4 text-sm text-mochi-muted">
                        {{ $shopDisplayName ?? config('app.name') }} - moderne Produkte, sicherer Checkout und schneller Versand.
                    </p>
                    <div class="mt-5 flex flex-wrap items-center gap-2 text-mochi-muted">
                        <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px]">VISA</span>
                        <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px]">Mastercard</span>
                        <span class="rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 text-[11px]">SumUp</span>
                    </div>
                </div>

                <!-- Links -->
                <div>
                    <h3 class="text-sm font-semibold text-white">Shop</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="{{ url('/shop') }}" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Alle Produkte</a></li>
                        <li><a href="{{ route('shop', ['sort' => 'newest']) }}" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Neue Artikel</a></li>
                        <li><a href="{{ route('shop', ['sale' => true]) }}" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Sale</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white">Rechtliches</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="{{ route('legal.impressum') }}" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Impressum</a></li>
                        <li><a href="{{ route('legal.datenschutz') }}" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Datenschutz</a></li>
                        <li><a href="{{ route('legal.agb') }}" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">AGB</a></li>
                        <li><a href="{{ route('legal.widerruf') }}" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Widerruf</a></li>
                        <li>
                            <button
                                type="button"
                                onclick="window.openCookieSettings?.()"
                                class="text-sm text-mochi-muted transition hover:text-white"
                            >
                                Cookies
                            </button>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white">Kontakt</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="{{ route('service') }}#kontakt" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Kundenservice</a></li>
                        <li><a href="{{ route('service') }}#faq" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">FAQ</a></li>
                        <li><a href="{{ route('service') }}#versand" wire:navigate class="text-sm text-mochi-muted transition hover:text-white">Versand &amp; Lieferung</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Folge uns</h3>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-mochi-muted">
                        <a href="{{ !empty($shopSocialInstagram) ? $shopSocialInstagram : '#' }}"
                           @if(!empty($shopSocialInstagram)) rel="noopener noreferrer" target="_blank" @endif
                           class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-mochi-muted transition hover:border-mochi-accent/35 hover:bg-mochi-accent/10 hover:text-mochi-coral {{ empty($shopSocialInstagram) ? 'pointer-events-none opacity-50' : '' }}"
                           aria-label="Instagram">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 7.2c-2.65 0-4.8 2.15-4.8 4.8s2.15 4.8 4.8 4.8 4.8-2.15 4.8-4.8-2.15-4.8-4.8-4.8zm0 7.95c-1.74 0-3.15-1.41-3.15-3.15S10.26 8.85 12 8.85s3.15 1.41 3.15 3.15-1.41 3.15-3.15 3.15zm6.2-8.25c0 .59-.48 1.05-1.05 1.05-.59 0-1.05-.48-1.05-1.05 0-.59.48-1.05 1.05-1.05.57 0 1.05.48 1.05 1.05zM12 0C8.74 0 8.33.01 7.05.07 5.78.14 4.9.33 4.15.63c-.77.3-1.43.71-2.08 1.36C1.42 2.64 1.01 3.3.71 4.07.4 4.85.21 5.73.14 7 .08 8.28.07 8.69.07 12s.01 3.72.07 4.95c.07 1.27.26 2.15.56 2.9.3.77.71 1.43 1.36 2.08.65.65 1.31 1.06 2.08 1.36.78.3 1.65.48 2.92.55 1.27.06 1.68.07 5.01.07s3.73-.01 4.96-.07c1.26-.07 2.14-.26 2.9-.56.77-.3 1.43-.71 2.08-1.36.65-.65 1.06-1.31 1.36-2.08.29-.77.48-1.65.54-2.92.06-1.27.07-1.68.07-4.99s-.01-3.71-.07-4.96c-.07-1.27-.26-2.15-.55-2.9-.3-.77-.71-1.43-1.36-2.08C21.36 1.42 20.7 1.01 19.93.71c-.78-.3-1.65-.48-2.92-.55C15.69.08 15.28.07 12 .07zm0 1.8c3.24 0 3.62.01 4.9.07 1.18.06 1.82.26 2.24.43.57.22.97.48 1.4.91.43.43.69.83.91 1.4.17.42.37 1.06.43 2.24.06 1.29.07 1.67.07 4.9s-.01 3.62-.07 4.9c-.06 1.18-.26 1.82-.43 2.24-.22.57-.48.97-.91 1.4-.43.43-.83.69-1.4.91-.42.17-1.06.37-2.24.43-1.29.06-1.67.07-4.9.07s-3.62-.01-4.9-.07c-1.18-.06-1.82-.26-2.24-.43-.57-.22-.97-.48-1.4-.91-.43-.43-.69-.83-.91-1.4-.17-.42-.37-1.06-.43-2.24-.06-1.29-.07-1.67-.07-4.9s.01-3.62.07-4.9c.06-1.18.26-1.82.43-2.24.22-.57.48-.97.91-1.4.43-.43.83-.69 1.4-.91.42-.17 1.06-.37 2.24-.43 1.28-.06 1.66-.07 4.9-.07z"/>
                            </svg>
                        </a>
                        <a href="{{ !empty($shopSocialFacebook) ? $shopSocialFacebook : '#' }}"
                           @if(!empty($shopSocialFacebook)) rel="noopener noreferrer" target="_blank" @endif
                           class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-mochi-muted transition hover:border-mochi-accent/35 hover:bg-mochi-accent/10 hover:text-mochi-coral {{ empty($shopSocialFacebook) ? 'pointer-events-none opacity-50' : '' }}"
                           aria-label="Facebook">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07c0 5.99 4.39 10.95 10.13 11.86v-8.39H7.08v-3.47h3.04V9.41c0-3.02 1.8-4.69 4.54-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.92-1.95 1.87v2.24h3.32l-.53 3.47h-2.79V24C19.61 23.02 24 18.05 24 12.07z"/>
                            </svg>
                        </a>
                        <a href="{{ !empty($shopSocialX) ? $shopSocialX : '#' }}"
                           @if(!empty($shopSocialX)) rel="noopener noreferrer" target="_blank" @endif
                           class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] text-mochi-muted transition hover:border-mochi-accent/35 hover:bg-mochi-accent/10 hover:text-mochi-coral {{ empty($shopSocialX) ? 'pointer-events-none opacity-50' : '' }}"
                           aria-label="X">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                    </div>
                    @if(empty($shopSocialInstagram) && empty($shopSocialFacebook) && empty($shopSocialX))
                        <p class="mt-2 text-xs text-mochi-muted">Social-Links im Admin unter Branding hinterlegen.</p>
                    @endif
                </div>
            </div>

            <div class="mt-12 border-t border-white/10 pt-8">
                @if(!empty($shopSocialInstagram) || !empty($shopSocialFacebook) || !empty($shopSocialX))
                    <div class="mb-6 flex flex-wrap items-center justify-center gap-4 text-sm text-mochi-muted">
                        @if(!empty($shopSocialInstagram))
                            <a href="{{ $shopSocialInstagram }}" class="transition hover:text-white" rel="noopener noreferrer" target="_blank">Instagram</a>
                        @endif
                        @if(!empty($shopSocialFacebook))
                            <a href="{{ $shopSocialFacebook }}" class="transition hover:text-white" rel="noopener noreferrer" target="_blank">Facebook</a>
                        @endif
                        @if(!empty($shopSocialX))
                            <a href="{{ $shopSocialX }}" class="transition hover:text-white" rel="noopener noreferrer" target="_blank">X</a>
                        @endif
                    </div>
                @endif
                <p class="text-center text-sm text-mochi-muted">
                    {{ $shopFooterText ?? '© '.date('Y').' '.config('app.name') }}
                </p>
            </div>
        </div>
    </footer>

    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-[#040712]/92 p-2 backdrop-blur-xl supports-[backdrop-filter]:bg-[#040712]/85 md:hidden">
        <div class="grid grid-cols-4 gap-1 text-center text-[11px] font-medium text-mochi-muted">
            <a href="{{ route('home') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-lg px-2 py-2 transition {{ request()->routeIs('home') ? 'bg-mochi-accent/15 font-semibold text-mochi-accent ring-1 ring-mochi-accent/35' : 'hover:bg-white/5 hover:text-white' }}">
                Home
            </a>
            <button type="button" @click="searchOpen = true" class="inline-flex min-h-11 items-center justify-center rounded-lg px-2 py-2 transition hover:bg-white/5 hover:text-white">Suche</button>
            <a href="{{ route('cart') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-lg px-2 py-2 transition {{ request()->routeIs('cart') ? 'bg-mochi-accent/15 font-semibold text-mochi-accent ring-1 ring-mochi-accent/35' : 'hover:bg-white/5 hover:text-white' }}">
                Cart
            </a>
            <a href="{{ route('shop') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-lg px-2 py-2 transition {{ request()->routeIs('shop') ? 'bg-mochi-accent/15 font-semibold text-mochi-accent ring-1 ring-mochi-accent/35' : 'hover:bg-white/5 hover:text-white' }}">Shop</a>
        </div>
    </div>

    <div x-show="cartDrawerOpen" x-transition.opacity class="drawer-backdrop" @click="cartDrawerOpen = false"></div>
    <aside x-show="cartDrawerOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="drawer-panel drawer-panel-motion flex flex-col text-mochi-text" x-cloak>
        <div class="flex items-center justify-between border-b border-white/10 bg-[#040712]/30 px-4 py-3 backdrop-blur-sm">
            <p class="text-sm font-semibold text-white">Schnell-Warenkorb</p>
            <button type="button" @click="cartDrawerOpen = false" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg text-mochi-muted transition hover:bg-white/5 hover:text-white" aria-label="Warenkorb schließen">✕</button>
        </div>
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <livewire:shop.cart-drawer />
        </div>
    </aside>

    <div id="global-shop-toast" role="status" aria-live="polite" aria-atomic="true" class="pointer-events-none fixed right-4 top-24 z-[95] max-w-sm sm:top-20 md:top-16">
        <div id="global-shop-toast-inner" class="rounded-xl border border-white/10 bg-[#080c12] px-4 py-3 text-sm font-medium text-white shadow-lg">
            <!-- Filled via JS (showShopToast / Warenkorb-Event) -->
        </div>
    </div>

    @session('shop_toast')
        @php
            $flashToast = is_array($value) ? $value : ['message' => (string) $value, 'type' => 'success'];
        @endphp
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.showShopToast?.(@json($flashToast['message'] ?? ''), @json($flashToast['type'] ?? 'success'));
            });
        </script>
    @endSession

    @livewireScripts
    @include('partials.cookie-consent')
</body>
</html>
