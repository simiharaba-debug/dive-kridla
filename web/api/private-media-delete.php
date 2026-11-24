<?php
require __DIR__ . '/../includes/api.php';
api_admin();
header('X-Robots-Tag: noindex, nofollow');
$d = api_json_body();
$id = (int)($d['id'] ?? 0);
if (!$id || !media_delete($id, 'private')) api_fail('Položka neexistuje.', 404);
json_out(['ok' => true, 'id' => $id]);
