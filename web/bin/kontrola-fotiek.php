<?php
// Kontrola fotografií a galérie (spúšťa KONTROLA-FOTIEK.bat, alebo: php bin/kontrola-fotiek.php)
// 1. Na chvíľu spustí vlastný vývojový server a stiahne všetky verejné stránky aj všetky kategórie fotogalérie.
// 2. Zozbiera všetky fotografie z assets/img, ktoré sa na stránkach zobrazujú.
// 3. Overí, že každá z nich je v data/media.php a teda aj vo fotogalérii, že súbory existujú,
//    že žiadna fotografia nie je v galérii dvakrát a že odkazy medzi stránkami fungujú.
// Výsledok vypíše na obrazovku a uloží do storage/kontrola-fotiek.txt. Pri chybe vráti kód 1.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
chdir($root);
$M = require $root . '/data/media.php';
$SITE = require $root . '/data/site.php';

$port = 18000 + random_int(0, 999);
$ini = php_ini_loaded_file();
$cmd = [PHP_BINARY]; if ($ini) { $cmd[] = '-c'; $cmd[] = $ini; }
array_push($cmd, '-S', "127.0.0.1:$port", 'router.php');
$null = stripos(PHP_OS, 'WIN') === 0 ? 'NUL' : '/dev/null';
$proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']], $pipes, $root);
if (!$proc) { fwrite(STDERR, "Nepodarilo sa spustiť server PHP.\n"); exit(2); }
$base = "http://127.0.0.1:$port/";
$ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
$fetch = function (string $url) use ($ctx): array {
    $body = @file_get_contents($url, false, $ctx);
    $code = 0; foreach ($http_response_header ?? [] as $h) if (preg_match('#^HTTP/\S+ (\d+)#', $h, $m)) $code = (int)$m[1];
    return [$code, (string)$body];
};
for ($i = 0; $i < 50; $i++) { usleep(100000); [$c] = $fetch($base . 'robots.txt'); if ($c) break; }

$out = []; $errors = 0;
$say = function (string $s) use (&$out) { $out[] = $s; echo $s, PHP_EOL; };
$err = function (string $s) use (&$errors, $say) { $errors++; $say('  CHYBA: ' . $s); };

// Stránky webu (bez prihlasovania a administrácie) + všetky kategórie galérie
$pages = [];
foreach ($SITE['pages'] as $slug => $p) if (empty($p['hidden'])) $pages[] = $slug === 'index' ? '' : $slug;   // adresy bez prípony .php
// kategórie galérie spravuje admin (tabuľka gallery_categories) – kontrolujú sa neprázdne verejné kategórie
require $root . '/includes/functions.php';
require $root . '/includes/gallery.php';
gallery_seed();
$galleryPages = []; $catKind = [];
foreach (gallery_categories('public') as $gc) {
    if (!gallery_items($gc)) continue;
    $galleryPages[$gc['slug']] = 'fotogaleria?kategoria=' . $gc['slug']; $catKind[$gc['slug']] = $gc['kind'];
}
$galleryPages['videa'] = 'fotogaleria?kategoria=videa'; $catKind['videa'] = 'video';
$hidden = [];   // pôvodné fotografie, ktoré admin z galérie odstránil (jeho rozhodnutie – nie sú chybou)
foreach (gallery_overrides() as $o) if ($o['item_type'] === 'photo' && (int)$o['hidden'] === 1) $hidden[$o['item_key']] = true;

