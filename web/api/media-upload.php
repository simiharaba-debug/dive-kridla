<?php
require __DIR__ . '/../includes/api.php';
$user = api_admin();
if (empty($_FILES['file']) || !is_array($_FILES['file']) || is_array($_FILES['file']['name'])) api_fail('Chýba súbor.', 400);
// Videá sa na verejný web nenahrávajú – pridávajú sa ako odkaz z YouTube (Fotogaléria → Videá).
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['file']['tmp_name'] ?? '') ?: '';
if (str_starts_with($mime, 'video/') || preg_match('/\.(mp4|webm|mov|avi|mkv)$/i', (string)$_FILES['file']['name']))
    api_fail('Videá sa na web nenahrávajú. Nahrajte video na YouTube a pridajte ho odkazom v kategórii Videá.', 422);
try {
    $m = media_store_upload($_FILES['file'], (string)($_POST['title'] ?? ''), (string)($_POST['description'] ?? ''), (int)$user['id']);
} catch (MediaException $e) {
    api_fail($e->getMessage(), 422);
}
json_out(['ok' => true, 'item' => media_public($m)], 201);
