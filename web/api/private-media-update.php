<?php
require __DIR__ . '/../includes/api.php';
api_admin();
header('X-Robots-Tag: noindex, nofollow');
$d = api_json_body();
$id = (int)($d['id'] ?? 0);
if (!$id || !media_get($id, 'private')) api_fail('Položka neexistuje.', 404);
$title = (string)($d['title'] ?? '');
if (trim($title) === '') api_fail('Názov nesmie byť prázdny.', 422);
json_out(['ok' => true, 'item' => private_media_public(media_update($id, $title, (string)($d['description'] ?? ''), 'private'))]);
