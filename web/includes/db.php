<?php
// Pripojenie k databáze cez PDO (pripravené dotazy, bez emulácie).
// Predvolene SQLite súbor storage/app.sqlite; pre PostgreSQL nastav DB_DSN, DB_USER, DB_PASS v .env
// (napr. DB_DSN=pgsql:host=db.example.sk;port=5432;dbname=divekridla;sslmode=require).
//
// Databáza sa pripravuje SAMA: pri prvom pripojení sa spustia chýbajúce migrácie a ak ešte neexistuje
// administrátor, vytvorí sa z ADMIN_* v .env. Projekt preto funguje aj na novom počítači / hostingu,
// kde nikto nespustil bin/migrate.php (predtým: „no such table: sessions“ → nefungovala registrácia ani prihlásenie).
require_once __DIR__ . '/config.php';

class DbUnavailable extends RuntimeException {}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = cfg('DB_DSN');
    $user = cfg('DB_USER');
    $pass = cfg('DB_PASS');
    if (!$dsn) {
        if (!extension_loaded('pdo_sqlite')) {
            throw new DbUnavailable('PHP nemá zapnuté rozšírenie pdo_sqlite (spusti web cez START-WEB.bat, ten použije pribalené PHP so správnym php.ini).');
        }
        $path = cfg('DB_PATH') ?: STORAGE_DIR . '/app.sqlite';
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new DbUnavailable("Do priečinka s databázou sa nedá zapisovať: $dir");
        }
        $dsn = 'sqlite:' . $path;
    }
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $pdo = null;
        throw new DbUnavailable('Nepodarilo sa pripojiť k databáze (' . db_driver_from_dsn($dsn) . '): ' . $e->getMessage(), 0, $e);
    }
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    }
    db_ensure_ready($pdo);
    return $pdo;
}

function db_driver_from_dsn(string $dsn): string { return strtolower(strtok($dsn, ':')) ?: 'neznámy'; }

/** Priečinok s migráciami pre aktuálny databázový ovládač (SQLite = migrations/, PostgreSQL = migrations/pgsql/). */
function db_migrations_dir(PDO $pdo): string {
    $drv = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($drv === 'sqlite') return ROOT . '/migrations';
    if ($drv === 'pgsql') return ROOT . '/migrations/pgsql';
    throw new DbUnavailable("Databáza typu '$drv' nie je podporovaná. Použi SQLite (predvolené) alebo PostgreSQL.");
}

/** Pri prvom pripojení v požiadavke: chýbajúce migrácie + prvý administrátor z .env. Lacné – 1 dotaz, ak je všetko hotové. */
function db_ensure_ready(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $files = glob(db_migrations_dir($pdo) . '/*.sql') ?: [];
    $applied = [];
    try { $applied = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN); } catch (PDOException $e) { /* tabuľka ešte neexistuje */ }
    if (count(array_diff(array_map('basename', $files), $applied)) > 0) {
        db_migrate();
    }
    db_ensure_admin($pdo);
}

/** Ak v databáze nie je žiadny administrátor a .env má ADMIN_*, vytvorí ho (nikdy nemení existujúce účty). */
function db_ensure_admin(PDO $pdo): void {
    try {
        if ((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0) return;
        require_once __DIR__ . '/admin_account.php';
        [$u, $m, $p] = admin_env_credentials();
        if ($u === '' || $m === '' || $p === '') {
            error_log('[db] V databáze nie je administrátor a .env nemá ADMIN_USERNAME / ADMIN_EMAIL / ADMIN_PASSWORD.');
            return;
        }
        $r = admin_sync_from_env('if-missing');
        if ($r['status'] === 'created') error_log('[db] Administrátor ' . $r['email'] . ' bol automaticky vytvorený z .env.');
    } catch (Throwable $e) {
        error_log('[db] Administrátora sa nepodarilo vytvoriť: ' . $e->getMessage());
    }
}

/** Spustí všetky ešte nespustené migrácie (SQLite: migrations/*.sql, PostgreSQL: migrations/pgsql/*.sql). */
function db_migrate(): array {
    $pdo = db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) PRIMARY KEY, applied_at VARCHAR(40) NOT NULL)');
    $done = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $applied = [];
    $files = glob(db_migrations_dir($pdo) . '/*.sql') ?: [];
    sort($files);
    foreach ($files as $f) {
        $name = basename($f);
        if (in_array($name, $done, true)) continue;
        $pdo->beginTransaction();
        try {
            $pdo->exec(file_get_contents($f));
            // ON CONFLICT: ak migráciu súbežne spustila iná požiadavka, nič sa nestane
            $pdo->prepare('INSERT INTO schema_migrations (name, applied_at) VALUES (?, ?) ON CONFLICT (name) DO NOTHING')->execute([$name, gmdate('c')]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // súbežná požiadavka mohla tú istú migráciu práve dokončiť
            $chk = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE name = ?'); $chk->execute([$name]);
            if ($chk->fetchColumn()) continue;
            throw $e;
        }
        $applied[] = $name;
    }
    return $applied;
}
