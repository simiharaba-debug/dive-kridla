<?php
// Bezpečná synchronizácia administrátorského účtu so súborom .env (ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_PASSWORD).
// Používa sa IBA z príkazového riadka (bin/sync-admin-password.php, bin/seed-admin.php) – nikdy pri načítaní stránky.
//  * aktualizuje EXISTUJÚCI administrátorský účet (nevytvára duplicitný),
//  * heslo vždy zahashuje (Argon2id / bcrypt), heslo sa nikde nevypisuje,
//  * po zmene zneplatní všetky relácie a „zapamätať si ma“ tokeny administrátora,
//  * nikdy nepovýši bežný registrovaný účet na administrátora (ochrana pred prevzatím).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/passwords.php';

class AdminSyncError extends RuntimeException {}

/** Načíta ADMIN_* – prednosť má súbor .env (ten upravuje administrátor); vráti aj upozornenia. */
function admin_env_credentials(): array {
    $out = []; $warn = [];
    foreach (['ADMIN_USERNAME', 'ADMIN_EMAIL', 'ADMIN_PASSWORD'] as $k) {
        $file = env_file_get($k);
        $proc = getenv($k);
        if ($file !== null && $file !== '' && $proc !== false && $proc !== '' && $proc !== $file) {
            $warn[] = "Premenná prostredia $k sa líši od hodnoty v .env – použije sa hodnota zo súboru .env.";
        }
        $out[$k] = ($file !== null && $file !== '') ? $file : (($proc !== false && $proc !== '') ? $proc : '');
    }
    return [trim($out['ADMIN_USERNAME']), trim($out['ADMIN_EMAIL']), $out['ADMIN_PASSWORD'], $warn];
}

/**
 * $mode: 'force' (vždy nastaví heslo z .env), 'if-changed' (iba ak sa heslo/e-mail/meno líši), 'if-missing' (iba vytvorí, ak admin neexistuje),
 *        'check' (nič nemení, iba zistí stav).
 * Vráti ['status' => created|updated|unchanged|exists|mismatch|match, 'email' => ..., 'warnings' => [...]].
 */
function admin_sync_from_env(string $mode = 'force'): array {
    [$u, $m, $p, $warn] = admin_env_credentials();
    if ($u === '' || $m === '' || $p === '') throw new AdminSyncError('V .env chýba ADMIN_USERNAME, ADMIN_EMAIL alebo ADMIN_PASSWORD.');
    if (!filter_var($m, FILTER_VALIDATE_EMAIL)) throw new AdminSyncError('ADMIN_EMAIL nie je platný e-mail.');
    if (!preg_match('/^[\p{L}\p{N}_.\- ]{3,30}$/u', $u)) throw new AdminSyncError('ADMIN_USERNAME musí mať 3 – 30 znakov (písmená, čísla, medzera, _ . -).');
    if ($err = password_policy_error($p, $u, $m)) throw new AdminSyncError("ADMIN_PASSWORD: $err");

    $pdo = db();
    $admins = $pdo->query("SELECT id, username, email, password_hash FROM users WHERE role = 'admin' ORDER BY id")->fetchAll();
    $target = null;
    if (count($admins) === 1) $target = $admins[0];
    elseif (count($admins) > 1) {
        foreach ($admins as $a) if (mb_strtolower($a['email']) === mb_strtolower($m)) $target = $a;
        if (!$target) throw new AdminSyncError('V databáze je viac administrátorov a žiadny nemá e-mail ADMIN_EMAIL z .env. Nastav ADMIN_EMAIL na e-mail účtu, ktorého heslo chceš zmeniť.');
    }

    // nový e-mail / meno nesmie patriť inému (bežnému) účtu – inak by šlo o prevzatie cudzieho účtu
    $conflict = $pdo->prepare('SELECT id, role FROM users WHERE (email = ? OR username = ?) AND id <> ?');
    $conflict->execute([$m, $u, $target['id'] ?? 0]);
    if ($c = $conflict->fetch()) {
        throw new AdminSyncError('ADMIN_EMAIL alebo ADMIN_USERNAME z .env už používa iný účet (' . ($c['role'] === 'admin' ? 'administrátor' : 'bežný používateľ')
            . '). Z bezpečnostných dôvodov sa tento účet nepovýši ani neprepíše. Zvoľ iný e-mail/meno.');
    }

    if (!$target) {
        if ($mode === 'check') return ['status' => 'missing', 'email' => $m, 'warnings' => $warn];
        $now = gmdate('c');
        $pdo->prepare("INSERT INTO users (username,email,password_hash,role,created_at,updated_at) VALUES (?,?,?,'admin',?,?)")->execute([$u, $m, hash_password($p), $now, $now]);
        $id = (int)$pdo->lastInsertId();
        admin_verify_password($id, $p, null);
        return ['status' => 'created', 'email' => $m, 'warnings' => $warn];
    }
    if ($mode === 'if-missing') return ['status' => 'exists', 'email' => $target['email'], 'warnings' => $warn];

    $same = password_verify($p, $target['password_hash']) && $target['email'] === $m && $target['username'] === $u;
    if ($mode === 'check') return ['status' => $same ? 'match' : 'mismatch', 'email' => $target['email'], 'warnings' => $warn];
    if ($mode === 'if-changed' && $same) return ['status' => 'unchanged', 'email' => $m, 'warnings' => $warn];

    $oldHash = $target['password_hash'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, updated_at = ? WHERE id = ? AND role = \'admin\'')
            ->execute([$u, $m, hash_password($p), gmdate('c'), $target['id']]);
        // odhlásiť všetky existujúce prihlásenia administrátora (aj „zapamätať si ma“)
        $pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$target['id']]);
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$target['id']]);
        // zrušiť prípadné zablokovanie prihlasovania po neúspešných pokusoch
        $pdo->prepare("DELETE FROM auth_attempts WHERE kind = 'login' AND ident IN (?, ?)")->execute([mb_strtolower($target['email']), mb_strtolower($m)]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    admin_verify_password((int)$target['id'], $p, $oldHash);
    return ['status' => 'updated', 'email' => $m, 'warnings' => $warn, 'email_changed' => $target['email'] !== $m];
}

/** Overí, že v databáze je uložený hash nového hesla (a nie pôvodný). */
function admin_verify_password(int $id, string $p, ?string $oldHash): void {
    $s = db()->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'admin'");
    $s->execute([$id]);
    $h = (string)$s->fetchColumn();
    if ($h === '' || !password_verify($p, $h) || ($oldHash !== null && hash_equals($oldHash, $h))) {
        throw new AdminSyncError('Overenie zlyhalo: nové heslo sa v databáze neprejavilo.');
    }
    if (!str_starts_with($h, '$argon2') && !str_starts_with($h, '$2y$')) throw new AdminSyncError('Overenie zlyhalo: heslo nie je uložené ako bezpečný hash.');
}
