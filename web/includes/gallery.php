<?php
// Galéria: kategórie fotografií a videí (verejná aj súkromná administrátorská galéria) a všetky úpravy administrátora.
//
// Zdroje položiek:
//   • pôvodné fotografie  – data/media.php (súbory assets/img/ID.webp), odkaz „s:ID“
//   • pôvodné videá       – data/videos.php (YouTube), odkaz „v:KĽÚČ“
//   • nahraté fotografie  – tabuľka media (verejné, „m:ID“) / private_media (súkromné, „p:ID“)
//   • pridané videá       – tabuľka videos (verejné aj súkromné, „v:db-ID“)
// Kategórie sú v tabuľke gallery_categories. Pôvodné kategórie z dátových súborov sa doplnia automaticky (static_key)
// a admin ich môže premenovať, zmeniť náhľad a poradie alebo odstrániť (deleted = 1 – dajú sa obnoviť).
// Presun / odstránenie pôvodnej položky sa zapíše do gallery_overrides (súbory sa nemažú).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/videos.php';

class GalleryException extends RuntimeException {}

/* ======================= Pomocné ======================= */

function gallery_scopes(): array { return ['public', 'private']; }

/** Slovenský text → adresa (napr. „Liahnutie mláďat“ → „liahnutie-mladat“). */
function gallery_slugify(string $s): string {
    $map = ['á'=>'a','ä'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ĺ'=>'l','ľ'=>'l','ň'=>'n','ó'=>'o','ô'=>'o','ö'=>'o','ŕ'=>'r','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ü'=>'u','ý'=>'y','ž'=>'z'];
    $s = strtr(mb_strtolower(trim($s)), $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return substr($s, 0, 60) ?: 'kategoria';
}

function gallery_now(): string { return gmdate('c'); }

/* ======================= Kategórie ======================= */

/** Doplní pôvodné kategórie (data/media.php, data/videos.php) a zaradí nezaradené nahraté položky. Raz za požiadavku. */
function gallery_seed(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = db();
    $have = $pdo->query("SELECT static_key FROM gallery_categories WHERE static_key IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    $have = array_flip($have);
    $now = gallery_now();
    $ins = $pdo->prepare('INSERT INTO gallery_categories (scope, kind, slug, title, description, cover, sort, static_key, deleted, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,0,?,?)');
    $sort = 0;
    foreach (media_registry()['categories'] as $slug => $c) {
        $sort += 10;
        if (isset($have['photo:' . $slug])) continue;
        $ins->execute(['public', 'photo', gallery_unique_slug('public', $slug), $c['title'], $c['text'] ?? '', 's:' . ($c['cover'] ?? ''), $sort, 'photo:' . $slug, $now, $now]);
    }
    foreach (videos_data()['topics'] ?? [] as $t => $label) {
        $sort += 10;
        if (isset($have['video:' . $t])) continue;
        $ins->execute(['public', 'video', gallery_unique_slug('public', 'videa-' . $t), $label, '', '', 1000 + $sort, 'video:' . $t, $now, $now]);
    }
    // nahraté položky bez kategórie (z čias pred kategóriami) → „Nové fotografie“ / „Nezaradené“
    foreach ([['public', 'media', 'Nové fotografie', 'nove'], ['private', 'private_media', 'Nezaradené fotografie', 'nezaradene']] as [$scope, $table, $title, $slug]) {
        $n = (int)$pdo->query("SELECT COUNT(*) FROM $table WHERE kind = 'image' AND category_id IS NULL")->fetchColumn();
        if (!$n) continue;
        $cid = gallery_find_or_create($scope, 'photo', $title, $slug);
        $pdo->prepare("UPDATE $table SET category_id = ? WHERE kind = 'image' AND category_id IS NULL")->execute([$cid]);
    }
    foreach (gallery_scopes() as $scope) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM videos WHERE scope = ? AND category_id IS NULL'); $st->execute([$scope]);
        if (!(int)$st->fetchColumn()) continue;
        $cid = gallery_find_or_create($scope, 'video', 'Ďalšie videá', 'dalsie-videa');
        $pdo->prepare('UPDATE videos SET category_id = ? WHERE scope = ? AND category_id IS NULL')->execute([$cid, $scope]);
    }
    gallery_cache_reset();
}

/** Existujúca (neodstránená) kategória s danou adresou, inak nová. */
function gallery_find_or_create(string $scope, string $kind, string $title, string $slug): int {
    $st = db()->prepare('SELECT id FROM gallery_categories WHERE scope = ? AND kind = ? AND slug = ? AND deleted = 0'); $st->execute([$scope, $kind, $slug]);
    return (int)($st->fetchColumn() ?: gallery_category_create($scope, $kind, $title, '', $slug));
}

function gallery_unique_slug(string $scope, string $base, ?int $exceptId = null): string {
    $base = gallery_slugify($base);
    if (in_array($base, ['videa', 'nove-kategorie'], true)) $base .= '-1';   // vyhradené adresy galérie
    $slug = $base; $i = 2;
    $st = db()->prepare('SELECT id FROM gallery_categories WHERE scope = ? AND slug = ?');
    while (true) {
        $st->execute([$scope, $slug]);
        $id = $st->fetchColumn();
        if (!$id || ($exceptId && (int)$id === $exceptId)) return $slug;
        $slug = $base . '-' . $i++;
    }
}

/** Všetky kategórie (aj odstránené), indexované podľa id. */
function gallery_categories_all(): array {
    $c = &$GLOBALS['GALLERY_CACHE']['cats'];
    if ($c !== null) return $c;
    $c = [];
    try {
        foreach (db()->query('SELECT * FROM gallery_categories ORDER BY sort, id') as $r) { $r['id'] = (int)$r['id']; $c[$r['id']] = $r; }
    } catch (Throwable $e) { error_log('[galeria] ' . $e->getMessage()); }
    return $c;
}
function gallery_cache_reset(): void { $GLOBALS['GALLERY_CACHE'] = []; }

/** Kategórie galérie ($kind = 'photo' | 'video' | null), bez odstránených. */
function gallery_categories(string $scope, ?string $kind = null): array {
    return array_values(array_filter(gallery_categories_all(), fn($c) => $c['scope'] === $scope && !$c['deleted'] && ($kind === null || $c['kind'] === $kind)));
}
function gallery_category(int $id, ?string $scope = null): ?array {
    $c = gallery_categories_all()[$id] ?? null;
    return ($c && !$c['deleted'] && ($scope === null || $c['scope'] === $scope)) ? $c : null;
}
function gallery_category_by_slug(string $scope, string $slug): ?array {
    foreach (gallery_categories($scope) as $c) if ($c['slug'] === $slug) return $c;
    return null;
}

function gallery_category_create(string $scope, string $kind, string $title, string $desc = '', ?string $slugBase = null): int {
    $title = trim(mb_substr(preg_replace('/\s+/u', ' ', $title), 0, 80));
    if ($title === '') throw new GalleryException('Zadajte názov novej kategórie.');
    if (!in_array($kind, ['photo', 'video'], true) || !in_array($scope, gallery_scopes(), true)) throw new GalleryException('Neplatná kategória.');
    $st = db()->prepare('SELECT COALESCE(MAX(sort), 0) FROM gallery_categories WHERE scope = ? AND kind = ?'); $st->execute([$scope, $kind]);
    $sort = (int)$st->fetchColumn() + 10;
    $now = gallery_now();
    db()->prepare('INSERT INTO gallery_categories (scope, kind, slug, title, description, cover, sort, static_key, deleted, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NULL,0,?,?)')
        ->execute([$scope, $kind, gallery_unique_slug($scope, $slugBase ?? $title), $title, trim(mb_substr($desc, 0, 300)), '', $sort, $now, $now]);
    $id = (int)db()->lastInsertId();
    gallery_cache_reset();
    return $id;
}

function gallery_category_update(int $id, string $title, string $desc): void {
    $title = trim(mb_substr(preg_replace('/\s+/u', ' ', $title), 0, 80));
    if ($title === '') throw new GalleryException('Názov kategórie nesmie byť prázdny.');
    db()->prepare('UPDATE gallery_categories SET title = ?, description = ?, updated_at = ? WHERE id = ?')->execute([$title, trim(mb_substr($desc, 0, 300)), gallery_now(), $id]);
    gallery_cache_reset();
}

/** Posun v poradí: $dir = -1 (vyššie) / +1 (nižšie) medzi kategóriami rovnakej galérie a typu. */
function gallery_category_move(int $id, int $dir): void {
    $c = gallery_category($id);
    if (!$c) return;
    $list = gallery_categories($c['scope'], $c['kind']);
    $ids = array_column($list, 'id');
    $i = array_search($id, $ids, true);
    $j = $i + ($dir < 0 ? -1 : 1);
    if ($i === false || !isset($ids[$j])) return;
    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    $up = db()->prepare('UPDATE gallery_categories SET sort = ?, updated_at = ? WHERE id = ?');
    foreach ($ids as $k => $cid) $up->execute([($k + 1) * 10 + ($c['kind'] === 'video' ? 1000 : 0), gallery_now(), $cid]);
    gallery_cache_reset();
}

/** Odstráni kategóriu aj s jej položkami. Nahraté súbory sa zmažú, pôvodné položky sa iba skryjú (dajú sa obnoviť). */
function gallery_category_delete(int $id): int {
    $c = gallery_category($id);
    if (!$c) throw new GalleryException('Kategória neexistuje.');
    $n = 0;
    foreach (gallery_items($c) as $it) { gallery_item_delete($c['scope'], $it['ref']); $n++; }
    if ($c['static_key']) db()->prepare('UPDATE gallery_categories SET deleted = 1, updated_at = ? WHERE id = ?')->execute([gallery_now(), $id]);
    else db()->prepare('DELETE FROM gallery_categories WHERE id = ?')->execute([$id]);
    gallery_cache_reset();
    return $n;
}

/** Nastaví náhľad kategórie (odkaz na položku tejto kategórie). */
function gallery_category_set_cover(int $id, string $ref): void {
    $c = gallery_category($id);
    if (!$c) throw new GalleryException('Kategória neexistuje.');
    $ok = false;
    foreach (gallery_items($c) as $it) if ($it['ref'] === $ref) { $ok = true; break; }
    if (!$ok) throw new GalleryException('Náhľad musí byť položka z tejto kategórie.');
    db()->prepare('UPDATE gallery_categories SET cover = ?, updated_at = ? WHERE id = ?')->execute([$ref, gallery_now(), $id]);
    gallery_cache_reset();
}

/* ======================= Pôvodné položky (gallery_overrides) ======================= */

function gallery_overrides(): array {
    $o = &$GLOBALS['GALLERY_CACHE']['ovr'];
    if ($o !== null) return $o;
    $o = [];
    try {
        foreach (db()->query('SELECT * FROM gallery_overrides') as $r) $o[$r['item_type'] . ':' . $r['item_key']] = $r;
    } catch (Throwable $e) { /* tabuľka ešte neexistuje (pred migráciou) */ }
    return $o;
}
function gallery_static_hidden(string $type, string $key): bool { return !empty(gallery_overrides()[$type . ':' . $key]['hidden']); }

/** Kategória pôvodnej položky: presun adminom, inak kategória z dátového súboru. */
function gallery_static_category(string $type, string $key, string $dataCat): ?int {
    $o = gallery_overrides()[$type . ':' . $key] ?? null;
    if ($o && $o['category_id']) return (int)$o['category_id'];
    $map = &$GLOBALS['GALLERY_CACHE']['static_map'];
    if ($map === null) { $map = []; foreach (gallery_categories_all() as $c) if ($c['static_key']) $map[$c['static_key']] = $c['id']; }
    return $map[$type . ':' . $dataCat] ?? null;
}

function gallery_override_set(string $type, string $key, array $vals): void {
    $o = gallery_overrides()[$type . ':' . $key] ?? null;
    $cat = array_key_exists('category_id', $vals) ? $vals['category_id'] : ($o['category_id'] ?? null);
    $hid = array_key_exists('hidden', $vals) ? (int)$vals['hidden'] : (int)($o['hidden'] ?? 0);
    if ($o) db()->prepare('UPDATE gallery_overrides SET category_id = ?, hidden = ?, updated_at = ? WHERE item_type = ? AND item_key = ?')->execute([$cat, $hid, gallery_now(), $type, $key]);
    else db()->prepare('INSERT INTO gallery_overrides (item_type, item_key, category_id, hidden, updated_at) VALUES (?,?,?,?,?)')->execute([$type, $key, $cat, $hid, gallery_now()]);
    gallery_cache_reset();
}

/** Počet odstránených pôvodných položiek a kategórií (na obnovenie). */
function gallery_removed_static(): array {
    $o = array_filter(gallery_overrides(), fn($r) => (int)$r['hidden'] === 1);
    $cats = array_filter(gallery_categories_all(), fn($c) => $c['deleted'] && $c['static_key']);
    return ['photos' => count(array_filter($o, fn($r) => $r['item_type'] === 'photo')), 'videos' => count(array_filter($o, fn($r) => $r['item_type'] === 'video')), 'categories' => array_values($cats)];
}
function gallery_restore_static(): void {
    db()->exec('UPDATE gallery_categories SET deleted = 0 WHERE static_key IS NOT NULL');
    db()->exec('UPDATE gallery_overrides SET hidden = 0');
    gallery_cache_reset();
}

/* ======================= Položky kategórie ======================= */

/**
 * Položky kategórie v poradí: pôvodné, potom nahraté (najstaršie prvé).
 * Položka: ref, type (photo|video), full, thumb, caption, title, desc, source (static|db), video (pre videá), legacy.
 */
function gallery_items(array $cat): array {
    $out = [];
    if ($cat['kind'] === 'photo') {
        if ($cat['scope'] === 'public') {
            foreach (media_registry()['photos'] as $id => [$dc, $alt]) {
                if (gallery_static_hidden('photo', $id)) continue;
                if (gallery_static_category('photo', $id, $dc) !== $cat['id']) continue;
                if (!is_file(ROOT . '/assets/img/' . $id . '-sm.webp')) continue;
                $out[] = ['ref' => 's:' . $id, 'type' => 'photo', 'full' => img_full($id), 'thumb' => img_thumb($id), 'caption' => $alt, 'title' => $alt, 'desc' => '', 'source' => 'static'];
            }
        }
        $table = $cat['scope'] === 'public' ? 'media' : 'private_media';
        try {
            $st = db()->prepare("SELECT * FROM $table WHERE kind = 'image' AND category_id = ? ORDER BY created_at ASC, id ASC"); $st->execute([$cat['id']]);
            foreach ($st->fetchAll() as $m) $out[] = gallery_media_item($m, $cat['scope']);
        } catch (Throwable $e) { error_log('[galeria] ' . $e->getMessage()); }
    } else {
        foreach (videos_all($cat['scope']) as $v) {
            if ($v['category_id'] !== $cat['id']) continue;
            $out[] = ['ref' => 'v:' . $v['key'], 'type' => 'video', 'full' => '', 'thumb' => 'https://i.ytimg.com/vi/' . $v['yt'] . '/hqdefault.jpg',
                      'caption' => $v['title'], 'title' => $v['title'], 'desc' => $v['desc'], 'source' => $v['static'] ? 'static' : 'db', 'video' => $v];
        }
    }
    return $out;
}

function gallery_media_item(array $m, string $scope): array {
    $p = $scope === 'public';
    return ['ref' => ($p ? 'm:' : 'p:') . (int)$m['id'], 'type' => 'photo', 'db_id' => (int)$m['id'],
            'full' => $p ? $m['path'] : private_media_url($m), 'thumb' => $p ? ($m['thumb_path'] ?: $m['path']) : private_media_url($m, 'thumb'),
            'caption' => trim($m['title'] . ($m['description'] ? ' – ' . $m['description'] : '')), 'title' => $m['title'], 'desc' => $m['description'], 'source' => 'db'];
}

/** Náhľad kategórie (adresa obrázka) – zvolený adminom, inak prvá položka. */
function gallery_cover_src(array $cat, ?array $items = null): ?string {
    $items ??= gallery_items($cat);
    foreach ($items as $it) if ($it['ref'] === $cat['cover']) return $it['thumb'];
    return $items[0]['thumb'] ?? null;
}

/** Staré nahraté videosúbory (pred YouTube) – iba na zmazanie v správe galérie. */
function gallery_legacy_videos(string $scope): array {
    $table = $scope === 'public' ? 'media' : 'private_media';
    try { return db()->query("SELECT * FROM $table WHERE kind = 'video' ORDER BY created_at DESC")->fetchAll(); } catch (Throwable $e) { return []; }
}

/** Položka podľa odkazu – overí, že patrí do danej galérie. Vráti [typ zdroja, kľúč/id]. */
function gallery_parse_ref(string $scope, string $ref): array {
    if (!preg_match('/^([smpv]):([A-Za-z0-9_-]{1,80})$/', $ref, $m)) throw new GalleryException('Neplatná položka.');
    [$all, $t, $k] = $m;
    if ($t === 's') { if ($scope !== 'public' || !media_photo($k)) throw new GalleryException('Položka neexistuje.'); return ['static_photo', $k]; }
    if ($t === 'm') { if ($scope !== 'public' || !media_get((int)$k, 'public')) throw new GalleryException('Položka neexistuje.'); return ['media', (int)$k]; }
    if ($t === 'p') { if ($scope !== 'private' || !media_get((int)$k, 'private')) throw new GalleryException('Položka neexistuje.'); return ['media', (int)$k]; }
    if (str_starts_with($k, 'db-')) {
        $st = db()->prepare('SELECT id FROM videos WHERE id = ? AND scope = ?'); $st->execute([(int)substr($k, 3), $scope]);
        if (!$st->fetchColumn()) throw new GalleryException('Video neexistuje.');
        return ['db_video', (int)substr($k, 3)];
    }
    if ($scope !== 'public' || !isset(videos_data()['videos'][$k])) throw new GalleryException('Video neexistuje.');
    return ['static_video', $k];
}

/** Odstráni položku z galérie (nahraté súbory sa zmažú, pôvodné sa skryjú). */
function gallery_item_delete(string $scope, string $ref): void {
    [$src, $k] = gallery_parse_ref($scope, $ref);
    match ($src) {
        'static_photo' => gallery_override_set('photo', $k, ['hidden' => 1]),
        'static_video' => gallery_override_set('video', $k, ['hidden' => 1]),
        'media'        => media_delete($k, $scope),
        'db_video'     => video_delete($k),
    };
    db()->prepare('UPDATE gallery_categories SET cover = ? WHERE cover = ?')->execute(['', $ref]);
    gallery_cache_reset();
}

/** Presunie položku do inej kategórie rovnakého typu v tej istej galérii. */
function gallery_item_move(string $scope, string $ref, int $catId): void {
    [$src, $k] = gallery_parse_ref($scope, $ref);
    $c = gallery_category($catId, $scope);
    $kind = in_array($src, ['static_video', 'db_video'], true) ? 'video' : 'photo';
    if (!$c || $c['kind'] !== $kind) throw new GalleryException('Vyberte kategóriu ' . ($kind === 'video' ? 'videí' : 'fotografií') . '.');
    match ($src) {
        'static_photo' => gallery_override_set('photo', $k, ['category_id' => $catId]),
        'static_video' => gallery_override_set('video', $k, ['category_id' => $catId]),
        'media'        => db()->prepare('UPDATE ' . ($scope === 'public' ? 'media' : 'private_media') . ' SET category_id = ?, updated_at = ? WHERE id = ?')->execute([$catId, gallery_now(), $k]),
        'db_video'     => db()->prepare('UPDATE videos SET category_id = ?, updated_at = ? WHERE id = ?')->execute([$catId, gallery_now(), $k]),
    };
    db()->prepare('UPDATE gallery_categories SET cover = ? WHERE cover = ? AND id <> ?')->execute(['', $ref, $catId]);
    gallery_cache_reset();
}

/** Úprava popisu nahratej fotografie alebo pridaného videa. */
function gallery_item_update(string $scope, string $ref, string $title, string $desc, string $orientation = ''): void {
    [$src, $k] = gallery_parse_ref($scope, $ref);
    if ($src === 'media') media_update($k, $title, $desc, $scope);
    elseif ($src === 'db_video') video_update($k, $title, $desc, $orientation ?: 'landscape');
    else throw new GalleryException('Popis pôvodnej položky sa upravuje v dátových súboroch webu.');
}

/** Rozhodne o cieľovej kategórii z formulára: existujúca (id) alebo nová (názov). */
function gallery_resolve_category(string $scope, string $kind, string $sel, string $newTitle, string $newDesc = ''): int {
    if ($sel === 'new' || $sel === '') return gallery_category_create($scope, $kind, $newTitle, $newDesc);
    $c = gallery_category((int)$sel, $scope);
    if (!$c || $c['kind'] !== $kind) throw new GalleryException('Vybraná kategória neexistuje.');
    return $c['id'];
}

/** Nahratie fotografií do kategórie. $files = položky z $_FILES (normalizované). Vráti [počet, chyby, odkazy]. */
function gallery_upload_photos(string $scope, int $catId, array $files, int $uid): array {
    $ok = 0; $errors = []; $refs = [];
    $table = $scope === 'public' ? 'media' : 'private_media';
    foreach ($files as $f) {
        $name = (string)($f['name'] ?? 'súbor');
        try {
            if (($f['error'] ?? 0) === UPLOAD_ERR_OK) {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
                if (!str_starts_with($mime, 'image/')) throw new MediaException('Do galérie sa nahrávajú iba fotografie (JPG, PNG, WebP). Videá pridajte odkazom z YouTube.');
            }
            $m = media_store_upload($f, pathinfo($name, PATHINFO_FILENAME), '', $uid, $scope);
            db()->prepare("UPDATE $table SET category_id = ? WHERE id = ?")->execute([$catId, (int)$m['id']]);
            $refs[] = ($scope === 'public' ? 'm:' : 'p:') . (int)$m['id'];
            $ok++;
        } catch (MediaException $e) {
            $errors[] = $name . ': ' . $e->getMessage();
        }
    }
    gallery_cache_reset();
    return [$ok, $errors, $refs];
}

/** $_FILES['x'] s viacerými súbormi → zoznam súborov. */
function gallery_files(string $field): array {
    $f = $_FILES[$field] ?? null;
    if (!$f) return [];
    if (!is_array($f['name'])) return ($f['error'] ?? 4) === UPLOAD_ERR_NO_FILE ? [] : [$f];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if (($f['error'][$i] ?? 4) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

/** Je pôvodná fotografia odstránená z galérie? (tematické galérie na podstránkach ju potom nezobrazia) */
function gallery_photo_visible(string $id): bool {
    try { return !gallery_static_hidden('photo', $id); } catch (Throwable $e) { return true; }
}
