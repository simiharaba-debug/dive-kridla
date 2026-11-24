<?php
// Smerovač pre vstavaný vývojový server PHP (ten ignoruje .htaccess):
//   php -d upload_max_filesize=200M -d post_max_size=210M -S localhost:8000 router.php
// Zakazuje prístup k interným súborom, podporuje adresy bez prípony .php a staré adresy s .php presmeruje.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (preg_match('#(^|/)(\.|storage/|includes/|data/|migrations/|bin/)#', ltrim($path, '/'))
    || preg_match('#\.(env|sqlite|sql|md|example|gitignore|ini)$#i', $path)
    || preg_match('#^/uploads/.*\.(php\d?|phtml|phar|html?|svg)$#i', $path)
    || $path === '/router.php') {
    http_response_code(404); include __DIR__ . '/404.php'; return true;
}
$file = __DIR__ . $path;
// Skryté prípony: /stranka.php → 301 na /stranka (a /index.php → /). Platí len pre GET/HEAD, formuláre (POST) idú priamo.
if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)
    && preg_match('#^/((?:[a-z0-9_-]+/)*)([a-z0-9_-]+)\.php$#i', $path, $pm) && strncmp($path, '/api/', 5) !== 0 && is_file($file)) {
    $q = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: /' . $pm[1] . (strtolower($pm[2]) === 'index' ? '' : $pm[2]) . ($q !== '' ? '?' . $q : ''), true, 301);
    return true;
}
if ($path !== '/' && is_file($file . '.php') && !is_file($file)) { $_SERVER['SCRIPT_NAME'] = $path . '.php'; require $file . '.php'; return true; }
if ($path !== '/' && !is_file($file)) { http_response_code(404); include __DIR__ . '/404.php'; return true; }
return false;   // ostatné (statické súbory, .php) obslúži PHP server
