@php
    $message = $nextEvent
        ? sprintf(
            'Nächstes Turnier: %s · %s · %s Uhr · %s',
            $nextEvent->title,
            $nextEvent->gameTypeLabel(),
            $nextEvent->starts_at->timezone(config('app.timezone'))->format('d.m., H:i'),
            $nextEvent->starts_at->timezone(config('app.timezone'))->locale(app()->getLocale())->translatedFormat('l'),
        )
        : 'Aktuell sind keine Turniere angekündigt — schaut bald wieder rein oder fragt im Laden nach.';
@endphp

<div
    class="relative border-b border-white/5 bg-[#040712]/55 text-gray-100 shadow-[0_8px_28px_-20px_rgba(0,0,0,0.45)] backdrop-blur-xl"
    role="status"
>
    <div
        class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,122,31,0.12),transparent_40%),radial-gradient(circle_at_80%_50%,rgba(96,165,250,0.06),transparent_45%)]"
        aria-hidden="true"
    ></div>
    <div
        class="relative flex min-h-[2.75rem] items-center overflow-hidden py-2 sm:min-h-12 sm:py-2.5"
    >
        <div
            class="animate-mochi-marquee flex w-max items-center gap-16 whitespace-nowrap font-display text-sm font-semibold leading-snug tracking-tight text-white sm:text-base"
        >
            <span class="inline-flex items-center">{{ $message }}</span>
            <span aria-hidden="true" class="inline-flex items-center">{{ $message }}</span>
        </div>
    </div>
</div>
