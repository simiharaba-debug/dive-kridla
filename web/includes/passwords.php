<?php
function hash_password(string $p): string {
    return password_hash($p, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);
}
/** Vráti text chyby alebo null, ak je heslo v poriadku. */
function password_policy_error(string $p, string $username = '', string $email = ''): ?string {
    if (mb_strlen($p) < 10) return 'Heslo musí mať aspoň 10 znakov.';
    if (mb_strlen($p) > 200) return 'Heslo je príliš dlhé (najviac 200 znakov).';
    if (!preg_match('/\p{L}/u', $p) || !preg_match('/\d/', $p)) return 'Heslo musí obsahovať aspoň jedno písmeno a jedno číslo.';
    if ($username !== '' && mb_strtolower($p) === mb_strtolower($username)) return 'Heslo nesmie byť rovnaké ako používateľské meno.';
    if ($email !== '' && mb_strtolower($p) === mb_strtolower($email)) return 'Heslo nesmie byť rovnaké ako e-mail.';
    return null;
}
