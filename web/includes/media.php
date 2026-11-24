<?php
// Médiá (fotografie a videá): ukladanie na disk + metadáta v databáze.
// Bezpečnosť: typ súboru sa zisťuje z OBSAHU (finfo + magic bytes), nikdy z názvu alebo z hlavičky od klienta.
// Obrázky sa znovu zakódujú (GD → WebP), takže sa nedá nahrať skrytý kód. Videá sa overia ffprobe.
// Názvy súborov generuje server (náhodné), pôvodný názov sa nepoužíva.
require_once __DIR__ . '/db.php';

const IMG_MAX_EDGE = 2200;
const THUMB_EDGE = 800;
const IMG_MAX_PIXELS = 50_000_000;   // ochrana pred „decompression bomb“

class MediaException extends RuntimeException {}

/**
 * Úložisko médií. 'public' = verejná galéria (tabuľka media, súbory v uploads/ – verejne dostupné),
 * 'private' = súkromná administrátorská galéria (tabuľka private_media, súbory MIMO webu v storage/private-media,
 * sprístupnené iba cez private-media.php po overení administrátora). Úložiská ani záznamy sa nikdy nemiešajú.
 */
function media_scope(string $scope): array {
    return match ($scope) {
        'public' => ['table' => 'media', 'abs' => ROOT, 'rel' => 'uploads'],
        'private' => ['table' => 'private_media', 'abs' => PRIVATE_MEDIA_DIR, 'rel' => ''],
        default => throw new InvalidArgumentException('Neznáme úložisko médií.'),
    };
}

function media_list(?string $kind = null, string $scope = 'public', string $order = 'DESC'): array {
    $t = media_scope($scope)['table'];
    $order = $order === 'ASC' ? 'ASC' : 'DESC';
    $sql = "SELECT m.*, u.username AS author FROM $t m LEFT JOIN users u ON u.id = m.uploaded_by"
         . ($kind ? ' WHERE m.kind = ?' : '') . " ORDER BY m.created_at $order, m.id $order";
    $s = db()->prepare($sql);
    $s->execute($kind ? [$kind] : []);
    return $s->fetchAll();
}
function media_get(int $id, string $scope = 'public'): ?array {
    $t = media_scope($scope)['table'];
    $s = db()->prepare("SELECT * FROM $t WHERE id = ?"); $s->execute([$id]); return $s->fetch() ?: null;
}
function media_public(array $m): array {   // iba polia, ktoré smú ísť do prehliadača
    return ['id' => (int)$m['id'], 'kind' => $m['kind'], 'title' => $m['title'], 'description' => $m['description'],
            'path' => $m['path'], 'thumb' => $m['thumb_path'], 'width' => $m['width'], 'height' => $m['height'], 'size' => (int)$m['size']];
}
/** Súkromná položka pre prehliadač: žiadne cesty na disku, iba adresy chráneného endpointu. */
function private_media_url(array $m, string $v = 'full'): string {
    if ($v === 'thumb' && $m['thumb_path'] === '') return 'assets/img/video-placeholder.svg';
    return 'private-media?id=' . (int)$m['id'] . ($v === 'thumb' ? '&v=thumb' : '');
}
function private_media_public(array $m): array {
    return ['id' => (int)$m['id'], 'kind' => $m['kind'], 'title' => $m['title'], 'description' => $m['description'],
            'path' => private_media_url($m), 'thumb' => private_media_url($m, 'thumb'), 'mime' => $m['mime'],
            'width' => $m['width'], 'height' => $m['height'], 'size' => (int)$m['size'], 'created_at' => $m['created_at']];
}
/** Absolútna cesta k súkromnému súboru – iba ak naozaj leží v súkromnom úložisku (ochrana pred ../). */
function private_media_file(string $rel): ?string {
    if ($rel === '' || str_contains($rel, '..') || str_contains($rel, "\0")) return null;
    $base = realpath(PRIVATE_MEDIA_DIR);
    $f = realpath(PRIVATE_MEDIA_DIR . '/' . $rel);
    if (!$base || !$f || !is_file($f)) return null;
    return str_starts_with($f, $base . DIRECTORY_SEPARATOR) ? $f : null;
}

function upload_error_text(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Súbor je väčší, než povoľuje server.',
        UPLOAD_ERR_PARTIAL => 'Súbor sa nahral len čiastočne. Skúste to znova.',
        UPLOAD_ERR_NO_FILE => 'Nebol vybraný žiadny súbor.',
        default => 'Súbor sa nepodarilo nahrať.',
    };
}
function clean_text(string $s, int $max): string {
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', trim($s));
    return mb_substr($s, 0, $max);
}
function rand_name(): string { return bin2hex(random_bytes(16)); }

