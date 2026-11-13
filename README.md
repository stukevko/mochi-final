# Mochi Cards – CMS & Shop

Ein System für alles: Online-Shop (Zahlung über SumUp), Events mit Kalender, News/Journal,
CMS-Seiten und Rechtstexte – verwaltet im **Mochi Hub** (Admin unter `/admin`).

Shop und Admin sind als **App installierbar** (Handy, Tablet, Desktop): eigenes Icon, Vollbild ohne
Browserleiste, Tab-Leiste unten, Offline-Seite bei Funkloch.

| Stack | Version |
| --- | --- |
| PHP | 8.3 · 8.4 · 8.5 |
| Laravel | 13.x (≥ 13.29) |
| Filament / Livewire | 5.x / 4.x |
| Datenbank | SQLite (Standard), MySQL/MariaDB oder PostgreSQL |
| Frontend | Vite 8, Tailwind CSS 4 |

---

## Installation

### Variante A – Docker (jede Maschine: Linux, macOS, Windows)

```bash
cp .env.example .env
docker compose run --rm app php artisan key:generate --show   # Ausgabe als APP_KEY in .env eintragen
docker compose up -d --build
docker compose exec app php artisan shop:ensure-admin
```

Shop: <http://localhost:8080> · Admin: <http://localhost:8080/admin>
Läuft mit Web-Server, Queue-Worker und Scheduler (Backups). Daten liegen in Docker-Volumes.

### Variante B – Lokal mit PHP & Node

Voraussetzungen: PHP ≥ 8.3 (Extensions: `intl`, `gd`, `pdo_sqlite`, `mbstring`, `zip`), Composer 2, Node ≥ 20.

```bash
composer run setup        # installiert alles, legt .env + SQLite an, migriert, baut Assets
php artisan shop:ensure-admin
composer run dev          # Server :8008 + Queue + Vite
```

### Variante C – Server (nginx/PHP-FPM oder Apache/Shared Hosting)

```bash
composer run deploy       # composer --no-dev, migrate, storage:link, optimize, queue:restart
npm ci && npm run build
```

* **nginx:** Vorlage in `deploy/nginx/mochi-cards.de.conf` (Gzip, Upload-Sandbox, Service-Worker-Header).
  PHP-Socket an die installierte Version anpassen.
* **Apache/Shared Hosting:** `public/.htaccess` und `storage/app/public/.htaccess` sind vorbereitet.
  Document-Root muss auf `public/` zeigen.
* Cron: `* * * * * php /pfad/artisan schedule:run` · Queue: `php artisan queue:work` (z. B. per Supervisor).

### Production-Checkliste (`.env`)

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://deine-domain.de
SESSION_SECURE_COOKIE=true
FILAMENT_REQUIRE_2FA=true        # Passkey-Pflicht im Admin
TRUSTED_PROXIES=*                # hinter nginx/CDN; ohne Proxy: 127.0.0.1
LOG_LEVEL=warning
```

Danach `php artisan shop:go-live-check`.

---

## Verkauf & Rechnungen

Bezahlt wird ausschließlich über **SumUp** (`SUMUP_TOKEN`, `SUMUP_MERCHANT_CODE` in `.env`).

1. Kunde bestellt → Ware wird **30 Minuten reserviert**, Weiterleitung zur SumUp-Bezahlseite.
2. Zahlung wird dreifach abgesichert erkannt: Rückkehr des Kunden, SumUp-Webhook
   (`POST /webhooks/payment/sumup`) und der Abgleich `shop:reconcile-payments` (alle 5 Minuten).
   Der Status wird immer direkt bei SumUp nachgefragt — gefälschte Meldungen bewirken nichts.
3. Bei Zahlungseingang: fortlaufende Rechnungsnummer (`RE-2026-00001`), Bestätigungs-Mail mit
   PDF-Rechnung an den Kunden, Benachrichtigung an den Laden.
4. Nicht bezahlt → Bestellung verfällt, Ware geht automatisch zurück in den Bestand.
   Storno im Admin bucht die Ware ebenfalls zurück.

**Wichtig:** Der Cron/Scheduler muss laufen (siehe oben), sonst werden abgebrochene Bestellungen
nicht freigegeben. Im Admin unter *Global Settings → Buchhaltung*: Steuernummer oder USt-IdNr.,
Rechnungs-Präfix, MwSt.-Satz und Versandkosten pflegen.

## Laden & Shop als eine Einheit (SumUp)

Im Admin unter **Management → Kasse & Shop (SumUp)**:

* **Artikel:** In SumUp anlegen und pflegen, dann die Artikelliste als CSV exportieren und hochladen —
  Name, Preis und Artikelnummer werden übernommen. (SumUp bietet für den Artikelkatalog keine
  öffentliche Schnittstelle, deshalb der Export.)
* **Bestand:** Verkäufe an der SumUp-Kasse werden alle 5 Minuten über die SumUp Sales API gelesen und
  vom Shop-Bestand abgezogen (`shop:sumup-sync-sales`). Zuordnung über die **Artikelnummer (SKU)**.
  Unbekannte Artikel landen als Entwurf im Shop.
* **Grenze:** Der Shop kann den Bestand *in SumUp* nicht ändern. Maßgeblich ist der Bestand im Shop.

Der SumUp-API-Schlüssel braucht die Berechtigung `sales.read`.

## Updates & Sicherheit

```bash
composer update && npm update     # Abhängigkeiten aktualisieren
composer run security             # composer audit + npm audit
php artisan test
```

`composer.json` blockiert Pakete mit bekannten Sicherheitslücken (`audit.block-insecure`).
GitHub Actions (`.github/workflows/ci.yml`) testet jeden Push gegen PHP 8.3, 8.4 und 8.5.

Weitere Doku: `README_ADMIN.md` (Technik/Betrieb) · `README_ADMIN_KUNDE.md` (Bedienung des Admins).
