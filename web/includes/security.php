<?php
require_once __DIR__ . '/config.php';
/** Bezpečnostné hlavičky pre každú odpoveď. CSP nepovoľuje inline skripty.
 *  YouTube: náhľady z i.ytimg.com a prehrávač youtube-nocookie.com (načíta sa až po kliknutí na video). */
function send_security_headers(): void {
    if (headers_sent()) return;
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https://i.ytimg.com; media-src 'self' blob:; frame-src https://www.youtube-nocookie.com; font-src 'self'; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    if (is_https()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header_remove('X-Powered-By');
}
