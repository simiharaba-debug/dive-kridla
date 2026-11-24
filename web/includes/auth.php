<?php
// Autentifikácia: relácie v databáze, CSRF, prihlásenie, „zapamätať si ma“, obmedzenie pokusov.
// Rola používateľa sa VŽDY číta z databázy na serveri – nikdy z dát poslaných prehliadačom.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/passwords.php';

const REMEMBER_COOKIE = 'dk_remember';
const SESSION_NAME = 'dk_sid';
const SESSION_TTL = 60 * 60 * 24 * 2;          // neaktívna relácia vyprší po 2 dňoch
const REMEMBER_TTL = 60 * 60 * 24 * 30;        // „zapamätať si ma“ = 30 dní

class DbSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false {
        $s = db()->prepare('SELECT data FROM sessions WHERE id = ? AND last_activity > ?');
        $s->execute([$id, time() - SESSION_TTL]);
        $d = $s->fetchColumn();
        return $d === false ? '' : (string)$d;
    }
    public function write(string $id, string $data): bool {
        $uid = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
        db()->prepare('INSERT INTO sessions (id, data, user_id, last_activity) VALUES (?,?,?,?)
                       ON CONFLICT(id) DO UPDATE SET data=excluded.data, user_id=excluded.user_id, last_activity=excluded.last_activity')
            ->execute([$id, $data, $uid, time()]);
        return true;
    }
    public function destroy(string $id): bool { db()->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]); return true; }
    public function gc(int $max): int|false { $s = db()->prepare('DELETE FROM sessions WHERE last_activity < ?'); $s->execute([time() - SESSION_TTL]); return $s->rowCount(); }
    public function validateId(string $id): bool {
        $s = db()->prepare('SELECT 1 FROM sessions WHERE id = ? AND last_activity > ?'); $s->execute([$id, time() - SESSION_TTL]); return (bool)$s->fetchColumn();
    }
    public function updateTimestamp(string $id, string $data): bool {
        db()->prepare('UPDATE sessions SET last_activity = ? WHERE id = ?')->execute([time(), $id]); return true;
    }
}

function cookie_opts(int $expires = 0): array {
    return ['expires' => $expires, 'path' => '/', 'secure' => is_https() || cfg('COOKIE_SECURE') === '1', 'httponly' => true, 'samesite' => 'Lax'];
}

/** Spustí reláciu (ak ešte nebeží). */
function session_open(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name(SESSION_NAME);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '50');
    $o = cookie_opts();
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $o['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    session_set_save_handler(new DbSessionHandler(), true);
    session_start();
}
/** Spustí reláciu len ak ju návštevník už má (anonymné návštevy nezapisujú do DB). */
function session_if_present(): void {
    if (isset($_COOKIE[SESSION_NAME]) || isset($_COOKIE[REMEMBER_COOKIE])) session_open();
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    session_open();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">'; }
function csrf_valid(?string $t): bool {
    return $t !== null && $t !== '' && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/* ---------- flash správy (zobrazia sa ako toast) ---------- */
function flash(string $type, string $msg): void { session_open(); $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_take(): array {
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['flash'])) return [];
    $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f;
}

/* ---------- aktuálny používateľ ---------- */
function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    session_if_present();
    $user = null;
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['uid'])) {
        $s = db()->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = ?');
        $s->execute([(int)$_SESSION['uid']]);
        $user = $s->fetch() ?: null;
        if (!$user) { unset($_SESSION['uid']); }
    }
    if (!$user) $user = remember_login();
    return $cache = $user;
}
function reset_user_cache(): void { /* cache je static v current_user(); po zmene presmerujeme, takže netreba */ }
function is_admin(): bool { $u = current_user(); return $u && $u['role'] === 'admin'; }

function login_user(int $id, bool $remember): void {
    session_open();
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    if ($remember) remember_issue($id);
}
function logout_user(): void {
    session_open();
    remember_clear();
    $id = session_id();
    $_SESSION = [];
    session_destroy();
    setcookie(SESSION_NAME, '', cookie_opts(time() - 3600));
}

/* ---------- „zapamätať si ma“ (selector + hashovaný validator) ---------- */
function remember_issue(int $uid): void {
    $sel = bin2hex(random_bytes(9)); $val = bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at) VALUES (?,?,?,?,?)')
        ->execute([$uid, $sel, hash('sha256', $val), time() + REMEMBER_TTL, time()]);
    setcookie(REMEMBER_COOKIE, "$sel:$val", cookie_opts(time() + REMEMBER_TTL));
}
function remember_clear(): void {
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        $sel = explode(':', $_COOKIE[REMEMBER_COOKIE])[0];
        db()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$sel]);
    }
    setcookie(REMEMBER_COOKIE, '', cookie_opts(time() - 3600));
    unset($_COOKIE[REMEMBER_COOKIE]);
}
function remember_login(): ?array {
    $c = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!preg_match('/^([a-f0-9]{18}):([a-f0-9]{64})$/', $c, $m)) return null;
    $s = db()->prepare('SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > ?');
    $s->execute([$m[1], time()]);
    $t = $s->fetch();
    if (!$t || !hash_equals($t['validator_hash'], hash('sha256', $m[2]))) {
        if ($t) db()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$m[1]]);   // podozrivé použitie
        setcookie(REMEMBER_COOKIE, '', cookie_opts(time() - 3600));
        return null;
    }
    db()->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([$t['id']]);   // rotácia tokenu
    $u = db()->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = ?');
    $u->execute([$t['user_id']]);
    $user = $u->fetch();
    if (!$user) return null;
    session_open();
    login_user((int)$user['id'], true);
    return $user;
}

