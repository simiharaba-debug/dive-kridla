<?php
// Statická verzia webu pre GitHub Pages (spúšťa OBNOVIT-ONLINE.bat, alebo: php bin/export-github.php).
// GitHub Pages nevie spustiť PHP. Skript preto spustí web lokálne, stiahne všetky VEREJNÉ stránky a uloží ich ako .html
// do koreňa repozitára; fotografie, štýly a skripty sa berú priamo z web/assets (nič sa neduplikuje).
// Prihlásenie, účty, administrácia a súkromná galéria v nej nie sú (potrebujú PHP hosting); formuláre otvoria e-mail.
// Po zmene obsahu: spustiť, commit, push.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__); $outDir = dirname($root);
chdir($root);
const MARK = '<!-- vygenerované: web/bin/export-github.php – neupravovať ručne -->';
$siteUrl = getenv('ONLINE_URL') ?: 'https://simiharaba-debug.github.io/dive-kridla/';
$basePath = parse_url($siteUrl, PHP_URL_PATH) ?: '/';

$port = 19000 + random_int(0, 999);
$cmd = [PHP_BINARY]; if ($ini = php_ini_loaded_file()) array_push($cmd, '-c', $ini);
array_push($cmd, '-S', "127.0.0.1:$port", 'router.php');
$null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
$proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']], $pipes, $root);
$base = "http://127.0.0.1:$port/";
$fetch = function (string $u) use ($base): array {
    $b = @file_get_contents($base . $u, false, stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true]]));
    $c = 0; foreach ($http_response_header ?? [] as $h) if (preg_match('#^HTTP/\S+ (\d+)#', $h, $m)) $c = (int)$m[1];
    return [$c, (string)$b];
};
for ($i = 0; $i < 50; $i++) { usleep(100000); if ($fetch('robots.txt')[0]) break; }

require $root . '/includes/functions.php';
require $root . '/includes/gallery.php';
gallery_seed();
$pages = [];
foreach ($SITE['pages'] as $slug => $p) if (empty($p['hidden'])) $pages[$slug] = $slug === 'index' ? '' : $slug;
foreach (gallery_categories('public') as $c) if (gallery_items($c)) $pages['fotogaleria-' . $c['slug']] = 'fotogaleria?kategoria=' . $c['slug'];
$pages['fotogaleria-videa'] = 'fotogaleria?kategoria=videa';
$pages['404'] = 'tato-stranka-neexistuje';

$uploads = [];
$convert = function (string $name, string $html) use (&$uploads, $siteUrl, $basePath): string {
    $path = function (string $u) use (&$uploads): string {
        if (str_starts_with($u, 'uploads/')) { $uploads[$u] = true; return 'web/assets/online/' . strtr(substr($u, 8), '/', '-'); }
        return str_starts_with($u, 'assets/') ? 'web/' . $u : $u;
    };
    $html = preg_replace_callback('#\b(src|href|data-src)="((?:assets|uploads)/[^"]*)"#', fn($m) => $m[1] . '="' . $path($m[2]) . '"', $html);
    $html = preg_replace_callback('#\b(srcset|data-srcset)="([^"]*)"#', fn($m) => $m[1] . '="' . implode(', ', array_map($path, preg_split('#,\s*#', $m[2]))) . '"', $html);
    $html = preg_replace('#(property="og:image" content=")(assets/)#', '$1' . $siteUrl . 'web/$2', $html);
    $html = preg_replace('#href="fotogaleria\?kategoria=([a-z0-9-]+)#', 'href="fotogaleria-$1', $html);
    $html = preg_replace('#<a class="nav__login[^>]*>.*?</a>#s', '', $html);
    $html = preg_replace('#<li><a href="(?:prihlasenie|registracia)">.*?</a></li>#s', '', $html);
    preg_match('#href="mailto:([^"?]+)"#', $html, $em);
    $html = preg_replace('#action="kontakt-odoslat" data-ajax#', 'action="priame-kontaktovanie" data-mailto="' . ($em[1] ?? '') . '"', $html, -1, $n);
    $html = preg_replace('#\s*<input type="hidden" name="(?:type|back)"[^>]*>#', '', $html);
    if ($n) $html = str_replace('</head>', '  <script defer src="web/assets/js/online-form.js"></script>' . "\n</head>", $html);
    if ($name === '404') $html = str_replace('<meta charset="utf-8">', '<meta charset="utf-8">' . "\n  <base href=\"$basePath\">", $html);
    return preg_replace('#^\s*<!doctype html>#i', "<!doctype html>\n" . MARK, $html);
};

$out = []; $bad = 0;
foreach ($pages as $name => $u) {
    $name = (string)$name;   // kľúč '404' je v poli PHP číslo
    [$code, $html] = $fetch($u);
    if ($code !== ($name === '404' ? 404 : 200) || !str_contains($html, '</html>')) { echo "CHYBA: $u vrátila $code\n"; $bad++; continue; }
    $out[$name] = $convert($name, $html);
}
proc_terminate($proc);
if ($bad) { echo "Nič sa nezmenilo – najprv treba opraviť chyby.\n"; exit(1); }   // radšej stará verzia ako rozbitá

foreach (glob("$outDir/*.html") as $f) if (str_contains((string)file_get_contents($f, false, null, 0, 300), MARK)) unlink($f);
foreach ($out as $name => $html) file_put_contents("$outDir/$name.html", $html);
file_put_contents("$outDir/.nojekyll", '');
foreach (array_keys($uploads) as $u) {
    if (!is_file("$root/$u")) continue;
    if (!is_dir("$root/assets/online")) mkdir("$root/assets/online");
    copy("$root/$u", "$root/assets/online/" . strtr(substr($u, 8), '/', '-'));
}
printf("Hotovo: %d stránok, %d nahratých fotografií → %s\n", count($out), count($uploads), $outDir);
