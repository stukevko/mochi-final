<?php

/**
 * Einrichtungs-Assistent für normalen Webspace (kein SSH, kein Composer, kein Cronjob nötig).
 *
 * Läuft bewusst OHNE Laravel, bis die .env geschrieben ist. Danach wird Laravel im selben
 * Aufruf gestartet, die Datenbank angelegt und der Administrator erstellt.
 *
 * Sicherheit: Der Assistent arbeitet nur, solange der Shop NICHT eingerichtet ist
 * (keine .env vorhanden bzw. Einrichtung wurde gerade begonnen). Danach sperrt er sich selbst.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root.'/.env';
$lockFile = $root.'/storage/app/installed.lock';
$progressFile = $root.'/storage/app/install-in-progress';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
header('X-Frame-Options: DENY');

$alreadyInstalled = is_file($lockFile) || (is_file($envFile) && ! is_file($progressFile));
if ($alreadyInstalled) {
    http_response_code(403);
    page('Bereits eingerichtet', '<p>Der Shop ist bereits eingerichtet. Aus Sicherheitsgründen ist der Assistent gesperrt.</p><p><a class="btn" href="/">Zum Shop</a> <a class="btn ghost" href="/admin">Zum Admin</a></p>');
    exit;
}

session_name('mochi_install');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(24));
}

// ------------------------------------------------------------------ Voraussetzungen
$checks = [];
$checks[] = ['PHP 8.3 oder neuer', version_compare(PHP_VERSION, '8.3.0', '>='), 'Installiert: PHP '.PHP_VERSION.'. Im Kundenbereich des Hosters auf PHP 8.3 oder 8.4 umstellen.'];
foreach (['pdo' => 'PDO', 'mbstring' => 'mbstring', 'openssl' => 'OpenSSL', 'intl' => 'intl', 'gd' => 'GD (Bilder)', 'fileinfo' => 'fileinfo', 'dom' => 'DOM/XML', 'curl' => 'cURL', 'ctype' => 'ctype', 'tokenizer' => 'tokenizer'] as $ext => $label) {
    $checks[] = ['PHP-Erweiterung '.$label, extension_loaded($ext), 'Im Kundenbereich des Hosters aktivieren oder den Support fragen: „Bitte PHP-Erweiterung '.$ext.' aktivieren“.'];
}
$hasSqlite = extension_loaded('pdo_sqlite');
$hasMysql = extension_loaded('pdo_mysql');
$checks[] = ['Datenbank (SQLite oder MySQL)', $hasSqlite || $hasMysql, 'PHP-Erweiterung pdo_sqlite oder pdo_mysql aktivieren.'];
$checks[] = ['Programmdateien vollständig (Ordner „vendor“)', is_file($root.'/vendor/autoload.php'), 'Bitte das komplette Webspace-Paket hochladen — nicht den Quellcode von GitHub.'];
$checks[] = ['Design-Dateien vorhanden (Ordner „public/build“)', is_file($root.'/public/build/manifest.json'), 'Bitte das komplette Webspace-Paket hochladen.'];
foreach (['' => 'Hauptordner', '/storage' => 'storage', '/storage/app' => 'storage/app', '/storage/framework' => 'storage/framework', '/storage/logs' => 'storage/logs', '/bootstrap/cache' => 'bootstrap/cache', '/database' => 'database'] as $dir => $label) {
    @mkdir($root.$dir, 0775, true);
    $checks[] = ['Ordner „'.$label.'“ beschreibbar', is_writable($root.$dir), 'Im FTP-Programm die Rechte dieses Ordners auf 755 (oder 775) setzen.'];
}
$ready = ! in_array(false, array_column($checks, 1), true);

// ------------------------------------------------------------------ Formular
$https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$behindProxy = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || isset($_SERVER['HTTP_X_FORWARDED_FOR']);
$host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$appUrl = ($https ? 'https' : 'http').'://'.$host;

$in = static fn (string $key, string $default = ''): string => trim((string) ($_POST[$key] ?? $default));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ready) {
    if (! hash_equals($_SESSION['token'], (string) ($_POST['token'] ?? ''))) {
        $errors[] = 'Die Sitzung ist abgelaufen. Bitte Seite neu laden und noch einmal absenden.';
    }

    $shopName = $in('shop_name');
    $adminName = $in('admin_name');
    $adminEmail = $in('admin_email');
    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $db = $in('db', $hasSqlite ? 'sqlite' : 'mysql');

    if ($shopName === '' || mb_strlen($shopName) > 80) {
        $errors[] = 'Bitte einen Shop-Namen eingeben (höchstens 80 Zeichen).';
    }
    if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte eine gültige E-Mail-Adresse für den Admin-Zugang eingeben.';
    }
    if (mb_strlen($adminPassword) < 10 || ! preg_match('/[A-Za-z]/', $adminPassword) || ! preg_match('/\d/', $adminPassword)) {
        $errors[] = 'Das Passwort braucht mindestens 10 Zeichen mit Buchstaben und Zahlen.';
    }
    if ($adminPassword !== (string) ($_POST['admin_password_repeat'] ?? '')) {
        $errors[] = 'Die beiden Passwörter stimmen nicht überein.';
    }

    $dbEnv = [];
    if ($db === 'mysql') {
        if (! $hasMysql) {
            $errors[] = 'MySQL wird von diesem Webspace nicht unterstützt.';
        } else {
            try {
                new PDO(
                    'mysql:host='.$in('db_host', 'localhost').';port='.(int) ($in('db_port', '3306') ?: 3306).';dbname='.$in('db_name').';charset=utf8mb4',
                    $in('db_user'),
                    (string) ($_POST['db_pass'] ?? ''),
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8],
                );
                $dbEnv = [
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => $in('db_host', 'localhost'),
                    'DB_PORT' => (string) ((int) ($in('db_port', '3306') ?: 3306)),
                    'DB_DATABASE' => $in('db_name'),
                    'DB_USERNAME' => $in('db_user'),
                    'DB_PASSWORD' => (string) ($_POST['db_pass'] ?? ''),
                ];
            } catch (Throwable $e) {
                $errors[] = 'Die MySQL-Datenbank ist mit diesen Angaben nicht erreichbar. Bitte Server, Datenbankname, Benutzer und Passwort prüfen.';
            }
        }
    } else {
        if (! $hasSqlite) {
            $errors[] = 'SQLite wird von diesem Webspace nicht unterstützt — bitte MySQL wählen.';
        }
        $dbEnv = ['DB_CONNECTION' => 'sqlite'];
    }

    if ($errors === []) {
        try {
            @file_put_contents($progressFile, date('c'));

            if (($dbEnv['DB_CONNECTION'] ?? '') === 'sqlite' && ! is_file($root.'/database/database.sqlite')) {
                if (@file_put_contents($root.'/database/database.sqlite', '') === false) {
                    throw new RuntimeException('Die Datenbank-Datei konnte nicht angelegt werden (Ordner „database“ nicht beschreibbar).');
                }
            }

            // Uploads: Symlink public/storage → storage/app/public; wo Symlinks verboten sind, echter Ordner.
            $publicDiskRoot = '';
            $link = $root.'/public/storage';
            @mkdir($root.'/storage/app/public', 0775, true);
            if (! is_link($link) && ! is_dir($link)) {
                $linked = function_exists('symlink') && @symlink($root.'/storage/app/public', $link);
                if (! $linked) {
                    @mkdir($link, 0775, true);
                }
            }
            if (is_dir($link) && ! is_link($link)) {
                $publicDiskRoot = 'public/storage';
                @copy($root.'/storage/app/public/.htaccess', $link.'/.htaccess');
            }

            $smtpHost = $in('mail_host');
            $env = array_merge([
                'APP_NAME' => $shopName,
                'APP_ENV' => 'production',
                'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
                'APP_DEBUG' => 'false',
                'APP_URL' => $appUrl,
                'APP_LOCALE' => 'de',
                'APP_FALLBACK_LOCALE' => 'de',
                'MOCHI_SITE_NAME' => $shopName,
                'FORCE_HTTPS' => $https ? 'true' : 'false',
                'TRUSTED_PROXIES' => $behindProxy ? '*' : '127.0.0.1',
                'LOG_CHANNEL' => 'stack',
                'LOG_STACK' => 'daily',
                'LOG_LEVEL' => 'warning',
            ], $dbEnv, [
                'SESSION_DRIVER' => 'file',
                'SESSION_LIFETIME' => '120',
                'SESSION_SECURE_COOKIE' => $https ? 'true' : 'false',
                'CACHE_STORE' => 'file',
                'QUEUE_CONNECTION' => 'sync',
                'FILESYSTEM_DISK' => 'local',
                'PUBLIC_DISK_ROOT' => $publicDiskRoot,
                'WEB_CRON' => 'true',
                'CRON_TOKEN' => bin2hex(random_bytes(20)),
                'FILAMENT_REQUIRE_2FA' => 'false',
                'MAIL_MAILER' => $smtpHost !== '' ? 'smtp' : 'log',
                'MAIL_HOST' => $smtpHost,
                'MAIL_PORT' => (string) ((int) ($in('mail_port', '587') ?: 587)),
                'MAIL_USERNAME' => $in('mail_user'),
                'MAIL_PASSWORD' => (string) ($_POST['mail_pass'] ?? ''),
                'MAIL_SCHEME' => ((int) $in('mail_port', '587')) === 465 ? 'smtps' : 'null',
                'MAIL_FROM_ADDRESS' => $in('mail_user') !== '' && filter_var($in('mail_user'), FILTER_VALIDATE_EMAIL) ? $in('mail_user') : $adminEmail,
                'MAIL_FROM_NAME' => $shopName,
                'SHOP_ORDER_NOTIFICATION_EMAIL' => $adminEmail,
                'SUMUP_TOKEN' => $in('sumup_token'),
                'SUMUP_MERCHANT_CODE' => $in('sumup_merchant'),
                'BACKUP_DESTINATION_DISKS' => 'local',
            ]);

            $lines = "# Erstellt vom Einrichtungs-Assistenten am ".date('d.m.Y H:i')."\n";
            foreach ($env as $key => $value) {
                $value = (string) $value;
                $needsQuotes = $value === '' ? false : (bool) preg_match('/[\s#"\'$\\\\=]/', $value);
                $lines .= $key.'='.($needsQuotes ? '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"' : $value)."\n";
            }
            if (@file_put_contents($envFile, $lines, LOCK_EX) === false) {
                throw new RuntimeException('Die Konfigurationsdatei (.env) konnte nicht geschrieben werden — Hauptordner nicht beschreibbar.');
            }
            @chmod($envFile, 0640);

            // ---------- Laravel starten: Tabellen anlegen, Admin erstellen ----------
            @set_time_limit(300);
            require $root.'/vendor/autoload.php';
            /** @var Illuminate\Foundation\Application $app */
            $app = require $root.'/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            $kernel->call('migrate', ['--force' => true]);

            $user = App\Models\User::query()->where('email', $adminEmail)->first() ?? new App\Models\User;
            $user->forceFill([
                'name' => $adminName !== '' ? $adminName : 'Admin',
                'email' => $adminEmail,
                'password' => $adminPassword,
                'role' => 'admin',
                'is_active' => true,
            ])->save();

            App\Models\Setting::set('shop_name', $shopName, 'text', 'general');
            App\Models\Setting::set('shop_email', $adminEmail, 'text', 'general');
            App\Models\Setting::set('order_notification_email', $adminEmail, 'text', 'email');

            $files = glob($root.'/database/migrations/*.php') ?: [];
            @file_put_contents($root.'/storage/app/schema-version', md5(implode('|', array_map('basename', $files))));

            @file_put_contents($lockFile, date('c'));
            @unlink($progressFile);
            $_SESSION = [];
            session_destroy();

            $hint = $smtpHost === ''
                ? '<p class="note">Es wurde kein E-Mail-Postfach eingetragen. Bestellbestätigungen werden deshalb noch nicht verschickt. Die Zugangsdaten deines Postfachs kannst du später in der Datei <code>.env</code> nachtragen (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD) — oder uns kurz Bescheid geben.</p>'
                : '';

            page('Fertig — der Shop läuft', '<p>Alles ist eingerichtet. Melde dich jetzt mit deiner E-Mail-Adresse und dem gewählten Passwort im Admin an.</p>'.$hint.'<p><a class="btn" href="/admin">Zum Admin</a> <a class="btn ghost" href="/">Shop ansehen</a></p><p class="small">Nächste Schritte im Admin: Logo hochladen, Rechtstexte eintragen, Steuernummer und Versand pflegen, Artikel aus SumUp übernehmen.</p>');
            exit;
        } catch (Throwable $e) {
            // .env bleibt für die Fehlersuche NICHT liegen — so kann der Assistent sauber neu gestartet werden.
            @unlink($envFile);
            $errors[] = 'Die Einrichtung ist fehlgeschlagen: '.htmlspecialchars($e->getMessage(), ENT_QUOTES).' — Bitte Angaben prüfen und erneut versuchen.';
        }
    }
}

