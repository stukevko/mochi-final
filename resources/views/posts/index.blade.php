@extends('layouts.app')

@section('title', 'News · '.$siteName)

@section('content')
    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || (($filters['category'] ?? '') !== null && (string) ($filters['category'] ?? '') !== '')
            || (($filters['type'] ?? '') !== null && (string) ($filters['type'] ?? '') !== '');
    @endphp
    <div class="mx-auto max-w-6xl space-y-8 px-4 py-8 sm:space-y-10 sm:px-6 sm:py-12">
        <div class="space-y-2">
            <h1 class="font-display text-2xl font-black tracking-tight text-white sm:text-4xl">News</h1>
            <p class="text-mochi-muted">News &amp; Blog — filtern, suchen, chronologisch lesen.</p>
        </div>

        <form
            method="get"
            action="{{ route('posts.index') }}"
            class="mochi-card space-y-4 rounded-3xl p-5 sm:p-6"
        >
            <label class="block font-display text-[10px] font-extrabold uppercase tracking-widest text-mochi-muted">
                Suche
            </label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Titel, Text…"
                    class="w-full rounded-xl border border-white/10 bg-mochi-bg/80 px-3 py-2.5 text-sm text-mochi-text outline-none ring-mochi-accent/30 placeholder:text-mochi-muted focus:ring-2"
                >
                <button type="submit" class="mochi-cta shrink-0 !py-2.5">
                    Suchen
                </button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold text-mochi-muted">Typ</label>
                    <select
                        name="type"
                        class="mt-1 w-full rounded-xl border border-white/10 bg-mochi-surface px-3 py-2.5 text-sm text-mochi-text"
                        onchange="this.form.submit()"
                    >
                        <option value="">Alle</option>
                        @foreach ($postTypes as $type)
                            <option value="{{ $type->value }}" @selected((string) ($filters['type'] ?? '') === $type->value)>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-mochi-muted">Kategorie</label>
                    <select
                        name="category"
                        class="mt-1 w-full rounded-xl border border-white/10 bg-mochi-surface px-3 py-2.5 text-sm text-mochi-text"
                        onchange="this.form.submit()"
                    >
                        <option value="">Alle</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) ($filters['category'] ?? '') === (string) $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <ul class="space-y-5">
            @forelse ($posts as $post)
                <li>
                    <a
                        href="{{ route('posts.show', $post) }}"
                        class="mochi-collectible-card mochi-card mochi-card-hover flex flex-col overflow-hidden rounded-3xl sm:flex-row"
                    >
                        <div class="shrink-0 p-3 sm:w-56 sm:p-4">
                            @include('partials.post-card-thumbnail', [
                                'post' => $post,
                                'class' => 'sm:aspect-auto sm:h-full sm:min-h-[11rem]',
                            ])
                        </div>
                        <div class="flex flex-1 flex-col justify-center p-6 pt-2 sm:py-6 sm:pl-0">
                            @if ($post->game_type)
                                <div class="flex flex-wrap items-center gap-2">
                                    @include('partials.game-type-badge', ['for' => $post, 'muted' => true])
                                </div>
                            @endif
                            <h2 class="mt-3 font-display text-xl font-bold text-mochi-text">{{ $post->title }}</h2>
                            <time class="mt-3 text-xs font-semibold tabular-nums text-mochi-muted" datetime="{{ $post->published_at?->toIso8601String() }}">
                                {{ $post->published_at?->format('d.m.Y') }}
                            </time>
                        </div>
                    </a>
                </li>
            @empty
                <li
                    class="rounded-3xl border border-dashed border-white/15 bg-gradient-to-br from-mochi-surface/35 to-[#080d14]/45 p-10 text-center backdrop-blur-xl sm:p-14"
                >
                    @if ($hasActiveFilters)
                        <p class="font-display text-lg font-bold text-white">Keine Treffer</p>
                        <p class="mx-auto mt-3 max-w-md text-sm text-mochi-muted">
                            Andere Filter oder einen anderen Suchbegriff probieren.
                        </p>
                    @else
                        <p class="font-display text-xl font-black text-white">Noch keine News</p>
                        <p class="mx-auto mt-4 max-w-lg text-sm leading-relaxed text-mochi-muted">
                            Aktuell planen wir neue Abenteuer für euch – schau bald wieder vorbei!
                        </p>
                    @endif
                </li>
            @endforelse
        </ul>

        <div class="text-mochi-muted [&_a]:font-semibold [&_a]:text-mochi-accent [&_a:hover]:text-mochi-accent-strong">
            {{ $posts->links() }}
        </div>
    </div>
@endsection