/* ---------- obmedzenie pokusov ---------- */
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }
function attempts_count(string $kind, string $ident, string $ip, int $window = 900): array {
    $a = db()->prepare('SELECT COUNT(*) FROM auth_attempts WHERE kind=? AND ident=? AND created_at>?');
    $a->execute([$kind, $ident, time() - $window]);
    $b = db()->prepare('SELECT COUNT(*) FROM auth_attempts WHERE kind=? AND ip=? AND created_at>?');
    $b->execute([$kind, $ip, time() - $window]);
    return [(int)$a->fetchColumn(), (int)$b->fetchColumn()];
}
function attempt_log(string $kind, string $ident): void {
    db()->prepare('INSERT INTO auth_attempts (kind, ident, ip, created_at) VALUES (?,?,?,?)')->execute([$kind, $ident, client_ip(), time()]);
    if (random_int(1, 40) === 1) db()->prepare('DELETE FROM auth_attempts WHERE created_at < ?')->execute([time() - 86400]);
}
/** Zruší posledný záznam pokusu (napr. keď správa nebola odoslaná pre chybu servera). */
function attempts_clear_one(string $kind, string $ident): void {
    db()->prepare('DELETE FROM auth_attempts WHERE id = (SELECT MAX(id) FROM auth_attempts WHERE kind=? AND ident=?)')->execute([$kind, $ident]);
}
function attempts_clear(string $kind, string $ident): void {
    db()->prepare('DELETE FROM auth_attempts WHERE kind=? AND ident=?')->execute([$kind, $ident]);
}

/* ---------- registrácia a prihlásenie (logika) ---------- */
/** Vráti [errors(array field=>msg), userId|null]. */
function register_user(string $username, string $email, string $pass, string $pass2): array {
    $err = [];
    $username = trim($username); $email = trim($email);
    if (!preg_match('/^[\p{L}\p{N}_.\- ]{3,30}$/u', $username)) $err['username'] = 'Používateľské meno musí mať 3 – 30 znakov (písmená, čísla, medzera, _ . -).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $err['email'] = 'Zadajte platný e-mail.';
    if ($e = password_policy_error($pass, $username, $email)) $err['password'] = $e;
    if ($pass !== $pass2) $err['password2'] = 'Heslá sa nezhodujú.';
    [$byIdent, $byIp] = attempts_count('register', client_ip(), client_ip(), 3600);
    if ($byIp >= 10) return [['form' => 'Príliš veľa registrácií z vašej adresy. Skúste to neskôr.'], null];
    if (!$err) {
        $s = db()->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $s->execute([$username, $email]);
        foreach ($s->fetchAll() as $row) {
            if (mb_strtolower($row['email']) === mb_strtolower($email)) $err['email'] = 'Tento e-mail je už zaregistrovaný.';
            if (mb_strtolower($row['username']) === mb_strtolower($username)) $err['username'] = 'Toto používateľské meno je už obsadené.';
        }
    }
    if ($err) return [$err, null];
    attempt_log('register', client_ip());
    $now = gmdate('c');
    try {
        // rola je vždy 'user' – administrátora vytvorí iba príkaz bin/seed-admin.php
        db()->prepare("INSERT INTO users (username,email,password_hash,role,created_at,updated_at) VALUES (?,?,?,'user',?,?)")
            ->execute([$username, $email, hash_password($pass), $now, $now]);
    } catch (PDOException $e) {
        return [['form' => 'Registráciu sa nepodarilo dokončiť. Skúste iné údaje.'], null];   // súbežná duplicita
    }
    return [[], (int)db()->lastInsertId()];
}

/** Vráti [errorMessage|null, userId|null]. */
function attempt_login(string $email, string $pass): array {
    $email = trim($email);
    $ident = mb_strtolower($email);
    [$byIdent, $byIp] = attempts_count('login', $ident, client_ip());
    if ($byIdent >= 5 || $byIp >= 30) return ['Príliš veľa neúspešných pokusov. Skúste to znova o niekoľko minút.', null];
    $s = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $s->execute([$email]);
    $u = $s->fetch();
    // pri neexistujúcom používateľovi overíme „falošný“ hash, aby bola odozva podobná
    static $dummy = null;
    $dummy ??= hash_password('dummy-password-1');
    $ok = password_verify($pass, $u['password_hash'] ?? $dummy) && $u;
    if (!$ok) { attempt_log('login', $ident); return ['Nesprávny e-mail alebo heslo.', null]; }
    attempts_clear('login', $ident);
    if (password_needs_rehash($u['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
        db()->prepare('UPDATE users SET password_hash=?, updated_at=? WHERE id=?')->execute([hash_password($pass), gmdate('c'), $u['id']]);
    }
    return [null, (int)$u['id']];
}

/** Presmerovanie na stránku z whitelistu (žiadny open redirect). */
function redirect(string $page): never { header('Location: ' . $page); exit; }
function require_login(): array {
    $u = current_user();
    if (!$u) { flash('error', 'Pre túto stránku sa musíte prihlásiť.'); redirect('prihlasenie'); }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') render_forbidden();
    return $u;
}