// ------------------------------------------------------------------ Ausgabe
$e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$body = '';

if (! $ready) {
    $body .= '<p>Bevor es losgeht, muss auf dem Webspace noch etwas angepasst werden:</p><ul class="checks">';
    foreach ($checks as [$label, $ok, $help]) {
        $body .= '<li class="'.($ok ? 'ok' : 'bad').'"><strong>'.$e($label).'</strong>'.($ok ? '' : '<span>'.$e($help).'</span>').'</li>';
    }
    $body .= '</ul><p><a class="btn" href="install.php">Erneut prüfen</a></p>';
    page('Webspace vorbereiten', $body);
    exit;
}

foreach ($errors as $error) {
    $body .= '<p class="error">'.$error.'</p>';
}

$val = static fn (string $key, string $default = ''): string => htmlspecialchars(trim((string) ($_POST[$key] ?? $default)), ENT_QUOTES, 'UTF-8');
$dbChoice = $in('db', $hasSqlite ? 'sqlite' : 'mysql');

$body .= '<p>Vier kurze Angaben, dann läuft dein Shop unter <strong>'.$e($appUrl).'</strong>.</p>
<form method="post" autocomplete="off">
<input type="hidden" name="token" value="'.$e($_SESSION['token']).'">

<h2>1. Dein Shop</h2>
<label>Name des Shops<input name="shop_name" required maxlength="80" value="'.$val('shop_name', 'Mochi Cards').'"></label>

