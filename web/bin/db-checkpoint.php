<?php
// Zapíše rozpracované zmeny SQLite (WAL) do storage/app.sqlite, aby sa súbor dal bezpečne skopírovať. Volá STOP-WEB.bat.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/config.php';
if (cfg('DB_DSN')) exit(0);
$path = cfg('DB_PATH') ?: STORAGE_DIR . '/app.sqlite';
if (!is_file($path)) exit(0);
try { (new PDO('sqlite:' . $path))->exec('PRAGMA wal_checkpoint(TRUNCATE)'); echo "Databaza ulozena.\n"; } catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); }
