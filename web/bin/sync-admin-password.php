<?php
// Zmena / synchronizácia hesla administrátora podľa .env.
//
//   1. V súbore .env zmeň ADMIN_PASSWORD (prípadne ADMIN_EMAIL / ADMIN_USERNAME).
//   2. Spusti:   tools\php\php.exe -c tools\php\php.ini bin\sync-admin-password.php      (Windows)
//                php bin/sync-admin-password.php                                         (Linux / hosting)
//      START-WEB.bat to robí automaticky pri každom spustení (režim --if-changed).
//
// Prepínače:
//   (bez prepínača)  nastaví heslo z .env existujúcemu administrátorovi (vždy nový hash)
//   --if-changed     zmení iba vtedy, keď sa heslo / e-mail / meno v .env líši od databázy
//   --check          nič nemení; vypíše, či heslo v .env zodpovedá databáze (kód 0 = áno, 2 = nie)
// Heslo sa nikdy nevypisuje. Príkaz funguje iba z príkazového riadka (z webu je priečinok bin/ zablokovaný).
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/admin_account.php';

$args = array_slice($argv ?? [], 1);
$mode = in_array('--check', $args, true) ? 'check' : (in_array('--if-changed', $args, true) ? 'if-changed' : 'force');
try {
    db_migrate();
    $r = admin_sync_from_env($mode);
} catch (AdminSyncError $e) {
    fwrite(STDERR, 'CHYBA: ' . $e->getMessage() . "\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, 'CHYBA: ' . get_class($e) . ' – ' . $e->getMessage() . "\n");
    exit(1);
}
foreach ($r['warnings'] as $w) fwrite(STDERR, "UPOZORNENIE: $w\n");
switch ($r['status']) {
    case 'created':   echo "Administrátor {$r['email']} bol vytvorený. Heslo z .env je nastavené a overené.\n"; break;
    case 'updated':   echo "Heslo administrátora {$r['email']} bolo zmenené podľa .env a overené. Staré heslo už neplatí, všetky predchádzajúce prihlásenia boli odhlásené.\n";
                      if (!empty($r['email_changed'])) echo "Prihlasovací e-mail administrátora je teraz: {$r['email']}\n"; break;
    case 'unchanged': echo "Heslo administrátora v databáze už zodpovedá .env – bez zmeny.\n"; break;
    case 'match':     echo "OK: heslo v .env zodpovedá administrátorovi {$r['email']}.\n"; exit(0);
    case 'mismatch':  echo "NEZHODA: heslo (alebo e-mail/meno) v .env sa líši od databázy. Spusti bez --check na synchronizáciu.\n"; exit(2);
    case 'missing':   echo "Administrátor zatiaľ neexistuje.\n"; exit(2);
}
exit(0);
