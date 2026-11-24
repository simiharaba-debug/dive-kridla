# Biologická ochrana – OZ Divé krídla

Web napísaný iba v **PHP, HTML, CSS a JavaScripte** (bez build nástrojov a frameworkov). Databáza: **SQLite cez PDO**.

## Spustenie (lokálne)

Potrebuješ PHP 8.1+ s rozšíreniami `pdo_sqlite`, `gd` (WebP), `fileinfo`, `mbstring`, `exif`. Voliteľne `ffmpeg`/`ffprobe` (náhľady a kontrola videí).

```bash
cp .env.example .env          # vyplň ADMIN_USERNAME, ADMIN_EMAIL, ADMIN_PASSWORD (vlastné silné heslo)
php bin/migrate.php           # vytvorí databázu storage/app.sqlite
php bin/seed-admin.php        # vytvorí / aktualizuje administrátora z hodnôt v .env
php -d upload_max_filesize=200M -d post_max_size=210M -S localhost:8000 router.php
```

Otvor <http://localhost:8000>. Dôležité je `router.php` – vstavaný server PHP ignoruje `.htaccess`, tento smerovač preto blokuje interné súbory.

## Prihlásenie administrátora

Administrátor **nemá predvolené heslo v kóde**. Vzniká príkazom `php bin/seed-admin.php` z premenných `ADMIN_USERNAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` (v `.env` alebo v prostredí servera). Prihlasuješ sa e-mailom a heslom na `/prihlasenie`. Heslo zmeníš opätovným spustením skriptu s novou hodnotou. Verejná registrácia vytvára vždy iba rolu `user`.

## Štruktúra

```
index.php, odchyt-holubov.php, ...   verejné stránky
fotogaleria.php                      verejná galéria + správa pre admina
prihlasenie.php registracia.php odhlasenie.php ucet.php admin.php
kontakt-odoslat.php                  kontaktné formuláre (JSON / presmerovanie)
api/                                 JSON API pre správu médií (iba admin + CSRF)
includes/  config db auth passwords media security api functions header footer
data/                                texty a zoznamy (upravuj tu obsah)
migrations/                          SQL migrácie (001_init.sql, ...)
bin/                                 migrate.php, seed-admin.php (iba CLI)
assets/css/style.css                 štýly + témové tokeny (svetlý/tmavý režim)
assets/js/theme-init.js main.js      téma pred vykreslením; ostatné skripty
uploads/images videos thumbs         nahraté fotografie a videá
storage/app.sqlite                   databáza (mimo verejného prístupu)
```

## Databáza

Tabuľky: `users` (id, username, email, password_hash, role, created_at, updated_at), `media` (id, kind, title, description, filename, path, thumb_path, mime, size, width, height, uploaded_by, created_at, updated_at), `sessions`, `remember_tokens`, `auth_attempts`, `schema_migrations`. Zmeny schémy pridávaj ako nové súbory `migrations/00X_*.sql` a spusti `php bin/migrate.php`.

Pre MySQL/PostgreSQL stačí nastaviť `DB_DSN`, `DB_USER`, `DB_PASS`; migračný SQL súbor je však písaný pre SQLite (`AUTOINCREMENT`, `ON CONFLICT`), takže by sa musel upraviť.

## Bezpečnosť (čo je implementované)

- Heslá: Argon2id (`password_hash`), nikdy v čistom texte; politika min. 10 znakov + písmeno + číslo.
- Relácie v databáze, cookie `HttpOnly`, `SameSite=Lax`, `Secure` pri HTTPS, nové ID po prihlásení; „zapamätať si ma“ = náhodný selector + SHA-256 hash validátora, rotuje sa.
- Rola sa pri každej požiadavke číta z databázy. API aj stránky kontrolujú oprávnenia na serveri; CSRF token pri každom POST (formuláre aj API).
- Obmedzenie pokusov o prihlásenie (5 na e-mail / 15 min, 30 na IP) a registrácií.
- Pripravené dotazy PDO (SQL injection), výstup cez `htmlspecialchars` (XSS), CSP bez inline skriptov, `X-Frame-Options`, `nosniff`, `Referrer-Policy`.
- Nahrávanie: typ z obsahu súboru (finfo + magic bytes), povolené JPG/PNG/WebP/MP4/WebM, limity veľkosti a rozlíšenia, fotografie sa znovu zakódujú do WebP (skrytý kód zanikne), názvy súborov generuje server, `uploads/` nespúšťa skripty.

