<?php
// Denná záloha – volá ju Cron v administrácii Webglobe (Web → Cron) adresou https://divekridla.sk/cron-zaloha
// Povolené sú iba servery Cronu Webglobe (zoznam v administrácii Webglobe → Cron); ostatným sa adresa tvári ako neexistujúca.
// Najviac jedna záloha za deň. Samotná záloha: ~/private/zaloha.php (zdroj bin/server-zaloha.php), výsledok v ~/backup mimo webu.
require __DIR__ . '/includes/config.php';

const CRON_IPS = ['62.109.128.59', '62.109.150.10', '62.109.128.13'];
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');
// Webglobe pri pridaní úlohy overuje existenciu skriptu cez HEAD (zo servera administrácie) – HEAD nič nespúšťa
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') exit;
$script = dirname(ROOT) . '/private/zaloha.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', CRON_IPS, true) || !is_file($script)) {
    http_response_code(404);
    exit;
}

date_default_timezone_set('Europe/Bratislava');
if (is_file(dirname(ROOT) . '/backup/db-' . date('Y-m-d') . '.sqlite')) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Dnešná záloha už existuje.\n";
    exit;
}
define('ZALOHA_WEB', true);
require $script;
