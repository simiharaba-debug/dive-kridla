<?php
// Jediná cesta k súborom súkromnej galérie. Súbory ležia mimo webu (storage/private-media) a odovzdajú sa
// IBA prihlásenému administrátorovi – rola sa číta z databázy pri každej požiadavke. Podporuje Range (posúvanie vo videu).
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/media.php';
send_security_headers();
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: private, no-store, max-age=0');

function deny(int $code, string $msg): never {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    exit;
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'HEAD') { header('Allow: GET, HEAD'); deny(405, 'Metóda nie je povolená.'); }
$u = current_user();
if (!$u) deny(401, 'Nie ste prihlásený.');
if ($u['role'] !== 'admin') deny(403, 'Prístup zamietnutý.');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();   // neblokovať ďalšie požiadavky počas streamovania

$id = (int)($_GET['id'] ?? 0);
$m = $id ? media_get($id, 'private') : null;
if (!$m) deny(404, 'Súbor neexistuje.');
$thumb = ($_GET['v'] ?? '') === 'thumb';
$rel = $thumb ? $m['thumb_path'] : $m['path'];
$file = private_media_file($rel);
if (!$file) deny(404, 'Súbor neexistuje.');
$mime = $thumb ? (str_ends_with($rel, '.jpg') ? 'image/jpeg' : 'image/webp') : $m['mime'];
if (!in_array($mime, ['image/webp', 'image/jpeg', 'video/mp4', 'video/webm'], true)) deny(415, 'Nepodporovaný typ.');

$size = filesize($file);
$start = 0; $end = $size - 1;
header('Content-Type: ' . $mime);
header('Accept-Ranges: bytes');
header('Content-Disposition: inline; filename="' . ($thumb ? 'nahlad' : 'subor') . '-' . $id . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $r) || ($r[1] === '' && $r[2] === '')) {
        header("Content-Range: bytes */$size"); deny(416, 'Neplatný rozsah.');
    }
    if ($r[1] === '') { $start = max(0, $size - (int)$r[2]); }
    else { $start = (int)$r[1]; if ($r[2] !== '') $end = min($end, (int)$r[2]); }
    if ($start > $end || $start >= $size) { header("Content-Range: bytes */$size"); deny(416, 'Neplatný rozsah.'); }
    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
}
header('Content-Length: ' . ($end - $start + 1));
if ($method === 'HEAD') exit;
while (ob_get_level()) ob_end_clean();
$fh = fopen($file, 'rb');
fseek($fh, $start);
$left = $end - $start + 1;
while ($left > 0 && !feof($fh) && !connection_aborted()) {
    $chunk = fread($fh, (int)min(262144, $left));
    if ($chunk === false) break;
    echo $chunk; flush();
    $left -= strlen($chunk);
}
fclose($fh);
