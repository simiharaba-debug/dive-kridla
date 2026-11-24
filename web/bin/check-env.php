<?php
// Overí, či PHP spĺňa požiadavky projektu. Výstup: "verzia|1" (všetko OK) alebo "verzia|0".
$need = ['pdo_sqlite', 'sqlite3', 'gd', 'fileinfo', 'mbstring', 'exif'];
$ok = version_compare(PHP_VERSION, '8.1.0', '>=');
foreach ($need as $e) if (!extension_loaded($e)) $ok = false;
if (!function_exists('imagewebp')) $ok = false;
echo PHP_VERSION, '|', $ok ? 1 : 0;