<h2>2. Dein Admin-Zugang</h2>
<label>Dein Name<input name="admin_name" maxlength="80" value="'.$val('admin_name').'"></label>
<label>E-Mail-Adresse<input type="email" name="admin_email" required value="'.$val('admin_email').'"><small>Damit meldest du dich an. Hierhin kommen auch die Bestellungen.</small></label>
<label>Passwort<input type="password" name="admin_password" required minlength="10" autocomplete="new-password"><small>Mindestens 10 Zeichen mit Buchstaben und Zahlen.</small></label>
<label>Passwort wiederholen<input type="password" name="admin_password_repeat" required minlength="10" autocomplete="new-password"></label>

<h2>3. SumUp <span class="opt">kann später nachgetragen werden</span></h2>
<label>SumUp API-Schlüssel<input name="sumup_token" value="'.$val('sumup_token').'"><small>In SumUp unter Einstellungen → Entwickler → API-Schlüssel. Ohne Schlüssel kann noch niemand bezahlen.</small></label>
<label>SumUp Händler-Code<input name="sumup_merchant" value="'.$val('sumup_merchant').'"><small>Steht in SumUp im Profil, z. B. MC1A2B3C.</small></label>

<h2>4. E-Mail-Postfach <span class="opt">kann später nachgetragen werden</span></h2>
<label>Postausgangsserver (SMTP)<input name="mail_host" placeholder="z. B. smtp.dein-hoster.de" value="'.$val('mail_host').'"><small>Die Daten eines Postfachs bei deinem Hoster. Darüber gehen Bestellbestätigungen und Rechnungen raus.</small></label>
<div class="row"><label>Port<input name="mail_port" inputmode="numeric" value="'.$val('mail_port', '587').'"></label><label>Benutzer (meist die E-Mail-Adresse)<input name="mail_user" value="'.$val('mail_user').'"></label></div>
<label>Passwort des Postfachs<input type="password" name="mail_pass" autocomplete="new-password"></label>