/** Spracuje jeden nahraný súbor ($_FILES['file']). Vráti záznam z DB alebo vyhodí MediaException. */
function media_store_upload(array $f, string $title, string $desc, int $userId, string $scope = 'public'): array {
    $sc = media_scope($scope);
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new MediaException(upload_error_text((int)($f['error'] ?? 4)));
    $tmp = $f['tmp_name'];
    if (!is_uploaded_file($tmp)) throw new MediaException('Neplatné nahratie súboru.');
    $size = filesize($tmp);
    if ($size === false || $size < 12) throw new MediaException('Súbor je prázdny alebo poškodený.');

    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($tmp);
    $head = file_get_contents($tmp, false, null, 0, 16);
    $kind = null;
    if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $okMagic = ($mime === 'image/jpeg' && str_starts_with($head, "\xFF\xD8\xFF"))
                || ($mime === 'image/png' && str_starts_with($head, "\x89PNG\r\n\x1a\n"))
                || ($mime === 'image/webp' && str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP');
        if (!$okMagic) throw new MediaException('Obsah súboru nezodpovedá obrázku.');
        $kind = 'image';
    } elseif (in_array($mime, ['video/mp4', 'video/webm'], true)) {
        $okMagic = ($mime === 'video/mp4' && substr($head, 4, 4) === 'ftyp') || ($mime === 'video/webm' && str_starts_with($head, "\x1A\x45\xDF\xA3"));
        if (!$okMagic) throw new MediaException('Obsah súboru nezodpovedá videu.');
        $kind = 'video';
    } else {
        throw new MediaException('Nepodporovaný formát. Povolené sú: JPG, PNG, WebP, MP4 a WebM.');
    }
    if ($kind === 'image' && $size > MAX_IMAGE_BYTES) throw new MediaException('Fotografia je príliš veľká (max ' . intdiv(MAX_IMAGE_BYTES, 1048576) . ' MB).');
    if ($kind === 'video' && $size > MAX_VIDEO_BYTES) throw new MediaException('Video je príliš veľké (max ' . intdiv(MAX_VIDEO_BYTES, 1048576) . ' MB).');

    $dirBase = $sc['abs'] . ($sc['rel'] !== '' ? '/' . $sc['rel'] : '');
    foreach (['images', 'videos', 'thumbs'] as $d) if (!is_dir("$dirBase/$d")) mkdir("$dirBase/$d", $scope === 'private' ? 0750 : 0755, true);
    $name = rand_name();
    $w = $h = null;
    if ($kind === 'image') {
        [$path, $thumb, $w, $h, $outSize, $outMime] = process_image($tmp, $name, $sc['abs'], $sc['rel']);
    } else {
        [$path, $thumb, $w, $h, $outSize, $outMime] = process_video($tmp, $name, $mime, $sc['abs'], $sc['rel']);
    }
    $now = gmdate('c');
    try {
        db()->prepare("INSERT INTO {$sc['table']} (kind,title,description,filename,path,thumb_path,mime,size,width,height,uploaded_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$kind, clean_text($title, 120), clean_text($desc, 1000), basename($path), $path, $thumb, $outMime, $outSize, $w, $h, $userId, $now, $now]);
    } catch (Throwable $e) {
        @unlink($sc['abs'] . '/' . $path); if ($thumb !== '' && !str_starts_with($thumb, 'assets/')) @unlink($sc['abs'] . '/' . $thumb);
        throw $e;
    }
    return media_get((int)db()->lastInsertId(), $scope);
}
function rel_join(string $base, string $p): string { return $base === '' ? $p : "$base/$p"; }

function process_image(string $tmp, string $name, string $abs = ROOT, string $rel = 'uploads'): array {
    $info = @getimagesize($tmp);
    if (!$info || empty($info[0]) || empty($info[1])) throw new MediaException('Obrázok je poškodený alebo ho nie je možné prečítať.');
    [$iw, $ih] = $info;
    if ($iw * $ih > IMG_MAX_PIXELS) throw new MediaException('Obrázok má príliš veľké rozlíšenie (max 50 megapixelov).');
    @ini_set('memory_limit', '768M');
    $src = @imagecreatefromstring(file_get_contents($tmp));
    if (!$src) throw new MediaException('Obrázok je poškodený alebo ho nie je možné spracovať.');
    if ($info['mime'] === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($tmp);
        $o = $ex['Orientation'] ?? 1;
        $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot) { $r = imagerotate($src, $rot, 0); if ($r) { $src = $r; } }
    }
    $iw = imagesx($src); $ih = imagesy($src);
    $full = scale_gd($src, IMG_MAX_EDGE);
    $thumb = scale_gd($src, THUMB_EDGE);
    $p = rel_join($rel, "images/$name.webp"); $t = rel_join($rel, "thumbs/$name.webp");
    $ok = imagewebp($full, "$abs/$p", 84) && imagewebp($thumb, "$abs/$t", 78);
    if (!$ok) { @unlink("$abs/$p"); @unlink("$abs/$t"); throw new MediaException('Obrázok sa nepodarilo uložiť.'); }
    return [$p, $t, imagesx($full), imagesy($full), filesize("$abs/$p"), 'image/webp'];
}
function scale_gd(GdImage $src, int $edge): GdImage {
    $w = imagesx($src); $h = imagesy($src);
    $k = min(1, $edge / max($w, $h));
    $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false); imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $dst;
}

