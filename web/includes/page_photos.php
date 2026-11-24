<?php
// Hlavné fotografie (hero) a náhľady (karty na úvodnej stránke) podstránok.
//   • pôvodné nastavenie: data/page_photos.php
//   • voľba administrátora: tabuľka page_photos (migrácia 005), stránka admin-fotky
// Odkaz na fotografiu: „s:ID“ = pôvodná fotografia z data/media.php, „m:ID“ = fotografia nahratá do verejnej galérie.
// Každá použitá fotografia je teda vždy aj vo fotogalérii.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/gallery.php';   // odstránená fotografia z galérie sa ako hlavná nepoužije

const PAGE_PHOTO_SLOTS = ['hero' => 'Hlavná fotografia', 'card' => 'Náhľad na úvodnej stránke'];

function page_photo_defaults(): array {
    static $D = null;
    return $D ??= require __DIR__ . '/../data/page_photos.php';
}

/** Voľby administrátora [slug][slot] => ref. Bez databázy sa použije pôvodné nastavenie. */
function page_photo_overrides(bool $fresh = false): array {
    static $O = null;
    if ($O !== null && !$fresh) return $O;
    $O = [];
    try {
        foreach (db()->query('SELECT slug, slot, ref FROM page_photos')->fetchAll() as $r) $O[$r['slug']][$r['slot']] = $r['ref'];
    } catch (Throwable $e) {
        error_log('[fotky-stranok] ' . $e->getMessage());
    }
    return $O;
}

/** Odkaz → údaje fotografie (full, thumb, alt) alebo null, ak fotografia už neexistuje / bola odstránená z galérie. */
function page_photo_from_ref(string $ref): ?array {
    if (!preg_match('/^([sm]):([A-Za-z0-9_-]{1,80})$/', $ref, $m)) return null;
    if ($m[1] === 's') {
        $id = $m[2];
        $p = media_photo($id);
        if (!$p || !is_file(ROOT . '/assets/img/' . $id . '.webp')) return null;
        if (function_exists('gallery_photo_visible') && !gallery_photo_visible($id)) return null;
        return ['ref' => $ref, 'full' => img_full($id), 'thumb' => img_thumb($id), 'alt' => $p[1]];
    }
    try { $row = media_get((int)$m[2], 'public'); } catch (Throwable $e) { return null; }
    if (!$row || $row['kind'] !== 'image') return null;
    $alt = trim((string)$row['title']) !== '' ? $row['title'] : 'Fotografia';
    return ['ref' => $ref, 'full' => $row['path'], 'thumb' => $row['thumb_path'] ?: $row['path'], 'alt' => $alt];
}

/** Pôvodný odkaz pre stránku a pozíciu (karta bez vlastnej fotografie = hlavná fotografia). */
function page_photo_default_ref(string $slug, string $slot): ?string {
    $d = page_photo_defaults()[$slug] ?? null;
    if (!$d) return null;
    if ($slot === 'card' && !array_key_exists('card', $d)) return null;   // stránka nemá kartu
    $id = $slot === 'card' ? ($d['card'] ?? null) : ($d['hero'] ?? null);
    return $id ? 's:' . $id : null;
}

/**
 * Fotografia stránky: voľba administrátora → pôvodné nastavenie.
 * Karta bez vlastnej voľby preberá hlavnú fotografiu stránky (aj zmenenú administrátorom).
 * Vráti ['ref','full','thumb','alt','custom' => bool] alebo null.
 */
function page_photo(string $slug, string $slot = 'hero'): ?array {
    $p = page_photo_base($slug, $slot);
    if ($p) $p['pos'] = page_photo_pos_for($slug, $slot, $p['ref']);
    return $p;
}
function page_photo_base(string $slug, string $slot): ?array {
    $o = page_photo_overrides()[$slug][$slot] ?? null;
    if ($o && ($p = page_photo_from_ref($o))) return $p + ['custom' => true];
    $def = page_photo_default_ref($slug, $slot);
    if ($def && ($p = page_photo_from_ref($def))) return $p + ['custom' => false];
    if ($slot === 'card') { $h = page_photo_base($slug, 'hero'); return $h ? ['custom' => false] + $h : null; }
    return null;
}

/* ---------- výrez fotografie (tabuľka page_photo_pos, migrácia 007) ---------- */