<details'.($dbChoice === 'mysql' || ! $hasSqlite ? ' open' : '').'>
<summary>Datenbank (nur ändern, wenn dein Hoster MySQL vorgibt)</summary>
<label class="radio"><input type="radio" name="db" value="sqlite"'.($dbChoice === 'sqlite' ? ' checked' : '').($hasSqlite ? '' : ' disabled').'> Eingebaute Datenbank (empfohlen — nichts einzustellen)</label>
<label class="radio"><input type="radio" name="db" value="mysql"'.($dbChoice === 'mysql' ? ' checked' : '').($hasMysql ? '' : ' disabled').'> MySQL-Datenbank vom Hoster</label>
<div class="row"><label>Server<input name="db_host" value="'.$val('db_host', 'localhost').'"></label><label>Port<input name="db_port" inputmode="numeric" value="'.$val('db_port', '3306').'"></label></div>
<label>Datenbankname<input name="db_name" value="'.$val('db_name').'"></label>
<div class="row"><label>Benutzer<input name="db_user" value="'.$val('db_user').'"></label><label>Passwort<input type="password" name="db_pass" autocomplete="new-password"></label></div>
</details>

<button class="btn" type="submit">Shop einrichten</button>
<p class="small">Das dauert etwa 10 bis 30 Sekunden. Bitte die Seite währenddessen nicht schließen.</p>
</form>';

