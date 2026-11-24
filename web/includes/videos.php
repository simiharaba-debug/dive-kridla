<?php
// Videá z YouTube: pevné videá z data/videos.php + videá pridané administrátorom (tabuľka videos).
// Na webe sa neprehráva žiadny vlastný videosúbor – iba YouTube (youtube-nocookie.com) v modálnom okne po kliknutí.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/gallery.php';   // kategórie a skryté položky (circular require_once je v poriadku)

class VideoException extends RuntimeException {}

function videos_data(): array { static $D = null; return $D ??= require __DIR__ . '/../data/videos.php'; }

/** Normalizovaná položka videa: key, yt, title, desc, w, h, portrait, duration, date, topic, db_id. */
function video_norm(string $key, array $v): array {
    $w = (int)($v['w'] ?? 0); $h = (int)($v['h'] ?? 0);
    $portrait = $w && $h ? $h > $w : !empty($v['portrait']);
    if (!$w || !$h) { [$w, $h] = $portrait ? [9, 16] : [16, 9]; }
    return [
        'key' => $key, 'yt' => (string)$v['yt'], 'title' => (string)($v['title'] ?? 'Video'), 'desc' => (string)($v['desc'] ?? ''),
        'w' => $w, 'h' => $h, 'portrait' => $portrait, 'duration' => isset($v['duration']) ? (int)$v['duration'] : null,
        'date' => (string)($v['date'] ?? ''), 'topic' => (string)($v['topic'] ?? ''), 'db_id' => $v['db_id'] ?? null,
        'category_id' => isset($v['category_id']) ? (int)$v['category_id'] : null, 'scope' => (string)($v['scope'] ?? 'public'),
        'static' => empty($v['db_id']),
    ];
}

/** Videá pridané administrátorom (najstaršie prvé) v danej galérii. Pri chybe databázy prázdny zoznam – web funguje ďalej. */
function videos_db(string $scope = 'public'): array {
    try {
        $st = db()->prepare('SELECT * FROM videos WHERE scope = ? ORDER BY created_at ASC, id ASC'); $st->execute([$scope]);
        $rows = $st->fetchAll();
    } catch (Throwable $e) { error_log('[videa] ' . $e->getMessage()); return []; }
    $out = [];
    foreach ($rows as $r) {
        $out['db-' . $r['id']] = video_norm('db-' . $r['id'], [
            'yt' => $r['yt_id'], 'title' => $r['title'], 'desc' => $r['description'],
            'w' => $r['width'] ?: ($r['orientation'] === 'portrait' ? 9 : 16), 'h' => $r['height'] ?: ($r['orientation'] === 'portrait' ? 16 : 9),
            'duration' => $r['duration'], 'date' => substr((string)$r['created_at'], 0, 10), 'db_id' => (int)$r['id'],
            'category_id' => $r['category_id'] ?? null, 'scope' => $scope,
        ]);
    }
    return $out;
}

/**
 * Všetky viditeľné videá galérie (verejná: pevné z data/videos.php + pridané adminom; súkromná: iba pridané adminom).
 * Odstránené pevné videá (gallery_overrides.hidden) sa vynechajú. 'category_id' = kategória v gallery_categories.
 */
function videos_all(string $scope = 'public'): array {
    $all = []; $seen = [];
    if ($scope === 'public') {
        foreach (videos_data()['videos'] as $k => $v) {
            if (gallery_static_hidden('video', $k)) continue;
            $n = video_norm($k, $v);
            $n['category_id'] = gallery_static_category('video', $k, $n['topic']);
            $all[$k] = $n; $seen[$n['yt']] = true;
        }
    }
    foreach (videos_db($scope) as $k => $v) if (empty($seen[$v['yt']])) { $all[$k] = $v; $seen[$v['yt']] = true; }
    return $all;
}
function video_get(string $key, string $scope = 'public'): ?array {
    static $c = [];
    $c[$scope] ??= videos_all($scope);
    return $c[$scope][$key] ?? null;
}

