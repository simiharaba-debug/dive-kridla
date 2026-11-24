<?php
// Diagnostika databázy a prihlasovania:  php bin/diagnostika.php   (na Windows: DIAGNOSTIKA.bat)
// Nič nemaže; chýbajúce tabuľky a prvého administrátora z .env vytvorí (rovnako ako web). Heslá nevypisuje.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/diagnostics.php';
$r = diagnostics_run();
$mark = ['ok' => '[OK]   ', 'warn' => '[POZOR]', 'bad' => '[CHYBA]', 'info' => '[INFO] '];
echo "Diagnostika – web Biologická ochrana\nProjekt: " . ROOT . "\n\n";
foreach ($r as [$l, $t, $d]) { echo $mark[$l] . ' ' . $t . "\n"; if ($d !== '') echo "         $d\n"; }
echo "\n" . diagnostics_summary($r) . "\n";
echo "\nPoznámka: účty sú uložené v databáze. Pri SQLite je databáza súbor storage\\app.sqlite na tomto počítači –\n"
   . "na inom počítači sú iné účty, kým tam databázu neprenesieš (ZALOHA-DATABAZY.bat / OBNOVIT-DATABAZU.bat)\n"
   . "alebo kým web nebeží na jednom spoločnom serveri (pozri NAVOD-DATABAZA.md).\n";
exit(count(array_filter($r, fn($x) => $x[0] === 'bad')) ? 1 : 0);
