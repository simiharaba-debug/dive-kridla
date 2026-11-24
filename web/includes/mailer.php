<?php
// Odosielanie e-mailov cez SMTP (PHPMailer, autentifikácia SMTP). Konfigurácia výhradne v .env:
//   SMTP_HOST, SMTP_PORT, SMTP_SECURE (tls|ssl|none), SMTP_USER, SMTP_PASS, MAIL_FROM, MAIL_FROM_NAME, MAIL_TO
// Heslo SMTP sa nikdy neposiela do prehliadača ani nezapisuje do logu.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

/** Vráti zoznam chýbajúcich / neplatných nastavení SMTP (iba názvy premenných, nikdy hodnoty). */
function mail_config_problems(): array {
    $p = [];
    foreach (['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS'] as $k) if (!cfg($k)) $p[] = $k;
    $port = cfg('SMTP_PORT', '587');
    if (!ctype_digit((string)$port) || (int)$port < 1 || (int)$port > 65535) $p[] = 'SMTP_PORT';
    if (!in_array(strtolower(cfg('SMTP_SECURE', 'tls')), ['tls', 'ssl', 'none'], true)) $p[] = 'SMTP_SECURE';
    $from = cfg('MAIL_FROM', cfg('SMTP_USER'));
    if ($from && !filter_var($from, FILTER_VALIDATE_EMAIL)) $p[] = 'MAIL_FROM';
    if (cfg('MAIL_TO') && !filter_var(cfg('MAIL_TO'), FILTER_VALIDATE_EMAIL)) $p[] = 'MAIL_TO';
    return $p;
}
function mail_recipient(string $fallback = ''): string { return cfg('MAIL_TO', $fallback); }

/** Odstráni znaky, ktoré by umožnili vložiť ďalšie hlavičky (e-mail injection). */
function mail_header_safe(string $s, int $max = 200): string {
    return mb_substr(trim(preg_replace('/[\r\n\t\x00-\x1F\x7F]+/u', ' ', $s)), 0, $max);
}

/**
 * Odošle správu. $m = ['to', 'subject', 'body', 'reply_email', 'reply_name'].
 * Vráti ['ok' => bool, 'error' => kód] – kódy: config | auth | connect | recipient | send.
 */
function send_mail(array $m, ?callable $debug = null): array {
    if ($problems = mail_config_problems()) {
        error_log('[mail] SMTP nie je nakonfigurované, chýba/neplatné: ' . implode(', ', $problems));
        return ['ok' => false, 'error' => 'config', 'missing' => $problems];
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = cfg('SMTP_HOST');
        $mail->Port = (int)cfg('SMTP_PORT', '587');
        $mail->SMTPAuth = true;
        $mail->Username = cfg('SMTP_USER');
        $mail->Password = cfg('SMTP_PASS');
        $sec = strtolower(cfg('SMTP_SECURE', 'tls'));
        if ($sec === 'ssl') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        elseif ($sec === 'tls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        else { $mail->SMTPSecure = ''; $mail->SMTPAutoTLS = false; }
        // Overenie certifikátu servera. PHP na Windows nemá vlastný zoznam dôveryhodných certifikačných autorít,
        // preto je pribalený aktuálny zoznam Mozilla (includes/lib/cacert.pem). Vlastný súbor: SMTP_CAFILE v .env.
        $ca = cfg('SMTP_CAFILE') ?: (is_file(__DIR__ . '/lib/cacert.pem') ? __DIR__ . '/lib/cacert.pem' : null);
        if ($ca) $mail->SMTPOptions = ['ssl' => ['cafile' => $ca, 'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]];
        $mail->Timeout = 20;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_BASE64;
        if ($debug) { $mail->SMTPDebug = 2; $mail->Debugoutput = function ($str) use ($debug) {
            // nikdy nevypisuj prihlasovacie údaje (AUTH výmena je v base64) – povolené sú iba bežné príkazy
            if (str_starts_with($str, 'CLIENT -> SERVER: ') && !preg_match('/^CLIENT -> SERVER: (EHLO|HELO|STARTTLS|MAIL FROM|RCPT TO|DATA|QUIT|RSET)\b/i', $str)) {
                $str = preg_match('/^CLIENT -> SERVER: AUTH \w+/i', $str, $mm) ? $mm[0] . ' [skryté]' : 'CLIENT -> SERVER: [skryté]';
            }
            $debug(rtrim($str));
        }; }
        $from = cfg('MAIL_FROM', cfg('SMTP_USER'));
        $mail->setFrom($from, mail_header_safe(cfg('MAIL_FROM_NAME', 'Web OZ Divé krídla'), 80), false);
        $mail->Sender = $from;
        if (!$mail->addAddress($m['to'])) return ['ok' => false, 'error' => 'recipient'];
        if (!empty($m['reply_email']) && PHPMailer::validateAddress($m['reply_email'])) {
            $mail->addReplyTo($m['reply_email'], mail_header_safe($m['reply_name'] ?? '', 100));
        }
        $mail->Subject = mail_header_safe($m['subject'], 200);
        $mail->Body = $m['body'];
        $mail->isHTML(false);
        $mail->XMailer = ' ';
        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (MailerException $e) {
        $info = mb_substr(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\P{C}\s]/u', '', mb_convert_encoding(explode(' SMTP server error: QUIT', $mail->ErrorInfo)[0], 'UTF-8', 'UTF-8')))), 0, 400);
        $code = stripos($info, 'authenticate') !== false ? 'auth' : (stripos($info, 'connect') !== false ? 'connect' : 'send');
        $GLOBALS['MAIL_LAST_ERROR'] = str_replace((string)cfg('SMTP_PASS'), '***', $info);
        error_log('[mail] Odoslanie zlyhalo (' . $code . '): ' . str_replace((string)cfg('SMTP_PASS'), '***', $info));
        return ['ok' => false, 'error' => $code];
    }
}

/** Zrozumiteľné vysvetlenie chyby pre administrátora (príkazový riadok / sprievodca). */
function mail_error_help(?string $code): string {
    $d = $GLOBALS['MAIL_LAST_ERROR'] ?? '';
    return match ($code) {
        'auth' => "Server odmietol prihlásenie. Pri Gmaile skontroluj, že SMTP_USER je celá gmail adresa a SMTP_PASS je 16-písmenové HESLO APLIKÁCIE (nie bežné heslo do Gmailu).",
        'connect' => "Nepodarilo sa pripojiť k serveru " . cfg('SMTP_HOST') . ':' . cfg('SMTP_PORT', '587') . ". Skontroluj internetové pripojenie, SMTP_HOST / SMTP_PORT / SMTP_SECURE, prípadne či antivírus alebo firewall neblokuje odchádzajúcu poštu.",
        'config' => 'SMTP nie je nastavené – chýba: ' . implode(', ', mail_config_problems()),
        'recipient' => 'Adresa príjemcu (MAIL_TO) je neplatná.',
        default => 'Chyba pri odosielaní.',
    } . ($d !== '' ? "\nTechnický detail: $d" : '');
}
