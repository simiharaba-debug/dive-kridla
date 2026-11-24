# Databáza, účty a prihlasovanie na viacerých počítačoch

## Čo bolo zle (zistené 13. 11. 2026)

Web ukladá účty (registrácia, administrátor, prihlásenia) do databázy **SQLite – súboru `storage\app.sqlite`**.
Na inom počítači sa stali tri veci naraz:

1. **Databáza sa nevytvorila.** PHP pri prvom pripojení vytvorí prázdny súbor `app.sqlite`, ale tabuľky vznikali
   iba príkazom `bin\migrate.php` (robil ho len START-WEB.bat). Keď sa web spustil inak, stránky sa zobrazili,
   ale registrácia aj prihlásenie skončili chybou `no such table: sessions` (biela stránka / chyba 500).
   Overené: na čistej kópii projektu presne toto nastane.
2. **Súbor `.env` a databáza sa nekopírujú s projektom** (sú v `.gitignore` – správne, obsahujú heslá a osobné údaje).
   Na novom počítači START-WEB.bat vytvoril nový `.env` s **novým náhodným heslom** administrátora
   (uloží ho do `Dokumenty\Biologicka-ochrana-admin-prihlasenie.txt`). Staré heslo z prvého počítača tam neplatí.
3. **`tools\php\php.ini` mal natvrdo cesty `C:\Users\simih\...`.** Na počítači s iným používateľom alebo priečinkom
   sa nenačítali rozšírenia (pdo_sqlite) – START-WEB.bat potom PHP sťahoval znova.

## Čo je opravené

- Databáza sa **pripraví sama** pri prvej návšteve: chýbajúce tabuľky sa vytvoria a ak nie je žiadny administrátor,
  vytvorí sa z `ADMIN_USERNAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` v `.env` (zahashované heslo, existujúce účty sa nemenia).
- `php.ini` sa pri každom spustení zapíše s cestami podľa aktuálneho umiestnenia projektu.
- Namiesto bielej stránky sa zobrazí zrozumiteľná chyba (technické detaily iba na localhost).
- **DIAGNOSTIKA.bat** / `http://localhost:8000/diagnostika.php` – skontroluje PHP, `.env`, databázu, tabuľky,
  administrátora, či heslo v `.env` sedí s databázou a zápis relácií. Heslá nevypisuje.
- **ZALOHA-DATABAZY.bat** a **OBNOVIT-DATABAZU.bat** – prenos účtov a galérie medzi počítačmi.
- Podpora **PostgreSQL** ako spoločnej databázy (`migrations\pgsql\`).

Heslo administrátora **nie je** v PHP kóde. Pri prihlásení sa vždy overuje hash v databáze; `.env` slúži len na vytvorenie
alebo zmenu administrátora.

## Situácia A – projekt skopírovaný na dva počítače (každý má vlastnú databázu)

Účty vytvorené na prvom počítači na druhom **automaticky neexistujú** – každý počítač má vlastný `storage\app.sqlite`.
Ak chceš na druhom počítači rovnaké účty:

1. Na prvom počítači spusti **ZALOHA-DATABAZY.bat** → vznikne `storage\zalohy\app-RRRRMMDD-HHMMSS.sqlite`.
2. Tento súbor (a ideálne aj `.env`) skopíruj na druhý počítač do rovnakých priečinkov.
3. Na druhom počítači spusti **OBNOVIT-DATABAZU.bat** a potom START-WEB.bat.

Pozor: odvtedy sú to opäť dve oddelené kópie – nový účet na jednom počítači sa na druhom neobjaví.
Nahrané fotky sú v `uploads\` a `storage\private-media\` – tie skopíruj tiež, ak ich chceš mať na oboch.

## Situácia B – jeden spoločný web pre všetky počítače

Aby sa z rôznych počítačov dalo prihlásiť rovnakým účtom, web musí bežať **na jednom serveri** (hosting) a všetci
ho otvárajú cez jeho adresu (napr. `https://www.divekridla.sk`). Lokálne `http://localhost:8000` je vždy len daný počítač.

- **Jeden server (bežný webhosting):** SQLite stačí. Nahraj projekt, vytvor na serveri `.env` s ADMIN_* (a SMTP),
  databáza a administrátor sa vytvoria pri prvej návšteve. Odporúčanie: `DB_PATH` mimo verejného priečinka.
- **Viac serverov alebo spravovaná databáza:** PostgreSQL. Do `.env` doplň:
  ```
  DB_DSN=pgsql:host=ADRESA_SERVERA;port=5432;dbname=NAZOV_DB;sslmode=require
  DB_USER=...
  DB_PASS=...
  ```
  Tabuľky sa vytvoria samé (`migrations\pgsql\`). Databáza musí povoliť rozšírenie `citext`
  (Neon, Supabase, Aiven aj väčšina hostingov ho majú). Overené lokálne s PostgreSQL 16: registrácia na jednej inštancii
  webu a prihlásenie na druhej, administrátor aj „zapamätať si ma“ fungujú.

### Čo ešte chýba na nasadenie (rozhodnutie a údaje od teba)
- hosting s PHP 8.1+ (pdo_sqlite alebo pdo_pgsql, gd, mbstring, openssl) a doména s HTTPS,
- pri PostgreSQL: prístupové údaje k databáze (host, názov, používateľ, heslo),
- `.env` na serveri (ADMIN_*, SMTP pre kontaktný formulár).
Žiadne verejné nasadenie ani pripojenie k externej databáze zatiaľ nebolo urobené.

## Užitočné príkazy (z priečinka `web`)
```
DIAGNOSTIKA.bat                     kontrola databázy a prihlasovania
ZALOHA-DATABAZY.bat                 záloha do storage\zalohy
OBNOVIT-DATABAZU.bat [súbor]        obnova (najnovšia záloha alebo zadaný súbor)
tools\php\php.exe -c tools\php\php.ini bin\sync-admin-password.php   nastaví heslo admina podľa .env
```
