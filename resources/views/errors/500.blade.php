@extends('layouts.app')

@section('title', 'Fehler · '.$siteName)

@section('content')
    <div class="mx-auto flex max-w-2xl flex-col items-center justify-center px-4 py-24 text-center sm:py-32">
        <p class="font-display text-7xl font-black tabular-nums text-mochi-muted/80 sm:text-8xl">500</p>
        <h1 class="mt-6 font-display text-2xl font-black tracking-tight text-white sm:text-3xl">
            Hier hat etwas nicht geklappt.
        </h1>
        <p class="mt-4 max-w-md text-sm leading-relaxed text-mochi-muted">
            Unser Team wird informiert. Bitte versuche es in ein paar Minuten erneut oder geh zurück zur Startseite.
        </p>
        <a
            href="{{ route('home') }}"
            class="mochi-cta mochi-cta-pulse mt-10 !px-8 !py-3.5 !text-sm font-extrabold sm:!text-base"
        >
            Zur Startseite
        </a>
    </div>
@endsection
