# MochiCards — Kurzanleitung für die Website-Pflege (Kundenversion)

**Version 1.0** · Mochi Cards · 2026

---

Diese Anleitung richtet sich an **dich als Betreiberin oder Betreiber**: Kalender pflegen, Texte und Bilder ändern, Einstellungen anpassen.

**Installation, Server und Updates** übernimmt normalerweise **dein Techniker oder Hosting-Anbieter**. Dafür gibt es die technische Datei **`README_ADMIN.md`** im gleichen Projektordner.

---

## 1. So arbeitest du im Admin

1. Im Browser die Adresse deiner Website öffnen und an die Admin-Adresse anhängen: **`/admin`**  
   *Beispiel:* Wenn die Website `https://www.dein-laden.de` heißt, lautet der Admin oft `https://www.dein-laden.de/admin`.
2. Mit **E-Mail** und **Passwort** anmelden — die Zugangsdaten bekommst du bei der Übergabe. **Passwort nur über sichere Kanäle** weitergeben.
3. Nach dem Login siehst du das **Dashboard** und die Menüpunkte (z. B. Events, Einstellungen, Kalender).

---

## 2. Content pflegen

### Shop-Renner (Hero-Bereich)

1. Menüpunkt **Website-Einstellungen** öffnen.
2. Ein **Bild** hochladen (das System kann es für die Darstellung vorbereiten, z. B. bis ca. **1400 px** Breite — je nach Einstellung).
3. **Name**, **Preis** und **Link** zum Shop (oder Produkt) eintragen.
4. **Speichern** — die Startseite zeigt den Bereich danach aktualisiert an.

### Kalender und Events

- **Termin verschieben:** Im **Admin-Kalender** kannst du einen Termin mit der Maus **ziehen** und an einen neuen Tag legen (Drag & Drop). Die Änderung wird gespeichert.
- **Termin bearbeiten:** Auf ein Event **klicken** — es öffnet sich ein **Panel von der Seite** (Slide-over). Du musst die Seite dafür nicht verlassen.
- **Farben:** Bekannte **Spielarten** (Pokémon, Magic usw.) haben feste Farben. Für Besonderheiten kannst du oft eine **eigene Kalenderfarbe** pro Event setzen.
- **Neue Spielart:** Wenn eine **Spielart** noch nicht in der Liste steht, wähle **„Eigene Spielart“** und trage unten den **Namen** ein (erscheint dann überall wie die anderen).

### News und weitere Inhalte

Je nach Einrichtung findest du weitere Bereiche (z. B. **Beiträge**, **News**, **Seiten**). Prinzip überall gleich: Eintrag wählen oder anlegen, **Text und Bilder** anpassen, **Speichern** / **Veröffentlichen** beachten.

---

## 3. Sicherheit — worauf du achten solltest

### Zwei-Faktor-Login (2FA), falls aktiviert

Manchmal musst du zusätzlich zum Passwort eine **App** nutzen (z. B. **Google Authenticator** oder **Authy**):

1. Beim **ersten Einrichten** wird oft ein **QR-Code** angezeigt — mit der App **scannen**.
2. Danach gibt die App alle **30 Sekunden** einen **6-stelligen Code** — den bei der Anmeldung eingeben.

**Wiederherstellungscodes:** Wenn du welche angezeigt bekommst: **unbedingt sicher aufbewahren** (Ausdruck im Tresor, oder Passwortmanager). **Ohne Codes und ohne Handy** kann der Nicht-mehr-Zugang nur ein Techniker lösen — das kostet Zeit.

Falls **kein** QR-Code erscheint: 2FA ist für euer Panel vielleicht noch nicht eingeschaltet — dann reicht Passwort; Fragen dazu an die Übergabe.

### Falsches Passwort zu oft eingeben

Nach **mehreren** fehlgeschlagenen Versuchen mit **demselben Internetanschluss** kann die Anmeldung für **etwa 15 Minuten** gesperrt sein. Einfach **warten** und es erneut probieren — oder aus einem anderen Netz testen.

### Gute Gewohnheiten

- **Starkes, eigenes Passwort** nur für diesen Admin-Zugang.
- **Niemals** Passwort per unsicherer Chat-Nachricht an Unbekannte.
- **Abmelden**, wenn du den Rechner verlässt (besonders auf geteilten Geräten).

---

## 4. Wartung im Alltag

- **„Geht was nicht richtig“ nach einer Änderung?** Im Admin gibt es oft ein **Blitz-Symbol** oder einen Punkt wie **„Cache leeren“** — damit werden angezeigte Inhalte neu geladen.
- **Technische Probleme** (weiße Seite, Fehlermeldung, E-Mails kommen nicht an): **Hosting oder Techniker** kontaktieren — nichts Löschen auf dem Server, wenn du dir unsicher bist.

---

## PDF aus dieser Datei machen

1. Diese Datei **`README_ADMIN_KUNDE.md`** in **Cursor** oder **Visual Studio Code** öffnen.
2. Eine Erweiterung wie **„Markdown PDF“** installieren.
3. In der Datei **Rechtsklick** → z. B. **„Markdown PDF: Export (pdf)“** — speichern, drucken oder an das Team mailen.

*Ende der Kunden-Kurzanleitung.*
