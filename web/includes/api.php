<?php
// Spoločné pomôcky pre JSON API (iba pre prihláseného administrátora; CSRF povinné).
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/media.php';

send_security_headers();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function api_fail(string $msg, int $code): never { json_out(['ok' => false, 'error' => $msg], $code); }

/** Overí metódu POST, prihlásenie, rolu admin (z databázy) a CSRF token z hlavičky X-CSRF-Token. */
function api_admin(): array {
    set_exception_handler(function (Throwable $e) {
        error_log('API error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        api_fail('Na serveri nastala chyba. Skúste to znova.', 500);
    });
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); api_fail('Metóda nie je povolená.', 405); }
    $u = current_user();
    if (!$u) api_fail('Nie ste prihlásený.', 401);
    if ($u['role'] !== 'admin') api_fail('Nemáte oprávnenie.', 403);
    $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (!csrf_valid($t)) api_fail('Neplatný bezpečnostný token. Obnovte stránku.', 403);
    return $u;
}
function api_json_body(): array {
    $d = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($d)) api_fail('Neplatné dáta.', 400);
    return $d;
}