$used = [];      // id => [stránky]
$gallery = [];   // id => [kategórie]
$links = [];
$imgRe = '#assets/img/([a-z0-9-]+?)(?:-sm)?\.webp#';
$say('KONTROLA FOTOGRAFIÍ A GALÉRIE – ' . date('j. n. Y H:i'));
$say('');
$say('1. Stránky webu');
foreach (array_merge($pages, array_values($galleryPages)) as $pg) {
    [$code, $html] = $fetch($base . $pg);
    if ($code !== 200) { $err("$pg vrátila kód $code"); continue; }
    preg_match_all($imgRe, $html, $m);
    $ids = array_unique($m[1]);
    $cat = array_search($pg, $galleryPages, true);
    if ($cat !== false) {
        preg_match_all('#class="bo-gallery__item"[^>]*href="assets/img/([a-z0-9-]+)\.webp"#', $html, $gm);
        foreach ($gm[1] as $id) $gallery[$id][] = $cat;
        if ($catKind[$cat] === 'photo' && !preg_match('#class="bo-gallery__item"#', $html)) $err("kategória $cat je prázdna");
        $say(sprintf('  %-45s %3d fotografií v galérii', $pg, count($gm[1])));
    } else {
        foreach ($ids as $id) $used[$id][] = $pg;
        $say(sprintf('  %-45s %3d fotografií', $pg, count($ids)));
    }
    if (stripos($html, 'prezentáci') !== false && preg_match('#(z|podľa) prezentáci#iu', $html)) $err("$pg obsahuje zmienku o prezentácii");
    preg_match_all('#href="([a-z0-9-]+)(?:[?\#][^"]*)?"#', $html, $lm);
    foreach ($lm[1] as $l) $links[$l] = $pg;
    if (preg_match('#(?:href|action)="[a-z0-9-]+\.php#', $html)) $err("$pg obsahuje odkaz s príponou .php");
    if (preg_match('#\.(mp4|webm)\b#i', $html)) $err("$pg obsahuje vlastný videosúbor (na webe majú byť len videá z YouTube)");
    if (preg_match_all('#class="bo-video[ "]#', $html) !== preg_match_all('#class="bo-video[^"]*"[^>]*data-yt="[A-Za-z0-9_-]{11}"#', $html)) $err("$pg má video bez YouTube ID");
    preg_match_all('#(?:src|href|poster)="(assets/[^"?\#]+)#', $html, $am);
    foreach (array_unique($am[1]) as $a) if (!is_file($root . '/' . rawurldecode($a))) $err("$pg odkazuje na neexistujúci súbor $a");
}

$say('');
$say('2. Odkazy medzi stránkami');
foreach (array_keys($links) as $l) {
    [$code] = $fetch($base . $l);
    if (!in_array($code, [200, 302, 303], true)) $err("odkaz $l (zo stránky {$links[$l]}) vrátil kód $code");
}
$say('  skontrolovaných odkazov: ' . count($links));

$say('');
$say('3. Fotografie použité na webe a vo fotogalérii');
// fotografia vo fotogalérii sa ráta aj vtedy, ak je obalom kategórie alebo náhľadom inej kategórie
foreach ($used as $id => $where) {
    if (!isset($M['photos'][$id])) { $err("$id (na stránkach: " . implode(', ', array_unique($where)) . ") nie je v data/media.php"); continue; }
    if (empty($gallery[$id]) && empty($hidden[$id])) $err("$id (na stránkach: " . implode(', ', array_unique($where)) . ") chýba vo fotogalérii");
}
foreach ($gallery as $id => $cats) if (count($cats) > 1) $err("$id je vo fotogalérii viackrát: " . implode(', ', $cats));
foreach ($M['photos'] as $id => [$cat, $alt]) {
    if (!isset($M['categories'][$cat])) $err("$id má neznámu kategóriu $cat");
    if (!is_file("$root/assets/img/$id.webp")) $err("chýba súbor assets/img/$id.webp");
    if (!is_file("$root/assets/img/$id-sm.webp")) $err("chýba náhľad assets/img/$id-sm.webp");
    if (trim($alt) === '') $err("$id nemá alternatívny text");
    if (empty($gallery[$id]) && empty($hidden[$id])) $err("$id je v evidencii, ale galéria ho nezobrazuje");
}
$G = require $root . '/data/galerie.php';
foreach ($G as $pg => $ids) foreach ($ids as $id) if (!isset($M['photos'][$id])) $err("data/galerie.php ($pg): $id nie je v data/media.php");
$VD = require $root . '/data/videos.php';
foreach ($VD['videos'] as $k => $v) if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $v['yt'] ?? '')) $err("video $k: neplatné YouTube ID");

$say(sprintf('  fotografií použitých na stránkach: %d', count($used)));
$say(sprintf('  fotografií vo fotogalérii:         %d (v evidencii %d, odstránených adminom %d, kategórií %d)', count($gallery), count($M['photos']), count($hidden), count($galleryPages)));
$say(sprintf('  použité na webe, ale chýbajú v galérii: %d', count(array_filter(array_keys($used), fn($id) => empty($gallery[$id])))));

$say('');
$say($errors ? "VÝSLEDOK: nájdené chyby: $errors" : 'VÝSLEDOK: v poriadku – každá fotografia z webu je aj vo fotogalérii.');
@file_put_contents($root . '/storage/kontrola-fotiek.txt', implode(PHP_EOL, $out) . PHP_EOL);
proc_terminate($proc);
exit($errors ? 1 : 0);
