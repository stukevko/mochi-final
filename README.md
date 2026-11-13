# Mochi Cards – CMS & Shop

Ein System für alles: Online-Shop (SumUp, Stripe, PayPal, Vorkasse), Events mit Kalender, News/Journal,
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

## Updates & Sicherheit

```bash
composer update && npm update     # Abhängigkeiten aktualisieren
composer run security             # composer audit + npm audit
php artisan test
```

`composer.json` blockiert Pakete mit bekannten Sicherheitslücken (`audit.block-insecure`).
GitHub Actions (`.github/workflows/ci.yml`) testet jeden Push gegen PHP 8.3, 8.4 und 8.5.

Weitere Doku: `README_ADMIN.md` (Technik/Betrieb) · `README_ADMIN_KUNDE.md` (Bedienung des Admins).
