<?php
// Odošle testovací e-mail cez SMTP nastavenie z .env a vypíše priebeh (bez hesla).
//   tools\php\php.exe -c tools\php\php.ini bin\test-mail.php            (Windows)
//   php bin/test-mail.php [adresat@domena.sk] [--debug]                  (inak MAIL_TO z .env)
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../includes/mailer.php';
$SITE = require __DIR__ . '/../data/site.php';
if ($p = mail_config_problems()) {
    fwrite(STDERR, "SMTP nie je nakonfigurované. V .env chýba alebo je neplatné: " . implode(', ', $p) . "\n");
    exit(1);
}
$pos = array_values(array_filter(array_slice($argv, 1), fn($a) => !str_starts_with($a, '--')));
$to = $pos[0] ?? mail_recipient($SITE['contact']['form_email']);
echo "Odosielam testovaciu správu na $to cez " . cfg('SMTP_HOST') . ':' . cfg('SMTP_PORT', '587') . " ...\n";
$r = send_mail([
    'to' => $to,
    'subject' => '[Web] Testovacia správa z kontaktného formulára',
    'body' => "Toto je testovacia správa z webu OZ Divé krídla.\nAk ju čítate, odosielanie e-mailov cez SMTP funguje.\n\nOdoslané: " . date('j. n. Y H:i:s') . "\n",
    'reply_email' => cfg('MAIL_FROM', cfg('SMTP_USER')), 'reply_name' => 'Test',
], in_array('--debug', $argv, true) ? function ($l) { echo "  $l\n"; } : null);
if ($r['ok']) { echo "OK: SMTP server správu prijal. Skontroluj schránku $to (aj priečinok Spam).\n"; exit(0); }
fwrite(STDERR, "CHYBA: správa nebola odoslaná.\n" . mail_error_help($r['error']) . "\n");
exit(1);
