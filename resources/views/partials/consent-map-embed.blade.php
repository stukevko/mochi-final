@props([
    'embedUrl',
    'externalUrl',
    'title' => 'Karte',
])

<div
    data-consent-map-root
    class="overflow-hidden rounded-3xl border border-white/10 bg-[#080c12] shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"
>
    <div
        data-consent-map-placeholder
        class="flex aspect-[4/3] min-h-[220px] flex-col items-center justify-center gap-4 px-6 py-8 text-center"
    >
        <p class="text-sm font-medium text-mochi-text">Interaktive Karte (Google Maps)</p>
        <p class="max-w-xs text-xs leading-relaxed text-mochi-muted">
            Die eingebettete Karte setzt Cookies beim Anbieter Google. Sie wird erst geladen, wenn du optionale
            Dienste akzeptierst oder unten auf „Karte anzeigen“ klickst.
        </p>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <button
                type="button"
                data-consent-load-maps
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-mochi-accent px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-mochi-coral"
            >
                Karte anzeigen
            </button>
            <a
                href="{{ $externalUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/15 px-4 py-2.5 text-sm font-medium text-mochi-text transition hover:border-white/25 hover:bg-white/5"
            >
                In Google Maps öffnen
            </a>
        </div>
    </div>
    <div
        data-consent-map-embed
        class="hidden aspect-[4/3] w-full [&_iframe]:h-full [&_iframe]:w-full [&_iframe]:min-h-[220px] [&_iframe]:border-0 [&_iframe]:grayscale [&_iframe]:invert [&_iframe]:contrast-[1.15]"
    >
        <iframe
            data-consent-embed="{{ $embedUrl }}"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            title="{{ $title }}"
            allowfullscreen
        ></iframe>
    </div>
</div>
