<?php
// Uloží nastavenie odosielania e-mailov do .env a hneď odošle testovaciu správu.
// Volá ho sprievodca NASTAVIT-EMAIL.bat. Údaje prichádzajú cez premenné prostredia (nie cez príkazový riadok,
// aby sa heslo neobjavilo v zozname procesov): MS_USER, MS_PASS, MS_TO, voliteľne MS_HOST, MS_PORT, MS_SECURE.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__);
$envFile = "$root/.env";

$user = trim((string)getenv('MS_USER'));
$pass = preg_replace('/\s+/', '', (string)getenv('MS_PASS'));          // Google zobrazuje heslo po štvoriciach s medzerami
$to = trim((string)getenv('MS_TO'));
$host = trim((string)(getenv('MS_HOST') ?: 'smtp.gmail.com'));
$port = trim((string)(getenv('MS_PORT') ?: '587'));
$secure = strtolower(trim((string)(getenv('MS_SECURE') ?: 'tls')));

$err = [];
if (!filter_var($user, FILTER_VALIDATE_EMAIL)) $err[] = 'E-mailová adresa odosielateľa nie je platná.';
if ($pass === '') $err[] = 'Heslo aplikácie je prázdne.';
if ($to !== '' && !filter_var($to, FILTER_VALIDATE_EMAIL)) $err[] = 'Adresa príjemcu nie je platná.';
if (!preg_match('/^[a-z0-9.-]+$/i', $host)) $err[] = 'Neplatný SMTP server.';
if (!ctype_digit($port)) $err[] = 'Neplatný port.';
if (!in_array($secure, ['tls', 'ssl', 'none'], true)) $err[] = 'Neplatné šifrovanie.';
foreach ([$user, $pass, $to] as $v) if (preg_match('/[\r\n"]/', $v)) $err[] = 'Údaje obsahujú nepovolené znaky.';
if ($err) { fwrite(STDERR, "CHYBA: " . implode("\n       ", array_unique($err)) . "\n"); exit(1); }
if ($host === 'smtp.gmail.com' && !preg_match('/^[a-z]{16}$/i', $pass)) {
    fwrite(STDERR, "UPOZORNENIE: Heslo aplikácie Google má presne 16 písmen (bez medzier sa zadáva aj s nimi). Zadané heslo tak nevyzerá – ak test zlyhá, vytvor nové heslo aplikácie.\n");
}

$set = ['SMTP_HOST' => $host, 'SMTP_PORT' => $port, 'SMTP_SECURE' => $secure, 'SMTP_USER' => $user, 'SMTP_PASS' => $pass,
        'MAIL_FROM' => $user, 'MAIL_TO' => $to];
$raw = is_file($envFile) ? file_get_contents($envFile) : '';
$nl = str_contains($raw, "\r\n") ? "\r\n" : "\n";
if ($raw !== '') copy($envFile, "$root/.env.bak-" . date('Ymd-His'));      // záloha pred zmenou
$lines = $raw === '' ? [] : preg_split('/\r\n|\n/', rtrim($raw, "\r\n"));
foreach ($set as $k => $v) {
    $found = false;
    foreach ($lines as $i => $l) if (preg_match('/^\s*' . $k . '\s*=/', $l)) { $lines[$i] = "$k=$v"; $found = true; }
    if (!$found) $lines[] = "$k=$v";
}
if (file_put_contents($envFile, implode($nl, $lines) . $nl, LOCK_EX) === false) { fwrite(STDERR, "CHYBA: Súbor .env sa nepodarilo uložiť.\n"); exit(1); }
foreach ($set as $k => $v) putenv("$k=$v");
echo "Nastavenie bolo uložené do .env.\n";

require "$root/includes/mailer.php";
$SITE = require "$root/data/site.php";
$dest = mail_recipient($SITE['contact']['form_email']);
echo "Odosielam testovaciu správu na $dest ...\n";
$r = send_mail([
    'to' => $dest,
    'subject' => '[Web] Test kontaktného formulára – funguje to',
    'body' => "Dobrý deň,\n\ntoto je testovacia správa z webu OZ Divé krídla.\nAk ju čítate, kontaktný formulár na webe je správne nastavený a správy od návštevníkov budú chodiť sem.\n\nOdoslané: " . date('j. n. Y H:i:s') . "\n",
    'reply_email' => $user, 'reply_name' => 'Web OZ Divé krídla',
]);
if ($r['ok']) {
    echo "\nHOTOVO: správa bola odoslaná. Pozrite sa do schránky $dest (ak tam nie je, skontrolujte aj priečinok Spam).\n";
    exit(0);
}
fwrite(STDERR, "\nNEPODARILO SA: " . mail_error_help($r['error']) . "\n");
exit(2);
