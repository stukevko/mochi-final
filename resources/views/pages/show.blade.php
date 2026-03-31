@extends('layouts.app')

@section('title', $page->title.' · '.$siteName)

@section('content')
    <article class="mx-auto max-w-6xl space-y-8 px-4 py-10 sm:px-6 sm:py-12">
        <h1 class="font-display text-3xl font-bold text-mochi-text sm:text-4xl">{{ $page->title }}</h1>
        <div class="cms-content">
            {!! $page->body !!}
        </div>
    </article>
@endsection
