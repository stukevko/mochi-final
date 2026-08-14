<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Kundenhandbuch — {{ config('app.name') }}</title>
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
    <h1>Shop-Betreiber-Handbuch</h1>
    <p class="meta">Erstellt am {{ $generatedAt }}</p>

    <h2>Shop nutzen</h2>
    <p>Produkte durchstöbern, in den Warenkorb legen und im Checkout mit SumUp bezahlen. Nach erfolgreicher Zahlung erhältst du eine Bestätigungs-Mail mit Rechnung im Anhang.</p>

    <h2>Bestellungen verwalten</h2>
    <ul>
        <li>Neue Bestellungen erscheinen im Admin unter „Bestellungen“.</li>
        <li>Nach Versand Tracking-Nummer eintragen und Versand-Mail auslösen.</li>
        <li>Rechnungen jederzeit als PDF herunterladen.</li>
    </ul>

    <h2>Inhalte anpassen</h2>
    <ul>
        <li>Startseite, Logo, Farben und Texte im Admin-Bereich.</li>
        <li>Events und Journal-Beiträge für Community und Turniere.</li>
    </ul>

    <h2>Vor dem Go-Live</h2>
    <ul>
        <li>SumUp-Zugangsdaten hinterlegen.</li>
        <li>Postfix/SMTP für E-Mail-Versand konfigurieren (nicht <code>log</code>; Absender <code>noreply-mochi@nexvalue.de</code>).</li>
        <li>Rechtstexte finalisieren.</li>
        <li><code>php artisan shop:check-views</code> und <code>shop:go-live-check</code> ausführen.</li>
    </ul>
</body>
</html>
