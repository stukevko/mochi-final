<span class="mochi-cart-icon-wrap">
    <span class="mochi-cart-radial-burst" aria-hidden="true"></span>
    <button
        type="button"
        x-on:click="$dispatch('open-cart-drawer')"
        @class([
            'relative z-10 rounded-full border border-white/15 p-2 text-white/90 transition hover:border-mochi-accent/40 hover:bg-white/5 hover:text-mochi-coral',
            'mochi-cta-pulse ring-2 ring-mochi-accent/35' => $this->itemCount > 0,
        ])
        aria-label="Warenkorb öffnen"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
            />
        </svg>

        <span
            id="cart-count-badge"
            @class([
                'absolute -right-1 -top-1 z-20 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[10px] font-bold text-[#0b0f16]',
                'bg-mochi-accent shadow-[0_0_12px_-2px_rgba(255,122,31,0.75)]' => $this->itemCount > 0,
                'bg-white/20 text-white' => $this->itemCount <= 0,
            ])
        >
            {{ $this->itemCount }}
        </span>
    </button>
</span>
