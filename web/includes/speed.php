<?php
// Rýchlosť načítania (24. 11. 2026). Výstup každej verejnej stránky prejde cez speed_output():
//   • <img loading="lazy">   → fotografia sa stiahne až tesne pred zobrazením (assets/js/speed.js), namiesto nej je
//                              priehľadný obrázok rovnakého pomeru strán (stránka neposkakuje). Bez JS platí <noscript>.
//   • fetchpriority="high"   → hlavná fotografia dostane srcset (telefón nesťahuje verziu pre veľký monitor)
//   • HTML sa odošle komprimované (gzip)
// Menšie verzie fotografií vytvára bin/obrazky-varianty.php do assets/img/r/.

/** Dostupné verzie fotografie [šírka => adresa] (vzostupne) a pomer strán; null = cudzí alebo súkromný obrázok. */
function speed_variants(string $src): ?array {
    static $cache = [];
    if (array_key_exists($src, $cache)) return $cache[$src];
    $root = dirname(__DIR__) . '/';
    if (!preg_match('#^(assets/img|uploads)/[A-Za-z0-9_/.-]+$#', $src) || str_contains($src, '..') || !is_file($root . $src)) return $cache[$src] = null;
    $s = @getimagesize($root . $src);
    if (!$s || !$s[0] || !$s[1]) return $cache[$src] = null;
    $set = [$s[0] => $src];
    if (preg_match('#^assets/img/([a-z0-9-]+?)(-sm)?\.webp$#', $src, $m)) {
        $ar = $s[0] / $s[1];
        foreach ([360, 800, 1200] as $w) {
            $f = 'assets/img/r/' . $m[1] . '-' . $w . '.webp';
            if (!is_file($root . $f)) continue;
            $v = @getimagesize($root . $f);
            if ($v && abs($v[0] / $v[1] - $ar) < 0.03) $set[$w] = $f;   // iba verzia s rovnakým výrezom
        }
        if (!empty($m[2])) $set = array_filter($set, fn($w) => $w <= $s[0], ARRAY_FILTER_USE_KEY);   // náhľad neťahá nič väčšie
    }
    ksort($set);
    return $cache[$src] = ['w' => $s[0], 'h' => $s[1], 'set' => $set];
}

function speed_srcset(array $set): string {
    $o = [];
    foreach ($set as $w => $u) $o[] = $u . ' ' . $w . 'w';
    return implode(', ', $o);
}

function speed_img(string $tag): string {
    if (!preg_match('#\ssrc="([^"]+)"#', $tag, $m)) return $tag;
    $src = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    $v = speed_variants($src);
    if (str_contains($tag, 'fetchpriority="high"')) {
        if (!$v || count($v['set']) < 2) return $tag;
        $sizes = ($GLOBALS['CURRENT'] ?? '') === 'index' ? '(min-width: 1000px) 65vw, 100vw' : '(min-width: 900px) 55vw, 100vw';
        return str_replace($m[0], $m[0] . ' srcset="' . e(speed_srcset($v['set'])) . '" sizes="' . $sizes . '"', $tag);
    }
    if (!str_contains($tag, 'loading="lazy"')) return $tag;
    $w = 4; $h = 3; $attr = ' data-src="' . $m[1] . '"';
    if ($v) { $w = $v['w']; $h = $v['h']; if (count($v['set']) > 1) $attr .= ' data-srcset="' . e(speed_srcset($v['set'])) . '"'; }
    $ph = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='$w' height='$h'/%3E";
    $lazy = str_replace([$m[0], ' loading="lazy"'], [' src="' . $ph . '"' . $attr . ' data-ar="' . round($w / $h, 4) . '"', ''], $tag);
    return $lazy . '<noscript>' . $tag . '</noscript>';
}

/** Spätné volanie ob_start() v functions.php. */
function speed_output(string $html, int $phase): string {
    if ($phase & PHP_OUTPUT_HANDLER_CLEAN || !($phase & PHP_OUTPUT_HANDLER_FINAL) || $html === '') return $html;
    if (empty($GLOBALS['ADMIN_LAYOUT']) && str_contains($html, '</html>')) {
        $html = preg_replace_callback('#<img\b[^>]*>#', fn($m) => speed_img($m[0]), $html) ?? $html;
    }
    if (strlen($html) > 1024 && PHP_SAPI !== 'cli' && !headers_sent() && function_exists('gzencode') && !ini_get('zlib.output_compression')
        && stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
        foreach (headers_list() as $h) if (stripos($h, 'Content-Encoding:') === 0 || stripos($h, 'Content-Length:') === 0) return $html;
        header('Content-Encoding: gzip'); header('Vary: Accept-Encoding');
        return gzencode($html, 6);
    }
    return $html;
}
