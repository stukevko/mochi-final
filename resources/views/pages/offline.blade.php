<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#040712">
    <meta name="robots" content="noindex">
    <title>Keine Verbindung – {{ config('mochicards.site_name', config('app.name')) }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    {{-- Bewusst ohne Vite-Assets: die Seite muss auch ohne Netz aus dem Service-Worker-Cache funktionieren. --}}
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100dvh; display: grid; place-items: center;
            padding: max(1.5rem, env(safe-area-inset-top)) 1.5rem max(1.5rem, env(safe-area-inset-bottom));
            background: linear-gradient(to bottom, #0b1120, #040712); color: #f8fafc;
            font: 400 1rem/1.55 Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        main { max-width: 22rem; text-align: center; }
        .sleeve {
            width: 4.5rem; height: 6.25rem; margin: 0 auto 1.75rem; border-radius: .7rem;
            border: 2px solid #ff7a1f; background: linear-gradient(160deg, rgb(255 122 31 / .18), rgb(255 181 71 / .04));
            box-shadow: 0 0 40px -12px rgb(255 122 31 / .6); transform: rotate(-6deg);
        }
        h1 { font-size: 1.375rem; line-height: 1.25; margin: 0 0 .5rem; font-weight: 600; }
        p { color: #a8b4c4; margin: 0 0 1.75rem; }
        button {
            min-height: 2.875rem; padding: 0 1.5rem; border: 0; border-radius: 999px; cursor: pointer;
            background: linear-gradient(135deg, #ff7a1f, #ffb547); color: #0b0f16; font: inherit; font-weight: 600;
        }
        button:focus-visible { outline: 2px solid #ffb547; outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <div class="sleeve" aria-hidden="true"></div>
        <h1>Du bist gerade offline</h1>
        <p>Sobald dein Gerät wieder Internet hat, geht es genau hier weiter. Dein Warenkorb bleibt gespeichert.</p>
        <button type="button" onclick="location.reload()">Erneut laden</button>
    </main>
    <script>window.addEventListener('online', () => location.reload());</script>
</body>
</html>