## Pred zverejnením na hosting

1. Nasaď cez **HTTPS** (cookie `Secure` a HSTS sa zapnú samé; za proxy nastav `TRUST_PROXY=1`).
2. Vytvor `.env` na serveri (nikdy ho nenahrávaj do repozitára) a spusti `migrate.php` + `seed-admin.php`.
3. Ak je to možné, nastav document root na priečinok projektu a **`storage/`, `includes/`, `data/`, `migrations/`, `bin/` a `.env` ukry** (v Apache to robí `.htaccess`; pri nginx pridaj `location ~ ^/(storage|includes|data|migrations|bin)/ { deny all; }` a `location ~ ^/uploads/.*\.php$ { deny all; }`). Databázu je najlepšie dať mimo webroot (`DB_PATH`).
4. Skontroluj limity PHP (`upload_max_filesize`, `post_max_size`, `memory_limit`) – pozri `.htaccess` / `.user.ini`.
5. Over odosielanie pošty (`mail()`) pre kontaktné formuláre; adresát je `form_email` v `data/site.php`.
6. Zálohuj `storage/app.sqlite` a `uploads/`.

## Kontaktný formulár – odosielanie e-mailov (SMTP)
Formulár odosiela skutočný e-mail cez SMTP (PHPMailer v `includes/lib/PHPMailer`). Nastavenie je iba v `.env`:
`SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE` (tls/ssl/none), `SMTP_USER`, `SMTP_PASS`, voliteľne `MAIL_FROM`, `MAIL_FROM_NAME`, `MAIL_TO`.
E-mail návštevníka sa nastaví ako Reply-To. Ochrana: validácia, honeypot, limit 5 správ / 15 min a 20 / deň z jednej IP, odmietnutie zalomení riadkov v hlavičkových poliach.
Ak SMTP nie je nastavené alebo zlyhá, návštevník dostane jasnú chybu – úspech sa nikdy nepredstiera.
Test: `tools\php\php.exe -c tools\php\php.ini bin\test-mail.php --debug` (heslo sa nevypisuje).

## Moja administrátorská galéria (súkromná)
Stránka `moja-galeria.php` – iba pre prihláseného administrátora (rola sa overuje z databázy pri každej požiadavke).
Súbory sú v `storage/private-media` (mimo webu), záznamy v tabuľke `private_media` (verejná galéria: `uploads/` + `media`).
Súbory sa odovzdávajú iba cez `private-media.php`, ktorý overí administrátora; priamy prístup cez URL je zablokovaný.
Stránka je `noindex`, nie je v menu, pätičke ani v zozname stránok.

## Zmena hesla administrátora cez .env
1. V `.env` zmeň `ADMIN_PASSWORD` (prípadne `ADMIN_EMAIL`).
2. Spusti `START-WEB.bat` – pri štarte sa heslo bezpečne prenesie do databázy (`bin/sync-admin-password.php --if-changed`),
   alebo ručne: `tools\php\php.exe -c tools\php\php.ini bin\sync-admin-password.php`.
Aktualizuje sa existujúci administrátor (žiadny duplicitný účet), heslo sa zahashuje (Argon2id), staré prihlásenia sa odhlásia.
Kontrola bez zmeny: `bin\sync-admin-password.php --check`. Bežný registrovaný účet sa nikdy nepovýši na administrátora.
Pôvodná chyba: `START-WEB` volal `seed-admin.php --if-missing`, ktorý pri existujúcom administrátorovi nič nemenil – nové heslo z `.env` sa do databázy nikdy nedostalo (a `seed-admin.php` bez prepínača padal na duplicitnom mene, lebo hľadal admina podľa zmeneného e-mailu).

### Najjednoduchšie nastavenie e-mailu
Dvakrát klikni na `NASTAVIT-EMAIL.bat`, zadaj Gmail adresu a 16-písmenové heslo aplikácie Google. Sprievodca uloží údaje do `.env` (pôvodný súbor zálohuje ako `.env.bak-…`) a hneď odošle testovaciu správu.
PHP na Windows nemá vlastné certifikáty – preto je pribalený zoznam `includes/lib/cacert.pem` (Mozilla); pripojenie k SMTP je vždy overené.
