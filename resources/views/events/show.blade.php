@extends('layouts.app')

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
    $shareUrl = url()->current();
    $plainDescription = $event->description
        ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($event->description))), 160)
        : '';
    $dateLine = $event->starts_at->format('d.m.Y, H:i').' Uhr';
    $ogDescription = trim($event->title.' · '.$dateLine.($plainDescription !== '' ? ' — '.$plainDescription : ''));
    $ogImage = $event->image_path ? url(Storage::url($event->image_path)) : null;
    $siteLabel = config('mochicards.site_name');
@endphp

@section('title', $event->title.' · '.$siteName)

@push('meta')
    <link rel="canonical" href="{{ $shareUrl }}">
    <meta name="description" content="{{ Str::limit($ogDescription, 320) }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $shareUrl }}">
    <meta property="og:title" content="{{ $event->title }}">
    <meta property="og:description" content="{{ Str::limit($ogDescription, 300) }}">
    <meta property="og:site_name" content="{{ $siteLabel }}">
    <meta property="og:locale" content="de_DE">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:secure_url" content="{{ $ogImage }}">
        <meta property="og:image:alt" content="{{ $event->title }}">
    @endif

    <meta property="article:published_time" content="{{ $event->starts_at->toIso8601String() }}">

    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $event->title }}">
    <meta name="twitter:description" content="{{ Str::limit($ogDescription, 200) }}">
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
@endpush

@section('content')
    <article class="mx-auto max-w-6xl space-y-8 px-4 py-10 sm:px-6 sm:py-12">
        <a href="{{ route('events.index') }}" class="text-sm font-semibold text-mochi-accent hover:text-mochi-accent-strong hover:underline">← Alle Events</a>

        <header class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                @include('partials.game-type-badge', ['for' => $event])
            </div>
            <h1 class="font-display text-3xl font-bold text-mochi-text sm:text-4xl">
                {{ $event->title }}
            </h1>
            <p class="text-mochi-muted">
                {{ $event->starts_at->format('d.m.Y, H:i') }}
                Uhr
                @if ($event->price !== null)
                    · Eintritt ab {{ number_format((float) $event->price, 2, ',', '.') }} €
                @endif
            </p>
        </header>

        <div class="mochi-card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-mochi-muted">
                Link in WhatsApp &amp; Co. teilen — ein Klick kopiert die Event-URL.
            </p>
            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    data-share-url="{{ $shareUrl }}"
                    class="mochi-cta !px-5 !py-2.5 !text-sm"
                >
                    Link kopieren
                </button>
                <span class="hidden text-sm font-semibold text-emerald-400" data-share-feedback aria-live="polite">
                    Kopiert!
                </span>
            </div>
        </div>
        <script>
            (function () {
                const btn = document.querySelector('[data-share-url]');
                if (!btn) return;
                const feedback = document.querySelector('[data-share-feedback]');
                btn.addEventListener('click', async function () {
                    const url = btn.getAttribute('data-share-url');
                    try {
                        await navigator.clipboard.writeText(url);
                    } catch (e) {
                        window.prompt('Link kopieren:', url);
                        return;
                    }
                    feedback?.classList.remove('hidden');
                    setTimeout(function () {
                        feedback?.classList.add('hidden');
                    }, 2000);
                });
            })();
        </script>

        @if ($event->image_path)
            <img
                src="{{ Storage::url($event->image_path) }}"
                alt=""
                class="max-h-96 w-full rounded-2xl object-cover ring-1 ring-white/10"
            >
        @endif

        @if ($event->description)
            <div class="cms-content">
                {!! $event->description !!}
            </div>
        @endif
    </article>
@endsection
