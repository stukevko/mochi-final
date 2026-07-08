@extends('layouts.app')

@section('title', 'Kontakt · '.$siteName)

@push('meta')
    <meta name="description" content="Mochi Cards Speyer — Kontaktformular, Öffnungszeiten und Anfahrt.">
@endpush

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <div class="max-w-2xl">
            <h1 class="font-display text-3xl font-black tracking-tight text-white sm:text-4xl">Kontakt</h1>
            <p class="mt-3 text-sm leading-relaxed text-mochi-muted sm:text-base">
                {{ config('mochicards.contact_intro') }}
            </p>
        </div>

        <div class="mt-10 grid gap-10 lg:grid-cols-12 lg:gap-12">
            <div class="lg:col-span-7">
                <div
                    class="mochi-card rounded-3xl border border-white/10 p-6 shadow-[0_24px_64px_-36px_rgba(0,0,0,0.85)] sm:p-8"
                >
                    <h2 class="font-display text-lg font-bold text-white">Nachricht schreiben</h2>
                    <p class="mt-1 text-xs text-mochi-muted">Pflichtfelder sind gekennzeichnet. Wir antworten per E-Mail.</p>
                    <div class="mt-6" data-consent-turnstile>
                        <livewire:contact-form />
                    </div>
                </div>
            </div>

            <aside class="lg:col-span-5">
                <div class="space-y-6">
                    <div
                        class="mochi-card rounded-3xl border border-white/10 p-6 sm:p-7"
                    >
                        <h2 class="font-display text-sm font-bold uppercase tracking-wide text-mochi-muted">Laden</h2>
                        <div class="mt-4 space-y-3 text-sm leading-relaxed text-mochi-text">
                            @if (filled($shopAddress ?? null))
                                <p class="whitespace-pre-line">{{ $shopAddress }}</p>
                            @else
                                <p>Mochi Cards<br>Maximilianstraße 42<br>67346 Speyer</p>
                            @endif
                            <a
                                href="{{ $mapsExternalUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex font-semibold text-mochi-accent underline underline-offset-2 hover:text-mochi-accent-strong"
                            >
                                Route in Google Maps →
                            </a>
                        </div>
                    </div>

                    <div class="mochi-card rounded-3xl border border-white/10 p-6 sm:p-7">
                        <h2 class="font-display text-sm font-bold uppercase tracking-wide text-mochi-muted">Öffnungszeiten</h2>
                        <pre class="mt-4 whitespace-pre-line font-sans text-sm leading-relaxed text-mochi-text">{{ $openingHours }}</pre>
                    </div>

                    @if (filled($shopEmail ?? null))
                        <div class="mochi-card rounded-3xl border border-white/10 p-6 sm:p-7">
                            <h2 class="font-display text-sm font-bold uppercase tracking-wide text-mochi-muted">E-Mail</h2>
                            <a
                                href="mailto:{{ $shopEmail }}"
                                class="mt-3 inline-block text-sm font-semibold text-mochi-accent hover:text-mochi-accent-strong"
                            >{{ $shopEmail }}</a>
                        </div>
                    @endif

                    @include('partials.consent-map-embed', [
                        'embedUrl' => $mapsEmbedUrl,
                        'externalUrl' => $mapsExternalUrl,
                        'title' => 'Karte Mochi Cards Speyer',
                    ])
                </div>
            </aside>
        </div>
    </div>
@endsection
