@extends('layouts.app')

@section('title', 'Widerruf · '.$siteName)

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="font-display text-2xl font-bold tracking-tight text-white">Widerruf</h1>
        <div class="cms-content mt-6 max-w-none">
            {!! $legalHtml !!}
        </div>
    </div>
@endsection
