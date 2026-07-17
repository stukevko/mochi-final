# Postfix (nur ausgehend) — Mochi Cards

Shop-Site: **mochi-cards.de**  
Absender / SPF-DKIM-Domain: **nexvalue.de**  
From: **noreply-mochi@nexvalue.de**

Laravel spricht den lokalen Postfix per SMTP `127.0.0.1:25` (oder `sendmail`) an. Kein eingehender Mail-Empfang nötig.

## 1. Postfix installieren (Ubuntu, node01)

```bash
sudo apt update
sudo DEBIAN_FRONTEND=noninteractive apt install -y postfix mailutils
```

Bei der Debconf-Abfrage (oder danach per Reconfigure):

- **General type:** `Internet Site` (direktes Zustellen)  
  oder `Satellite system`, falls ein Upstream-Relay (z. B. Hoster-SMTP) genutzt wird
- **System mail name:** Hostname der VM bzw. `mochi-cards.de` (intern); der sichtbare From bleibt `noreply-mochi@nexvalue.de`

Erneut konfigurieren:

```bash
sudo dpkg-reconfigure postfix
```

## 2. Nur ausgehend absichern

In `/etc/postfix/main.cf` (Auszug):

```cf
# Nur lokal annehmen (kein offenes Relay)
inet_interfaces = loopback-only
mynetworks = 127.0.0.0/8 [::1]/128

# Absender-Domain für EHLO / Envelope (anpassen an Server-FQDN)
myhostname = node01.mochi-cards.de
mydestination = $myhostname, localhost

# Optional: feste Absender-Umschreibung falls Apps ohne From senden
# sender_canonical_maps = hash:/etc/postfix/sender_canonical
```

Danach:

```bash
sudo systemctl restart postfix
sudo systemctl enable postfix
```

Port 25 von außen **nicht** in der Firewall freigeben (nur localhost).

## 3. Laravel `.env` auf der VM (`/var/www/mochi-cards`)

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_SCHEME=null
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply-mochi@nexvalue.de"
MAIL_FROM_NAME="${APP_NAME}"
```

Alternativ:

```env
MAIL_MAILER=sendmail
MAIL_SENDMAIL_PATH="/usr/sbin/sendmail -bs -i"
MAIL_FROM_ADDRESS="noreply-mochi@nexvalue.de"
```

Config-Cache neu bauen:

```bash
cd /var/www/mochi-cards
php artisan config:clear
php artisan config:cache
```

## 4. DNS: SPF / DKIM für **nexvalue.de**

Die öffentliche Absenderdomain ist **nexvalue.de**, nicht mochi-cards.de.

### SPF

TXT-Record auf `nexvalue.de` (bestehenden SPF erweitern, nicht doppelt anlegen), z. B.:

```text
v=spf1 ip4:<ÖFFENTLICHE-IPv4-VON-node01> ~all
```

Bei Satellite/Relay die IP bzw. `include:` des Relays eintragen.

### DKIM (empfohlen)

OpenDKIM auf der VM oder DKIM beim Hoster; Public Key als TXT unter z. B. `mail._domainkey.nexvalue.de`.

### DMARC (optional)

```text
_dmarc.nexvalue.de. TXT "v=DMARC1; p=none; rua=mailto:postmaster@nexvalue.de"
```

PTR/rDNS der Server-IP sollte zum EHLO-Hostname passen (bessere Zustellung).

## 5. Tests

```bash
# Rohtest Postfix
echo "Postfix OK" | mail -s "Postfix test" deine@mail.de

# Laravel (Bestellmails gehen synchron per sendNow — kein Queue-Worker nötig dafür)
cd /var/www/mochi-cards
php artisan shop:test-mail deine@mail.de
php artisan shop:go-live-check
```

Logs bei Problemen: `/var/log/mail.log` bzw. `journalctl -u postfix -e`.

**Hinweis:** In `.env` idealerweise `QUEUE_CONNECTION=sync`. Bei `database`/`redis` ohne Worker kommen andere Jobs (z. B. Versandmail `OrderShipped`) nicht an — Bestellbestätigung aber schon (seit `sendNow`-Fix).
