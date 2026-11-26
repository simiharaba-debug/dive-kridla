<?php
// Denná záloha na hostingu Webglobe (spúšťa Cron v administrácii Webglobe, PHP 8.5 CLI).
// Ukladá do ~/backup (mimo public_html, z webu nedostupné):
//   db-RRRR-MM-DD.sqlite        – konzistentná kópia databázy (SQLite backup API, funguje aj pri zápise)
//   subory-RRRR-MM-DD.tar.gz    – public_html/uploads + private/private-media
// Ponecháva posledných KEEP_DAYS dní. Na server sa nahráva do ~/private/zaloha.php (nie do public_html).
// Obnova: OBNOVA-ZO-ZALOHY v CLAUDE-README (časť Hosting) – nikdy bez súhlasu používateľa.

// Spustenie: príkazový riadok (php8.5 ~/private/zaloha.php) alebo cron-zaloha.php (Cron Webglobe volá URL s tajným kľúčom).
if (PHP_SAPI !== 'cli' && !defined('ZALOHA_WEB')) { http_response_code(404); exit; }
if (PHP_SAPI !== 'cli') { header('Content-Type: text/plain; charset=utf-8'); @set_time_limit(300); }
if (!defined('STDERR')) define('STDERR', fopen('php://output', 'w'));

date_default_timezone_set('Europe/Bratislava');
const KEEP_DAYS = 14;
$home    = getenv('HOME') ?: dirname(__DIR__);
$db      = $home . '/private/app.sqlite';
$out     = $home . '/backup';
$day     = date('Y-m-d');
$log     = [];

if (!is_dir($out) && !mkdir($out, 0700, true)) { fwrite(STDERR, "Nedá sa vytvoriť $out\n"); exit(1); }
@chmod($out, 0700);

// 1) databáza
$dbFile = "$out/db-$day.sqlite";
@unlink($dbFile);
$src = new SQLite3($db, SQLITE3_OPEN_READONLY);
$dst = new SQLite3($dbFile);
if (!$src->backup($dst)) { fwrite(STDERR, "Záloha databázy zlyhala\n"); exit(1); }
$ok = $dst->querySingle('PRAGMA integrity_check');
$dst->close(); $src->close();
@chmod($dbFile, 0600);
$log[] = "db: " . filesize($dbFile) . " B, integrita: $ok";

// 2) súbory (nahraté fotky + súkromná galéria)
// PharData namiesto príkazu tar – PHP-FPM na Webglobe nesmie spúšťať programy (exec → 126).
$tar = "$out/subory-$day.tar";
@unlink($tar); @unlink("$tar.gz");
$rc = 0; $count = 0;
try {
    $p = new PharData($tar);
    foreach (['public_html/uploads', 'private/private-media'] as $rel) {
        $dir = "$home/$rel";
        if (!is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $p->addFile($f->getPathname(), $rel . '/' . substr($f->getPathname(), strlen($dir) + 1));
            $count++;
        }
    }
    $p->compress(Phar::GZ);
    unset($p);
    @unlink($tar);
    $tar .= '.gz';
} catch (Throwable $e) { $rc = 1; $log[] = 'chyba balenia: ' . $e->getMessage(); }
@chmod($tar, 0600);
$log[] = "subory: " . (is_file($tar) ? filesize($tar) . " B, $count súborov" : 'CHYBA') . " (rc=$rc)";

// 3) rotácia
$limit = time() - KEEP_DAYS * 86400;
foreach (glob("$out/{db-*.sqlite,subory-*.tar.gz}", GLOB_BRACE) as $f) {
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $f, $m) && strtotime($m[1]) < $limit) { unlink($f); $log[] = 'zmazané: ' . basename($f); }
}

$line = date('c') . ' ' . implode('; ', $log) . "\n";
file_put_contents("$out/zaloha.log", $line, FILE_APPEND);
echo $line;
exit($ok === 'ok' && $rc === 0 ? 0 : 1);
