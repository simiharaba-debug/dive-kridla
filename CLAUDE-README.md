# CLAUDE-README – pracovné poznámky k projektu Biologická ochrana PHP

Tento súbor si Claude udržiava aktuálny, aby pri ďalšej práci vedel, čo v projekte je, ako sa spúšťa
a čo už bolo zmenené. Po každej väčšej úprave doplniť sekciu „Denník zmien“.
(Kópia je aj v Claude projekte „stranka biologicka ochrana“ ako claude/CLAUDE-README.md.)

## ZAČNI TU – rýchla orientácia (stav 25. 11. 2026)
- **Aktuálna kópia: `C:\Users\simih\Desktop\dive-kridla`** (git, vetva main, GitHub `simiharaba-debug/dive-kridla`, repozitár je VEREJNÝ).
  `Desktop\biologicka-ochrana-php` je staršia kópia z 23. 11. – neupravovať. Staré cesty nižšie v texte sú historické.
- Pred prácou: `git fetch` + `git status -sb` (používateľ pracuje aj na inom zariadení). V gite NIE SÚ: `web/.env`, `web/storage/*` (databáza),
  `web/uploads/*`, `web/tools`, `_zaloha`. Na inom zariadení ich treba preniesť ručne.
- Zdroj webu = `web/`. Súbory `*.html` v koreni sú VYGENEROVANÉ (GitHub Pages) – neupravovať ručne.
- Čistý PHP 8.1+ / CSS / JS, bez frameworku, bez npm, bez buildu. Databáza SQLite, migrácie sa spúšťajú samé.
- `.env` nikdy nevypisovať (heslá); zistiť sa smie len to, ktoré kľúče sú vyplnené.

### Ako vzniká stránka
`stránka.php` → `includes/functions.php` (načíta data/site.php, data/services.php, nastavenia z DB) → `page_start('slug')` (header.php, pri admin
stránkach admin_header.php) → `page_hero()` → obsah z `data/*.php` → `page_end()` (footer.php) → `speed_output()` (lazy load, gzip).

**Najprv zisti, či hodnotu neprepisuje databáza.** Databáza (administrácia) má prednosť pred `data/*.php`. Prepisuje sa: texty úvodu, kontakt,
SEO, oznámenie, farby, vzhľad a efekty (`site_settings`), hlavné fotky a náhľady (`page_photos`, `page_photo_pos`), kategórie galérie
(`gallery_categories`, `gallery_overrides`), pridané videá (`videos`), nahraté fotky (`media`).

### Stránka → šablóna → texty
| Adresa | Šablóna (web/) | Texty (web/data/) |
|---|---|---|
| `/` | `index.php` (starý dizajn služieb: `includes/home_predosly.php`) | služby a témy priamo v index.php; nadpis/perex/výzva `HOME_DEFAULTS` v `includes/site_settings.php`; čísla `services.php` |
| `/odchyt-holubov` | `odchyt-holubov.php` | `services.php` |
| `/vypustanie-sokolov` | `vypustanie-sokolov.php` | `services.php`, `edukacia.php` |
| `/biologicka-ochrana` | `biologicka-ochrana.php` | `services.php` |
| `/odchov-sokolov` | `odchov-sokolov.php` | `sokol.php`, `odchov.php`, `sokoliarstvo.php` |
| `/vseobecne-sokoliarstvo` | `vseobecne-sokoliarstvo.php` | `sokoliarstvo.php` |
| `/rehabilitacia-vtakov` | `rehabilitacia-vtakov.php` | `rehabilitacia.php`, `sokoliarstvo.php` |
| `/anatomia-dravcov` | `anatomia-dravcov.php` | `anatomia.php` |
| `/zdravie-dravcov` | `zdravie-dravcov.php` | `zdravie.php` |
| `/nieco-o-nas` | `nieco-o-nas.php` | `o-nas.php` |
| `/fotogaleria`, `/moja-galeria` | `includes/gallery_page.php` | databáza; pôvodné `media.php`, `videos.php` |
| `/priame-kontaktovanie` | `priame-kontaktovanie.php` | `site.php` (contact) |
| odoslanie formulárov | `kontakt-odoslat.php` → `includes/mailer.php` | SMTP v `.env` |
| účty | `prihlasenie` `registracia` `ucet` `odhlasenie` | `includes/auth.php` |
| administrácia | `admin` `admin-nastavenia` `admin-fotky` `admin-pouzivatelia` | `includes/admin_*.php` |

Ďalšie dáta: `galerie.php` (fotky v galérii podstránky), `page_photos.php` (pôvodné hlavné fotky), `site.php` (menu, SEO, kontakt).
Nepoužívané: `data/galeria.php`, `_stiahni-webnode.php`.