page('Shop einrichten', $body);

// ------------------------------------------------------------------ Layout
function page(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>'.htmlspecialchars($title, ENT_QUOTES).'</title><style>
:root{color-scheme:dark}*{box-sizing:border-box}
body{margin:0;background:linear-gradient(#0b1120,#040712) fixed;color:#f8fafc;font:400 1rem/1.55 Inter,system-ui,-apple-system,"Segoe UI",sans-serif;padding:1.5rem 1rem 3rem}
main{max-width:34rem;margin:0 auto}
h1{font-size:1.6rem;line-height:1.2;margin:1rem 0 .75rem}
h2{font-size:1.05rem;margin:2rem 0 .25rem;padding-top:1.25rem;border-top:1px solid rgb(255 255 255/.1)}
.opt{font-weight:400;font-size:.8rem;color:#a8b4c4;margin-left:.35rem}
p{color:#cbd5e1}strong{color:#fff}
label{display:block;margin-top:.9rem;font-size:.9rem;font-weight:500;color:#f8fafc}
input:not([type=radio]){display:block;width:100%;margin-top:.35rem;min-height:2.9rem;padding:.6rem .8rem;border-radius:.7rem;border:1px solid rgb(255 255 255/.16);background:#0a0f1c;color:#fff;font:inherit;font-size:1rem}
input:focus-visible,.btn:focus-visible,summary:focus-visible{outline:2px solid #ffb547;outline-offset:2px}
small{display:block;margin-top:.3rem;font-weight:400;color:#a8b4c4;font-size:.8rem}
.row{display:grid;grid-template-columns:1fr 2fr;gap:.75rem}
.radio{display:flex;gap:.6rem;align-items:center;font-weight:400}
details{margin-top:2rem;padding:1rem;border:1px solid rgb(255 255 255/.1);border-radius:.9rem}summary{cursor:pointer;font-weight:500}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:3rem;margin-top:1.75rem;padding:0 1.6rem;border:0;border-radius:999px;background:linear-gradient(135deg,#ff7a1f,#ffb547);color:#0b0f16;font:inherit;font-weight:600;text-decoration:none;cursor:pointer}
.btn.ghost{background:none;border:1px solid rgb(255 255 255/.25);color:#fff}
.error{padding:.8rem 1rem;border-radius:.7rem;border:1px solid rgb(244 63 94/.45);background:rgb(244 63 94/.12);color:#fecdd3}
.note{padding:.8rem 1rem;border-radius:.7rem;border:1px solid rgb(245 158 11/.4);background:rgb(245 158 11/.1);color:#fde68a}
.small{font-size:.8rem;color:#a8b4c4}
.checks{list-style:none;padding:0}.checks li{padding:.6rem 0 .6rem 1.8rem;border-bottom:1px solid rgb(255 255 255/.08);position:relative}
.checks li::before{position:absolute;left:0;font-weight:700}.checks .ok::before{content:"✓";color:#34d399}.checks .bad::before{content:"✕";color:#fb7185}
.checks span{display:block;color:#fda4af;font-size:.85rem}code{background:rgb(255 255 255/.08);padding:.1rem .35rem;border-radius:.3rem}
</style></head><body><main><h1>'.htmlspecialchars($title, ENT_QUOTES).'</h1>'.$body.'</main></body></html>';
}
