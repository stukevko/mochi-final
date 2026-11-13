{{--
    Mobile App-Leiste (unter md). Eine Leiste für Shop- und CMS-Layout.
    Der aktive Tab trägt die „Kartenhülle“ (Sleeve) — sie gleitet per View Transition
    beim Seitenwechsel zum neuen Tab.
--}}
@php
    $tabs = [
        ['route' => 'home', 'active' => ['home'], 'label' => 'Start',
            'icon' => 'M2.25 12 11.2 3.05a1.125 1.125 0 0 1 1.6 0L21.75 12M4.5 9.75v10.125c0 .62.5 1.125 1.125 1.125H9.75v-4.875c0-.62.5-1.125 1.125-1.125h2.25c.62 0 1.125.5 1.125 1.125V21h4.125c.62 0 1.125-.5 1.125-1.125V9.75'],
        ['route' => 'shop', 'active' => ['shop', 'product.show'], 'label' => 'Shop',
            'icon' => 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.35m-16.5 11.65V9.35m0 0a3 3 0 0 0 3.75-.62 3 3 0 0 0 4.5 0 3 3 0 0 0 4.5 0 3 3 0 0 0 3.75.62m-16.5 0a3 3 0 0 1-.62-4.72l1.19-1.19A1.5 1.5 0 0 1 5.32 3h13.36c.4 0 .78.16 1.06.44l1.19 1.19a3 3 0 0 1-.62 4.72'],
        ['route' => 'events.index', 'active' => ['events.index', 'events.show', 'events.calendar'], 'label' => 'Events',
            'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
        ['route' => 'cart', 'active' => ['cart'], 'label' => 'Warenkorb', 'badge' => true,
            'icon' => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.36-1.99 1.26 12c.07.66-.45 1.24-1.12 1.24H4.25a1.13 1.13 0 0 1-1.12-1.24l1.26-12A1.13 1.13 0 0 1 5.51 7.5h12.98c.58 0 1.06.44 1.12 1.01Z'],
    ];

    $moreActive = request()->routeIs('contact', 'about', 'service', 'posts.index', 'posts.show', 'legal.*', 'pages.show');
    $moreIcon = 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5';
    $moreLinks = [
        ['label' => 'News', 'url' => route('posts.index')],
        ['label' => 'Über uns', 'url' => route('about')],
        ['label' => 'Kontakt & Anfahrt', 'url' => route('contact')],
        ['label' => 'Versand & Service', 'url' => route('service')],
    ];
    $legalLinks = [
        ['label' => 'Impressum', 'url' => route('legal.impressum')],
        ['label' => 'Datenschutz', 'url' => route('legal.datenschutz')],
        ['label' => 'AGB', 'url' => route('legal.agb')],
        ['label' => 'Widerruf', 'url' => route('legal.widerruf')],
    ];
@endphp

@unless (request()->routeIs('checkout', 'checkout.*', 'payment.*'))
    <div class="md:hidden" x-data="{ more: false }" x-on:keydown.escape.window="more = false">
        <nav class="app-tabbar" aria-label="Hauptnavigation">
            <ul>
                @foreach ($tabs as $tab)
                    @php $isActive = ! $moreActive && request()->routeIs(...$tab['active']); @endphp
                    <li>
                        <a
                            href="{{ route($tab['route']) }}"
                            @class(['app-tab', 'is-active' => $isActive])
                            @if ($isActive) aria-current="page" @endif
                        >
                            @if ($isActive)
                                <span class="app-tab-sleeve" aria-hidden="true"></span>
                            @endif
                            <span class="app-tab-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                                </svg>
                                @if (! empty($tab['badge']))
                                    <livewire:shop.cart-badge />
                                @endif
                            </span>
                            <span class="app-tab-label">{{ $tab['label'] }}</span>
                        </a>
                    </li>
                @endforeach
                <li>
                    <button type="button" @class(['app-tab w-full', 'is-active' => $moreActive]) x-on:click="more = true" aria-haspopup="dialog" x-bind:aria-expanded="more">
                        @if ($moreActive)
                            <span class="app-tab-sleeve" aria-hidden="true"></span>
                        @endif
                        <span class="app-tab-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $moreIcon }}" />
                            </svg>
                        </span>
                        <span class="app-tab-label">Mehr</span>
                    </button>
                </li>
            </ul>
        </nav>

        <div class="app-sheet-backdrop" x-show="more" x-transition.opacity x-on:click="more = false" x-cloak></div>
        <div
            class="app-sheet"
            role="dialog"
            aria-modal="true"
            aria-label="Weitere Seiten"
            x-show="more"
            x-trap.noscroll="more"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            x-cloak
        >
            <div class="app-sheet-grip" aria-hidden="true"></div>
            @foreach ($moreLinks as $link)
                <a href="{{ $link['url'] }}" class="app-sheet-link">
                    {{ $link['label'] }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" /></svg>
                </a>
            @endforeach
            <button type="button" data-app-install hidden class="app-sheet-link">
                Als App installieren
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0 4-4m-4 4-4-4M5 19h14" /></svg>
            </button>
            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-mochi-muted">
                @foreach ($legalLinks as $link)
                    <a href="{{ $link['url'] }}" class="py-1">{{ $link['label'] }}</a>
                @endforeach
                <button type="button" class="py-1" x-on:click="more = false; window.openCookieSettings?.()">Cookies</button>
            </div>
            <button type="button" x-on:click="more = false" class="mt-4 flex min-h-11 w-full items-center justify-center rounded-xl border border-white/15 text-sm font-medium text-mochi-text">Schließen</button>
        </div>
    </div>
@endunless