function is_windows(): bool { return DIRECTORY_SEPARATOR === '\\'; }
function tool_exists(string $t): bool {
    static $c = [];
    if (isset($c[$t])) return $c[$t];
    if (!function_exists('shell_exec')) return $c[$t] = false;
    $cmd = is_windows() ? 'where ' . escapeshellarg($t) . ' 2>nul' : 'command -v ' . escapeshellarg($t) . ' 2>/dev/null';
    return $c[$t] = trim((string)@shell_exec($cmd)) !== '';
}
/** Spustí externý príkaz s časovým limitom (Linux: timeout, Windows: bez neho) a vráti jeho výstup. */
function run_tool(string $cmd, int $seconds = 30): string {
    $full = is_windows() ? $cmd . ' 2>nul' : 'timeout ' . $seconds . ' ' . $cmd . ' 2>/dev/null';
    return (string)@shell_exec($full);
}
function process_video(string $tmp, string $name, string $mime, string $abs = ROOT, string $rel = 'uploads'): array {
    $ext = $mime === 'video/webm' ? 'webm' : 'mp4';
    $w = $h = null;
    if (tool_exists('ffprobe')) {
        $out = run_tool('ffprobe -v error -select_streams v:0 -show_entries stream=width,height,codec_name -of csv=p=0 ' . escapeshellarg($tmp), 20);
        $parts = explode(',', trim($out));
        if (count($parts) < 3 || !ctype_digit($parts[1] ?? '') || !ctype_digit($parts[2] ?? '')) throw new MediaException('Video je poškodené alebo neobsahuje obrazovú stopu.');
        $w = (int)$parts[1]; $h = (int)$parts[2];
    }
    $p = rel_join($rel, "videos/$name.$ext");
    if (!move_uploaded_file($tmp, "$abs/$p")) throw new MediaException('Video sa nepodarilo uložiť.');
    @chmod("$abs/$p", $rel === '' ? 0640 : 0644);
    $t = rel_join($rel, "thumbs/$name.jpg");
    $made = false;
    if (tool_exists('ffmpeg')) {
        foreach (['1', '0'] as $ss) {   // snímka z 1. sekundy, pri krátkom videu zo začiatku
            run_tool('ffmpeg -y -v error -ss ' . $ss . ' -i ' . escapeshellarg("$abs/$p") . ' -frames:v 1 -vf scale=800:-2 -q:v 4 ' . escapeshellarg("$abs/$t"), 30);
            if (is_file("$abs/$t") && filesize("$abs/$t") > 0) { $made = true; break; }
        }
    }
    if (!$made) $t = $rel === '' ? '' : 'assets/img/video-placeholder.svg';   // súkromné: prázdne = zástupný obrázok
    return [$p, $t, $w, $h, filesize("$abs/$p"), $mime];
}

function media_update(int $id, string $title, string $desc, string $scope = 'public'): ?array {
    $t = media_scope($scope)['table'];
    db()->prepare("UPDATE $t SET title=?, description=?, updated_at=? WHERE id=?")->execute([clean_text($title, 120), clean_text($desc, 1000), gmdate('c'), $id]);
    return media_get($id, $scope);
}
function media_delete(int $id, string $scope = 'public'): bool {
    $m = media_get($id, $scope);
    if (!$m) return false;
    $t = media_scope($scope)['table'];
    db()->prepare("DELETE FROM $t WHERE id = ?")->execute([$id]);
    foreach ([$m['path'], $m['thumb_path']] as $rel) {
        if ($scope === 'private') { if ($f = private_media_file($rel)) @unlink($f); }
        elseif (str_starts_with($rel, 'uploads/') && !str_contains($rel, '..')) @unlink(ROOT . '/' . $rel);
    }
    return true;
}
