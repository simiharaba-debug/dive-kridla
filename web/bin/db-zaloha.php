<?php
// Konzistentná kópia SQLite databázy (aj s nedokončeným WAL), napr. na prenos účtov na iný počítač.
//   php bin/db-zaloha.php            → storage/zalohy/app-RRRRMMDD-HHMMSS.sqlite
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/db.php';
if (cfg('DB_DSN')) { fwrite(STDERR, "Web používa spoločnú databázu (DB_DSN) – zálohuj ju nástrojmi databázového servera (pg_dump).\n"); exit(1); }
$pdo = db();
$dir = STORAGE_DIR . '/zalohy';
if (!is_dir($dir)) mkdir($dir, 0750, true);
$file = $dir . '/app-' . date('Ymd-His') . '.sqlite';
$pdo->exec('VACUUM INTO ' . $pdo->quote($file));
$n = (int)(new PDO('sqlite:' . $file))->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "Záloha vytvorená: $file\nPoužívateľov v zálohe: $n\n";
