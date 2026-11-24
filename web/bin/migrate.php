<?php
// Spustenie migrácií:  php bin/migrate.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/db.php';
$a = db_migrate();
echo $a ? "Spustené migrácie: " . implode(', ', $a) . "\n" : "Databáza je aktuálna.\n";
