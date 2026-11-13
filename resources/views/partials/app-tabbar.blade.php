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
        ['route' => 'contact', 'active' => ['contact', 'about', 'service', 'posts.index', 'posts.show'], 'label' => 'Mehr',
            'icon' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ];
@endphp

@unless (request()->routeIs('checkout', 'checkout.*', 'payment.*'))
    <nav class="app-tabbar md:hidden" aria-label="Hauptnavigation">
        <ul>
            @foreach ($tabs as $tab)
                @php $isActive = request()->routeIs(...$tab['active']); @endphp
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
        </ul>
    </nav>
@endunless
