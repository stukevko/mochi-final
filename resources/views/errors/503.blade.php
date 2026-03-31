<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Wartung — {{ config('app.name', 'Mochi') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-[#070a12] font-sans text-slate-100 antialiased">
    <main class="flex min-h-dvh flex-col items-center justify-center px-6 py-16">
        <div class="max-w-md text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#ff7a1f]/90">Mochi Hub</p>
            <h1 class="font-display mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                Kurz in der Festung verriegelt
            </h1>
            <p class="mt-4 text-base leading-relaxed text-slate-400">
                Wir führen gerade Wartungsarbeiten durch oder unsere Datenbank antwortet nicht.
                Bitte in wenigen Minuten erneut versuchen.
            </p>
            <p class="mt-8 text-xs text-slate-600">HTTP 503 · Service nicht verfügbar</p>
        </div>
    </main>
</body>
</html>
