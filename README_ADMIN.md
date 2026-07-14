# 🃏 MochiCards Admin-Handbuch v1.0

Crafted with ❤️ by Kevko — Cursor build — 2026

---

## 0. Voraussetzungen (kurz checken)

Bevor du startest, sollten auf dem Rechner **oder** auf dem Hosting folgende Dinge verfügbar sein:

| Was | Wofür |
|-----|--------|
| **PHP 8.3 oder neuer** | Laravel & Filament |
| **Composer** ([getcomposer.org](https://getcomposer.org)) | PHP-Abhängigkeiten installieren |
| **Node.js + npm** | Design & Skripte bauen (`vite build`) — oft **nur auf deinem PC**, nicht auf jedem Webspace |
| **Datenbank** | Standard in diesem Projekt: **SQLite** (eine Datei). Alternativ: **MySQL/MariaDB** (häufig beim Hoster) |

Du musst **kein Programmierer** sein — aber du solltest **Dateien kopieren**, einen **Ordner entpacken** und idealerweise **ein Terminal / SSH** öffnen können (oder jemanden haben, der die Befehle für dich ausführt).

---

## 🚀 1. Installation & Erststart — Schritt für Schritt

### Schritt 1 — Projekt liegt auf dem Rechner / Server

- Alle Dateien des Projekts in einen Ordner legen (z. B. `mochicards-cms`).
- Im Terminal in **diesen Ordner** wechseln (bei Windows z. B. PowerShell: `cd Pfad\zum\Ordner`).

### Schritt 2 — `.env` anlegen (Konfiguration)

1. Im Projektroot liegt meist eine Vorlage **`.env.example`**.
2. **Kopieren** und in **`.env`** umbenennen (ohne `.example`).
3. Später passt du mindestens an:
   - **`APP_NAME`** — Anzeigename der App
   - **`APP_URL`** — volle Adresse der Website, z. B. `https://www.deine-domain.de`  
     Lokal oft: `http://127.0.0.1:8008` (passt zum Beispiel-Port in dieser Vorlage)

4. **Geheimer Schlüssel** erzeugen (wichtig für Sitzungen & Verschlüsselung):

   ```bash
   php artisan key:generate
   ```

### Schritt 3 — Datenbank

**Variante A — SQLite (Standard, einfach für Tests)**

1. In `.env` soll stehen: `DB_CONNECTION=sqlite`
2. Leere Datei anlegen, falls sie fehlt: `database/database.sqlite` (Ordner `database/` gibt es schon im Projekt).
3. Auf manchen Systemen: sicherstellen, dass der Webserver/PHP **schreiben** darf.

**Variante B — MySQL/MariaDB (typisch beim Hoster)**

1. Im Hoster-Kundenmenü eine **Datenbank + User** anlegen.
2. In `.env` eintragen (Beispiele siehe `.env.example`):

   - `DB_CONNECTION=mysql`
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

### Schritt 4 — PHP-Pakete installieren (Composer)

**Auf deinem Entwicklungs-PC** (alles zum Mitentwickeln):

```bash
composer install
```

**Auf dem Live-Server** (schlanker, ohne Entwickler-Tools):

```bash
composer install --no-dev --optimize-autoloader
```

Wenn hinter dem Terminal etwas rot leuchtet: die Fehlermeldung googeln oder an den Techniker weitergeben — oft fehlt eine PHP-Erweiterung (`pdo`, `pdo_sqlite`, `mbstring` …).

### Schritt 5 — Frontend bauen (npm)

Das erzeugt die „fertigen“ CSS/JS-Dateien für die Website **und** das Admin-Design.

```bash
npm install
npm run build
```

**Hinweis für reines Webhosting:** Viele Pakete haben **kein** Node.js auf dem Server. Dann: **lokal** `npm install && npm run build` ausführen und den kompletten Projektordner (inkl. `public/build/`) per FTP/Git auf den Server legen.

### Schritt 6 — Tabellen anlegen + Demo-Inhalte + erster Admin

```bash
php artisan migrate --seed
```

Der Seeder legt u. a. einen **Admin-Benutzer** an (siehe `database/seeders/DatabaseSeeder.php`):

| Feld | Wert (nur für den ersten Login!) |
|------|-----------------------------------|
| **E-Mail** | `admin@mochicards.test` |
| **Passwort** | `password` |

**Wichtig:** Nach dem ersten Login im echten Betrieb **sofort ein sicheres Passwort setzen** (Profil / Passwort ändern — je nach Filament-Einstellung).

### Schritt 7 — Bilder & Uploads (Symlink)

Damit hochgeladene Bilder unter `/storage` erreichbar sind:

```bash
php artisan storage:link
```

(Einmalig nach Installation oder wenn der Hoster es verlangt.)

### Schritt 8 — Lokal im Browser öffnen

1. Entwicklungsserver starten (Beispiel, Port wie in `.env.example`):

   ```bash
   php artisan serve --port=8008
   ```

2. **Öffentliche Website:** im Browser `http://127.0.0.1:8008` (oder dein `APP_URL`).
3. **Admin (Filament):** **`APP_URL` + `/admin`** — z. B. `http://127.0.0.1:8008/admin`  
   Dort mit der Admin-E-Mail und dem Passwort einloggen.

### Schritt 9 — Live-Server (Kurz-Checkliste)

- **`APP_ENV=production`**, **`APP_DEBUG=false`**
- **`APP_URL=https://…`** exakt wie im Browser (mit `https://`)
- **`SESSION_SECURE_COOKIE=true`** — nur sinnvoll **mit HTTPS** (bei reinem `http://` lokal zum Testen ggf. `false`, sonst bleiben Cookies leer)
- Webserver-Dokumentroot auf den Ordner **`public/`** zeigen lassen (Standard bei Laravel)
- Dateirechte: `storage/` und `bootstrap/cache/` beschreibbar

Optional danach Cache frischziehen:

```bash
php artisan optimize:clear
```

---

## 🎨 2. Content-Management (Für Dummies)

### ✨ Der Shop-Renner (Hero)

1. Gehe zu **Website-Einstellungen**.
2. Lade ein Bild hoch (System optimiert es automatisch auf **1400px**).
3. Name, Preis und Link eingeben → **Speichern** → „Zack“ Live.

### 📅 Kalender & Events

- **Drag & Drop:** Im Admin-Kalender kannst du Termine einfach verschieben.
- **Slide-over:** Klick auf ein Event öffnet die Bearbeitung rechts, ohne die Seite zu verlassen.
- **Farben:** Jede Spielart (Pokémon, Magic etc.) hat ihre Farbe. Sonderfarben sind manuell wählbar. **Eigene Spielarten** kannst du mit der Option „Eigene Spielart“ samt freiem Namen anlegen.

---

## 🛡️ 3. Sicherheit (WICHTIG)

### 🔑 Passkey-MFA (Zwei-Faktor)

- Der Admin nutzt **Passkeys** statt Authenticator-Apps (kein TOTP/QR-Code mehr).
- **Einrichtung:** Nach E-Mail+Passwort-Login im **Profil** (`/admin/profile`) einen Passkey registrieren (Fingerabdruck, Face ID, Geräte-PIN oder Sicherheitsschlüssel).
- **Anmeldung:** E-Mail + Passwort, danach Passkey-Bestätigung.
- **Wichtig:** Ohne registrierten Passkey und bei `FILAMENT_REQUIRE_2FA=true` ist kein Admin-Zugang möglich. Es gibt **keinen E-Mail-Fallback** — verlorene Passkeys können nur durch einen Techniker (DB/Server) behoben werden. **Mindestens zwei Geräte/Passkeys** empfohlen.
- Production: `APP_URL` muss exakt der Live-Domain entsprechen (z. B. `https://mochi-cards.de`); `PASSKEYS_RELYING_PARTY_ID` und `PASSKEYS_ALLOWED_ORIGINS` nur bei Abweichung setzen.

### 🚫 Schutz

- **Rate-Limiting:** **5** falsche Logins → **15 Minuten** Sperre für diese **IP** (siehe angepasste Login-Logik im Projekt).

---

## 🛠️ 4. Wartung

- **Cache leeren:** Über das Blitz-Icon im Admin oder im Terminal:

  ```bash
  php artisan optimize:clear
  ```

- **Bilder:** liegen unter **`storage/app/public`** (öffentlich erreichbar über den Symlink aus Schritt 7).

**Dokumentation Ende.**

---

## 💡 Der „PDF-Trick“ für dich

- **Technik & Installation (diese Datei):** In VS Code / Cursor mit einer Extension wie **„Markdown PDF“** → Rechtsklick in der Datei → PDF exportieren.
- **Nur für Endkunden (ohne Terminal):** Siehe **`README_ADMIN_KUNDE.md`** — gleicher PDF-Export; Inhalt ist auf Redaktion und Sicherheit für Laien reduziert.

Damit ist MochiCards **Ready to Ship.** 🚢🃏
