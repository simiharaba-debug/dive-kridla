<?php
// Obnova SQLite databázy zo zálohy (napr. prenesenej z iného počítača). Web musí byť zastavený (STOP-WEB.bat).
//   php bin/db-obnova.php                     → použije najnovší súbor v storage/zalohy/
//   php bin/db-obnova.php cesta\k\zaloha.sqlite
// Súčasná databáza sa pred prepísaním odloží do storage/zalohy/pred-obnovou-*.sqlite (nič sa nestratí).
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/config.php';
if (cfg('DB_DSN')) { fwrite(STDERR, "Web používa spoločnú databázu (DB_DSN) – obnova sa robí na databázovom serveri.\n"); exit(1); }
$target = cfg('DB_PATH') ?: STORAGE_DIR . '/app.sqlite';
$dir = STORAGE_DIR . '/zalohy';
$src = $argv[1] ?? null;
if (!$src) {
    $list = glob($dir . '/app-*.sqlite') ?: [];
    usort($list, fn($a, $b) => filemtime($b) <=> filemtime($a));
    $src = $list[0] ?? null;
}
if (!$src || !is_file($src)) { fwrite(STDERR, "Nenašiel som súbor zálohy. Skopíruj zálohu z druhého počítača do storage\\zalohy\\ alebo zadaj cestu.\n"); exit(1); }
try {
    $chk = new PDO('sqlite:' . $src, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($chk->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') throw new RuntimeException('poškodený súbor');
    $n = (int)$chk->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $chk = null;
} catch (Throwable $e) { fwrite(STDERR, "Súbor nie je platná databáza webu: " . $e->getMessage() . "\n"); exit(1); }
if (!is_dir($dir)) mkdir($dir, 0750, true);
if (is_file($target)) {
    $old = new PDO('sqlite:' . $target);
    $old->exec('VACUUM INTO ' . $old->quote($dir . '/pred-obnovou-' . date('Ymd-His') . '.sqlite'));
    $old = null;
}
foreach (['-wal', '-shm'] as $x) if (is_file($target . $x)) @unlink($target . $x);
if (!copy($src, $target)) { fwrite(STDERR, "Kopírovanie zlyhalo – je web zastavený?\n"); exit(1); }
echo "Databáza obnovená zo súboru: $src\nPoužívateľov: $n\nTeraz spusti START-WEB.bat.\n";
