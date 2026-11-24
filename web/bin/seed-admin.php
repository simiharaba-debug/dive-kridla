<?php
// Vytvorenie administrátora z .env (ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_PASSWORD).
//   php bin/seed-admin.php               vytvorí administrátora, alebo existujúcemu nastaví heslo z .env
//   php bin/seed-admin.php --if-missing  iba vytvorí, ak administrátor ešte neexistuje (nič nemení)
// Na zmenu hesla používaj bin/sync-admin-password.php (rovnaká logika, viac možností).
// Nikdy nevytvára duplicitný účet – existujúci administrátor sa aktualizuje.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/admin_account.php';
$mode = in_array('--if-missing', $argv ?? [], true) ? 'if-missing' : 'force';
try {
    db_migrate();
    $r = admin_sync_from_env($mode);
} catch (Throwable $e) {
    fwrite(STDERR, 'CHYBA: ' . $e->getMessage() . "\n");
    exit(1);
}
foreach ($r['warnings'] as $w) fwrite(STDERR, "UPOZORNENIE: $w\n");
echo match ($r['status']) {
    'created' => "Administrátor '{$r['email']}' vytvorený.\n",
    'exists' => "Administrátor už existuje.\n",
    default => "Administrátor '{$r['email']}' aktualizovaný.\n",
};
