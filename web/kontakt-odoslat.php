<?php
// Spracovanie formulárov (kontakt aj žiadosť o informácie na vyžiadanie).
// Odosiela SKUTOČNÝ e-mail cez SMTP (PHPMailer, includes/mailer.php). Úspech sa hlási IBA vtedy, keď SMTP server správu prijal.
// Ochrana: validácia všetkých polí, honeypot, obmedzenie počtu správ z jednej IP, ochrana pred e-mail injection.
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
send_security_headers();
header('X-Robots-Tag: noindex');
$SITE = require __DIR__ . '/data/site.php';
$wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch');

const CONTACT_LIMIT_15MIN = 5;
const CONTACT_LIMIT_DAY = 20;

function clean($v, $max = 5000) {
    $v = trim(str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], (string)$v));
    return mb_substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v), 0, $max);
}
function respond(bool $ok, string $msg, array $errors = [], int $code = 200): never {
    global $wantsJson, $back, $anchor;
    if ($wantsJson) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'message' => $msg] + ($errors ? ['errors' => $errors] : []), JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: ' . $back . '?odoslane=' . ($ok ? '1' : '0') . $anchor);
    exit;
}

$allowed = array_keys($SITE['pages']);
$back = basename(explode('#', (string)($_POST['back'] ?? './'))[0]);
$anchor = (preg_match('/#([a-z0-9-]+)$/i', (string)($_POST['back'] ?? ''), $mm)) ? '#' . $mm[1] : '';
if (!in_array(preg_replace('/\.php$/', '', $back), $allowed, true)) { $back = './'; $anchor = ''; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); respond(false, 'Neplatná požiadavka.', [], 405); }

