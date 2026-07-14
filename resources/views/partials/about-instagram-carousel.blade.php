@if (($galleryUrls ?? []) !== [])
    @php
        $heading = trim((string) ($about->about_instagram_heading ?? ''));
        $instagramLink = trim((string) ($instagramUrl ?? ''));
    @endphp

    <section class="mt-10" aria-label="{{ $heading !== '' ? $heading : 'Instagram' }}">
        @if ($heading !== '')
            <h2 class="text-center font-display text-base font-semibold text-white sm:text-lg">{{ $heading }}</h2>
        @endif

        <div
            class="mx-auto mt-4 max-w-[15rem] sm:max-w-[17rem]"
            x-data="{
                active: 0,
                total: {{ count($galleryUrls) }},
                timer: null,
                start() {
                    if (this.total <= 1) return;
                    this.timer = setInterval(() => this.next(), 4500);
                },
                stop() {
                    if (this.timer) clearInterval(this.timer);
                },
                next() {
                    this.active = (this.active + 1) % this.total;
                },
                prev() {
                    this.active = (this.active - 1 + this.total) % this.total;
                },
                go(index) {
                    this.active = index;
                }
            }"
            x-init="start()"
            x-on:mouseenter="stop()"
            x-on:mouseleave="start()"
        >
            <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-mochi-surface/40 shadow-[0_16px_40px_-28px_rgba(0,0,0,0.9)]">
                <div class="relative aspect-[4/5]">
                    @foreach ($galleryUrls as $index => $src)
                        <figure
                            x-show="active === {{ $index }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 scale-[1.02]"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-[0.98]"
                            @class(['absolute inset-0', $index === 0 ? '' : ''])
                            @if ($index > 0) style="display: none;" @endif
                        >
                            @if ($instagramLink !== '')
                                <a
                                    href="{{ $instagramLink }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block h-full w-full"
                                    aria-label="Instagram öffnen"
                                >
                                    <img
                                        src="{{ $src }}"
                                        alt=""
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </a>
                            @else
                                <img
                                    src="{{ $src }}"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @endif
                        </figure>
                    @endforeach
                </div>

                @if (count($galleryUrls) > 1)
                    <button
                        type="button"
                        class="absolute left-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-[#040712]/70 text-white/90 backdrop-blur-sm transition hover:border-mochi-accent/40 hover:text-mochi-coral"
                        x-on:click="prev()"
                        aria-label="Vorheriges Bild"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-white/15 bg-[#040712]/70 text-white/90 backdrop-blur-sm transition hover:border-mochi-accent/40 hover:text-mochi-coral"
                        x-on:click="next()"
                        aria-label="Nächstes Bild"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                @endif
            </div>

            @if (count($galleryUrls) > 1)
                <div class="mt-3 flex justify-center gap-1.5" role="tablist" aria-label="Karussell-Navigation">
                    @foreach ($galleryUrls as $index => $src)
                        <button
                            type="button"
                            role="tab"
                            x-bind:aria-selected="active === {{ $index }} ? 'true' : 'false'"
                            x-on:click="go({{ $index }})"
                            class="h-1.5 rounded-full transition-all duration-300"
                            x-bind:class="active === {{ $index }} ? 'w-5 bg-mochi-accent' : 'w-1.5 bg-white/25 hover:bg-white/40'"
                            aria-label="Bild {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif

            @if ($instagramLink !== '')
                <p class="mt-3 text-center">
                    <a
                        href="{{ $instagramLink }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-xs font-medium text-mochi-accent underline-offset-2 hover:text-mochi-accent-strong hover:underline"
                    >
                        Folgt uns auf Instagram
                    </a>
                </p>
            @endif
        </div>
    </section>
@endif