/** YouTube ID z odkazu (youtu.be, watch?v=, shorts/, embed/, live/, m.youtube.com, music.youtube.com) alebo priamo ID. */
function yt_parse_id(string $input): ?string {
    $s = trim($input);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $s)) return $s;
    if (!preg_match('#^https?://#i', $s)) $s = 'https://' . $s;
    $u = parse_url($s);
    if (!$u || empty($u['host'])) return null;
    $host = strtolower(preg_replace('/^(www\.|m\.|music\.)/i', '', $u['host']));
    $path = $u['path'] ?? '';
    $id = null;
    if ($host === 'youtu.be') $id = explode('/', trim($path, '/'))[0] ?? null;
    elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
        if (preg_match('#^/(shorts|embed|live|v)/([^/?]+)#', $path, $m)) $id = $m[2];
        else { parse_str($u['query'] ?? '', $q); $id = $q['v'] ?? null; }
    }
    return ($id && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) ? $id : null;
}
function yt_is_shorts_link(string $input): bool { return (bool)preg_match('#youtube\.com/shorts/#i', $input); }

/** Stiahne stránku z YouTube (krátky časový limit). Ak server nemá internet, vráti null – nič sa nepokazí. */
function yt_http_get(string $url, int $timeout = 6): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => $timeout, CURLOPT_USERAGENT => 'Mozilla/5.0 (Divé krídla web)',
            CURLOPT_HTTPHEADER => ['Accept-Language: sk,en;q=0.8']]);
        $r = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return ($r !== false && $code >= 200 && $code < 300) ? $r : null;
    }
    if (!ini_get('allow_url_fopen')) return null;
    $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => "User-Agent: Mozilla/5.0\r\nAccept-Language: sk,en;q=0.8\r\n"]]);
    $r = @file_get_contents($url, false, $ctx);
    return $r === false ? null : $r;
}

/** Metadáta videa z YouTube (názov, rozmery, dĺžka). Najlepšia snaha – chýbajúce položky sú null. */
function yt_fetch_meta(string $id): array {
    $meta = ['title' => null, 'w' => null, 'h' => null, 'duration' => null, 'exists' => null];
    $oe = yt_http_get('https://www.youtube.com/oembed?format=json&url=' . rawurlencode('https://www.youtube.com/watch?v=' . $id), 5);
    if ($oe !== null && ($j = json_decode($oe, true))) { $meta['title'] = trim((string)($j['title'] ?? '')) ?: null; $meta['exists'] = true; }
    $page = yt_http_get('https://www.youtube.com/watch?v=' . $id, 7);
    if ($page !== null) {
        if (preg_match('/"width":(\d+),"height":(\d+)/', $page, $m)) { $meta['w'] = (int)$m[1]; $meta['h'] = (int)$m[2]; }
        if (preg_match('/"lengthSeconds":"(\d+)"/', $page, $m)) $meta['duration'] = (int)$m[1];
    }
    return $meta;
}

