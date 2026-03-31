@php
    $iconClass = 'h-5 w-5 sm:h-6 sm:w-6';
    $padClass = 'p-2 sm:p-2.5';
    $ringOffset = 'focus-visible:ring-offset-[#080c12]';
    $insta = isset($instagramUrl) ? trim((string) $instagramUrl) : '';
    $tiktok = isset($tiktokUrl) ? trim((string) $tiktokUrl) : '';
@endphp

@if ($insta !== '' || $tiktok !== '')
    <div class="mochi-social-icons flex shrink-0 items-center gap-0.5 sm:gap-1">
        @if ($insta !== '')
            <a
                href="{{ $insta }}"
                target="_blank"
                rel="noopener noreferrer"
                class="{{ $padClass }} {{ $ringOffset }} rounded-lg text-white/70 transition duration-300 hover:text-mochi-coral hover:drop-shadow-[0_0_10px_rgba(255,154,77,0.55)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mochi-accent/50 focus-visible:ring-offset-2"
                aria-label="Instagram"
            >
                <svg class="{{ $iconClass }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path
                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 1-2.881 0 1.44 1.44 0 0 1 2.881 0z"
                    />
                </svg>
            </a>
        @endif
        @if ($tiktok !== '')
            <a
                href="{{ $tiktok }}"
                target="_blank"
                rel="noopener noreferrer"
                class="{{ $padClass }} {{ $ringOffset }} rounded-lg text-white/70 transition duration-300 hover:text-mochi-coral hover:drop-shadow-[0_0_10px_rgba(255,154,77,0.55)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mochi-accent/50 focus-visible:ring-offset-2"
                aria-label="TikTok"
            >
                <svg class="{{ $iconClass }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path
                        d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"
                    />
                </svg>
            </a>
        @endif
    </div>
@endif
