<?php
// Pomocné funkcie a načítanie dát. Každá stránka začína `require 'includes/functions.php';`
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/media.php';
send_security_headers();
set_exception_handler('render_fatal_error');
require_once __DIR__ . '/speed.php';
ob_start('speed_output');   // lazy load fotografií + gzip (includes/speed.php); výstup sa drží v pamäti, aby sa relácia/cookies mohli nastaviť aj počas vykresľovania stránky
$SITE = require __DIR__ . '/../data/site.php';
$SERVICES = require __DIR__ . '/../data/services.php';
require_once __DIR__ . '/site_settings.php';
site_apply_contact($SITE);   // telefón, e-maily a text kontaktu môže zmeniť administrátor (admin-nastavenia?s=kontakt)
site_apply_seo($SITE);       // titulky a popisy podstránok pre vyhľadávače (admin-nastavenia?s=seo)
/** Stránky, ktoré sa vykresľujú v rozhraní administrácie (bočný panel namiesto hlavičky webu). */
const ADMIN_LAYOUT_PAGES = ['admin', 'admin-fotky', 'admin-nastavenia', 'admin-pouzivatelia', 'moja-galeria'];
$ACCESS_TOPICS = [
    'Povolenia a osvedčenia (odchyt, preprava zvierat)',
    'Podrobnosti k vypúšťaniu sokolov a metodike',
    'Projekt SOKOL – ďalšie podrobnosti',
    'Rehabilitácia a nález poraneného dravca',
    'Iné',
];

