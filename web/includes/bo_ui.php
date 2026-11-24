<?php
// Pomocné funkcie pre UI moduly (assets/css/bo-ui.css, assets/js/bo-ui.js).
// Stránka ich načíta: require __DIR__ . '/includes/bo_ui.php';  (po includes/functions.php)
require_once __DIR__ . '/gallery.php';   // kategórie galérie + videá z YouTube

/**
 * Tematická galéria: zobrazí prvých N fotografií, na poslednej „+X ďalších fotografií“, klik otvorí lightbox.
 * $items = [['id' => 'kanada-01', 'caption' => '...'], ...]  (súbory assets/img/ID.webp a ID-sm.webp)
 * Fotografie, ktorých súbor neexistuje, sa preskočia.
 */
function bo_gallery(array $items, int $visible = 4, int $visibleMobile = 3, string $label = 'Galéria', string $class = ''): void {
    $root = __DIR__ . '/../';
    $items = array_map(fn($it) => is_string($it) ? ['id' => $it] : $it, $items);
    $items = array_values(array_filter($items, fn($it) => gallery_photo_visible($it['id']) && is_file($root . img_thumb($it['id']))));   // bez fotiek odstránených z galérie
    if (!$items) return;
    echo '<div class="bo-gallery ' . e($class) . '" data-bo-gallery data-visible="' . $visible . '" data-visible-mobile="' . $visibleMobile . '" role="list" aria-label="' . e($label) . '">';
    foreach ($items as $i => $it) {
        $cap = $it['caption'] ?? media_alt($it['id']);
        $hidden = $i >= $visible ? ' hidden' : '';   // bez JavaScriptu sa ukáže len prvých N
        echo '<a class="bo-gallery__item" role="listitem" href="' . e(img_full($it['id'])) . '" data-caption="' . e($cap) . '"' . $hidden . '>'
           . '<img src="' . e(img_thumb($it['id'])) . '" alt="' . e($cap) . '" loading="lazy" decoding="async"></a>';
    }
    echo '</div>';
}

/**
 * Video z YouTube – náhľad s tlačidlom, prehrá sa až po kliknutí v modálnom okne (assets/js/bo-ui.js).
 * $v = kľúč z data/videos.php ('odchov-znaska'), ['video' => kľúč] alebo už normalizovaná položka z videos_all().
 * Pomer strán (--ar) zodpovedá skutočnému videu, takže videá na výšku a na šírku sa v rade pekne zarovnajú.
 */
function bo_video($v): void {
    if (is_string($v)) $v = video_get($v);
    elseif (is_array($v) && !empty($v['video'])) $v = video_get($v['video']);
    elseif (is_array($v) && !empty($v['yt']) && !isset($v['key'])) $v = video_norm('yt-' . $v['yt'], $v);
    if (!$v) return;
    $ar = round($v['w'] / $v['h'], 4);
    $dur = video_dur_label($v['duration']);
    $label = 'Prehrať video: ' . $v['title'] . ($dur ? ' (' . $dur . ')' : '');
    echo '<button type="button" class="bo-video' . ($v['portrait'] ? ' bo-video--short' : '') . '" style="--ar:' . $ar . '"'
       . ' data-yt="' . e($v['yt']) . '" data-title="' . e($v['title']) . '" data-desc="' . e($v['desc']) . '" data-w="' . (int)$v['w'] . '" data-h="' . (int)$v['h'] . '"'
       . ' aria-haspopup="dialog" aria-label="' . e($label) . '" title="' . e($v['desc'] ?: $v['title']) . '">'
       . '<img src="https://i.ytimg.com/vi/' . e($v['yt']) . '/hqdefault.jpg" alt="" loading="lazy" decoding="async" width="480" height="360">'
       . '<span class="bo-video__play" aria-hidden="true"></span>'
       . ($dur ? '<span class="bo-video__dur" aria-hidden="true">' . e($dur) . '</span>' : '')
       . ($v['portrait'] ? '<span class="bo-video__badge" aria-hidden="true">na výšku</span>' : '')
       . '<span class="bo-video__title">' . e($v['title']) . '</span></button>';
}

/**
 * Skupina videí. Videá na šírku aj na výšku majú v jednom rade rovnakú výšku (šírka podľa pomeru strán),
 * šípky v modálnom okne listujú v rámci skupiny.  $layout: 'row' (vedľa seba, napr. 2 videá) | 'grid' (zarovnané riadky, galéria)
 */
function bo_videos(array $list, string $layout = 'grid', string $label = 'Videá', string $class = ''): void {
    $items = [];
    foreach ($list as $v) {
        if (is_string($v)) $v = video_get($v);
        elseif (is_array($v) && !empty($v['video'])) $v = video_get($v['video']);
        if ($v) $items[] = $v;
    }
    if (!$items) return;
    echo '<div class="bo-videos bo-videos--' . e($layout) . ($class ? ' ' . e($class) : '') . '" data-bo-videos role="group" aria-label="' . e($label) . '">';
    foreach ($items as $v) bo_video($v);
    echo '</div>';
    videos_jsonld($items);
}

/**
 * Galéria z položiek kategórie (gallery_items): pôvodné aj nahraté fotografie, rovnaké správanie ako bo_gallery
 * (lightbox, šípky, swipe). Pre fotogalériu sa zobrazia všetky.
 */
function gallery_grid(array $items, string $label): void {
    $items = array_values(array_filter($items, fn($it) => $it['type'] === 'photo'));
    if (!$items) return;
    $n = count($items);
    echo '<div class="bo-gallery bo-gallery--all" data-bo-gallery data-visible="' . $n . '" data-visible-mobile="' . $n . '" role="list" aria-label="' . e($label) . '">';
    foreach ($items as $it) {
        echo '<a class="bo-gallery__item" href="' . e($it['full']) . '" role="listitem" data-caption="' . e($it['caption']) . '">'
           . '<img src="' . e($it['thumb']) . '" alt="' . e($it['caption'] ?: 'Fotografia') . '" loading="lazy" decoding="async"></a>';
    }
    echo '</div>';
}

/** Tematická galéria podstránky z data/galerie.php s nadpisom (napr. „Ďalšie fotografie z odchytu“). Popisy sa berú z data/media.php. */
function gallery_block(string $page, string $title, int $visible = 4, int $visibleMobile = 3): void {
    static $G = null;
    $G ??= require __DIR__ . '/../data/galerie.php';
    $items = $G[$page] ?? [];
    if (!$items) return;
    echo '<div class="bo-gallery-block" data-reveal><div class="bo-gallery-block__head"><h3>' . e($title) . '</h3><span>' . count($items) . ' ' . e(sk_photos(count($items))) . '</span></div>';
    bo_gallery($items, $visible, $visibleMobile, $title);
    echo '</div>';
}

/** 1 fotografia, 2–4 fotografie, 5+ fotografií */
function sk_photos(int $n): string { return $n === 1 ? 'fotografia' : ($n >= 2 && $n <= 4 ? 'fotografie' : 'fotografií'); }

/** Obrázok v obsahu (popis z evidencie médií, otvorí sa v lightboxe). */
function media_figure(string $id, string $class = '', ?string $caption = null): void {
    $cap = $caption ?? media_alt($id);
    echo '<figure class="bo-figure ' . e($class) . '"><a href="' . e(img_full($id)) . '" data-lightbox="fig-' . e($id) . '" data-caption="' . e($cap) . '">'
       . '<img src="' . e(img_thumb($id)) . '" alt="' . e($cap) . '" loading="lazy" decoding="async"></a>'
       . '<figcaption class="bo-figure__cap">' . e($cap) . '</figcaption></figure>';
}
