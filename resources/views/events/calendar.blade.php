@extends('layouts.app')

@section('title', 'Kalender · '.$siteName)

@section('content')
    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 sm:py-12">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="space-y-2">
                <h1 class="font-display text-3xl font-black tracking-tight text-white sm:text-4xl">Kalender</h1>
                <p class="text-sm text-mochi-muted">
                    Klicke auf einen Termin — du landest auf der Event-Detailseite.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('events.index') }}"
                    class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-mochi-muted transition hover:border-mochi-accent/30 hover:text-mochi-text"
                >
                    Liste
                </a>
                <span class="mochi-cta !cursor-default !py-2 !text-sm">Kalender</span>
            </div>
        </div>

        <div
            class="mochi-fc-host rounded-2xl border border-white/10 bg-[#060a12]/60 p-3 shadow-[0_24px_64px_-40px_rgba(0,0,0,0.9)] backdrop-blur-xl sm:p-4"
        >
            <div
                id="mochi-event-calendar"
                data-feed-url="{{ route('events.feed') }}"
            ></div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-5 backdrop-blur-xl sm:px-6">
            <p class="font-display text-[11px] font-bold uppercase tracking-[0.18em] text-mochi-muted">
                Spielarten
            </p>
            <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-mochi-text/90">
                @foreach ($gameTypes as $game)
                    @php
                        $s = $game->eventCalendarStyle();
                    @endphp
                    <li class="flex items-center gap-2">
                        <span
                            class="h-2.5 w-2.5 shrink-0 rounded-full ring-2 ring-white/20"
                            style="background-color: {{ $s['borderColor'] }};"
                            aria-hidden="true"
                        ></span>
                        <span>{{ $game->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