### Chcem zmeniť… → kam ísť
| Zmena | Kde |
|---|---|
| telefón, e-mail, text kontaktu | Administrácia → Nastavenia → Kontakt; pôvodné `data/site.php` |
| kam chodia správy z formulára | `.env` `MAIL_TO` |
| nadpis a perex úvodu | Administrácia → Úvodná stránka; pôvodné `HOME_DEFAULTS` |
| karty služieb / témy na úvode | `index.php` (`$cards`, `$topics`) |
| menu, názvy stránok | `data/site.php` `pages`: `main`/`nav` = hlavné menu, ostatné „Ďalšie“, `hidden` = nikde |
| nová podstránka | `nazov.php` (skopíruj `anatomia-dravcov.php`) + `data/site.php` + `data/page_photos.php` + dlaždica v index.php |
| hlavička / pätička / logo | `includes/header.php`, `includes/footer.php`, `includes/logo.php` + `assets/favicon.svg` |
| farby | Administrácia → Farby; kód bo-ui.css časť 13, `PALETTE_*` v site_settings.php |
| písmo, základ, formuláre | `assets/css/style.css`; veľkosť rozhrania fx.css časť 17 |
| úvod – vzhľad / pohyb | `assets/css/home.css` / `assets/js/home.js` |
| animácie, kurzor, opona, náklon | `assets/css/fx.css` + `assets/js/fx.js` (očíslované časti) |
| galéria „+N“, lightbox, okno videa | `assets/js/bo-ui.js` + bo-ui.css časti 4–6; HTML `includes/bo_ui.php` |
| mobilné menu, téma, toasty, formuláre | `assets/js/main.js`; prvé vykreslenie `assets/js/theme-init.js` |
| vzhľad administrácie | `assets/css/admin.css` + `assets/js/admin.js`; menu `includes/admin_ui.php` |
| správa galérie | `includes/gallery.php` (logika), `includes/gallery_page.php` (HTML), `assets/js/bo-gallery-admin.js`, bo-ui.css časť 15 |
| hlavné fotky stránok, výrez | `admin-fotky.php`, `includes/page_photos.php`, `assets/js/bo-page-photos.js`, bo-ui.css časti 16 a 22 |
| nová fotka v kóde | `assets/img/ID.webp` + `ID-sm.webp` + riadok v `data/media.php` → `OBRAZKY-VARIANTY.bat` → `KONTROLA-FOTIEK.bat` |
| nové video | Fotogaléria → Pridať video, alebo `data/videos.php` |
| etapy odchovu | `data/sokol.php`, bo-ui.css časti 7 a 19 |
| práva, prihlásenie, limity pokusov | `includes/auth.php`, `includes/passwords.php` |
| nahrávanie, veľkosť súborov | `includes/media.php`, `.env` `MAX_IMAGE_MB`, `.user.ini`, `.htaccess` |
| bezpečnostné hlavičky (CSP) | `includes/security.php` |
| databázová zmena | nový `migrations/00X_*.sql` **aj** `migrations/pgsql/00X_*.sql` |
| adresy bez .php, presmerovania | `.htaccess` (hosting) **aj** `router.php` (lokálne) – meniť oba |
| SEO: canonical, schema.org, sitemap, overenie Google | `includes/seo.php`, `sitemap.php`, `robots.txt`; titulky Administrácia → SEO, pôvodné `data/site.php` (titulok do 60, popis do 160 znakov) |
| časté otázky | `data/faq.php` (iba fakty z webu) |
| doména webu | `.env` `SITE_URL` (predvolené https://divekridla.sk), `robots.txt`, www presmerovanie v `.htaccess` |
| rýchlosť, lazy load | `includes/speed.php`, `assets/js/speed.js` |

Poradie CSS: `style.css` → `bo-ui.css` → `fx.css` → `home.css` (len úvod) → paleta z DB; neskoršie prebíja skoršie.
V bo-ui.css je číslo 14 dvakrát (telefón; videá) a farby z častí 1a/10/11 prepisuje časť 13 – farby meniť v 13.

### Po každej zmene
1. Syntax: `web\tools\php\php.exe -l web\subor.php`.
2. START-WEB.bat → pozrieť na PC aj 390 px, svetlý aj tmavý režim, konzola bez chýb. Pri fotkách aj KONTROLA-FOTIEK.bat.
3. Doplniť Denník zmien (a túto mapu, ak sa zmenila štruktúra).
4. Commit → push. (GitHub Pages používateľ vypol 25. 11. 2026, `OBNOVIT-ONLINE.bat` sa už nespúšťa; `*.html` v koreni sú zastarané.)
5. Po spustení hostingu: nahrať zmenené súbory (nižšie).

## Hosting – výber a nasadenie (pripravené 25. 11. 2026, ZATIAĽ NENASADENÉ)
- Odporúčaný balík: **Webhosting Start** (5 GB, 1 doména). Web na serveri zaberie asi 75 MB, videá sú na YouTube, MySQL netreba (SQLite).
  V ponuke Startu nie sú denné zálohy → zálohovať databázu a fotky ručne. Poskytovateľ zatiaľ nepotvrdený.
- Nutné: PHP 8.1+ (lokálne 8.3) s `pdo_sqlite`, `gd` + WebP, `mbstring`, `fileinfo`, `openssl`, `exif`; Apache alebo kompatibilný server
  s `.htaccess` a mod_rewrite; FTPS/SFTP; SSL; zapisovateľné `storage/` a `uploads/`; odchádzajúce SMTP (587/465).
  Na MySQL web nefunguje (podporované je SQLite a PostgreSQL).

### Prvé nasadenie
1. Koreň domény = OBSAH priečinka `web/` (nie celý repozitár, nie `*.html` z koreňa).
2. Nenahrávať: `tools/`, `*.bat`, `*.ps1`, `router.php`, `_stiahni-webnode.php`, `.env.bak*`, `storage/*.log`, `storage/server.*`,
   `assets/online/`. Priečinok `bin/` treba len pri prístupe cez SSH.
3. Databáza: kópia cez `ZALOHA-DATABAZY.bat` → nahrať ako `storage/app.sqlite` (nie priamo otvorený súbor – WAL).
   Vopred odstrániť testovací účet `test-claude` (so súhlasom používateľa). Nahrať aj `uploads/` a `storage/private-media/`.
4. `.env` pre server: ADMIN_*, SMTP_*, MAIL_*; heslá vypĺňa používateľ sám. Ideálne `DB_PATH` a `PRIVATE_MEDIA_DIR` mimo webroot.
   Ak HTTPS končí na proxy: `TRUST_PROXY=1`.
5. SSL zapnúť pred prvou návštevou cez https (web posiela HSTS aj pre subdomény), potom presmerovanie http → https; `.htaccess` ho zatiaľ nemá.
6. Chyba 500 hneď po nahratí: preveriť v `.htaccess` a `uploads/.htaccess` riadky `Options` a `Header` mimo IfModule.
   `.user.ini` žiada `memory_limit=768M` – hosting môže mať nižší strop.

### Kontrola po nasadení
- Všetky stránky 200, `/odchyt-holubov.php` → 301, neexistujúca adresa 404, http → https.
- Zablokované musia byť (403/404): `/.env`, `/storage/app.sqlite`, `/includes/db.php`, `/data/site.php`, `/migrations/001_init.sql`, `/bin/migrate.php`, `/README.md`.
- Prihlásenie admina (robí používateľ), nahratie a zmazanie fotky, odoslanie formulára, telefón 390 px.
- Potom: presmerovanie http → https, `/sitemap.xml` a `/robots.txt` = 200, www → bez www, Search Console (overiť doménu, odoslať sitemap.xml), Bing.
  Vypnúť testovacie oznámenie „Vyber oznamenie“ (je v databáze).

### Aktualizácia po spustení
**Pravda je potom databáza na serveri.** Nikdy neprepísať serverové `.env`, `storage/`, `uploads/`. Nahrávať len zmenený kód
(`git diff --name-only <nasadený commit> -- web`), predtým stiahnuť zálohu. Nasadený commit zapisovať do Denníka zmien.

## Projekt
- Web OZ Divé krídla – biologická ochrana pomocou dravých vtákov (renovácia viacerých starších stránok).
- Na webe je uvedený len Daniel Haraba (tel. 0907 419 111), žiadne iné osoby ani odkazy na iné organizácie.
- Umiestnenie: pozri „ZAČNI TU“.
- Fotky na spracovanie: `C:\Users\simih\Desktop\fotky cerpanie`

## Spustenie
- `web\START-WEB.bat` (spúšťa `bin\windows-start.ps1`, pribalené PHP v `tools\php`), zastavenie `web\STOP-WEB.bat`.
- Web beží na http://localhost:8000 (port v `storage\server.port`, log `storage\server.log`).
- Ak server nebeží, najprv spustiť START-WEB.bat, až potom testovať v prehliadači.
- Vstavaný server PHP ignoruje .htaccess – interné priečinky blokuje `router.php`.

## Pravidlá pri úpravách
- Pred väčším zásahom vytvoriť zálohu (`_zaloha\RRRR-MM-DD_HHMM\`), nič nemazať bez kontroly.
- Nemazať fotografie, ktoré sú súčasťou obsahu webu, ani pôvodné videá (len ich nenačítavať automaticky).
- Prihlasovanie/databáza: 13. 11. 2026 večer opravené na žiadosť používateľa (kolo 2). Ďalšie zásahy len s dôvodom a testom.
- Heslá nikdy natvrdo v PHP kóde.
- Fakty v textoch len z overených zdrojov (pôvodný web, sokol-edukacia1.webnode.sk, tri prezentácie používateľa).
- Na webe sa NIKDY nespomína, že texty/fotky/videá pochádzajú z prezentácie.
- **Každá fotografia na webe musí byť v `data/media.php`** (tým je automaticky vo fotogalérii). Po zmene spusti `KONTROLA-FOTIEK.bat`.
- Formulácia: „Sokol sťahovavý je najrýchlejší stavovec na svete pri strmhlavom lete.“
- Na fotkách z webnode galérie je väčšinou jastrab lesný – nepopisovať ich ako sokola.
- CSP (`includes/security.php`) nepovoľuje inline skripty – JS len v súboroch v `assets/js`.
- **Pri KAŽDEJ úprave grafiky skontrolovať aj telefón** (390 px, svetlý aj tmavý režim, celá stránka – hlavička, úvod, pätička,
  žiadne horizontálne posúvanie) a chyby hneď opraviť. Požiadavka používateľa z 22. 11. 2026.
- Logo = jednoduché SVG pierko (od 22. 11. 2026 22:45, na žiadosť používateľa; hlava jastraba zamietnutá), `includes/logo.php`
  (`logo_svg()`, `hawk_svg()` je len alias), rovnaký tvar v `assets/favicon.svg`.

## Štruktúra projektu (web\)
- Stránky: `index.php`, `odchyt-holubov.php`, `vypustanie-sokolov.php`, `biologicka-ochrana.php`, `odchov-sokolov.php`,
  `vseobecne-sokoliarstvo.php`, `rehabilitacia-vtakov.php`, `nieco-o-nas.php`, (`edukacia-v-odchove-dravych-vtakov.php` = len 301 na vypustanie-sokolov#metodika),
  `anatomia-dravcov.php`, `zdravie-dravcov.php`, `fotogaleria.php`, `priame-kontaktovanie.php`; účty/admin: `prihlasenie`, `registracia`, `ucet`, `admin`, `moja-galeria`.
- `includes/` – `functions.php` (page_start/page_end, photo_grid, section_head…), `header.php`, `footer.php`,
  `security.php` (CSP), `auth.php`, `db.php`, `media.php`, `mailer.php`, **`bo_ui.php`** (bo_gallery, bo_video, bo_videos), **`videos.php`** (YouTube videá – evidencia, pridávanie adminom, JSON-LD).
- `data/` – texty a zoznamy: `site.php` (stránky, kontakt), `services.php`, `edukacia.php`, `odchov.php`, `videos.php`,
  **`media.php`** (jednotná evidencia fotiek + kategórie galérie), `galerie.php` (len ID fotiek pre galérie podstránok),
  **`sokol.php`** (projekt SOKOL), **`anatomia.php`**, **`zdravie.php`**, `kanada.php` (pôvodné názvy fotiek z Kanady).
  `galeria.php` sa už nepoužíva (prázdny stub).
- `assets/css/style.css` + **`assets/css/bo-ui.css`** (+ `fx.css` pri novom dizajne), `assets/js/theme-init.js`, `main.js` + **`assets/js/bo-ui.js`** (+ `fx.js`).
- `assets/img/ID.webp` (veľká) + `ID-sm.webp` (náhľad) – helper `img_full()` / `img_thumb()`.
- `assets/video/` – zmazané 17. 11. 2026 (všetky videá sú z YouTube). `storage/app.sqlite` – databáza.
- `tools/php` – pribalené PHP (bez ICU knižníc, nie sú potrebné); `tools/ffmpeg` zmazaný, START-WEB ho už nesťahuje.

## Farby webu – DVOJFAREBNÁ PALETA (od 15. 11. 2026, požiadavka používateľa)
- Celý web len z dvoch farieb a ich odtieňov: svetlý režim **#efe7d8**, tmavý režim **#3b3329**; druhá farba = text a akcenty.
- Definované v poslednej časti `assets/css/bo-ui.css` („13. DVOJFAREBNÁ PALETA“) – prepisuje staršie farby zo style.css
  a z častí 1a/10/11 (zelené odtiene už nikde). Tmavé pásy (hero podstránok, pätička, karty s cenou…) sú v oboch režimoch hnedé
  s krémovým textom. Logo v hlavičke, favicon, theme-color (header.php, main.js) a chybová stránka v rovnakých farbách.
- Nepoužívať zelenú ani iné farby; nové prvky stavať z tokenov `--bg --surface --tint --text --muted --accent --panel`.

## UI moduly (assets/css/bo-ui.css, assets/js/bo-ui.js) – integrované 13. 11. 2026
- Načítavajú sa v `includes/header.php` po style.css / main.js.
- Paleta: časť „1a. Paleta webu“ v bo-ui.css prepisuje tokeny style.css (--bg, --surface, --accent…).
  Svetlý: krémová #f6f1e7, béžová #efe7d8, karty #fbf8f2, akcent #4f6b4a.
  Tmavý: uhlová #161716, povrchy #232522, text #e9e4d8, šalviový akcent #9fb493.
  Návrat k pôvodným farbám = zmazať časť 1a. Tmavý režim prepína main.js (kľúč localStorage `dk-theme`).
- Karty podstránok: `<a class="bo-card">` + `.bo-card__media` / `__title` / `__text` / `__more` (úvodná stránka,
  `bo-cards--3` = 3 stĺpce na desktope; na mobile kompaktné vodorovné karty).
- Tematická galéria: v PHP `bo_gallery($items, $visible, $visibleMobile, $label)`; položky `['id'=>..., 'caption'=>...]`.
  Na poslednej viditeľnej „+N ďalších fotografií“, klik otvorí lightbox (šípky, swipe, Esc, náhľady).
- Video: `bo_videos(['kľúč1','kľúč2'], 'row'|'grid', 'popis skupiny')` alebo `bo_video('kľúč')` – kľúče z `data/videos.php` (pozri sekciu Videá nižšie).
  YouTube: CSP povoľuje `img-src https://i.ytimg.com` a `frame-src https://www.youtube-nocookie.com`.
- Animácie: `data-reveal` (bo-ui.js) aj pôvodné `.reveal` (main.js).

## Obsah sekcie SOKOL – edukácia odchovu dravých vtákov
- Od 13. 11. 2026 (večer) je to stránka **`odchov-sokolov.php`** (v menu „SOKOL – odchov“) – podľa zadania „v sekcii odchovu“.
  Obsah: projekt (perex, prečo odchov, „Vedeli ste?“, čo robíme), 6 etáp odchovu s fotkami (+N, lightbox) a videami
  na kliknutie, galéria mláďat, chovný pár rároha, hniezdna biológia, poďakovanie ZVF.
- Metodika vypúšťania sokolov je od 22. 11. 2026 súčasťou `vypustanie-sokolov.php` (pozri „Zmeny obsahu (22. 11. 2026)“).
- Texty a fotky v `data/sokol.php`. 24 fotiek z webnode stiahnutých cez PC používateľa (jednorazový skript, potom vypnutý)
  → `assets/img/sokol-01…24`, originály v `biologicka-ochrana-php\_import\webnode\`. Na fotkách je jastrab lesný.
- Texty SKS projektu z `data/odchov.php` (intro, why) sa už na stránke nezobrazujú (pravidlo „žiadne iné organizácie“),
  súbor ostal nezmenený.
- Video „Vypustenie sokola“ = `fotky cerpanie\VID_20240927_093510291.mp4` → web verzia `assets/video/vypustenie-sokola.mp4`
  (1280 px, 2 MB); originál ostal nedotknutý. Poster/hero: `vypustenie-sokola.jpg` / `assets/img/vypustenie-sokola.webp`.

## Fotky „biologicka ochrana Kanada“
- 24 vybraných z 108 (čučoriedkové polia, čerešňové a ovocné sady, práca s vábidlom) → `assets/img/kanada-01…24`.
- Zoznam s popismi a pôvodnými názvami súborov: `data/kanada.php`; galéria na `biologicka-ochrana.php#v-praxi`.
- Vynechané: duplicitné a takmer rovnaké zábery. Ďalšie sa dajú doplniť rovnakým postupom.

## Fotky a galérie (kolo 2)
- Tematické galérie podstránok: `data/galerie.php` + `gallery_block()` v `includes/bo_ui.php`. Každá fotka je len na jednej podstránke.
- Odchyt holubov: odchyt/povala/neporiadok · Vypúšťanie: fotopasca pp-32…37 a mladé sokoly pp-9…11 · Biologická ochrana: Kanada (16)
  · Sokoliarstvo: Kanada portréty sokolov (8) · Rehabilitácia: `rehab-01…08` (z prezentácie Choroby dravcov, ~700 px – nízke rozlíšenie)
  · O nás: Kanada 2006 (`kanada-25…32`) · SOKOL: `sokol-01…24`.
- Zábery z fotopasce pp-17…54 orezané o pásik WACHMAN (GPS súradnice, dátum) – vyzerali ako screenshoty. Originály v `_zaloha\2026-11-13_2005`.
- Prezentácia ANATÓMIA: len diagramy/obrázky z cudzích zdrojov (vodoznaky) – nepoužité. Choroby dravcov: použité len neodpudivé
  vyšetrenia; pitvy a parazity nie.
- Popisy pod fotkami odstránené: v hero sa zobrazia len pri prejdení kurzorom, inak v lightboxe.
- Hlavné fotky: O nás = vypustenie sokola (video 2024), SOKOL = samica jastraba s mláďaťom, Biologická ochrana = farma-3,
  Sokoliarstvo = kanada-13. Od 22. 11. 2026 hlavné fotky a náhľady určuje `data/page_photos.php` / admin (pozri „Fotografie stránok“).

## Fotogaléria a evidencia fotiek (15. 11. 2026)
- `data/media.php`: `categories` (slug => názov, popis, obal) a `photos` (ID => [kategória, alt text]); súbor = `assets/img/ID.webp` + `ID-sm.webp`.
  Novú fotku pridáš jedným riadkom – objaví sa v galérii aj s popisom (popisy na podstránkach sa berú odtiaľto).
- `img_full()` / `img_thumb()` zaznamenávajú použitie; fotka mimo evidencie sa zapíše do logu `[galeria] …`.
- `fotogaleria.php` = prehľad kategórií; `fotogaleria.php?kategoria=SLUG` = fotky kategórie (lightbox bo-ui.js, šípky, swipe, Esc),
  `?kategoria=videa` = všetky videá (MP4 + YouTube, načítanie po kliknutí), `?kategoria=nove` = súbory nahraté adminom (DB) + správa galérie
  (karta sa verejne zobrazí, až keď existuje aspoň 1 nahratý súbor). Odkazy „Správa galérie“ vedú na `?kategoria=nove#sprava`.
- Pôvodných 11 kategórií: odchyt-holubov, povaly, vypustanie-sokolov, volny-let, biologicka-ochrana, sokoliarstvo, chovne-pary, rozmnozovanie,
  liahnutie, mladata, veterina. Od 17. 11. ich spravuje admin v galérii (pozri „Správa galérie – kategórie“).
- Kontrola: `KONTROLA-FOTIEK.bat` (= `bin/kontrola-fotiek.php`) – spustí dočasný server, prejde všetky stránky a kategórie,
  porovná fotky na webe s galériou, overí súbory, duplicity, odkazy a zmienky o prezentácii. Výsledok: `storage/kontrola-fotiek.txt`.
- `tim-daniel/ivan/michal/miro.webp` sa na webe nepoužívajú (pravidlo len Daniel Haraba) – preto nie sú v galérii.

## Obsah z troch prezentácií (15. 11. 2026)
- ANATÓMIA, MORFOLÓGIA… → nová stránka `anatomia-dravcov.php` (texty `data/anatomia.php`). Obrázky z prezentácie (schémy z cudzích
  zdrojov s vodoznakmi) nepoužité – stránka používa vlastné fotky.
- Choroby dravcov – veterinárna starostlivosť → nová stránka `zdravie-dravcov.php` (`data/zdravie.php`); konkrétne lieky a dávky zámerne
  neuvedené. Galéria rehab-* presunutá z Rehabilitácie sem (Rehabilitácia odkazuje na Zdravie).
- SOKOL – edukácia odchovu (310 MB, takmer bez textu) → `odchov-sokolov.php`: nové sekcie Podmienky chovu, Prirodzený a umelý odchov
  (inseminácia), Na čo si dať pozor; doplnené fotky v etapách a mláďatách. 41 fotiek `odchov-01…41` (otočené podľa snímok, bez EXIF,
  max 1600 px) a 5 videí `assets/video/odchov-*.mp4` (H.264, ≤ 960/1280 px). Vynechané: fotky a video s dieťaťom, logá fondov,
  uhynuté embryo/korisť, duplicity webnode fotiek a YouTube videí (spermie, monitor vajca, liahnutie, kŕmenie).

## Databáza a prihlasovanie (kolo 2) – pozri aj web\NAVOD-DATABAZA.md
- Príčina problému na inom PC: tabuľky vznikali len cez CLI migráciu → „no such table: sessions“; nový `.env` = nové heslo admina;
  php.ini s natvrdo zapísanými cestami C:\Users\simih\…
- Oprava: `includes/db.php` – automatické migrácie + vytvorenie admina z .env pri prvom pripojení; `bin/windows-phpini.ps1`
  zapisuje php.ini podľa aktuálnej cesty; priateľská chybová stránka (render_fatal_error v functions.php).
- Nástroje: DIAGNOSTIKA.bat / diagnostika.php (iba localhost), ZALOHA-DATABAZY.bat, OBNOVIT-DATABAZU.bat, bin/db-checkpoint.php (STOP-WEB).
- PostgreSQL: `DB_DSN=pgsql:...` + `migrations/pgsql/` (citext). Otestované v kontajneri s PostgreSQL 16.
- Testovací účet na PC používateľa: `test-claude` / test-claude@example.test (vytvorený pri teste registrácie) – dá sa zmazať.

## Videá – IBA YouTube, modálne okno (17. 11. 2026)
- Na webe nie je žiadny vlastný videosúbor. Evidencia: `data/videos.php` (kľúč => yt ID, názov, popis, rozmery w/h, dĺžka, dátum, téma)
  + videá pridané adminom cez web (tabuľka `videos`, migrácia `003_videos.sql` / `pgsql/003_videos.sql`, spustí sa automaticky).
- Mapovanie starých MP4 → YouTube (kanál Šimon Haraba): video-1 W6eX6ASuujM (na výšku), video-2 i8Ys_Tj_18U, video-4 SMH8NrJ-jhE,
  video-5 cBU4MpqhO9s, odchov-naklovane-vajce V8ZwIruMICE (na výšku), odchov-inseminacia I_Z6SjC4UtY (na výšku), odchov-znaska XF_BZfITRQM,
  odchov-vtlacena-samica InR4r_idoLw, odchov-par-sokolov ky5AweMWgXg. Staršie YouTube videá (spermie, monitoring vajca, liahnutie, kŕmenie)
  ostali, len majú kľúče v data/videos.php.
- Bez YouTube verzie → z webu odstránené: video-3, video-6, video-7, vypustenie-sokola.mp4 (fotka `vypustenie-sokola` ostala).
  Priečinok `assets/video` bol neskôr (17. 11.) natrvalo zmazaný na žiadosť používateľa.
- Prehrávanie: klik na náhľad → modálne okno (`bo-ui.js`, „VIDEO V MODÁLNOM OKNE“), iframe youtube-nocookie až po kliknutí,
  okno podľa orientácie videa, šípky/swipe v rámci skupiny `[data-bo-videos]`, Esc/✕/klik mimo zavrie a zastaví video.
- Rozloženie (bo-ui.css časť 14): `--ar` = skutočný pomer strán; `grid` = zarovnané riadky (rovnaká výška v rade), na mobile 2 stĺpce
  (na šírku cez celú šírku, na výšku vo dvojiciach); `row` = vedľa seba v kartách (etapy, prirodzený/umelý odchov).
- Admin: Fotogaléria → Videá → „Pridať video z YouTube“ (odkaz youtu.be / watch / shorts, ?si= sa ignoruje). Názov a orientáciu
  sa server pokúsi zistiť z YouTube (oEmbed + stránka videa), inak shorts = na výšku. Úprava názvu/popisu/orientácie a odstránenie tamtiež.
  Do kategórie „Nové fotografie“ sa už videá nenahrávajú (API ich odmietne); staré nahraté videá vidí len admin.
- Metadáta: JSON-LD schema.org VideoObject pri každej skupine videí, aria-label s dĺžkou, title = popis.

## Správa galérie – kategórie (17. 11. 2026 dopoludnia)
- Verejná `fotogaleria.php` aj súkromná `moja-galeria.php` = jedna logika: `includes/gallery_page.php` (stránka + POST akcie admina)
  a `includes/gallery.php` (kategórie, položky, presuny, mazanie). JS pre admina: `assets/js/bo-gallery-admin.js`, CSS: bo-ui.css časť 15. Ikony = vlastné inline SVG cez `gsvg('meno')` v gallery_page.php (žiadne emoji).
- Tabuľky (migrácia `004_gallery.sql` / `pgsql/004_gallery.sql`): `gallery_categories` (scope public|private, kind photo|video, slug,
  title, description, cover, sort, static_key, deleted), `gallery_overrides` (presun/odstránenie pôvodných položiek),
  stĺpec `category_id` v `media`, `private_media`, `videos`; `videos.scope` (public|private), unikátnosť (scope, yt_id).
- Pôvodné kategórie z `data/media.php` a `data/videos.php` (topics) sa doplnia automaticky (`gallery_seed()`, static_key
  `photo:slug` / `video:tema`, video kategórie majú slug `videa-tema`). Potom ich spravuje admin – zmena v dátovom súbore sa už neprejaví.
- Odkazy na položky: `s:ID` pôvodná fotka, `m:ID` nahratá verejná, `p:ID` súkromná, `v:KĽÚČ` pôvodné video, `v:db-ID` pridané video.
  Náhľad kategórie = `cover` (odkaz), inak prvá položka.
- Admin: nahrať fotky / pridať video do existujúcej alebo novej kategórie (názov, popis), náhľad kliknutím už pri výbere fotiek,
  nová prázdna kategória, poradie ← →, premenovanie, „Nastaviť ako náhľad“, presun, úprava popisu, odstránenie položky aj kategórie.
  Nahraté súbory sa mažú natrvalo; pôvodné fotky/videá/kategórie sa len skryjú → „Obnoviť všetky pôvodné“ v prehľade.
  Odstránená pôvodná fotka zmizne aj z tematických galérií podstránok (bo_gallery), hero/ilustračné fotky ostávajú.
- Nahrávanie: JS posiela fotky po jednej (`X-Requested-With: fetch` → JSON), bez JS obyčajný formulár (php.ini max_file_uploads = 200).
  Do galérie sa nahrávajú iba fotografie; staré nahraté videosúbory vidí admin v prehľade a môže ich zmazať.
- `?kategoria=videa` = všetky videá zoskupené podľa kategórií. Úvodná stránka ukazuje prvých 6 neprázdnych kategórií fotiek.
- KONTROLA-FOTIEK berie kategórie z databázy a fotky odstránené adminom nepovažuje za chybu.

## Adresy bez prípony .php (17. 11. 2026)
- Všetky odkazy, formuláre a presmerovania sú bez `.php` (`fotogaleria?kategoria=videa`, úvod = `./`). Funkcia `url()` vracia slug.
- Staré adresy `/stranka.php` → 301 na `/stranka` (router.php pre vstavaný server, .htaccess pre Apache); POST a `api/` bez zmeny.

## Fotografie stránok – administrácia (22. 11. 2026)
- Hlavná fotografia (hero) a náhľad (karta na úvodnej stránke) každej podstránky: pôvodné nastavenie `data/page_photos.php`,
  voľba admina v tabuľke `page_photos` (migrácia `005_page_photos.sql` + pgsql, spustí sa automaticky), logika `includes/page_photos.php`.
- Admin: Administrácia → „Fotografie stránok“ (`admin-fotky`, aj v menu účtu). Výber z verejnej fotogalérie (filter kategórie, hľadanie)
  alebo nahratie novej fotky – uloží sa do kategórie galérie (predvolene nová „Fotografie stránok“), takže je aj vo fotogalérii.
  „Obnoviť pôvodnú“ vráti hodnotu z data/page_photos.php. Náhľad bez vlastnej voľby = hlavná fotografia stránky.
- `page_hero()` už nedostáva ID fotky – berie ju z `page_photo(slug,'hero')`. index.php karty cez `page_photo(slug,'card')`.
  JS `assets/js/bo-page-photos.js`, CSS časť 16 v bo-ui.css.

## Zmeny obsahu (22. 11. 2026)
- Vypúšťanie sokolov + metodika = jedna stránka `vypustanie-sokolov.php` (služba, „Metodika vypúšťania sokolov v mestskom prostredí“,
  priebeh, videá, voľný let). Stránka „Edukácia v odchove dravých vtákov“ zrušená (z menu, data/site.php, úvodu), stará adresa → 301.
- Rehabilitácia: hlavná fotka `kanada-33` (namiesto rehab-02). Zdravie: hlavná fotka `kanada-22` (namiesto rehab-01; kanada-22 vyradená
  z galérie Sokoliarstva, aby nebola dvakrát). `rehab-08` (kontrola zobáka) z webu aj z galérie odstránená, súbor ostal v assets/img.
- `odchov-36` nie je vývržok, ale poškodené vajce → popis „Zvyšky poškodeného vajca“, kategória rozmnozovanie; z Anatómie aj z tipu
  „Vývržok“ na Odchove odstránená (tip je teraz bez fotky). Fotka vývržku zatiaľ nie je – nesťahovať z internetu.
- Anatómia: Dýchanie, krvný obeh a hlas = `odchov-01` (mláďatá s otvorenými zobákmi – hlas); Trávenie a vývržok = `odchov-02`
  (jastrab pri kŕmení korisťou). Zdravie – Tráviaca sústava je zatiaľ bez fotky.

## Dizajn 2026 – vrstva „fx“ (22. 11. 2026 večer)
- Nový vzhľad = `assets/css/fx.css` + `assets/js/fx.js`, načítajú sa v `includes/header.php` iba ak `design_fx()` (html má triedu `fx`).
  Predošlý dizajn = tieto dva súbory sa nenačítajú, inak sa nič nemení (stránky aj obsah rovnaké).
- Prepínač: Administrácia → „Vzhľad webu“ (admin.php, POST + CSRF) → tabuľka `site_settings` (migrácia `006_site_settings.sql` + pgsql),
  helper `includes/site_settings.php` (`site_design()`, `design_fx()`); poradie: DB → `DESIGN=novy|predosly` v .env → 'novy'.
- Záloha súborov pred redizajnom: `_zaloha\2026-11-22_2200_pred-redizajnom\web\` (všetky PHP stránky, includes, CSS, JS, data/site.php).
- Efekty: opona pri prechode stránok, vlastný kurzor (bodka + kruh, nad fotkou/videom/kartou text Zväčšiť/Prehrať/Otvoriť; len myš),
  magnetické tlačidlá s výplňou zo smeru kurzora, nadpisy po slovách (h1 hero, h2 sekcií), odhaľovanie s rozostrením, fotky cez masku,
  parallax + náklon úvodnej fotky, otočná pečiatka, nekonečný pás služieb (rýchlosť podľa posúvania), 3D náklon kariet s odleskom,
  skrývanie hlavičky (desktop), ukazovateľ čítania, zrnitosť, kruhové prepnutie tmavého režimu (View Transitions), počítadlá, veľký nápis v pätičke.
  Pätička: `fx-wordmark` (veľkosť dopočíta JS na šírku obsahu). Posledná osamotená karta v `bo-cards--3` je široká.
- Úprava 22. 11. 22:30 (na žiadosť používateľa): odstránený točiaci sa pás služieb aj otočná pečiatka (nevracať), prechody zrýchlené
  (opona 0,5 s, odchod 0,24 s), hlavná fotka úvodu `vypustenie-pole` (Daniel vypúšťa sokola na poli, 1920×1080) – úvod v novom dizajne:
  text v dvoch stĺpcoch hore, široká fotka 21:9 pod ním (mobil 4:3), znak jastraba `fx-emblem` pri fotke (bez otáčania).
  Parallax fotiek: posun max. 6 % výšky pri zväčšení 1,18 – nikdy nesmie vzniknúť medzera nad/pod fotkou.
- 22. 11. 22:45: na žiadosť používateľa vrátená pôvodná hlavná fotka úvodu `farma-2` a pôvodné rozloženie úvodu (text vľavo, fotka
  4:5 vpravo, na mobile 4:3); index.php = rovnaký ako pred redizajnom, znak `fx-emblem` odstránený. `vypustenie-pole` ostala len v galérii.
- Rešpektuje prefers-reduced-motion (všetko vypnuté) a dotykové zariadenia (bez kurzora/náklonu/magnetu). Len dve farby palety.
- Admin: nová sekcia „Knižnica médií“ (kategórie verejnej galérie z DB s počtami a náhľadmi, skryté pôvodné položky), štatistika fotiek knižnice.
  Štýly admina (`.design-switch`, `.lib-*`) sú v bo-ui.css časť 17 – fungujú v oboch dizajnoch.

## Nová administrácia (22. 11. 2026 neskoro večer)
- Stránky `admin`, `admin-fotky`, `admin-nastavenia`, `admin-pouzivatelia`, `moja-galeria` sa (pre admina) vykresľujú v rozhraní administrácie:
  `page_start()` → `includes/admin_header.php` + `admin_footer.php` (zoznam `ADMIN_LAYOUT_PAGES` vo functions.php). Bočný panel (zbaliteľný `[`,
  na mobile vysúvací), horná lišta s drobčekmi, rýchle hľadanie **Ctrl+K** / `/`, skratka `g n` = nástenka. Navigácia a ikony: `includes/admin_ui.php`
  (`adm_nav()`, `adm_icon()`, `adm_head()`). CSS `assets/css/admin.css`, JS `assets/js/admin.js` (prednačítanie stránok pri hoveri + v čase nečinnosti,
  ukazovateľ načítania, View Transitions, neuložené zmeny, počítadlá, živé náhľady, filtrovanie tabuliek). Verejný fx dizajn sa v admine nenačítava.
- `admin-nastavenia?s=` **uvod** (texty úvodu – `site_home()`, predvolené `HOME_DEFAULTS`), **oznamenie** (pruh nad hlavičkou – `site_banner()`,
  zapnutie, text, odkaz, „zobrazovať do“; návštevník ho zavrie, pamätá sa v localStorage), **seo** (titulok/popis podstránok – `site_apply_seo()`),
  **kontakt** (telefón, verejné e-maily, text na Kontakte – `site_apply_contact()`, prepisuje `$SITE['contact']` všade; adresát formulárov ostáva
  v .env MAIL_TO), **vzhlad** (dizajn, predvolený režim system/light/dark = `data-theme-default` na `<html>` + theme-init.js, efekty fx jednotlivo →
  triedy `fx-off-*` na `<html>`, kontroluje ich fx.js). Všetko v tabuľke `site_settings` (JSON hodnoty), bez novej migrácie; prázdne = pôvodné.
- `admin-pouzivatelia`: hľadanie, filter roly, zmena roly, odhlásenie zo zariadení, odstránenie (nie vlastný účet, nie posledný admin, nie účet s nahratými súbormi).
- Natvrdo zapísaný telefón nahradený `$SITE['contact']` (pätička, index, O nás, Zdravie, Rehabilitácia, hlásenia formulárov). SEO popisy v data/site.php
  telefón stále obsahujú (meniteľné v SEO).

## Rýchlosť načítania (24. 11. 2026)
- `includes/speed.php` (spätné volanie `ob_start` vo functions.php): `<img loading="lazy">` verejných stránok → `data-src` + `data-srcset`, pôvodný
  `<img>` v `<noscript>`; hlavná fotka (`fetchpriority="high"`) dostane `srcset`; HTML ide gzipom. V šablónach sa píše obyčajné `loading="lazy"`.
- `assets/js/speed.js`: stiahne fotku ~300 px pred zobrazením (meria polohu, nie IntersectionObserver – ten nevidí clip-path masky), prednačíta
  podstránku pri prejdení myšou / dotyku (nie admin/účet, nie pri šetrení dát).
- Menšie verzie fotiek `assets/img/r/ID-{360,800,1200}.webp` → po pridaní fotky do assets/img spustiť `OBRAZKY-VARIANTY.bat`.
- GSAP + ScrollTrigger sa načítavajú iba na úvode. Opona kratšia (odchod 0,13 s, príchod 0,38 s). router.php: gzip a cache pre assets.

## Právo iba prezerať administrátorskú galériu (24. 11. 2026)
- `users.gallery_view` (migrácia 008 + pgsql), `can_view_private_gallery()` / `require_gallery_viewer()` v auth.php. Zapína admin:
  Používatelia → „Povoliť galériu“. Platí pre `moja-galeria` a `private-media`; všetky úpravy, api/ a admin stránky ostávajú len pre rolu admin.
  Zmena roly právo vynuluje.

## Online verzia – GitHub Pages (24. 11. 2026)
- https://simiharaba-debug.github.io/dive-kridla/ – Pages publikuje koreň vetvy main. PHP tam nejde, preto `OBNOVIT-ONLINE.bat`
  (`bin/export-github.php`) vygeneruje statické `*.html` do koreňa repozitára (neupravovať ručne), `.nojekyll`, nahraté verejné fotky do
  `assets/online`. **Po každej zmene obsahu/dizajnu: export → commit → push.** Bez prihlásenia, administrácie a súkromnej galérie;
  formuláre otvoria e-mail (`online-form.js`). Plná verzia potrebuje PHP hosting.
- Používateľ chce každú zmenu vždy uložiť aj na GitHub.

## Na zváženie (nezmenené)
- `data/o-nas.php`: „Člen SKS od roku 1998“ (Slovenský klub sokoliarov) – ponechané, ide o životopisný údaj; rozhodnúť.
- (vyriešené) Fotka Daniela Harabu na O nás = `daniel-2024` (záber z videa 27. 9. 2024, potvrdené používateľom); stará `tim-daniel` ostala v assets.
- `data/site.php` má v kontaktoch aj druhý e-mail (acipiter@pobox.sk) – overiť, či má byť verejne.

## Denník zmien
- 13. 11. 2026 (dopoludnie) – Pripravené a otestované UI moduly (paleta, karty, galéria +N, lightbox, video na kliknutie,
  animácie), podklady k sekcii SOKOL. Do projektu zatiaľ nič nezapísané – prepojenie s počítačom nefungovalo.
- 13. 11. 2026 16:20 – Záloha `_zaloha\2026-11-13_1620\` (header.php, security.php, site.php, index.php,
  edukacia-…php, biologicka-ochrana.php). Integrované UI moduly + nová paleta; CSP doplnené o YouTube;
  sekcia SOKOL (etapy odchovu so 7 videami na kliknutie, „Vedeli ste?“, služby, poďakovanie ZVF);
  galéria Kanada (24 fotiek) na stránke Biologická ochrana; karty na úvodnej stránke.
  Otestované v prehliadači na localhost:8000 – desktop 1366 px aj mobil 375 px, svetlý aj tmavý režim, bez chýb v konzole.
  Prihlasovanie a databáza nezmenené.
- 13. 11. 2026 20:05–21:00 – Záloha `_zaloha\2026-11-13_2005\`. Oprava DB/prihlasovania (auto-migrácie, admin z .env, portable php.ini,
  diagnostika, záloha/obnova DB, PostgreSQL). Otestované: čistá kópia bez DB (predtým chyba, teraz OK), prenos účtu medzi kópiami,
  dve inštancie so spoločným PostgreSQL, registrácia + prihlásenie na PC používateľa (admin overený diagnostikou).
  Fotky: webnode (24), fotopasca orezaná, vet. prezentácia (8), Kanada (+9); galérie +N na všetkých podstránkach; sekcia SOKOL
  presunutá na odchov-sokolov.php; popisy pod fotkami len pri hoveri; tlmenejšie panely, tmavý režim bez výraznej zelenej;
  mobil (menšie hero, typografia, odsadenia). Test 360/375/390/430/768/1024/1366/1920 px – bez horizontálneho posúvania,
  najväčšia fotka na mobile 27–33 % výšky. Admin: overené cez diagnostiku (hash v DB = heslo v .env), skutočné prihlásenie admina testované v kontajneri (heslo z .env som nezadával).
- 15. 11. 2026 – Záloha zmenených súborov `_zaloha\2026-11-15_1800\`. Odstránené vety „Texty a fotografie pochádzajú z prezentácie…“,
  „Videá z prezentácie…“, „Fotografie z druhej polovice prezentácie…“ (edukacia-…php). Jednotná evidencia fotiek `data/media.php`,
  nová fotogaléria s kategóriami, kontrolný skript; nové stránky Anatómia a Zdravie dravcov; rozšírený Odchov (obsah prezentácie SOKOL).
  Lightbox na mobile: šípky boli orezané – presunuté do stredu po stranách. Test v kontajneri (kópia webu): všetky stránky 200,
  360/390/768/1024/1366/1920 px bez horizontálneho posúvania, bez JS chýb, lightbox (klik, šípky, Esc, swipe), návrat na kategórie,
  video po kliknutí, nahratie/zmazanie súboru adminom v kategórii „nove“; kontrola fotiek: 179 použitých fotiek, všetky v galérii.
- 15. 11. 2026 19:00 – Projekt presunutý používateľom na Plochu. Test na PC (localhost:8000): 25 stránok/kategórií 200, 389 súborov
  (fotky, videá, náhľady) bez chyby, lightbox OK. Nová dvojfarebná paleta #efe7d8 / #3b3329 (časť 13 v bo-ui.css, logo, favicon,
  theme-color). Záloha `_zaloha\2026-11-15_1900\`. Overené v kontajneri (svetlý aj tmavý režim, viac podstránok) aj na PC.
- 17. 11. 2026 – Záloha pôvodných súborov `_zaloha\2026-11-17_0900\`. Všetky videá na webe nahradené YouTube verziami (9 nových
  podľa názvov), video-3/6/7 a vypustenie-sokola z webu odstránené; modálne prehrávanie; zarovnanie videí na výšku/šírku; admin pridáva
  videá odkazom (tabuľka videos); doplnené popisy, dĺžky a rozmery; skryté prípony .php v odkazoch + 301 zo starých adries.
  Test v kontajneri (kópia webu, PHP server s router.php): všetky stránky 200, žiadny odkaz s .php ani .mp4, 301 zo starých adries,
  pridanie/úprava/zmazanie videa adminom, neplatný odkaz a duplicita odmietnuté; prehliadač 1366 px a 375 px – modálne okno (na výšku
  aj na šírku, šípky, Esc), bez horizontálneho posúvania, bez JS chýb. Prehrávanie samotného YouTube overiť na PC.
- 17. 11. 2026 10:00 – Upratanie miesta (~395 MB, na žiadosť používateľa, natrvalo): `tools/ffmpeg` (329 MB), `assets/video` (28 MB),
  `tools/php/icu*.dll` (37 MB – PE importy overené: php8.dll ani zapnuté rozšírenia ICU nepotrebujú, iba php_intl, ktoré nie je zapnuté).
  `bin/windows-start.ps1` už FFmpeg nesťahuje (záloha v `_zaloha\2026-11-17_0900\web\bin`). Web na PC po zmazaní: všetky stránky 200.
  Zostáva ~105 MB: tools/php (~55 MB), assets/img (50 MB) – všetko potrebné.
- 17. 11. 2026 11:00 – Kompletná správa galérie s kategóriami (verejná aj súkromná admin galéria, fotky aj YouTube videá):
  nové kategórie, výber kategórie pri nahrávaní, náhľady všetkých kategórií, poradie, presuny, mazanie položiek aj kategórií,
  obnovenie pôvodných. Záloha zmenených súborov `_zaloha\2026-11-17_1100\`. Test v kontajneri: migrácia čistej DB aj DB zo stavu
  po 003 (staré nahraté fotky → „Nové fotografie“, videá → „Ďalšie videá“), všetky akcie admina cez HTTP (aj neplatný CSRF / bez
  prihlásenia), JS nahrávanie s výberom náhľadu v prehliadači 1366 a 390 px, bez JS chýb, kontrola fotiek bez chýb.
- 17. 11. 2026 11:45 – Nový dizajn administrácie galérie: emoji nahradené vlastnými SVG ikonami (`gsvg()`), záložky „Nahrať fotografie /
  Pridať video / Nová kategória“, pole na pretiahnutie fotiek, segmentový výber typu, ikonový panel pod kartami kategórií,
  položky s ikonami na fotke (náhľad, odstrániť) a „Presunúť do…“, nebezpečná zóna v nastaveniach kategórie. Len farby palety
  (svetlý aj tmavý režim). Záloha `_zaloha\2026-11-17_1130\`. Test: 1366 / 390 px, tmavý režim, nahratie s výberom náhľadu,
  presun, odstránenie – bez JS chýb.

- 22. 11. 2026 14:30 – Záloha pôvodných súborov `_zaloha\2026-11-22_1430\`. Fotografie stránok (admin), zlúčenie Vypúšťania s metodikou,
  výmena hlavných fotiek Rehabilitácie a Zdravia, odstránená kontrola zobáka, opravená fotka vývržku. Test v kontajneri (kópia webu, čistá DB):
  všetky stránky 200, stará adresa edukácie 301, kontrola fotiek bez chýb (okrem písiem/PDF, ktoré v testovacej kópii neboli),
  admin: prihlásenie, zmena hlavnej fotky výberom, nahratie novej fotky ako náhľad, obnovenie; 1366 a 390 px bez horizontálneho posúvania.
- 22. 11. 2026 22:00 – Záloha `_zaloha\2026-11-22_2200_pred-redizajnom\`. Nový dizajn 2026 (fx.css/fx.js) s prepínačom v administrácii (DB tabuľka
  site_settings), knižnica médií v admin.php. Test v kontajneri: všetky stránky 200, 1440 px svetlý/tmavý, 390 px mobil bez horizontálneho posúvania,
  hover kariet/tlačidiel, kurzor, prepnutie režimu, prechod stránok, admin prihlásenie + prepnutie dizajnu tam a späť (aj neplatný CSRF), bez JS chýb.
  Na PC (localhost:8000): 12 stránok 200 s novým dizajnom, efekty aktívne, bez chýb v konzole.
- 22. 11. 2026 22:30 – Záloha `_zaloha\2026-11-22_2230\`. Nové logo (jastrab) v hlavičke, pätičke, favicone a pri úvodnej fotke; preč pás
  služieb a pečiatka; rýchlejšie prechody; nová hlavná fotka úvodu (`vypustenie-pole`, v data/media.php aj page_photos.php); opravená
  medzera pri parallaxe, nápis v pätičke presne na šírku, podčiarknutie odkazov v pätičke na mobile. Test: 1440 px svetlý/tmavý,
  390 px svetlý/tmavý (index, podstránka, galéria, pätička), bez horizontálneho posúvania a JS chýb; na PC aj v režime mobil 375 px.
- 22. 11. 2026 22:56 – Záloha `_zaloha\2026-11-22_2256_pred-adminom\`. Nová administrácia (bočný panel, Ctrl+K, nástenka, nastavenia: úvod, oznámenie,
  SEO, kontakt, vzhľad a efekty, používatelia). Test na kópii webu s kópiou DB (VACUUM INTO, port 8123, testovacia relácia – heslo nezadávané):
  všetky admin aj verejné stránky 200, uloženie každej sekcie, odmietnutý javascript: odkaz a neplatný CSRF, ochrany pri rolách/mazaní, prejavenie
  na webe (nadpis, oznámenie, telefón, SEO titulok, triedy fx-off), 1024 px svetlý/tmavý, zbalený panel, 390 px bez horizontálneho posúvania, bez PHP chýb.
  Ostrá databáza nezmenená. Na PC (localhost:8000) overené len verejné stránky 200 – admin si treba pozrieť po prihlásení.
- 22. 11. 2026 (neskoro v noci) – Na žiadosť používateľa: (1) svetlo za kurzorom v úvode sa k spodnému okraju plynulo stráca a po odchode kurzora zmizne
  (fx.css maska, trieda `fx-glow`); (2) Etapy odchovu = časová os `.bo-timeline` (bo-ui.css časť 19): text vľavo (prichytený), vpravo mozaika fotiek
  (`bo-gallery--tl1/2/3`) a pod ňou zarovnaný rad videí, ktorý presne vyplní šírku; jediné video na výšku stojí vedľa fotiek; (3) zaujímavosti bez fotky
  (Vývržok) = pruh „Dobré vedieť“ pod kartami (časť 20) – fotka z internetu NEstiahnutá (licencia, staršie pravidlo); (4) moderné `select` na celom webe
  (časť 21, v Chrome/Edge 135+ aj štylizovaný rozbalený zoznam cez `appearance: base-select`); (5) **výrez fotografií stránok** – admin-fotky →
  „Upraviť výrez“ (ťahanie, priblíženie 100–300 %, náhľad počítač/mobil), tabuľka `page_photo_pos` (migrácia 007 + pgsql), na webe `data-fp` +
  `--fp-x/y/z` (časť 22), po výmene fotky sa výrez zruší; (6) **Farby webu** – admin-nastavenia?s=farby: 10 farieb × svetlý/tmavý režim
  (`PALETTE_TOKENS`, odvodené odtiene `PALETTE_DERIVED` cez color-mix), hotové palety Pôvodná/Lesná/Morská/Terakota, živý náhľad a kontrast WCAG;
  výstup `<style id="dk-palette">` v header.php iba pre verejný web (admin ostáva v pôvodných farbách). Test na kópii webu (port 8123): všetky stránky 200,
  uloženie výrezu a palety, neplatná farba odmietnutá, obnovenie, 1024 px a 390 px, svetlý/tmavý, bez PHP chýb. Samostatná záloha pred týmto kolom nebola
  urobená – pôvodný stav súborov je v `_zaloha\2026-11-22_2256_pred-adminom\`.
- 23. 11. 2026 20:30 – Záloha `_zaloha\2026-11-23_2004_pred-uvodom\`. **Nový úvod** (iba nový dizajn; starý úvod = `includes/home_predosly.php`): `assets/css/home.css`
  (len index) – fotka po pravý okraj s oblúkom (`.hx`), pás troch služieb v troch odtieňoch `.deck` (tokeny `--s1/2/3-bg`, odvodené z palety cez color-mix),
  rozťahovacie panely `.xp`, veta + čísla `.law`, mozaika tém `.bento`, 8 kategórií galérie. Pohyb: `assets/js/home.js` + lokálne
  `assets/js/vendor/` gsap 3.13.0, ScrollTrigger, lenis 1.3.4 (Lenis na všetkých verejných stránkach s novým dizajnom, len myš). react-bits je pre React, preto
  nepoužité priamo. Hlavná fotka úvodu `kanada-15` (rovnaký záber ako farma-2, 1600 px). `--container` 112rem od 1700 px.
  6 fotiek z Downloads `reprezentacieplusakefotkypotrebujem` → `endoskopia-1…4`, `oko-1…2` (kategória veterina, stránka Zdravie dravcov, nová sústava „Oči“;
  z endoskopia-3 orezaná tvár cudzej osoby). Neskôr: témy = 8 rovnakých dlaždíc `.themes` (namiesto mozaiky; pribudla Fotogaléria, `data/page_photos.php`
  bez 'hero'), pomer 4:3 / telefón 1:1 presne ako rámček výrezu v admin-fotky; pomery výrezu služieb a úvodu zosúladené s novým úvodom. Test headless Edge 320–2560 px, svetlý/tmavý, bez JS chýb, kontrola fotiek v poriadku.
- 23. 11. 2026 (večer, 3. kolo) – Záloha `_zaloha\*_pred-pismom`. Mierka rozhrania cez `html.fx{font-size}` (fx.css časť 17: telefón 15 px, PC 17–23 px podľa šírky),
  spodné hranice drobných popiskov, menšie nadpisy na telefóne, polia formulárov 16 px. Hover: `.deck` nemení rozmery; karty sa nezdvíhajú a náklon počúva
  na nehybnom `li` (fx.js 12) – **hover nikdy nesmie posúvať prvok spod kurzora**. Telefón v hlavičke až od 1700 px. Fotky: Zdravie – nová sekcia
  `#vysetrenie` (endoskopia-1, -2), Tráviaca sústava endoskopia-3, Podávanie liečiv endoskopia-4, Oči oko-1/-2; Rehabilitácia – výzva s oko-2.
  Test: 17 stránok × 7 rozlíšení, hover sweep, admin a predošlý dizajn na dočasnej kópii (zmazaná).
- 24. 11. 2026 - Zaloha `_zaloha\2026-11-24_pred-lazyload`. Lazy load, mensie verzie fotiek, prednacitanie, pravo prezerat galeriu, online verzia pre GitHub Pages. Merane v headless Edge: uvod 2197 -> 594 KB, odchov 3082 -> 322 KB. Prava testovane na kopii DB.
- 24. 11. 2026 - theme-init.js: verejna stranka po otvoreni a obnoveni zacina uplne hore (oznamenie bolo odrezane kvoli zapamatanemu posunu); navrat Spat poziciu zachova.
- 25. 11. 2026 – CLAUDE-README: mapa „ZAČNI TU“, výber hostingu a postup nasadenia; nový `CLAUDE.md`. Kód webu nezmenený, nič nenasadené.
- 25. 11. 2026 (SEO) – záloha `_zaloha�6-11-25_pred-seo` (bez assets). `includes/seo.php`, `sitemap.php`, `data/faq.php`, `assets/og.jpg`; nové titulky a popisy, H1 na úvode, Vypúšťaní, Biologickej ochrane a Rehabilitácii, vlastné titulky kategórií galérie, časté otázky na 4 stránkach, 404 = noindex. Test: 17 adries, JSON-LD, 29 adries v sitemap, svetlý/tmavý, 390 px bez horizontálneho posúvania. `.htaccess` netestované na Apache. Domény a Search Console ešte nie sú.
