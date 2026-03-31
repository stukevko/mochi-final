<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wartung — {{ config('app.name', 'Shop') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-slate-50 font-sans text-slate-800 antialiased">
    <main class="flex min-h-dvh flex-col items-center justify-center px-6 py-16">
        <div class="max-w-md text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Kurz nicht verfügbar</p>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Wir sind gleich wieder für Sie da</h1>
            <p class="mt-4 text-base text-slate-600">
                Unser Shop wird gerade gepflegt. Bitte versuchen Sie es in wenigen Minuten erneut.
            </p>
        </div>
    </main>
</body>
</html>
