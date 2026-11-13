<?php
/**
 * Mochi Cards – Hochlade-Helfer für normalen Webspace.
 *
 * So geht's: Diese Datei UND "mochi-cards-webspace.zip" in den Ordner der Domain hochladen,
 * dann im Browser  https://deine-domain.de/mochi-setup.php  öffnen. Der Helfer entpackt alles
 * auf dem Server, räumt sich selbst weg und startet den Einrichtungs-Assistenten.
 * Für Updates genauso – Daten (.env, database, storage) bleiben erhalten.
 */
declare(strict_types=1);

@set_time_limit(0);
@ini_set('memory_limit', '256M');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

const BATCH_SECONDS = 8;

function out(string $title, string $body, ?string $refresh = null, string $marker = ''): never
{
    $meta = $refresh !== null ? '<meta http-equiv="refresh" content="0;url='.htmlspecialchars($refresh, ENT_QUOTES).'">' : '';
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'.$meta
        .'<title>Mochi Cards – '.htmlspecialchars($title).'</title><style>body{font-family:system-ui,sans-serif;background:#14101f;color:#f4f1fa;margin:0;display:grid;place-items:center;min-height:100vh}'
        .'main{max-width:32rem;padding:2rem;margin:1rem;background:#1f1830;border-radius:1rem}h1{font-size:1.4rem;margin:0 0 .75rem}p{line-height:1.5;color:#cfc8de}'
        .'a.btn{display:inline-block;margin-top:1rem;padding:.8rem 1.2rem;border-radius:.7rem;background:#ff7a1f;color:#14101f;font-weight:700;text-decoration:none}'
        .'.bar{height:.6rem;border-radius:1rem;background:#32284a;overflow:hidden;margin-top:1rem}.bar i{display:block;height:100%;background:#ff7a1f}</style></head>'
        .'<body><main><h1>'.htmlspecialchars($title).'</h1>'.$body.'</main>'.$marker.'</body></html>';
    exit;
}

if (PHP_VERSION_ID < 80300) {
    out('PHP ist zu alt', '<p>Dieser Webspace läuft mit PHP '.htmlspecialchars(PHP_VERSION).'. Der Shop braucht <strong>PHP 8.3 oder neuer</strong>. Das stellst du im Kundenbereich deines Hosters um (meist unter „PHP-Version“). Danach diese Seite neu laden.</p>');
}

$zips = glob(__DIR__.'/mochi*.zip') ?: [];
if ($zips === []) {
    out('ZIP-Datei fehlt', '<p>Bitte die Datei <strong>mochi-cards-webspace.zip</strong> in denselben Ordner hochladen, in dem diese Datei liegt. Danach diese Seite neu laden.</p>');
}
$zipPath = $zips[0];

if (! class_exists(ZipArchive::class)) {
    out('Entpacken hier nicht möglich', '<p>Auf diesem Webspace fehlt die PHP-Erweiterung „zip“. Zwei Wege: beim Hoster „zip“ einschalten lassen – oder die ZIP-Datei am Computer entpacken und alle Dateien per FTP hochladen (siehe LIESMICH.txt).</p>');
}
if (! is_writable(__DIR__)) {
    out('Ordner nicht beschreibbar', '<p>Der Server darf in diesen Ordner nicht schreiben. Bitte beim Hoster die Schreibrechte für den Ordner der Domain prüfen lassen.</p>');
}

$zip = new ZipArchive;
if ($zip->open($zipPath) !== true) {
    out('ZIP-Datei beschädigt', '<p>Die ZIP-Datei lässt sich nicht öffnen. Vermutlich wurde der Upload abgebrochen – bitte noch einmal hochladen.</p>');
}

$total = $zip->numFiles;
$offset = max(0, (int) ($_GET['i'] ?? 0));
$started = microtime(true);
// Eigene Daten des Shops werden nie überschrieben.
$keep = ['.env', 'database/database.sqlite', 'storage/app/installed.lock'];

for ($i = $offset; $i < $total; $i++) {
    $name = (string) $zip->getNameIndex($i);
    if ($name === '' || str_contains($name, '..') || str_starts_with($name, '/')) {
        continue;
    }
    if (in_array($name, $keep, true) && file_exists(__DIR__.'/'.$name)) {
        continue;
    }
    if (! $zip->extractTo(__DIR__, $name)) {
        out('Entpacken fehlgeschlagen', '<p>Die Datei <code>'.htmlspecialchars($name).'</code> konnte nicht geschrieben werden. Meist ist der Speicherplatz voll oder es fehlen Schreibrechte. Seite neu laden, um es noch einmal zu versuchen.</p>');
    }
    if (microtime(true) - $started > BATCH_SECONDS && $i + 1 < $total) {
        $next = $i + 1;
        $zip->close();
        $percent = (int) floor($next / max(1, $total) * 100);
        out('Shop wird entpackt … '.$percent.' %', '<p>Bitte dieses Fenster offen lassen. Das dauert ein bis zwei Minuten.</p><div class="bar"><i style="width:'.$percent.'%"></i></div>', '?i='.$next, '<!-- next:'.$next.' -->');
    }
}
$zip->close();

// Zwischenspeicher der alten Version leeren (nur bei Updates relevant).
foreach (array_merge(glob(__DIR__.'/bootstrap/cache/*.php') ?: [], glob(__DIR__.'/storage/framework/views/*.php') ?: []) as $stale) {
    @unlink($stale);
}
@unlink($zipPath);
@unlink(__FILE__);

$isUpdate = is_file(__DIR__.'/.env');
out(
    $isUpdate ? 'Update eingespielt' : 'Entpackt – jetzt einrichten',
    $isUpdate
        ? '<p>Die neue Version ist da. Deine Daten sind unverändert. Die Datenbank aktualisiert sich beim nächsten Aufruf von selbst.</p><a class="btn" href="./">Zum Shop</a>'
        : '<p>Alle Dateien liegen auf dem Server. Im nächsten Schritt gibst du Shop-Name, E-Mail und Passwort ein.</p><a class="btn" href="./install.php">Shop einrichten</a>',
    null,
    '<!-- done -->'
);