/** Pridanie videa administrátorom. $orientation: 'auto' | 'landscape' | 'portrait'. */
function video_add(string $link, string $title, string $desc, string $orientation, int $uid, string $scope = 'public', ?int $categoryId = null): array {
    $id = yt_parse_id($link);
    if (!$id) throw new VideoException('Odkaz nie je platné video z YouTube. Vložte napríklad https://youtu.be/… alebo https://www.youtube.com/watch?v=…');
    foreach (videos_all($scope) as $v) if ($v['yt'] === $id) throw new VideoException('Toto video už v galérii je („' . $v['title'] . '“).');
    $title = trim(mb_substr($title, 0, 120)); $desc = trim(mb_substr($desc, 0, 1000));
    $meta = yt_fetch_meta($id);
    if ($title === '') $title = $meta['title'] ? mb_substr(mb_strtoupper(mb_substr($meta['title'], 0, 1)) . mb_substr($meta['title'], 1), 0, 120) : 'Video';
    if (!in_array($orientation, ['landscape', 'portrait'], true)) {
        if ($meta['w'] && $meta['h']) $orientation = $meta['h'] > $meta['w'] ? 'portrait' : 'landscape';
        else $orientation = yt_is_shorts_link($link) ? 'portrait' : 'landscape';
    }
    // rozmery z YouTube použijeme len vtedy, keď súhlasia so zvolenou orientáciou
    $w = $meta['w']; $h = $meta['h'];
    if (!$w || !$h || (($h > $w) !== ($orientation === 'portrait'))) { [$w, $h] = $orientation === 'portrait' ? [9, 16] : [16, 9]; }
    $now = gmdate('c');
    db()->prepare('INSERT INTO videos (scope, category_id, yt_id, title, description, orientation, width, height, duration, added_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$scope, $categoryId, $id, $title, $desc, $orientation, $w, $h, $meta['duration'], $uid ?: null, $now, $now]);
    return ['id' => (int)db()->lastInsertId(), 'yt' => $id, 'title' => $title, 'orientation' => $orientation, 'meta_ok' => $meta['exists'] === true];
}

function video_update(int $dbId, string $title, string $desc, string $orientation): bool {
    $title = trim(mb_substr($title, 0, 120)) ?: 'Video'; $desc = trim(mb_substr($desc, 0, 1000));
    if (!in_array($orientation, ['landscape', 'portrait'], true)) $orientation = 'landscape';
    $s = db()->prepare('SELECT width, height FROM videos WHERE id = ?'); $s->execute([$dbId]); $r = $s->fetch();
    if (!$r) return false;
    $w = (int)$r['width']; $h = (int)$r['height'];
    if (!$w || !$h || (($h > $w) !== ($orientation === 'portrait'))) { [$w, $h] = $orientation === 'portrait' ? [9, 16] : [16, 9]; }
    $u = db()->prepare('UPDATE videos SET title = ?, description = ?, orientation = ?, width = ?, height = ?, updated_at = ? WHERE id = ?');
    $u->execute([$title, $desc, $orientation, $w, $h, gmdate('c'), $dbId]);
    return true;
}

function video_delete(int $dbId): bool {
    $s = db()->prepare('DELETE FROM videos WHERE id = ?'); $s->execute([$dbId]);
    return $s->rowCount() > 0;
}

function videos_db_count(string $scope = 'public'): int {
    try { $s = db()->prepare('SELECT COUNT(*) FROM videos WHERE scope = ?'); $s->execute([$scope]); return (int)$s->fetchColumn(); } catch (Throwable $e) { return 0; }
}

/** 81 → „1:21“ */
function video_dur_label(?int $s): string { return $s ? intdiv($s, 60) . ':' . str_pad((string)($s % 60), 2, '0', STR_PAD_LEFT) : ''; }
/** 81 → „PT1M21S“ (ISO 8601 pre schema.org) */
function video_dur_iso(?int $s): string { return $s ? 'PT' . (intdiv($s, 60) ? intdiv($s, 60) . 'M' : '') . ($s % 60) . 'S' : ''; }

/** Štruktúrované metadáta schema.org/VideoObject (JSON-LD – nie je to spustiteľný skript, CSP ho neblokuje). */
function videos_jsonld(array $videos): void {
    if (!$videos) return;
    $list = [];
    foreach (array_values($videos) as $v) {
        $o = ['@type' => 'VideoObject', 'name' => $v['title'], 'description' => $v['desc'] ?: $v['title'],
              'thumbnailUrl' => 'https://i.ytimg.com/vi/' . $v['yt'] . '/hqdefault.jpg',
              'embedUrl' => 'https://www.youtube-nocookie.com/embed/' . $v['yt'],
              'contentUrl' => 'https://www.youtube.com/watch?v=' . $v['yt'], 'width' => $v['w'], 'height' => $v['h'], 'inLanguage' => 'sk'];
        if ($v['date']) $o['uploadDate'] = $v['date'];
        if ($v['duration']) $o['duration'] = video_dur_iso($v['duration']);
        $list[] = $o;
    }
    $data = count($list) === 1 ? ['@context' => 'https://schema.org'] + $list[0]
          : ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => array_map(fn($o, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'item' => $o], $list, array_keys($list))];
    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}
