<?php
// Nahratie do SÚKROMNEJ galérie (iba administrátor; rola sa overuje z databázy pri každej požiadavke).
require __DIR__ . '/../includes/api.php';
$user = api_admin();
header('X-Robots-Tag: noindex, nofollow');
if (empty($_FILES['file']) || !is_array($_FILES['file']) || is_array($_FILES['file']['name'])) api_fail('Chýba súbor.', 400);
try {
    $m = media_store_upload($_FILES['file'], (string)($_POST['title'] ?? ''), (string)($_POST['description'] ?? ''), (int)$user['id'], 'private');
} catch (MediaException $e) {
    api_fail($e->getMessage(), 422);
}
json_out(['ok' => true, 'item' => private_media_public($m)], 201);
