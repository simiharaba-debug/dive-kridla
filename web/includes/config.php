<?php
// Konfigurácia: hodnoty z premenných prostredia alebo zo súboru .env (v koreni projektu).
// Súbor .env sa NEPUBLIKUJE (je v .gitignore a webserver ho nikdy neodovzdá). Vzor: .env.example
define('ROOT', dirname(__DIR__));

function env_load() {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;
    $file = ROOT . '/.env';
    if (!is_readable($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) $v = substr($v, 1, -1);
        if (getenv($k) === false) { putenv("$k=$v"); $_ENV[$k] = $v; }
    }
}
/** Hodnota priamo zo súboru .env (bez ohľadu na premenné prostredia procesu). null = v súbore nie je. */
function env_file_get(string $key): ?string {
    $file = ROOT . '/.env';
    if (!is_readable($file)) return null;
    $val = null;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        if (trim($k) !== $key) continue;
        $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) $v = substr($v, 1, -1);
        $val = $v;   // pri viacnásobnom výskyte platí posledný
    }
    return $val;
}
function cfg($key, $default = null) {
    env_load();
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}
function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (cfg('TRUST_PROXY') === '1' && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

define('STORAGE_DIR', ROOT . '/storage');
define('UPLOAD_DIR', ROOT . '/uploads');
// Súkromná administrátorská galéria – MIMO verejne dostupných priečinkov (storage/ je pre web zablokovaný).
define('PRIVATE_MEDIA_DIR', rtrim(cfg('PRIVATE_MEDIA_DIR', STORAGE_DIR . '/private-media'), '/\\'));
define('MAX_IMAGE_BYTES', (int)cfg('MAX_IMAGE_MB', 15) * 1048576);
define('MAX_VIDEO_BYTES', (int)cfg('MAX_VIDEO_MB', 150) * 1048576);
