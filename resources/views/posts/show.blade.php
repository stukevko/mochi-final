@extends('layouts.app')

@section('title', $post->title.' · '.$siteName)

@section('content')
    <article class="mx-auto max-w-6xl space-y-8 px-4 py-10 sm:px-6 sm:py-14">
        <a href="{{ route('posts.index') }}" class="text-sm font-bold text-mochi-accent hover:text-mochi-accent-strong hover:underline">
            ← News
        </a>

        <div class="overflow-hidden rounded-3xl ring-1 ring-white/[0.08]">
            @include('partials.post-card-thumbnail', [
                'post' => $post,
                'class' => 'aspect-[21/9] max-h-56 rounded-none sm:max-h-64',
            ])
        </div>

        <header class="space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                @include('partials.post-type-badge', ['type' => $post->type])
                @if ($post->game_type)
                    @include('partials.game-type-badge', ['for' => $post])
                @endif
            </div>
            @if ($post->category)
                <p class="text-[11px] font-bold uppercase tracking-wider text-mochi-muted">
                    {{ $post->category->name }}
                </p>
            @endif
            <h1 class="font-display text-3xl font-bold leading-tight text-mochi-text sm:text-4xl">
                {{ $post->title }}
            </h1>
            <p class="text-sm text-mochi-muted">{{ $post->published_at?->format('d.m.Y, H:i') }}</p>
        </header>

        @if ($post->excerpt)
            <p class="text-lg font-medium leading-relaxed text-mochi-text/95">{{ $post->excerpt }}</p>
        @endif

        <div class="cms-content">
            {!! $post->body !!}
        </div>
    </article>
@endsection
