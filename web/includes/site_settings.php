<?php
// Nastavenia webu uložené v databáze (tabuľka site_settings, migrácia 006 – name/value, bez ďalšej migrácie).
// Vzhľad webu: 'novy' = dizajn 2026 s animáciami (assets/css/fx.css + assets/js/fx.js),
//              'predosly' = verzia pred redizajnom 22. 11. 2026 (rovnaké stránky, bez vrstvy fx).
// Poradie: voľba administrátora v databáze → DESIGN v .env → 'novy'.
//
// Od 22. 11. 2026 (nová administrácia) aj: kontakt, texty úvodnej stránky, oznámenie na webe, SEO podstránok,
// predvolený režim (svetlý/tmavý) a jednotlivé efekty dizajnu. Každá skupina = jeden riadok s JSON hodnotou.
// Bez databázy alebo bez uloženej voľby sa vždy použijú pôvodné hodnoty z data/site.php a z kódu stránok.
require_once __DIR__ . '/db.php';

const SITE_DESIGNS = [
    'novy' => ['Nový dizajn (2026)', 'Plynulé animácie, vlastný kurzor, prechody medzi stránkami, efekty fotografií a kariet.'],
    'predosly' => ['Predošlý dizajn', 'Verzia webu pred redizajnom – rovnaký obsah, jednoduchšie animácie.'],
];

/** Predvolený režim pre návštevníka, ktorý si ho sám neprepol. */
const SITE_THEMES = [
    'system' => ['Podľa zariadenia', 'Svetlý alebo tmavý podľa nastavenia telefónu či počítača návštevníka.'],
    'light' => ['Svetlý', 'Krémový podklad #efe7d8, tmavý text.'],
    'dark' => ['Tmavý', 'Tmavohnedý podklad #3b3329, svetlý text.'],
];

/** Efekty nového dizajnu, ktoré sa dajú vypnúť jednotlivo (kľúč => [názov, popis]). */
const SITE_FX = [
    'curtain' => ['Opona pri prechode stránok', 'Krátka animácia pri kliknutí na odkaz.'],
    'cursor' => ['Vlastný kurzor', 'Bodka s kruhom a popisom „Zväčšiť / Prehrať / Otvoriť“ (iba myš).'],
    'magnet' => ['Magnetické tlačidlá', 'Tlačidlá sa jemne pritiahnu ku kurzoru.'],
    'tilt' => ['3D náklon kariet', 'Karty sa pri prejdení kurzorom naklonia s odleskom.'],
    'grain' => ['Zrnitosť pozadia', 'Jemná textúra ako na fotografickom papieri.'],
    'hideheader' => ['Skrývanie hlavičky', 'Pri posúvaní nadol sa hlavička skryje, pri posúvaní nahor vráti.'],
    'progress' => ['Ukazovateľ čítania', 'Tenká čiara hore ukazuje, koľko zo stránky je prečítané.'],
];

function site_setting(string $name, ?string $default = null): ?string {
    $all = site_settings_all();
    return $all[$name]['value'] ?? $default;
}

/** Všetky uložené nastavenia [name => ['value','updated_at','updated_by']] (jedna požiadavka na DB). */
function site_settings_all(bool $fresh = false): array {
    static $cache = null;
    if ($cache !== null && !$fresh) return $cache;
    $cache = [];
    try { foreach (db()->query('SELECT name, value, updated_by, updated_at FROM site_settings')->fetchAll() as $r) $cache[$r['name']] = $r; }
    catch (Throwable $e) { error_log('[nastavenia] ' . $e->getMessage()); }
    return $cache;
}

