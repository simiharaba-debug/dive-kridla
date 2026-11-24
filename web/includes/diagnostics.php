<?php
// Kontrola databázy, administrátora a relácií – používa bin/diagnostika.php (príkazový riadok)
// aj diagnostika.php (prehliadač, iba na tomto počítači). Heslá ani hashe sa NIKDY nevypisujú.
require_once __DIR__ . '/db.php';

/** Vráti zoznam kontrol: [['ok'|'warn'|'bad'|'info', 'nadpis', 'detail'], ...] */
function diagnostics_run(): array {
    $r = [];
    $add = function (string $level, string $title, string $detail = '') use (&$r) { $r[] = [$level, $title, $detail]; };

    // --- PHP ---
    $add('info', 'PHP ' . PHP_VERSION, 'php.ini: ' . (php_ini_loaded_file() ?: 'žiadny (rozšírenia môžu chýbať)'));
    foreach (['pdo_sqlite' => true, 'sqlite3' => false, 'mbstring' => true, 'openssl' => false, 'gd' => false, 'fileinfo' => false] as $ext => $must) {
        if (extension_loaded($ext)) $add('ok', "Rozšírenie $ext je zapnuté");
        else $add($must ? 'bad' : 'warn', "Rozšírenie $ext CHÝBA", $must ? 'Bez neho nefunguje registrácia ani prihlásenie. Spúšťaj web cez START-WEB.bat (opraví php.ini).' : 'Odporúčané (e-maily, nahrávanie fotiek).');
    }
    if (!defined('PASSWORD_ARGON2ID')) $add('info', 'Heslá sa hashujú cez bcrypt (Argon2id v tomto PHP nie je)', 'Je to bezpečné; prihlasovanie funguje s oboma.');

    // --- .env ---
    $env = ROOT . '/.env';
    if (is_file($env)) {
        $add('ok', 'Súbor .env existuje');
        $u = env_file_get('ADMIN_USERNAME'); $m = env_file_get('ADMIN_EMAIL'); $p = env_file_get('ADMIN_PASSWORD');
        if ($u && $m && $p) $add('ok', 'V .env je nastavený administrátor', "ADMIN_EMAIL = $m (heslo sa nevypisuje)");
        else $add('warn', 'V .env chýba ADMIN_USERNAME, ADMIN_EMAIL alebo ADMIN_PASSWORD', 'Administrátor sa nevytvorí automaticky.');
    } else {
        $add('warn', 'Súbor .env neexistuje', 'START-WEB.bat ho vytvorí z .env.example a vygeneruje nové heslo administrátora (uloží ho do Dokumenty\\Biologicka-ochrana-admin-prihlasenie.txt).');
    }

    // --- databáza ---
    $dsn = cfg('DB_DSN');
    if ($dsn) {
        $add('info', 'Databáza: ' . db_driver_from_dsn($dsn) . ' (spoločná databáza na serveri – DB_DSN v .env)', preg_replace('/password=[^;]*/i', 'password=***', $dsn));
    } else {
        $path = cfg('DB_PATH') ?: STORAGE_DIR . '/app.sqlite';
        $add('info', 'Databáza: SQLite súbor na TOMTO počítači', $path . (is_file($path) ? ' (' . round(filesize($path) / 1024) . ' kB, zmenený ' . date('d.m.Y H:i', filemtime($path)) . ')' : ' (zatiaľ neexistuje – vytvorí sa)'));
        $dir = dirname($path);
        is_dir($dir) && is_writable($dir) ? $add('ok', 'Do priečinka s databázou sa dá zapisovať', $dir) : $add('bad', 'Do priečinka s databázou sa NEDÁ zapisovať', $dir);
    }

    try {
        $pdo = db();   // pri pripojení sa spustia chýbajúce migrácie a vytvorí sa administrátor z .env
        $add('ok', 'Pripojenie k databáze funguje', 'Chýbajúce tabuľky sa vytvárajú automaticky pri prvom pripojení.');
        $mig = $pdo->query('SELECT name FROM schema_migrations ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
        $add('ok', 'Tabuľky sú pripravené', 'Migrácie: ' . implode(', ', $mig));
        $users = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $admins = $pdo->query("SELECT email FROM users WHERE role = 'admin' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        $add('info', "Používateľov v databáze: $users");
        if ($admins) $add('ok', 'Administrátor existuje', 'Prihlasovací e-mail: ' . implode(', ', $admins));
        else $add('bad', 'V databáze NIE JE žiadny administrátor', 'Doplň ADMIN_USERNAME, ADMIN_EMAIL a ADMIN_PASSWORD do .env a spusti START-WEB.bat.');

        if ($admins && is_file(ROOT . '/.env') && env_file_get('ADMIN_PASSWORD')) {
            require_once __DIR__ . '/admin_account.php';
            try {
                $c = admin_sync_from_env('check');
                if ($c['status'] === 'match') $add('ok', 'Heslo administrátora v .env zodpovedá databáze', 'Prihlásenie: ' . $c['email'] . ' + heslo ADMIN_PASSWORD z .env');
                else $add('warn', 'Heslo (alebo e-mail) administrátora v .env sa LÍŠI od databázy', 'Platí heslo uložené v databáze. Na zjednotenie spusti START-WEB.bat – heslo z .env sa bezpečne prenesie do databázy.');
            } catch (Throwable $e) { $add('warn', 'Kontrola hesla administrátora: ' . $e->getMessage()); }
        }

        // test zápisu relácie (bez nej nefunguje formulár – „Platnosť formulára vypršala“)
        $id = 'diag-' . bin2hex(random_bytes(6));
        $pdo->prepare('INSERT INTO sessions (id, data, user_id, last_activity) VALUES (?,?,?,?)')->execute([$id, '', null, time()]);
        $ok = (bool)$pdo->query("SELECT 1 FROM sessions WHERE id = " . $pdo->quote($id))->fetchColumn();
        $pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
        $ok ? $add('ok', 'Relácie (session) sa dajú zapisovať do databázy') : $add('bad', 'Reláciu sa nepodarilo zapísať');
    } catch (Throwable $e) {
        $add('bad', 'Databáza nefunguje', $e->getMessage());
    }

    foreach (['uploads' => UPLOAD_DIR, 'storage' => STORAGE_DIR] as $n => $d) {
        is_dir($d) && is_writable($d) ? $add('ok', "Priečinok $n/ je zapisovateľný") : $add('warn', "Priečinok $n/ nie je zapisovateľný", $d);
    }
    return $r;
}

function diagnostics_summary(array $r): string {
    $bad = count(array_filter($r, fn($x) => $x[0] === 'bad'));
    $warn = count(array_filter($r, fn($x) => $x[0] === 'warn'));
    return $bad ? "Nájdené chyby: $bad, upozornenia: $warn" : ($warn ? "Bez chýb, upozornenia: $warn" : 'Všetko v poriadku');
}
