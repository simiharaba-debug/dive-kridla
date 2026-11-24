<?php
// Administrácia – nástenka: štatistiky, stav webu, rýchle akcie, posledné zmeny, knižnica médií a nahrané súbory.
// Rozhranie (bočný panel, Ctrl+K) = includes/admin_header.php + admin_ui.php. Vzhľad webu sa od 22. 11. 2026
// nastavuje v admin-nastavenia?s=vzhlad (starý formulár z tejto stránky sa tam presmeruje).
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/videos.php';
require_once __DIR__ . '/includes/gallery.php';
require_once __DIR__ . '/includes/bo_ui.php';
require_once __DIR__ . '/includes/admin_ui.php';
$admin = require_admin();          // neprihlásený → prihlásenie, bežný používateľ → 403 (rozhoduje databáza)
$pdo = db();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { header('Location: admin-nastavenia?s=vzhlad', true, 303); exit; }

/* ---------- Knižnica médií (pôvodné fotografie + galéria v databáze) ---------- */
$lib = ['cats' => 0, 'photos' => 0, 'videos' => 0, 'hidden' => 0, 'list' => []];
try {
    gallery_seed();
    foreach (gallery_categories('public') as $gc) {
        $its = gallery_items($gc);
        $lib['cats']++;
        if ($gc['kind'] === 'video') $lib['videos'] += count($its); else $lib['photos'] += count($its);
        $lib['list'][] = [$gc, $its];
    }
    $lib['hidden'] = count(gallery_removed_static());
} catch (Throwable $e) { error_log('[admin] ' . $e->getMessage()); }
$counts = $pdo->query("SELECT (SELECT COUNT(*) FROM users) AS users, (SELECT COUNT(*) FROM users WHERE role='admin') AS admins,
    (SELECT COUNT(*) FROM media WHERE kind='image') AS images, (SELECT COUNT(*) FROM media WHERE kind='video') AS videos,
    (SELECT COALESCE(SUM(size),0) FROM media) AS bytes, (SELECT COUNT(*) FROM private_media) AS private_items,
    (SELECT COUNT(*) FROM page_photos) AS page_photos")->fetch();
$media = media_list();
$videoCount = count(videos_all());

/* ---------- Stav nastavení ---------- */
$set = site_settings_all();
$banner = site_banner();
$seoCount = count(array_filter(site_setting_json('seo'), fn($v) => is_array($v) && (($v['title'] ?? '') !== '' || ($v['description'] ?? '') !== '')));
$homeCustom = count(array_filter(site_setting_json('home'), fn($v) => trim((string)$v) !== ''));
$fxOff = count(array_filter(site_fx_flags(), fn($on) => !$on));

/* ---------- Posledné zmeny (nastavenia, fotografie stránok, nahrané súbory, registrácie) ---------- */
$SET_LABELS = ['design' => ['Vzhľad webu', 'palette', 'admin-nastavenia?s=vzhlad'], 'theme_default' => ['Predvolený režim', 'moon', 'admin-nastavenia?s=vzhlad'],
    'fx_flags' => ['Efekty dizajnu', 'sparkle', 'admin-nastavenia?s=vzhlad'], 'contact' => ['Kontaktné údaje', 'phone', 'admin-nastavenia?s=kontakt'],
    'home' => ['Texty úvodnej stránky', 'layout', 'admin-nastavenia?s=uvod'], 'banner' => ['Oznámenie na webe', 'megaphone', 'admin-nastavenia?s=oznamenie'],
    'seo' => ['Titulky pre vyhľadávače', 'search', 'admin-nastavenia?s=seo'], 'palette' => ['Farby webu', 'palette', 'admin-nastavenia?s=farby']];
$names = [];
foreach ($pdo->query('SELECT id, username FROM users')->fetchAll() as $u) $names[(int)$u['id']] = $u['username'];
$feed = [];
foreach ($set as $n => $r) if (isset($SET_LABELS[$n])) $feed[] = ['t' => $r['updated_at'], 'ic' => $SET_LABELS[$n][1], 'title' => $SET_LABELS[$n][0] . ' – zmenené', 'who' => $names[(int)$r['updated_by']] ?? '', 'href' => $SET_LABELS[$n][2]];
try {
    foreach ($pdo->query('SELECT slug, slot, updated_by, updated_at FROM page_photos ORDER BY updated_at DESC LIMIT 6')->fetchAll() as $r) {
        $feed[] = ['t' => $r['updated_at'], 'ic' => 'image', 'title' => ($r['slot'] === 'hero' ? 'Hlavná fotografia' : 'Náhľad') . ': ' . ($r['slug'] === 'index' ? 'Úvodná stránka' : ($SITE['pages'][$r['slug']]['label'] ?? $r['slug'])), 'who' => $names[(int)$r['updated_by']] ?? '', 'href' => 'admin-fotky#p-' . $r['slug']];
    }
} catch (Throwable $e) { error_log('[admin] ' . $e->getMessage()); }
foreach (array_slice($media, 0, 6) as $m) $feed[] = ['t' => $m['created_at'], 'img' => $m['thumb_path'], 'title' => 'Nahraté: ' . ($m['title'] ?: '(bez názvu)'), 'who' => $m['author'] ?? '', 'href' => 'fotogaleria#sprava'];
foreach ($pdo->query('SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 4')->fetchAll() as $u) $feed[] = ['t' => $u['created_at'], 'ic' => 'user', 'title' => 'Nový účet: ' . $u['username'], 'who' => '', 'href' => 'admin-pouzivatelia'];
usort($feed, fn($a, $b) => strcmp((string)$b['t'], (string)$a['t']));
$feed = array_slice($feed, 0, 8);

$hour = (int)date('G');
$hello = $hour < 10 ? 'Dobré ráno' : ($hour < 18 ? 'Dobrý deň' : 'Dobrý večer');
$GLOBALS['ADMIN_ACTIVE'] = 'admin';
page_start('admin');
adm_head($hello . ', ' . $admin['username'], 'Prehľad webu na jednom mieste. Kamkoľvek sa dostanete aj cez rýchle hľadanie – stlačte Ctrl + K.',
    '<a class="adm-btn adm-btn--ghost" href="./" target="_blank" rel="noopener">' . adm_icon('eye', 16) . 'Zobraziť web</a><a class="adm-btn" href="fotogaleria#sprava">' . adm_icon('upload', 16) . 'Nahrať fotografie</a>', 'home');
?>
<ul class="adm-stats">
  <li><a class="adm-stat" href="fotogaleria#sprava"><span class="adm-stat__ic"><?= adm_icon('image', 17) ?></span><span class="adm-stat__n" data-adm-num><?= (int)$lib['photos'] ?></span><span class="adm-stat__l">fotografií v knižnici · <?= (int)$lib['cats'] ?> kategórií</span></a></li>
  <li><a class="adm-stat" href="fotogaleria?kategoria=videa"><span class="adm-stat__ic"><?= adm_icon('video', 17) ?></span><span class="adm-stat__n"><?= $videoCount ?></span><span class="adm-stat__l">videí z YouTube · <?= videos_db_count() ?> pridaných cez web</span></a></li>
  <li><a class="adm-stat" href="admin-pouzivatelia"><span class="adm-stat__ic"><?= adm_icon('users', 17) ?></span><span class="adm-stat__n"><?= (int)$counts['users'] ?></span><span class="adm-stat__l">používateľov · <?= (int)$counts['admins'] ?> admin</span></a></li>
  <li><a class="adm-stat" href="admin-fotky"><span class="adm-stat__ic"><?= adm_icon('layout', 17) ?></span><span class="adm-stat__n"><?= (int)$counts['page_photos'] ?></span><span class="adm-stat__l">zmenených fotografií stránok</span></a></li>
  <li><a class="adm-stat" href="#subory"><span class="adm-stat__ic"><?= adm_icon('database', 17) ?></span><span class="adm-stat__n"><?= e(fmt_size((int)$counts['bytes'])) ?></span><span class="adm-stat__l">nahrané cez web · <?= (int)$counts['images'] ?> <?= e(sk_photos((int)$counts['images'])) ?></span></a></li>
  <li><a class="adm-stat" href="moja-galeria"><span class="adm-stat__ic"><?= adm_icon('lock', 17) ?></span><span class="adm-stat__n"><?= (int)$counts['private_items'] ?></span><span class="adm-stat__l">položiek v mojej galérii</span></a></li>
</ul>

<div class="adm-grid adm-grid--main">
  <div>
    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('sparkle') ?>Rýchle akcie</h2></div>
      <ul class="adm-quick">
        <li><a href="fotogaleria#sprava"><span class="adm-quick__ic"><?= adm_icon('upload') ?></span><span><strong>Nahrať fotografie</strong><small>do verejnej galérie</small></span></a></li>
        <li><a href="fotogaleria?kategoria=videa#sprava"><span class="adm-quick__ic"><?= adm_icon('video') ?></span><span><strong>Pridať video</strong><small>odkazom z YouTube</small></span></a></li>
        <li><a href="admin-fotky"><span class="adm-quick__ic"><?= adm_icon('image') ?></span><span><strong>Zmeniť fotku stránky</strong><small>hlavná fotografia a náhľad</small></span></a></li>
        <li><a href="admin-nastavenia?s=uvod"><span class="adm-quick__ic"><?= adm_icon('text') ?></span><span><strong>Upraviť úvod</strong><small>nadpis, perex, slogan</small></span></a></li>
        <li><a href="admin-nastavenia?s=oznamenie"><span class="adm-quick__ic"><?= adm_icon('megaphone') ?></span><span><strong>Oznámenie na webe</strong><small>pruh nad hlavičkou</small></span></a></li>
        <li><a href="admin-nastavenia?s=kontakt"><span class="adm-quick__ic"><?= adm_icon('phone') ?></span><span><strong>Kontaktné údaje</strong><small>telefón a e-mail</small></span></a></li>
      </ul>
    </section>

    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('grid') ?>Knižnica médií</h2><a class="adm-card__link" href="fotogaleria#sprava">Spravovať galériu <?= adm_icon('arrow', 14) ?></a>
        <p>Rovnaké kategórie ako vo fotogalérii. Hlavné fotografie stránok sa vyberajú z tejto knižnice.<?php if ($lib['hidden']): ?> Skrytých pôvodných položiek: <?= (int)$lib['hidden'] ?> (obnoviť ich možno v správe galérie).<?php endif; ?></p></div>
      <ul class="adm-lib">
        <?php foreach ($lib['list'] as [$gc, $its]): $cover = $its ? gallery_cover_src($gc, $its) : null; $n = count($its); ?>
          <li><a href="fotogaleria?kategoria=<?= e($gc['slug']) ?>#sprava">
            <span class="adm-lib__media"><?php if ($cover): ?><img src="<?= e($cover) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?></span>
            <span class="adm-lib__body"><strong><?= e($gc['title']) ?></strong><small><?= $n ?> <?= $gc['kind'] === 'video' ? ($n === 1 ? 'video' : ($n >= 2 && $n <= 4 ? 'videá' : 'videí')) : e(sk_photos($n)) ?></small></span>
          </a></li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>

  <div>
    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('shield') ?>Stav webu</h2></div>
      <ul class="adm-list">
        <li><span class="adm-list__ic"><?= adm_icon('palette', 16) ?></span><span class="adm-list__txt"><strong>Vzhľad</strong><small><?= e(SITE_DESIGNS[site_design()][0]) ?> · <?= e(SITE_THEMES[site_theme_default()][0]) ?><?= $fxOff ? ' · vypnuté efekty: ' . $fxOff : '' ?></small></span><a class="adm-card__link" href="admin-nastavenia?s=vzhlad">Zmeniť</a></li>
        <li><span class="adm-list__ic"><?= adm_icon('megaphone', 16) ?></span><span class="adm-list__txt"><strong>Oznámenie na webe</strong><small><?= $banner ? e(mb_strimwidth($banner['text'], 0, 60, '…')) : 'Návštevníci žiadne oznámenie nevidia' ?></small></span>
          <?= $banner ? '<span class="adm-badge adm-badge--on"><span class="adm-dot"></span>Zapnuté</span>' : '<span class="adm-badge adm-badge--off">Vypnuté</span>' ?></li>
        <li><span class="adm-list__ic"><?= adm_icon('layout', 16) ?></span><span class="adm-list__txt"><strong>Úvodná stránka</strong><small><?= $homeCustom ? 'Vlastné texty: ' . $homeCustom . ' z ' . count(HOME_DEFAULTS) : 'Pôvodné texty' ?></small></span><a class="adm-card__link" href="admin-nastavenia?s=uvod">Upraviť</a></li>
        <li><span class="adm-list__ic"><?= adm_icon('search', 16) ?></span><span class="adm-list__txt"><strong>Vyhľadávače</strong><small><?= $seoCount ? 'Vlastné titulky/popisy: ' . $seoCount . ' stránok' : 'Pôvodné titulky a popisy' ?></small></span><a class="adm-card__link" href="admin-nastavenia?s=seo">Upraviť</a></li>
        <li><span class="adm-list__ic"><?= adm_icon('phone', 16) ?></span><span class="adm-list__txt"><strong>Kontakt</strong><small><?= e($SITE['contact']['phones'][0]['label']) ?> · <?= e($SITE['contact']['emails'][0] ?? '') ?></small></span><a class="adm-card__link" href="admin-nastavenia?s=kontakt">Upraviť</a></li>
      </ul>
    </section>

    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('clock') ?>Posledné zmeny</h2></div>
      <?php if (!$feed): ?><div class="adm-empty"><p>Zatiaľ žiadne zmeny.</p></div><?php else: ?>
      <ul class="adm-list">
        <?php foreach ($feed as $f): ?>
          <li><span class="adm-list__ic"><?= !empty($f['img']) ? '<img src="' . e($f['img']) . '" alt="" loading="lazy">' : adm_icon($f['ic'], 16) ?></span>
            <span class="adm-list__txt"><a href="<?= e($f['href']) ?>"><strong><?= e($f['title']) ?></strong></a><small><?= e(adm_ago($f['t'])) ?><?= $f['who'] !== '' ? ' · ' . e($f['who']) : '' ?></small></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>
  </div>
</div>

<section class="adm-card adm-section" id="subory">
  <div class="adm-card__head"><h2><?= adm_icon('upload') ?>Nahrané súbory</h2><span class="adm-badge" data-adm-filter-count="#adm-media"><?= count($media) ?></span>
    <p>Všetko, čo bolo nahraté cez web do verejnej galérie. Upraviť alebo odstrániť ich môžete priamo v galérii.</p></div>
  <?php if (!$media): ?><div class="adm-empty"><?= adm_icon('image', 28) ?><p>Zatiaľ nebol nahraný žiadny súbor.</p><a class="adm-btn adm-btn--sm" href="fotogaleria#sprava">Nahrať prvý súbor</a></div>
  <?php else: ?>
  <div class="adm-tools"><label class="adm-input-icon"><?= adm_icon('search', 16) ?><span class="sr-only">Hľadať v nahraných súboroch</span><input type="search" placeholder="Hľadať podľa názvu alebo autora…" data-adm-filter="#adm-media" autocomplete="off"></label></div>
  <div class="adm-tablewrap"><table class="adm-table adm-table--cards" id="adm-media">
    <thead><tr><th>Náhľad</th><th>Názov</th><th>Typ</th><th>Veľkosť</th><th>Nahral</th><th>Dátum</th></tr></thead>
    <tbody><?php foreach ($media as $m): ?>
      <tr><td><img class="adm-table__thumb" src="<?= e($m['thumb_path']) ?>" alt="" loading="lazy"></td>
        <td><strong><?= e($m['title'] ?: '(bez názvu)') ?></strong></td><td data-l="Typ"><?= $m['kind'] === 'video' ? 'Video' : 'Fotografia' ?></td>
        <td data-l="Veľkosť"><?= e(fmt_size((int)$m['size'])) ?></td><td data-l="Nahral"><?= e($m['author'] ?? '–') ?></td>
        <td data-l="Dátum"><?= e(date('j. n. Y H:i', strtotime($m['created_at']))) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php page_end();
