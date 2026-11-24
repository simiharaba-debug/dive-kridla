<?php
// Menšie verzie fotografií pre rýchle načítanie (spúšťa OBRAZKY-VARIANTY.bat, alebo: php bin/obrazky-varianty.php)
// assets/img/ID.webp → assets/img/r/ID-{360,800,1200}.webp. Originály sa nemenia, hotové súbory sa preskočia.
// Prehliadač si cez srcset (includes/speed.php) vyberie najmenšiu dostatočnú verziu. Spustiť po pridaní novej fotky.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$dir = dirname(__DIR__) . '/assets/img';
if (!is_dir("$dir/r")) mkdir("$dir/r");
$made = 0; $bytes = 0;
foreach (glob("$dir/*.webp") as $f) {
    $id = basename($f, '.webp');
    if (str_ends_with($id, '-sm')) continue;
    [$w, $h] = getimagesize($f);
    $im = null;
    foreach ([360 => 70, 800 => 74, 1200 => 76] as $tw => $q) {
        $out = "$dir/r/$id-$tw.webp";
        if ($tw >= $w * 0.9 || (is_file($out) && filemtime($out) >= filemtime($f))) continue;
        $im ??= imagecreatefromwebp($f);
        $t = imagescale($im, $tw, (int)round($h * $tw / $w), IMG_BICUBIC);
        imagewebp($t, $out, $q);
        imagedestroy($t);
        clearstatcache();
        if (filesize($out) >= filesize($f)) { unlink($out); continue; }   // nič by neušetrila
        $made++; $bytes += filesize($out);
    }
    if ($im) imagedestroy($im);
}
printf("Vytvorených verzií: %d (%.1f MB)\n", $made, $bytes / 1048576);
