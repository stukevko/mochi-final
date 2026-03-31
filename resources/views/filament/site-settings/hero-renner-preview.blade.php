@props([
    'title' => 'Shop-Highlight',
    'price' => null,
    'imageUrl' => null,
])

@php
    $letter = strtoupper(\Illuminate\Support\Str::substr((string) $title, 0, 1));
@endphp

<div class="space-y-3" wire:key="hero-renner-preview-{{ md5(($title ?? '').($price ?? '').($imageUrl ?? '')) }}">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Vorschau — Neon-Ring wie auf der Startseite (Animation vereinfacht).
    </p>
    <div
        class="relative overflow-hidden rounded-2xl border border-white/10 bg-[#0a0e16] p-6 shadow-inner dark:border-white/10"
    >
        <div class="relative mx-auto flex min-h-[200px] items-center justify-center">
            <div
                class="pointer-events-none absolute left-1/2 top-1/2 aspect-square w-[min(78%,200px)] -translate-x-1/2 -translate-y-1/2"
                aria-hidden="true"
            >
                <div class="mochi-admin-neon-ring absolute inset-[-18%] rounded-full"></div>
            </div>
            @if ($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt=""
                    class="relative z-10 max-h-40 w-auto object-contain drop-shadow-lg"
                >
            @else
                <div
                    class="relative z-10 flex h-36 w-36 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500/35 to-slate-800 font-display text-5xl font-black text-white/90"
                    aria-hidden="true"
                >
                    {{ $letter }}
                </div>
            @endif
        </div>
        <p class="relative z-10 mt-4 text-center text-sm font-bold text-white">
            {{ $title }}
        </p>
        @if (filled($price))
            <p class="relative z-10 mt-1 text-center text-lg font-black text-orange-400">
                {{ $price }}
            </p>
        @endif
    </div>
</div>

<style>
    @keyframes mochi-admin-neon-ring {
        0%,
        100% {
            transform: scale(0.9);
            opacity: 0.55;
        }
        50% {
            transform: scale(1.08);
            opacity: 0.92;
        }
    }
    .mochi-admin-neon-ring {
        background: radial-gradient(
            circle at 50% 42%,
            rgba(255, 180, 90, 0.55) 0%,
            rgba(255, 122, 31, 0.35) 22%,
            rgba(255, 100, 50, 0.12) 48%,
            transparent 72%
        );
        filter: blur(30px);
        animation: mochi-admin-neon-ring 3.2s ease-in-out infinite;
    }
    @media (prefers-reduced-motion: reduce) {
        .mochi-admin-neon-ring {
            animation: none;
            opacity: 0.85;
            transform: scale(1);
        }
    }
</style>