/** Všetky uložené výrezy [slug][slot] => ['ref','x','y','z']. */
function page_photo_positions(bool $fresh = false): array {
    static $P = null;
    if ($P !== null && !$fresh) return $P;
    $P = [];
    try {
        foreach (db()->query('SELECT slug, slot, ref, pos_x, pos_y, zoom FROM page_photo_pos')->fetchAll() as $r)
            $P[$r['slug']][$r['slot']] = ['ref' => $r['ref'], 'x' => (float)$r['pos_x'], 'y' => (float)$r['pos_y'], 'z' => (float)$r['zoom']];
    } catch (Throwable $e) { error_log('[vyrez] ' . $e->getMessage()); }
    return $P;
}
/** Výrez pre fotografiu na danom mieste – iba ak patrí k tej istej fotografii, inak stred bez priblíženia. */
function page_photo_pos_for(string $slug, string $slot, string $ref): array {
    $p = page_photo_positions()[$slug][$slot] ?? null;
    return ($p && $p['ref'] === $ref) ? ['x' => $p['x'], 'y' => $p['y'], 'z' => $p['z'], 'custom' => true] : ['x' => 50.0, 'y' => 50.0, 'z' => 1.0, 'custom' => false];
}
/** Atribúty pre <img>: data-fp + CSS premenné (object-position a priblíženie, pozri bo-ui.css časť 22). Prázdne = predvolený stred. */
function page_photo_attr(?array $ph): string {
    $p = $ph['pos'] ?? null;
    if (!$p || empty($p['custom'])) return '';
    return ' data-fp style="--fp-x:' . round($p['x'], 2) . '%;--fp-y:' . round($p['y'], 2) . '%;--fp-z:' . round($p['z'], 3) . '"';
}
/** Uloží výrez ($x, $y v %, $z 1–3); null = zmaže (stred). */
function page_photo_pos_set(string $slug, string $slot, ?array $pos, int $uid): void {
    if (!isset(page_photo_defaults()[$slug]) || !isset(PAGE_PHOTO_SLOTS[$slot])) throw new InvalidArgumentException('Neznáma stránka.');
    $ph = page_photo_base($slug, $slot);
    if (!$ph) throw new InvalidArgumentException('Stránka nemá fotografiu.');
    $pdo = db();
    $pdo->prepare('DELETE FROM page_photo_pos WHERE slug = ? AND slot = ?')->execute([$slug, $slot]);
    if ($pos !== null) {
        $x = max(0, min(100, (float)$pos['x'])); $y = max(0, min(100, (float)$pos['y'])); $z = max(1, min(3, (float)$pos['z']));
        if (abs($x - 50) > .2 || abs($y - 50) > .2 || $z > 1.001)
            $pdo->prepare('INSERT INTO page_photo_pos (slug, slot, ref, pos_x, pos_y, zoom, updated_by, updated_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$slug, $slot, $ph['ref'], $x, $y, $z, $uid, gmdate('c')]);
    }
    page_photo_positions(true);
}

/** Uloží voľbu administrátora ($ref = null → obnoví pôvodnú fotografiu). */
function page_photo_set(string $slug, string $slot, ?string $ref, int $uid): void {
    if (!isset(page_photo_defaults()[$slug]) || !isset(PAGE_PHOTO_SLOTS[$slot])) throw new InvalidArgumentException('Neznáma stránka.');
    if ($slot === 'card' && !array_key_exists('card', page_photo_defaults()[$slug])) throw new InvalidArgumentException('Táto stránka nemá náhľad na úvodnej stránke.');
    $pdo = db();
    $pdo->prepare('DELETE FROM page_photos WHERE slug = ? AND slot = ?')->execute([$slug, $slot]);
    if ($ref !== null) {
        if (!page_photo_from_ref($ref)) throw new InvalidArgumentException('Vybraná fotografia neexistuje.');
        $pdo->prepare('INSERT INTO page_photos (slug, slot, ref, updated_by, updated_at) VALUES (?,?,?,?,?)')->execute([$slug, $slot, $ref, $uid, gmdate('c')]);
    }
    try { $pdo->prepare('DELETE FROM page_photo_pos WHERE slug = ? AND slot = ?')->execute([$slug, $slot]); } catch (Throwable $e) { /* pred migráciou 007 */ }
    page_photo_overrides(true);
    page_photo_positions(true);
}