function site_setting_set(string $name, string $value, ?int $userId): void {
    $st = db()->prepare('INSERT INTO site_settings (name, value, updated_by, updated_at) VALUES (?, ?, ?, ?)
        ON CONFLICT (name) DO UPDATE SET value = excluded.value, updated_by = excluded.updated_by, updated_at = excluded.updated_at');
    $st->execute([$name, $value, $userId, gmdate('c')]);
    site_settings_all(true);
}
function site_setting_delete(string $name): void {
    db()->prepare('DELETE FROM site_settings WHERE name = ?')->execute([$name]);
    site_settings_all(true);
}

/** JSON skupina nastavení (pole) – neplatný alebo chýbajúci záznam = []. */
function site_setting_json(string $name): array {
    $v = site_setting($name);
    if ($v === null || $v === '') return [];
    $d = json_decode($v, true);
    return is_array($d) ? $d : [];
}
function site_setting_json_set(string $name, array $value, ?int $userId): void {
    site_setting_set($name, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $userId);
}

/* ---------------------------------------------------------------- vzhľad */

/** Aktuálny vzhľad webu ('novy' | 'predosly'). */
function site_design(): string {
    static $d = null;
    if ($d !== null) return $d;
    $env = function_exists('cfg') ? (string)cfg('DESIGN') : '';
    $d = site_setting('design', in_array($env, ['novy', 'predosly'], true) ? $env : 'novy');
    if (!isset(SITE_DESIGNS[$d])) $d = 'novy';
    return $d;
}
function design_fx(): bool { return site_design() === 'novy'; }

function site_theme_default(): string {
    $t = (string)site_setting('theme_default', 'system');
    return isset(SITE_THEMES[$t]) ? $t : 'system';
}

/** Zapnuté efekty [kľúč => bool]; predvolene všetky zapnuté. */
function site_fx_flags(): array {
    $saved = site_setting_json('fx_flags');
    $out = [];
    foreach (SITE_FX as $k => $_) $out[$k] = !array_key_exists($k, $saved) || !empty($saved[$k]);
    return $out;
}
/** Triedy pre <html> (fx-off-kurzor …), podľa ktorých fx.js a fx.css vypnú efekt. */
function site_fx_classes(): string {
    $c = [];
    foreach (site_fx_flags() as $k => $on) if (!$on) $c[] = 'fx-off-' . $k;
    return implode(' ', $c);
}

/* ---------------------------------------------------------------- kontakt */

/** Telefón v tvare na zobrazenie → odkaz tel: (0907 … → +421907…). */
function phone_href(string $label): string {
    $digits = preg_replace('/[^0-9+]/', '', $label);
    if (str_starts_with($digits, '00')) $digits = '+' . substr($digits, 2);
    elseif (str_starts_with($digits, '0')) $digits = '+421' . substr($digits, 1);
    return 'tel:' . $digits;
}

/** Prepíše kontakt v $SITE voľbou administrátora (telefón, verejné e-maily, text na stránke Kontakt). */
function site_apply_contact(array &$SITE): void {
    $c = site_setting_json('contact');
    if (!$c) return;
    if (!empty($c['phone'])) $SITE['contact']['phones'][0] = ['label' => $c['phone'], 'href' => phone_href($c['phone'])];
    if (!empty($c['emails']) && is_array($c['emails'])) $SITE['contact']['emails'] = array_values($c['emails']);
    if (isset($c['note']) && trim((string)$c['note']) !== '') $SITE['contact']['note'] = (string)$c['note'];
}

/* ---------------------------------------------------------------- SEO */

/** Titulky a popisy podstránok pre vyhľadávače (prázdne pole = pôvodná hodnota z data/site.php). */
function site_apply_seo(array &$SITE): void {
    foreach (site_setting_json('seo') as $slug => $v) {
        if (!isset($SITE['pages'][$slug]) || !empty($SITE['pages'][$slug]['hidden']) || !is_array($v)) continue;
        if (!empty($v['title'])) $SITE['pages'][$slug]['title'] = (string)$v['title'];
        if (!empty($v['description'])) $SITE['pages'][$slug]['description'] = (string)$v['description'];
    }
}

/* ---------------------------------------------------------------- úvodná stránka */

const HOME_DEFAULTS = [
    'eyebrow' => 'OZ Divé krídla · Biologická ochrana',
    'title' => 'Odchyt holubov a biologická ochrana pomocou dravcov',
    'lead' => 'Odchyt mestských holubov, vypúšťanie sokolov do mesta a ochrana sadov a viníc pomocou dravých vtákov. Služby poskytujeme po dohode – cena dohodou.',
    'slogan' => 'Mestské holuby – Váš problém, naša starosť.',
    'cta_title' => 'Navrhneme riešenie priamo pre vašu lokalitu',
    'cta_text' => 'Služby poskytujeme po dohode – cena dohodou.',
];
/** Text úvodnej stránky (voľba administrátora → pôvodný text). */
function site_home(string $key): string {
    static $h = null;
    $h ??= site_setting_json('home');
    $v = trim((string)($h[$key] ?? ''));
    return $v !== '' ? $v : (HOME_DEFAULTS[$key] ?? '');
}

/* ---------------------------------------------------------------- farebná paleta (admin-nastavenia?s=farby) */

/** Farby webu, ktoré môže administrátor zmeniť: kľúč => [CSS premenná, názov, popis, svetlý režim, tmavý režim].
 *  Predvolené hodnoty = dvojfarebná paleta z bo-ui.css časť 13. Iba web – administrácia ostáva v pôvodných farbách. */
const PALETTE_TOKENS = [
    'bg' => ['--bg', 'Pozadie stránky', 'Hlavná farba plochy celého webu.', '#efe7d8', '#3b3329'],
    'surface' => ['--surface', 'Karty a polia', 'Karty, formuláre, rozbaľovacie ponuky.', '#f7f2e9', '#463d32'],
    'text' => ['--text', 'Písmo', 'Bežný text odsekov a formulárov.', '#3b3329', '#efe7d8'],
    'heading' => ['--heading', 'Nadpisy', 'Nadpisy stránok a sekcií, logo.', '#3b3329', '#efe7d8'],
    'muted' => ['--muted', 'Tlmený text', 'Popisy, poznámky, menej dôležitý text.', '#6a5e50', '#cfc3b0'],
    'accent' => ['--accent', 'Akcent a odkazy', 'Odkazy, ikony, zvýraznenia.', '#3b3329', '#efe7d8'],
    'btn_bg' => ['--btn-bg', 'Tlačidlá', 'Výplň hlavných tlačidiel.', '#3b3329', '#efe7d8'],
    'btn_fg' => ['--btn-fg', 'Text tlačidiel', 'Písmo na hlavných tlačidlách.', '#efe7d8', '#3b3329'],
    'panel' => ['--panel', 'Tmavé pásy a pätička', 'Úvod podstránok, výzvy, pätička, cenové karty.', '#3b3329', '#2c261f'],
    'on_dark' => ['--on-dark', 'Písmo na tmavých pásoch', 'Text a tlačidlá na tmavých pásoch a v pätičke.', '#efe7d8', '#efe7d8'],
];
/** Odvodené farby (odtiene, čiary, priehľadnosti) – vypočítajú sa z hlavných, iba keď sa ich základ zmenil. */
const PALETTE_DERIVED = [
    '--surface-alt' => [['bg', 'text'], 'color-mix(in srgb, {bg}, {text} 6%)'],
    '--tint' => [['bg', 'text'], 'color-mix(in srgb, {bg}, {text} 10%)'],
    '--tint-2' => [['bg', 'text'], 'color-mix(in srgb, {bg}, {text} 18%)'],
    '--sage-300' => [['bg', 'text'], 'color-mix(in srgb, {bg}, {text} 18%)'],
    '--line' => [['text'], 'color-mix(in srgb, {text} 14%, transparent)'],
    '--line-strong' => [['text'], 'color-mix(in srgb, {text} 36%, transparent)'],
    '--input-border' => [['text'], 'color-mix(in srgb, {text} 45%, transparent)'],
    '--focus-ring' => [['text'], 'color-mix(in srgb, {text} 22%, transparent)'],
    '--input-bg' => [['surface', 'bg'], 'color-mix(in srgb, {surface} 70%, {bg})'],
    '--header-bg' => [['bg'], 'color-mix(in srgb, {bg} 94%, transparent)'],
    '--bo-surface-2' => [['bg', 'text'], 'color-mix(in srgb, {bg}, {text} 10%)'],
    '--btn-bg-hover' => [['btn_bg', 'bg'], 'color-mix(in srgb, {btn_bg}, {bg} 14%)'],
    '--panel-deep' => [['panel'], 'color-mix(in srgb, {panel}, #000 18%)'],
    '--forest-900' => [['panel'], '{panel}'], '--forest-800' => [['panel'], '{panel}'], '--forest-600' => [['panel'], '{panel}'],
    '--forest-950' => [['panel'], 'color-mix(in srgb, {panel}, #000 25%)'],
    '--on-dark-muted' => [['on_dark'], 'color-mix(in srgb, {on_dark} 74%, transparent)'],
    '--sand' => [['on_dark'], '{on_dark}'],
    '--bo-accent-strong' => [['heading'], '{heading}'],
    '--bo-on-accent' => [['bg'], '{bg}'],
    '--c-light' => [['on_dark'], '{on_dark}'],
    '--c-dark' => [['panel'], '{panel}'],
    '--fx-curtain' => [['panel'], 'color-mix(in srgb, {panel}, #000 25%)'],
    '--fx-curtain-2' => [['panel'], 'color-mix(in srgb, {panel}, #000 35%)'],
];

/** Uložená paleta ['light' => [kľúč => #hex], 'dark' => [...]] – iba zmenené farby. */
function site_palette(): array {
    $p = site_setting_json('palette');
    $out = ['light' => [], 'dark' => []];
    foreach (['light', 'dark'] as $m) foreach (($p[$m] ?? []) as $k => $v)
        if (isset(PALETTE_TOKENS[$k]) && is_string($v) && preg_match('/^#[0-9a-f]{6}$/i', $v)) $out[$m][$k] = strtolower($v);
    return $out;
}
/** Hodnota farby (uložená → predvolená). */
function palette_value(string $mode, string $key): string {
    return site_palette()[$mode][$key] ?? PALETTE_TOKENS[$key][$mode === 'dark' ? 4 : 3];
}
/** CSS s vlastnou paletou pre <head> verejného webu ('' = žiadne zmeny). */
function site_palette_css(): string {
    $pal = site_palette();
    if (!$pal['light'] && !$pal['dark']) return '';
    $css = '';
    foreach (['light' => ':root', 'dark' => ':root[data-theme="dark"]'] as $mode => $sel) {
        $changed = $pal[$mode];
        if (!$changed) continue;
        $val = fn($k) => palette_value($mode, $k);
        $decl = [];
        foreach ($changed as $k => $v) $decl[] = PALETTE_TOKENS[$k][0] . ':' . $v;
        foreach (PALETTE_DERIVED as $var => [$deps, $tpl]) {
            if (!array_intersect($deps, array_keys($changed))) continue;
            $decl[] = $var . ':' . preg_replace_callback('/\{(\w+)\}/', fn($m) => $val($m[1]), $tpl);
        }
        $css .= $sel . '{' . implode(';', $decl) . '}';
        if (isset($changed['on_dark']) || isset($changed['panel'])) {   // tlačidlá a text na tmavých pásoch
            $dark = '.page-hero,.band--forest,.band--dark,.site-footer,.price-card,.dark-card,.bo-fact,.facts div,.ci,.auth__side';
            $pre = $mode === 'dark' ? ':root[data-theme="dark"] ' : ':root:not([data-theme="dark"]) ';
            $css .= $pre . str_replace(',', ',' . $pre, $dark)
                  . '{--btn-bg:' . $val('on_dark') . ';--btn-fg:' . $val('panel') . ';--btn-bg-hover:color-mix(in srgb,' . $val('on_dark') . ',#fff 20%)}';
        }
    }
    return $css;
}
/** Farba lišty prehliadača na mobile (theme-color) podľa pozadia. */
function site_theme_color(string $mode = 'light'): string { return palette_value($mode, 'bg'); }

/* ---------------------------------------------------------------- oznámenie */

/** Oznámenie nad hlavičkou webu: ['on','text','link','link_label','until'] alebo null, ak je vypnuté / skončilo. */
function site_banner(): ?array {
    $b = site_setting_json('banner');
    if (empty($b['on']) || trim((string)($b['text'] ?? '')) === '') return null;
    if (!empty($b['until']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $b['until']) && $b['until'] < date('Y-m-d')) return null;
    return $b + ['link' => '', 'link_label' => '', 'until' => ''];
}
