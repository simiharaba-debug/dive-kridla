<?php
// Rozhranie administrácie – <head>, bočný panel, horná lišta, rýchle hľadanie (Ctrl+K).
// Načíta page_start() pre stránky z ADMIN_LAYOUT_PAGES (iba prihlásený administrátor).
// Verejný dizajn (fx) sa tu zámerne nenačítava – administrácia má byť rýchla a pokojná.
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/logo.php';
$user = current_user();
$flashes = flash_take();
[$admGroup, $admItem] = adm_current();
$admActive = adm_active();
?>
<!doctype html>
<html lang="sk" class="adm" data-theme-default="<?= e(site_theme_default()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#efe7d8">
  <meta name="view-transition" content="same-origin">
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <link rel="preload" href="assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <script src="<?= e(asset('assets/js/theme-init.js')) ?>"></script>
  <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/bo-ui.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
  <script defer src="<?= e(asset('assets/js/main.js')) ?>"></script>
  <script defer src="<?= e(asset('assets/js/bo-ui.js')) ?>"></script>
  <script defer src="<?= e(asset('assets/js/admin.js')) ?>"></script>
</head>
<body data-page="<?= e($GLOBALS['CURRENT']) ?>" data-csrf="<?= e(csrf_token()) ?>">
<a class="skip" href="#obsah">Preskočiť na obsah</a>
<div class="adm-shell">
  <aside class="adm-side" id="adm-side" aria-label="Administrácia">
    <div class="adm-side__top">
      <a class="adm-brand" href="admin" title="Nástenka">
        <?= logo_svg(30, 'adm-brand__mark') ?>
        <span class="adm-brand__txt"><strong>Divé krídla</strong><small>Administrácia</small></span>
      </a>
      <button type="button" class="adm-iconbtn adm-side__close" data-adm-close aria-label="Zavrieť menu"><?= adm_icon('x') ?></button>
    </div>
    <button type="button" class="adm-search" data-adm-cmd aria-haspopup="dialog">
      <?= adm_icon('search', 16) ?><span class="adm-search__txt">Hľadať…</span><kbd class="adm-kbd">Ctrl K</kbd>
    </button>
    <nav class="adm-nav" aria-label="Sekcie administrácie">
      <?php foreach (adm_nav() as $group => $items): ?>
        <p class="adm-nav__group"><?= e($group) ?></p>
        <ul>
          <?php foreach ($items as [$key, $href, $label, $icon]): $on = $key === $admActive; ?>
            <li><a class="adm-nav__link<?= $on ? ' is-active' : '' ?>" href="<?= e($href) ?>"<?= $on ? ' aria-current="page"' : '' ?> data-tip="<?= e($label) ?>"><?= adm_icon($icon) ?><span><?= e($label) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    </nav>
    <div class="adm-side__foot">
      <a class="adm-nav__link" href="./" data-tip="Zobraziť web"><?= adm_icon('external') ?><span>Zobraziť web</span></a>
      <div class="adm-me">
        <span class="adm-me__av" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['username'], 0, 1))) ?></span>
        <span class="adm-me__txt"><strong><?= e($user['username']) ?></strong><small><?= e($user['email']) ?></small></span>
        <form method="post" action="odhlasenie" class="adm-me__out"><?= csrf_field() ?><button type="submit" class="adm-iconbtn" aria-label="Odhlásiť sa" title="Odhlásiť sa"><?= adm_icon('logout') ?></button></form>
      </div>
    </div>
  </aside>
  <div class="adm-scrim" data-adm-close hidden></div>

  <div class="adm-main">
    <header class="adm-top">
      <button type="button" class="adm-iconbtn adm-top__menu" data-adm-open aria-controls="adm-side" aria-expanded="false" aria-label="Otvoriť menu"><?= adm_icon('menu', 20) ?></button>
      <button type="button" class="adm-iconbtn adm-top__collapse" data-adm-collapse aria-label="Zbaliť bočný panel" title="Zbaliť / rozbaliť panel ( [ )"><?= adm_icon('sidebar', 19) ?></button>
      <nav class="adm-crumbs" aria-label="Umiestnenie">
        <a href="admin">Administrácia</a>
        <?php if ($admItem && $admItem[0] !== 'admin'): ?><span aria-hidden="true">/</span><span class="adm-crumbs__g"><?= e($admGroup) ?></span><?php endif; ?>
        <span aria-hidden="true">/</span><strong aria-current="page"><?= e($admItem[2] ?? ($GLOBALS['SITE']['pages'][$GLOBALS['CURRENT']]['label'] ?? '')) ?></strong>
      </nav>
      <div class="adm-top__tools">
        <button type="button" class="adm-iconbtn adm-top__search" data-adm-cmd aria-label="Rýchle hľadanie (Ctrl+K)"><?= adm_icon('search', 19) ?></button>
        <button type="button" class="adm-iconbtn theme-toggle" id="theme-toggle" aria-label="Prepnúť tmavý režim" aria-pressed="false" title="Svetlý / tmavý režim">
          <span class="i-sun"><?= adm_icon('sun', 19) ?></span><span class="i-moon"><?= adm_icon('moon', 19) ?></span>
        </button>
        <a class="adm-btn adm-btn--ghost adm-top__site" href="./" target="_blank" rel="noopener"><?= adm_icon('eye', 16) ?><span>Zobraziť web</span></a>
      </div>
    </header>
    <div class="adm-progress" aria-hidden="true"></div>
    <div class="toasts" id="toasts" role="region" aria-label="Oznámenia" aria-live="polite"<?= $flashes ? ' data-flash="' . e(json_encode($flashes, JSON_UNESCAPED_UNICODE)) . '"' : '' ?>></div>
    <main id="obsah" class="adm-content">
