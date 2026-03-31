@extends('layouts.app')

@section('title', 'Events · '.$siteName)

@section('content')
    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:px-6 sm:py-12">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="space-y-2">
                <h1 class="font-display text-3xl font-black tracking-tight text-white sm:text-4xl">Events</h1>
                <p class="text-sm text-mochi-muted">Ganze Karte = ein Klick.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="mochi-cta !cursor-default !py-2 !text-sm">Liste</span>
                <a
                    href="{{ route('events.calendar') }}"
                    class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-mochi-muted transition hover:border-mochi-accent/30 hover:text-mochi-text"
                >
                    Kalender
                </a>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('events.index') }}"
                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $activeGame === null || $activeGame === '' ? 'mochi-cta !py-2' : 'border border-white/10 bg-white/5 text-mochi-muted hover:border-mochi-accent/30 hover:text-mochi-text' }}"
            >
                Alle
            </a>
            @foreach ($gameTypes as $game)
                <a
                    href="{{ route('events.index', ['game' => $game->value]) }}"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition {{ (string) $activeGame === $game->value ? 'mochi-cta !py-2' : 'border border-white/10 bg-white/5 text-mochi-muted hover:border-mochi-accent/30 hover:text-mochi-text' }}"
                >
                    {{ $game->label() }}
                </a>
            @endforeach
        </div>

        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($events as $event)
                <li>
                    <a
                        href="{{ route('events.show', $event) }}"
                        class="mochi-event-poster group mochi-collectible-card flex flex-col overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-none backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-mochi-accent/25 hover:shadow-[0_16px_48px_-28px_rgba(0,0,0,0.85),0_0_36px_-14px_rgba(255,122,31,0.18)]"
                    >
                        <div class="mochi-event-poster__media relative min-h-0 overflow-hidden">
                            @if ($event->image_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::url($event->image_path) }}"
                                    alt=""
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                                >
                            @else
                                <div
                                    class="flex h-full min-h-[12rem] w-full items-center justify-center bg-mochi-surface-2 text-center text-xs font-bold text-mochi-muted"
                                >
                                    {{ $event->gameTypeLabel() }}
                                </div>
                            @endif
                            <div
                                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#05080d]/85 via-[#05080d]/15 to-transparent"
                            ></div>
                        </div>
                        <div class="mochi-event-poster__meta flex flex-col justify-center border-t border-white/10 px-4 py-3">
                            <div class="mb-1.5">
                                @include('partials.game-type-badge', ['for' => $event, 'muted' => true])
                            </div>
                            <p class="line-clamp-2 font-display text-sm font-bold leading-snug text-white">
                                {{ $event->title }}
                            </p>
                            <time
                                class="mt-1.5 text-[10px] font-semibold tabular-nums text-mochi-muted"
                                datetime="{{ $event->starts_at->toIso8601String() }}"
                            >
                                {{ $event->starts_at->format('d.m.Y · H:i') }}
                                @if ($event->price !== null)
                                    · {{ number_format((float) $event->price, 2, ',', '.') }} €
                                @endif
                            </time>
                        </div>
                    </a>
                </li>
            @empty
                <li
                    class="col-span-full rounded-3xl border border-dashed border-white/15 bg-gradient-to-br from-mochi-surface/35 to-[#080d14]/45 p-10 text-center backdrop-blur-xl sm:p-14"
                >
                    @if ($activeGame === null || $activeGame === '')
                        <p class="font-display text-xl font-black text-white">Noch keine Events</p>
                        <p class="mx-auto mt-4 max-w-lg text-sm leading-relaxed text-mochi-muted">
                            Aktuell planen wir neue Abenteuer für euch – schau bald wieder vorbei!
                        </p>
                    @else
                        <p class="font-display text-lg font-bold text-white">Keine Events für diesen Filter</p>
                        <p class="mx-auto mt-3 max-w-md text-sm text-mochi-muted">
                            Probiere eine andere Spielart oder schau später wieder vorbei.
                        </p>
                    @endif
                </li>
            @endforelse
        </ul>

        <div class="text-mochi-muted [&_a]:font-semibold [&_a]:text-mochi-accent [&_a:hover]:text-mochi-accent-strong [&_span]:text-mochi-muted">
            {{ $events->links() }}
        </div>
    </div>
@endsection