// CSRF: formulár sa smie odoslať iba z tohto webu (Origin / Sec-Fetch-Site posiela každý súčasný prehliadač).
// Bez tokenu v relácii, aby verejné stránky nemuseli zakladať cookie každému návštevníkovi.
$originHost = parse_url((string)($_SERVER['HTTP_ORIGIN'] ?? ''), PHP_URL_HOST);
if (($originHost && strcasecmp($originHost, (string)($_SERVER['HTTP_HOST'] ?? '')) !== 0)
    || (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site')) {
    respond(false, 'Neplatná požiadavka.', [], 403);
}

// honeypot – skryté pole vypĺňajú iba roboty
if (clean($_POST['website'] ?? '') !== '') respond(false, 'Správu sa nepodarilo odoslať.', [], 400);

$type = ($_POST['type'] ?? '') === 'access' ? 'access' : 'contact';
$raw = [
    'name' => (string)($_POST['name'] ?? ''), 'email' => (string)($_POST['email'] ?? ''),
    'phone' => (string)($_POST['phone'] ?? ''), 'subject' => (string)($_POST['subject'] ?? ''),
    'topic' => (string)($_POST['topic'] ?? ''), 'org' => (string)($_POST['org'] ?? ''),
    'message' => (string)($_POST['message'] ?? ''),
];
$err = [];
// polia, ktoré idú do hlavičiek, nesmú obsahovať zalomenie riadku (e-mail injection) – odmietneme ich, nie „opravíme“
foreach (['name', 'email', 'phone', 'subject', 'topic', 'org'] as $k) {
    if (preg_match('/[\r\n\x00]|%0[ad]/i', $raw[$k])) $err[$k] = 'Pole obsahuje nepovolené znaky.';
}
$name = clean($raw['name'], 200); $email = clean($raw['email'], 254); $phone = clean($raw['phone'], 40);
$topic = clean($raw['topic'], 200); $org = clean($raw['org'], 200); $msg = clean($raw['message'], 6000);
$subject = $type === 'access' ? 'Žiadosť o informácie: ' . $topic : clean($raw['subject'], 200);

if (!isset($err['name'])) {
    if ($name === '') $err['name'] = 'Zadajte svoje meno.';
    elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) $err['name'] = 'Meno musí mať 2 – 100 znakov.';
    elseif (!preg_match("/^[\\p{L}\\p{M} .,'’\\-]+$/u", $name)) $err['name'] = 'Meno môže obsahovať iba písmená, medzery a znaky . , \' -';
}
if (!isset($err['email'])) {
    if ($email === '') $err['email'] = 'Zadajte e-mailovú adresu, aby sme vám mohli odpovedať.';
    elseif (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@[^@]+\.[a-z]{2,}$/i', $email)) $err['email'] = 'Zadajte platnú e-mailovú adresu (napr. meno@domena.sk).';
}
if (!isset($err['phone']) && $phone !== '' && !preg_match('/^\+?[0-9 ()\/\-]{6,20}$/', $phone)) $err['phone'] = 'Zadajte platné telefónne číslo alebo pole nechajte prázdne.';
if ($type === 'contact' && !isset($err['subject'])) {
    if ($subject === '') $err['subject'] = 'Zadajte predmet správy.';
    elseif (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) $err['subject'] = 'Predmet musí mať 3 – 150 znakov.';
}
if ($type === 'access' && !isset($err['topic']) && ($topic === '' || mb_strlen($topic) > 150)) $err['topic'] = 'Vyberte, o aké informácie máte záujem.';
if ($msg === '') $err['message'] = 'Napíšte text správy.';
elseif (mb_strlen($msg) < 10) $err['message'] = 'Správa je príliš krátka (aspoň 10 znakov).';
elseif (mb_strlen($msg) > 5000) $err['message'] = 'Správa je príliš dlhá (najviac 5000 znakov).';
elseif (preg_match_all('#https?://#i', $msg) > 3) $err['message'] = 'Správa obsahuje priveľa odkazov.';

if ($err) respond(false, 'Skontrolujte prosím vyznačené polia.', $err, 422);

// rate limiting podľa IP (záznamy v tabuľke auth_attempts, druh 'contact')
$ip = client_ip();
[, $last15] = attempts_count('contact', $ip, $ip, 900);
[, $lastDay] = attempts_count('contact', $ip, $ip, 86400);
if ($last15 >= CONTACT_LIMIT_15MIN || $lastDay >= CONTACT_LIMIT_DAY) {
    header('Retry-After: 900');
    respond(false, 'Odoslali ste priveľa správ za krátky čas. Skúste to prosím neskôr alebo nám zavolajte na ' . $SITE['contact']['phones'][0]['label'] . '.', [], 429);
}
attempt_log('contact', $ip);

$to = mail_recipient($SITE['contact']['form_email']);
$body = ($type === 'access' ? "Žiadosť o informácie na vyžiadanie z webu\n" : "Nová správa z kontaktného formulára na webe\n")
      . str_repeat('=', 44) . "\n"
      . "Meno:     $name\n"
      . "E-mail:   $email\n"
      . ($phone !== '' ? "Telefón:  $phone\n" : '')
      . ($topic !== '' ? ($type === 'access' ? 'Téma:     ' : 'Služba:   ') . "$topic\n" : '')
      . ($org !== '' ? "Organizácia / dôvod: $org\n" : '')
      . ($type === 'contact' ? "Predmet:  $subject\n" : '')
      . str_repeat('-', 44) . "\n\n" . $msg . "\n\n"
      . str_repeat('-', 44) . "\nOdpovedať môžete priamo tlačidlom „Odpovedať“ – správa pôjde na $email.\n"
      . 'Odoslané: ' . date('j. n. Y H:i') . "\n";

$r = send_mail(['to' => $to, 'subject' => '[Web] ' . $subject, 'body' => $body, 'reply_email' => $email, 'reply_name' => $name]);
if ($r['ok']) respond(true, 'Ďakujeme, vaša správa bola odoslaná. Ozveme sa vám čo najskôr.');

attempts_clear_one('contact', $ip);   // neúspešné odoslanie nezapočítavame do limitu
$msgFail = match ($r['error']) {
    'config' => 'Odosielanie e-mailov zatiaľ nie je na serveri nastavené, správa NEBOLA odoslaná. Zavolajte nám prosím na ' . $SITE['contact']['phones'][0]['label'] . ' alebo napíšte na ' . $SITE['contact']['emails'][0] . '.',
    default => 'Správu sa nepodarilo odoslať (chyba poštového servera). Skúste to prosím neskôr, zavolajte na ' . $SITE['contact']['phones'][0]['label'] . ' alebo napíšte na ' . $SITE['contact']['emails'][0] . '.',
};
respond(false, $msgFail, [], 503);
