@php
    $feedUrls = $footerCarouselUrls ?? [];
    $dup = count($feedUrls) > 0 ? array_merge($feedUrls, $feedUrls) : [];
@endphp

@if ($dup !== [] && ! empty($instagramUrl))
    <section
        class="border-t border-white/10 bg-[#060a10]/95 backdrop-blur-lg"
        aria-label="Foto-Feed"
    >
        <div class="mx-auto max-w-6xl px-4 py-7 sm:px-6 sm:py-8">
            <div class="relative overflow-hidden">
                <div
                    class="flex w-max gap-3 pr-3 animate-mochi-instagram-marquee hover:[animation-play-state:paused]"
                >
                    @foreach ($dup as $src)
                        <a
                            href="{{ $instagramUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="relative h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-mochi-surface/50 shadow-[0_12px_32px_-20px_rgba(0,0,0,0.9)] sm:h-28 sm:w-28"
                            aria-label="Instagram"
                        >
                            <img
                                src="{{ $src }}"
                                alt=""
                                class="h-full w-full object-cover transition duration-300 hover:scale-105"
                                loading="lazy"
                                decoding="async"
                            >
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
