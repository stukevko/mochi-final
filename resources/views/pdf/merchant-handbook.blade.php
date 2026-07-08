<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Händler-Handbuch — {{ $appName }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; margin: 0; padding: 32px 36px; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 22px 0 8px; color: #c45a00; }
        p, li { margin: 0 0 8px; }
        ul { padding-left: 18px; }
        .meta { color: #666; font-size: 10px; margin-bottom: 24px; }
    </style>
</head>
<body>
    <h1>Händler-Handbuch</h1>
    <p class="meta">{{ $appName }} · erstellt am {{ $generatedAt }}</p>

    <h2>1. Admin-Zugang</h2>
    <p>Der Shop-Admin ist unter <strong>/admin</strong> erreichbar. Benutzer und Rollen werden unter „Benutzer“ verwaltet.</p>

    <h2>2. Produkte & Bestand</h2>
    <ul>
        <li>Produkte anlegen, Bilder hochladen, Varianten pflegen.</li>
        <li>Bestand wird beim Checkout automatisch reduziert.</li>
        <li>Inaktive Produkte erscheinen nicht im Shop.</li>
    </ul>

    <h2>3. Bestellungen</h2>
    <ul>
        <li>Status: Ausstehend → In Bearbeitung → Versendet → Geliefert.</li>
        <li>„Als bezahlt markieren“ und „Versendet (mit Mail)“ im Bestell-Detail.</li>
        <li>Rechnung als PDF herunterladen oder per Bestell-Mail an Kunden.</li>
    </ul>

    <h2>4. Zahlungen (SumUp)</h2>
    <ul>
        <li>SumUp-Token und Merchant Code in <code>.env</code> oder unter Zahlungsanbieter.</li>
        <li>Go-Live-Check: <code>php artisan shop:go-live-check</code></li>
    </ul>

    <h2>5. Inhalte & Events</h2>
    <ul>
        <li>Startseite, Hero, Journal und Events im Admin pflegen.</li>
        <li>Events sind Kalender-Einträge — kein Ticketverkauf.</li>
    </ul>

    <h2>6. Rechtliches & Branding</h2>
    <ul>
        <li>Impressum, AGB, Datenschutz und Widerruf unter Einstellungen pflegen.</li>
        <li>Logo unter Branding & Design hochladen (ersetzt den Platzhalter).</li>
    </ul>

    <h2>7. Wartung & Checks</h2>
    <ul>
        <li><code>php artisan shop:check-views</code> — prüft alle kritischen Views/PDFs/Mails.</li>
        <li><code>php artisan shop:go-live-check</code> — prüft Produktions-Readiness.</li>
        <li><code>php artisan storage:link</code> — muss auf dem Server gesetzt sein.</li>
    </ul>
</body>
</html>