/** Namiesto bielej stránky „500“ zobrazí zrozumiteľné hlásenie. Technické detaily iba na tomto počítači (localhost). */
function render_fatal_error(Throwable $e): void {
    error_log('[web] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) ob_end_clean();
    $isDb = $e instanceof DbUnavailable || $e instanceof PDOException;
    if (!headers_sent()) { http_response_code($isDb ? 503 : 500); header('Content-Type: text/html; charset=utf-8'); }
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) && !isset($_SERVER['HTTP_X_FORWARDED_FOR']);
    $title = $isDb ? 'Databáza momentálne nie je dostupná' : 'Na stránke nastala chyba';
    $msg = $isDb ? 'Registrácia, prihlásenie a správa galérie potrebujú databázu. Ostatné stránky webu fungujú.' : 'Skúste to prosím o chvíľu znova.';
    echo '<!doctype html><html lang="sk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . '</title>'
       . '<style>body{font:16px/1.6 system-ui,sans-serif;background:#efe7d8;color:#3b3329;margin:0;padding:2rem}main{max-width:40rem;margin:8vh auto;background:#f7f2e9;border:1px solid #d3c6af;border-radius:18px;padding:2rem}h1{font-size:1.5rem;margin:0 0 .75rem}code,pre{background:#e1d6c2;border-radius:6px;padding:.1rem .35rem;font-size:.85rem;white-space:pre-wrap;word-break:break-word}a{color:#3b3329}</style></head><body><main>'
       . '<h1>' . e($title) . '</h1><p>' . e($msg) . '</p>';
    if ($local) {
        echo '<p><strong>Technická príčina (vidno iba na tomto počítači):</strong></p><pre>' . e($e->getMessage()) . '</pre>'
           . '<p>Čo urobiť: zastav web (STOP-WEB.bat) a spusti ho znova cez <code>START-WEB.bat</code>. Podrobnú kontrolu urobí <code>DIAGNOSTIKA.bat</code> alebo stránka <a href="diagnostika">diagnostika</a>.</p>';
    }
    echo '<p><a href="./">← Späť na úvodnú stránku</a></p></main></body></html>';
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Adresa stránky podľa cesty z dát ('/odchov-sokolov' alebo 'odchov-sokolov'). */
function url($path) {
    $p = trim($path, '/');
    return ($p === '' || $p === 'index') ? './' : $p;
}
/** Adresa statického súboru s verziou (obíde starú cache po zmene). */
function asset($path) { $f = __DIR__ . '/../' . $path; return $path . (is_file($f) ? '?v=' . filemtime($f) : ''); }
function img_full($id) { media_use($id); return 'assets/img/' . $id . '.webp'; }
function img_thumb($id) { media_use($id); return 'assets/img/' . $id . '-sm.webp'; }

/* ---------- Jednotná evidencia fotografií (data/media.php) ---------- */
function media_registry(): array { static $M = null; return $M ??= require __DIR__ . '/../data/media.php'; }
function media_photo(string $id): ?array { return media_registry()['photos'][$id] ?? null; }
function media_alt(string $id): string { return media_photo($id)[1] ?? ''; }
/** Zaznamená použitie fotografie na stránke. Fotografia mimo evidencie sa zapíše do logu (a teda chýba vo fotogalérii). */
function media_use(string $id): void {
    $GLOBALS['MEDIA_USED'][$id] = true;
    if (!media_photo($id) && empty($GLOBALS['MEDIA_WARNED'][$id])) {
        $GLOBALS['MEDIA_WARNED'][$id] = true;
        error_log('[galeria] Fotografia "' . $id . '" je použitá na stránke ' . ($_SERVER['SCRIPT_NAME'] ?? '?') . ', ale nie je v data/media.php (chýba vo fotogalérii).');
    }
}
/** Fotografie zaradené do kategórie (v poradí z evidencie). */
function media_in_category(string $cat): array {
    $out = [];
    foreach (media_registry()['photos'] as $id => [$c, $alt]) if ($c === $cat) $out[] = ['id' => $id, 'caption' => $alt];
    return $out;
}

/** Odseky textu. */
function paragraphs($items, $class = '') {
    echo '<div class="prose ' . e($class) . '">';
    foreach ($items as $p) echo '<p>' . e($p) . '</p>';
    echo '</div>';
}

/** Fotografie otvárateľné v lightboxe (skupina = data-lightbox). */
function photo_grid($ids, $alt, $caption = '', $group = 'g', $class = '') {
    echo '<div class="photo-grid ' . e($class) . (count($ids) <= 2 ? ' two' : '') . '">';
    foreach ($ids as $i => $id) {
        $reg = media_alt($id);   // popis z jednotnej evidencie fotografií (data/media.php)
        echo '<a href="' . e(img_full($id)) . '" data-lightbox="' . e($group) . '" data-caption="' . e($reg ?: $caption) . '" class="photo">'
           . '<img src="' . e(img_thumb($id)) . '" alt="' . e($reg ?: $alt . ' (' . ($i + 1) . ')') . '" loading="lazy" decoding="async"></a>';
    }
    echo '</div>';
}

function page_start($slug) {
    global $SITE;
    $page = $SITE['pages'][$slug];
    $GLOBALS['CURRENT'] = $slug;
    $title = $page['title'];
    $desc = $page['description'];
    $noindex = !empty($page['noindex']);
    if ($noindex && !headers_sent()) header('X-Robots-Tag: noindex, nofollow');
    if (in_array($slug, ADMIN_LAYOUT_PAGES, true) && is_admin()) { $GLOBALS['ADMIN_LAYOUT'] = true; include __DIR__ . '/admin_header.php'; return; }
    include __DIR__ . '/header.php';
}

/** Stránka 403 pre prihláseného používateľa bez oprávnenia (kontrola prebieha na serveri). */
function render_forbidden(): never {
    global $SITE;
    http_response_code(403);
    $GLOBALS['CURRENT'] = 'admin';
    $title = 'Prístup zamietnutý | OZ Divé krídla'; $desc = ''; $noindex = true;
    include __DIR__ . '/header.php';
    echo '<section class="band"><div class="container container--narrow center"><p class="eyebrow">Chyba 403</p><h1>Prístup zamietnutý</h1><p class="lead center-lead">Táto časť webu je určená iba administrátorom. Váš účet na ňu nemá oprávnenie.</p><div class="btn-row btn-row--center"><a class="btn btn--primary" href="ucet">Môj účet</a><a class="btn btn--outline" href="./">Úvodná stránka</a></div></div></section>';
    include __DIR__ . '/footer.php';
    exit;
}
/** Tlačidlá upraviť / zmazať na položke galérie (zobrazuje sa iba administrátorovi; server oprávnenie overuje znova). */
function mitem_actions_html(): string {
    return '<div class="mitem__actions">'
        . '<button type="button" class="icon-btn" data-act="edit" aria-label="Upraviť údaje"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>'
        . '<button type="button" class="icon-btn icon-btn--danger" data-act="delete" aria-label="Zmazať"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/></svg></button></div>';
}
function page_end() { global $SITE; include __DIR__ . (empty($GLOBALS['ADMIN_LAYOUT']) ? '/footer.php' : '/admin_footer.php'); }

/** Úvodný pás podstránky: text vľavo, fotografia v ráme vpravo (nikdy cez celú obrazovku).
 *  Fotografiu určuje data/page_photos.php, prípadne administrátor (admin-fotky); $image len výnimočne. */
function page_hero($eyebrow, $title, $lead, $image = null, $imgAlt = '', $caption = '') {
    require_once __DIR__ . '/page_photos.php';
    $fp = '';
    if ($image === null) {
        $ph = page_photo($GLOBALS['CURRENT'] ?? '', 'hero');
        $src = $ph['full'] ?? null; $imgAlt = $ph['alt'] ?? ''; $caption = $imgAlt;
        $fp = page_photo_attr($ph);   // výrez nastavený administrátorom (admin-fotky → Upraviť výrez)
    } else {
        $src = img_full($image);
    }
    ?>
    <section class="page-hero">
      <div class="container page-hero__grid">
        <div>
          <p class="eyebrow eyebrow--sand"><?= e($eyebrow) ?></p>
          <h1><?= e($title) ?></h1>
          <?php if ($lead): ?><p class="lead"><?= e($lead) ?></p><?php endif; ?>
        </div>
        <?php if ($src): ?>
        <figure class="frame">
          <img src="<?= e($src) ?>" alt="<?= e($imgAlt) ?>" fetchpriority="high" decoding="async"<?= $fp ?>>
          <?php if ($caption): ?><figcaption><?= e($caption) ?></figcaption><?php endif; ?>
        </figure>
        <?php endif; ?>
      </div>
    </section>
    <?php
}

function section_head($eyebrow, $title, $lead = '', $light = false) {
    echo '<div class="section-head reveal' . ($light ? ' light' : '') . '"><p class="eyebrow' . ($light ? ' eyebrow--sand' : '') . '">' . e($eyebrow) . '</p><h2>' . e($title) . '</h2>';
    if ($lead) echo '<p class="lead">' . e($lead) . '</p>';
    echo '</div>';
}

/** Podrobný blok služby (úvod, postup, fotografie, cena dohodou). */
function service_block($s, $id = null) {
    global $SERVICES, $SITE;
    ?>
    <article class="service reveal" id="<?= e($id ?? $s['id']) ?>">
      <div class="service__main">
        <h2><?= e($s['title']) ?></h2>
        <?php paragraphs($s['intro']); ?>
        <h3><?= e($s['stepsTitle']) ?></h3>
        <ol class="steps">
          <?php foreach ($s['steps'] as $i => $st): ?>
            <li><span class="steps__n"><?= $i + 1 ?></span><span><?= e($st) ?></span></li>
          <?php endforeach; ?>
        </ol>
        <?php if (!empty($s['outro'])) paragraphs($s['outro'], 'prose--note'); ?>
      </div>
      <aside class="service__side">
        <?php photo_grid($s['photos'], $s['title'], $s['photoNote'] ?? $s['title'], 'srv-' . $s['id'], count($s['photos']) % 2 ? 'photo-grid--first-wide' : ''); ?>
        <div class="price-card">
          <p class="eyebrow eyebrow--sand">Cena</p>
          <p class="price-card__price"><?= e($SERVICES['priceNote']) ?></p>
          <p>Cenu dohodneme individuálne podľa lokality a rozsahu služby.</p>
          <p class="price-card__season"><?= e($s['season']) ?></p>
          <a class="btn btn--primary" href="priame-kontaktovanie">Dohodnúť službu</a>
        </div>
        <?php if (!empty($s['more'])): ?>
          <a class="link-card" href="<?= e(url($s['more']['to'])) ?>"><?= e($s['more']['label']) ?> <span aria-hidden="true">→</span></a>
        <?php endif; ?>
      </aside>
    </article>
    <?php
}

function find_service($id) {
    global $SERVICES;
    foreach ($SERVICES['services'] as $s) if ($s['id'] === $id) return $s;
    return null;
}

/** Formulár žiadosti o informácie na vyžiadanie. */
function access_request($defaultTopic = null, $id = 'na-vyziadanie') {
    global $ACCESS_TOPICS;
    $defaultTopic = $defaultTopic ?? $ACCESS_TOPICS[0];
    ?>
    <section class="band band--forest" id="<?= e($id) ?>">
      <div class="container two-col">
        <div class="reveal">
          <p class="eyebrow eyebrow--sand">Informácie na vyžiadanie</p>
          <h2>Špecifické informácie poskytujeme individuálne</h2>
          <p class="lead">Niektoré podrobnosti – napríklad povolenia, osvedčenia a potvrdenia – nezverejňujeme na webe. Všetky dokumenty Vám na vyžiadanie predložíme. Stačí nám napísať, o čo máte záujem.</p>
          <p class="small on-dark">Žiadosť vybavujeme osobne, preto prosíme o stručné uvedenie dôvodu a kontaktu.</p>
        </div>
        <form class="form-card reveal" method="post" action="kontakt-odoslat" data-ajax data-validate novalidate aria-label="Žiadosť o informácie na vyžiadanie">
          <input type="hidden" name="type" value="access">
          <input type="hidden" name="back" value="<?= e(preg_replace('/\.php$/', '', basename($_SERVER['SCRIPT_NAME']))) ?>#<?= e($id) ?>">
          <div class="hp" aria-hidden="true"><label>Nechajte prázdne <input name="website" tabindex="-1" autocomplete="off"></label></div>
          <label>O aké informácie máte záujem?
            <select name="topic"><?php foreach ($ACCESS_TOPICS as $t): ?><option<?= $t === $defaultTopic ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
          </label>
          <div class="form-row">
            <label>Meno a priezvisko * <input name="name" required minlength="2" maxlength="100" autocomplete="name"></label>
            <label>E-mail * <input name="email" type="email" required maxlength="190" autocomplete="email" inputmode="email"></label>
          </div>
          <div class="form-row">
            <label>Telefón (nepovinné) <input name="phone" type="tel" maxlength="20" autocomplete="tel" inputmode="tel"></label>
            <label>Organizácia / dôvod žiadosti <input name="org" maxlength="200" placeholder="Napríklad: mesto, správca budovy, škola…"></label>
          </div>
          <label>Správa * <textarea name="message" rows="4" required minlength="10" maxlength="5000"></textarea></label>
          <button class="btn btn--dark" type="submit">Požiadať o informácie</button>
          <?php form_status(); ?>
        </form>
      </div>
    </section>
    <?php
}

function form_status() {
    if (isset($_GET['odoslane'])) {
        echo '<p class="form-status" role="status">' . ($_GET['odoslane'] === '1'
            ? 'Ďakujeme, správa bola odoslaná. Ozveme sa vám.'
            : 'Správu sa nepodarilo odoslať. Skontrolujte vyplnené polia, zavolajte na ' . e($GLOBALS['SITE']['contact']['phones'][0]['label']) . ' alebo napíšte priamo na e-mail uvedený na stránke Kontakt.') . '</p>';
    }
}

/** Odkazy na ostatné stránky. */
function other_pages($current) {
    global $SITE;
    echo '<section class="band"><div class="container">';
    section_head('Ďalšie stránky', 'Pokračujte na ďalšiu tému');
    echo '<ul class="link-grid">';
    foreach ($SITE['pages'] as $slug => $p) {
        if ($slug === 'index' || $slug === $current || !empty($p['hidden'])) continue;
        echo '<li class="reveal"><a class="link-card" href="' . e(url($slug)) . '">' . e($p['label']) . ' <span aria-hidden="true">→</span></a></li>';
    }
    echo '</ul></div></section>';
}

/** Pole formulára (štítok, vstup, chyba zo servera / z okamžitej validácie). */
function form_field(array $o): void {
    $name = $o['name']; $type = $o['type'] ?? 'text'; $err = $o['error'] ?? '';
    $id = 'f-' . $name;
    echo '<div class="field' . ($err ? ' has-error' : '') . '">';
    echo '<label for="' . e($id) . '">' . e($o['label']) . '</label>';
    if ($type === 'password') echo '<div class="pw">';
    echo '<input id="' . e($id) . '" name="' . e($name) . '" type="' . e($type) . '"'
       . ' value="' . ($type === 'password' ? '' : e($o['value'] ?? '')) . '"'
       . (!empty($o['autocomplete']) ? ' autocomplete="' . e($o['autocomplete']) . '"' : '')
       . (!empty($o['required']) ? ' required' : '')
       . (!empty($o['placeholder']) ? ' placeholder="' . e($o['placeholder']) . '"' : '')
       . ($err ? ' aria-invalid="true"' : '')
       . ' aria-describedby="' . e($id) . '-msg"'
       . ' ' . ($o['attrs'] ?? '') . '>';
    if ($type === 'password') echo '<button type="button" class="pw__toggle" aria-label="Zobraziť heslo" aria-pressed="false"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></div>';
    if (!empty($o['strength'])) echo '<div class="strength" data-strength-for="' . e($id) . '" aria-hidden="true"><span></span></div>';
    echo '<p class="field-msg" id="' . e($id) . '-msg">' . e($err ?: ($o['hint'] ?? '')) . '</p></div>';
}

/** Boxík s hlavným upozornením nad formulárom. */
function form_alert(string $msg): void { if ($msg) echo '<p class="alert alert--error" role="alert">' . e($msg) . '</p>'; }

/** Spoločný rámec prihlasovacích stránok: ľavý panel s fotografiou, vpravo karta formulára. */
function auth_shell_start(string $eyebrow, string $title, string $lead): void {
    ?>
    <section class="auth">
      <div class="container auth__grid">
        <aside class="auth__side">
          <img src="<?= e(img_full('farma-3')) ?>" alt="" decoding="async">
          <div class="auth__side-text">
            <p class="eyebrow eyebrow--sand">OZ Divé krídla</p>
            <p class="display-on-dark">Biologická ochrana pomocou dravých vtákov.</p>
          </div>
        </aside>
        <div class="auth__main">
          <div class="auth__card">
            <p class="eyebrow"><?= e($eyebrow) ?></p>
            <h1><?= e($title) ?></h1>
            <p class="lead"><?= e($lead) ?></p>
    <?php
}
function auth_shell_end(): void { echo '</div></div></div></section>'; }
