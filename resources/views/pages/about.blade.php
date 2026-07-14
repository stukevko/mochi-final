@extends('layouts.app')

@php
    $pageTitle = filled($about->about_page_title) ? (string) $about->about_page_title : 'Über uns';
    $metaDescription = trim((string) ($about->about_meta_description ?? ''));
    if ($metaDescription === '') {
        $metaDescription = trim((string) ($about->about_intro ?? ''));
    }
    $highlights = $about->aboutHighlights();
    $ctaLabel = trim((string) ($about->about_cta_label ?? ''));
    $ctaUrl = trim((string) ($about->about_cta_url ?? ''));
    if ($ctaUrl !== '' && ! str_starts_with($ctaUrl, 'http://') && ! str_starts_with($ctaUrl, 'https://') && ! str_starts_with($ctaUrl, '/')) {
        $ctaUrl = '/'.$ctaUrl;
    }
@endphp

@section('title', $pageTitle.' · '.$siteName)

@push('meta')
    @if ($metaDescription !== '')
        <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($metaDescription), 160, '…') }}">
    @endif
@endpush

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-12">
            <div class="lg:col-span-7">
                <header class="max-w-2xl">
                    <h1 class="font-display text-3xl font-black tracking-tight text-white sm:text-4xl">
                        {{ $pageTitle }}
                    </h1>
                    @if (filled($about->about_hero_subtitle))
                        <p class="mt-3 text-base font-medium text-mochi-accent sm:text-lg">
                            {{ $about->about_hero_subtitle }}
                        </p>
                    @endif
                    @if (filled($about->about_intro))
                        <p class="mt-4 text-sm leading-relaxed text-mochi-muted sm:text-base">
                            {{ $about->about_intro }}
                        </p>
                    @endif
                </header>

                @if (filled($about->about_story))
                    <div class="cms-content mt-10 border-t border-white/10 pt-10">
                        {!! $about->about_story !!}
                    </div>
                @endif

                @if ($highlights !== [])
                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($highlights as $highlight)
                            <article
                                class="mochi-card rounded-2xl border border-white/10 p-5 shadow-[0_16px_48px_-32px_rgba(0,0,0,0.85)]"
                            >
                                @if (filled($highlight['title']))
                                    <h2 class="font-display text-sm font-bold text-white">{{ $highlight['title'] }}</h2>
                                @endif
                                @if (filled($highlight['body']))
                                    <p class="mt-2 text-sm leading-relaxed text-mochi-muted">{{ $highlight['body'] }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif

                @if (filled($about->about_extra_title) || filled($about->about_extra_body))
                    <section class="mt-10 border-t border-white/10 pt-10">
                        @if (filled($about->about_extra_title))
                            <h2 class="font-display text-xl font-bold text-white">{{ $about->about_extra_title }}</h2>
                        @endif
                        @if (filled($about->about_extra_body))
                            <div class="cms-content mt-4">
                                {!! $about->about_extra_body !!}
                            </div>
                        @endif
                    </section>
                @endif

                @if ($ctaLabel !== '' && $ctaUrl !== '')
                    <div class="mt-10">
                        <a
                            href="{{ $ctaUrl }}"
                            class="mochi-cta inline-flex items-center justify-center !px-6 !py-3"
                        >
                            {{ $ctaLabel }}
                        </a>
                    </div>
                @endif
            </div>

            <aside class="lg:col-span-5">
                <div class="lg:sticky lg:top-24">
                    @include('partials.about-instagram-carousel', [
                        'galleryUrls' => $galleryUrls,
                        'about' => $about,
                    ])

                    @if (($galleryUrls ?? []) === [])
                        <div
                            class="mt-4 rounded-2xl border border-dashed border-white/15 bg-white/[0.03] px-5 py-8 text-center text-sm text-mochi-muted"
                        >
                            <p>Instagram-Karussell: Im Admin unter <strong>Über uns → Instagram</strong> bis zu 5 Bilder hochladen.</p>
                            @if (! empty($instagramUrl))
                                <p class="mt-3">
                                    <a
                                        href="{{ $instagramUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-medium text-mochi-accent hover:text-mochi-accent-strong"
                                    >
                                        Auf Instagram folgen →
                                    </a>
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
@endsection
