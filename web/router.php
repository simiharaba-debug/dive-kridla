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
if ($path === '/sitemap.xml') { require __DIR__ . '/sitemap.php'; return true; }
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
// CSS, JS, SVG komprimovane; súbory s verziou (?v=) a fotografie si prehliadač pamätá
if ($path !== '/' && is_file($file) && preg_match('#^/(assets|uploads)/.*\.(css|js|svg|webp|jpe?g|png|woff2)$#i', $path, $sm)) {
    $mime = ['css' => 'text/css; charset=utf-8', 'js' => 'text/javascript; charset=utf-8', 'svg' => 'image/svg+xml', 'webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'woff2' => 'font/woff2'];
    $ext = strtolower($sm[2]); $etag = '"' . dechex(filemtime($file)) . '-' . dechex(filesize($file)) . '"';
    header_remove('X-Powered-By'); header('X-Content-Type-Options: nosniff'); header('ETag: ' . $etag); header('Vary: Accept-Encoding');
    header('Cache-Control: public, max-age=' . (isset($_GET['v']) ? '31536000, immutable' : (in_array($ext, ['css', 'js'], true) ? '0, must-revalidate' : '2592000')));
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); return true; }
    header('Content-Type: ' . $mime[$ext]);
    $out = file_get_contents($file);
    if (in_array($ext, ['css', 'js', 'svg'], true) && function_exists('gzencode') && stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) { header('Content-Encoding: gzip'); $out = gzencode($out, 6); }
    header('Content-Length: ' . strlen($out));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo $out;
    return true;
}
return false;   // ostatné (statické súbory, .php) obslúži PHP server
