<?php
// Hlavička: <head>, menu. Premenné $title, $desc a $CURRENT nastavuje page_start().
$c = $SITE['contact'];
$cur = $GLOBALS['CURRENT'];
$main = array_filter($SITE['pages'], fn($p) => !empty($p['main']) || !empty($p['nav']));
$more = array_filter($SITE['pages'], fn($p, $k) => empty($p['main']) && empty($p['nav']) && empty($p['hidden']) && !in_array($k, ['index', 'priame-kontaktovanie']), ARRAY_FILTER_USE_BOTH);
$user = current_user();
$flashes = flash_take();
$noindex = $noindex ?? false;
require_once __DIR__ . '/site_settings.php';
require_once __DIR__ . '/logo.php';
$fx = design_fx();   // vzhľad webu volí administrátor (admin → Vzhľad webu), predošlý dizajn = bez vrstvy fx
$htmlClass = trim(($fx ? 'fx ' . site_fx_classes() : ''));   // fx-off-… = efekt vypnutý v administrácii
$banner = site_banner();
?>
<!doctype html>
<html lang="sk"<?= $htmlClass !== '' ? ' class="' . e($htmlClass) . '"' : '' ?> data-theme-default="<?= e(site_theme_default()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="<?= e(site_theme_color('light')) ?>" data-dark="<?= e(site_theme_color('dark')) ?>">
<?php if ($noindex): ?>  <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
  <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="sk_SK">
  <meta property="og:site_name" content="OZ Divé krídla – biologická ochrana">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($desc) ?>">
  <meta property="og:image" content="<?= e(abs_url('assets/og.jpg')) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
<?php seo_head($title, $desc, $noindex); // canonical, schema.org – includes/seo.php ?>
  <link rel="preload" href="assets/fonts/fraunces-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <script src="<?= e(asset('assets/js/theme-init.js')) ?>"></script>
  <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/bo-ui.css')) ?>">
  <script defer src="<?= e(asset('assets/js/speed.js')) ?>"></script>
  <script defer src="<?= e(asset('assets/js/main.js')) ?>"></script>
  <script defer src="<?= e(asset('assets/js/bo-ui.js')) ?>"></script>
<?php if ($fx): ?>
  <link rel="stylesheet" href="<?= e(asset('assets/css/fx.css')) ?>">
  <script defer src="<?= e(asset('assets/js/fx.js')) ?>"></script>
<?php if ($cur === 'index'): // GSAP potrebuje iba úvod, podstránkam stačí Lenis ?>  <link rel="stylesheet" href="<?= e(asset('assets/css/home.css')) ?>">
  <script defer src="<?= e(asset('assets/js/vendor/gsap.min.js')) ?>"></script>
  <script defer src="<?= e(asset('assets/js/vendor/ScrollTrigger.min.js')) ?>"></script>
<?php endif; ?>
  <script defer src="<?= e(asset('assets/js/vendor/lenis.min.js')) ?>"></script>
  <script defer src="<?= e(asset('assets/js/home.js')) ?>"></script>
<?php endif; ?>
<?php if (($palCss = site_palette_css()) !== ''): // vlastná farebná paleta z administrácie (admin-nastavenia?s=farby) ?>
  <style id="dk-palette"><?= $palCss ?></style>
<?php endif; ?>
</head>
<body data-page="<?= e($cur) ?>"<?= ($user && $user['role'] === 'admin') ? ' data-csrf="' . e(csrf_token()) . '"' : '' ?>>
<a class="skip" href="#obsah">Preskočiť na obsah</a>
<?php if ($banner): $bid = substr(md5($banner['text'] . $banner['link']), 0, 10); ?>
<div class="site-banner" role="region" aria-label="Oznámenie" data-banner="<?= e($bid) ?>">
  <p class="site-banner__txt"><?= e($banner['text']) ?><?php if ($banner['link'] !== ''): ?> <a href="<?= e($banner['link']) ?>"><?= e($banner['link_label'] !== '' ? $banner['link_label'] : 'Viac informácií') ?> <span aria-hidden="true">→</span></a><?php endif; ?></p>
  <button type="button" class="site-banner__x" aria-label="Skryť oznámenie">×</button>
</div>
<?php endif; ?>
<header class="site-header">
  <div class="container site-header__bar">
    <a class="logo" href="./">
      <?= logo_svg(40, 'logo__mark') ?>
      <span><strong>Divé krídla</strong> <small>Biologická ochrana</small><b class="sr-only">, úvodná stránka</b></span>
    </a>
    <button class="nav-toggle" aria-expanded="false" aria-controls="menu" aria-label="Otvoriť menu"><span></span><span></span><span></span></button>
    <nav id="menu" class="nav" aria-label="Hlavná navigácia">
      <ul class="nav__main">
        <?php foreach ($main as $slug => $p): ?>
          <li><a class="nav__link nav__link--main<?= $cur === $slug ? ' is-active' : '' ?>" href="<?= e(url($slug)) ?>"<?= $cur === $slug ? ' aria-current="page"' : '' ?>><?= e($p['short']) ?></a></li>
        <?php endforeach; ?>
        <li class="nav__more">
          <button class="nav__link nav__more-btn" aria-expanded="false" aria-controls="more-menu">Ďalšie <span aria-hidden="true">▾</span></button>
          <ul id="more-menu" class="nav__dropdown">
            <?php foreach ($more as $slug => $p): ?>
              <li><a href="<?= e(url($slug)) ?>"<?= $cur === $slug ? ' aria-current="page"' : '' ?>><?= e($p['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>
      <div class="nav__tools">
        <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Prepnúť tmavý režim" aria-pressed="false" title="Svetlý / tmavý režim">
          <svg class="i-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="i-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
        </button>
        <?php if ($user): ?>
          <div class="nav__more nav__user">
            <button class="nav__link nav__more-btn nav__user-btn" aria-expanded="false" aria-controls="user-menu"><span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['username'], 0, 1))) ?></span><span class="nav__user-name"><?= e($user['username']) ?></span> <span aria-hidden="true">▾</span></button>
            <ul id="user-menu" class="nav__dropdown nav__dropdown--right">
              <li class="nav__dropdown-meta"><strong><?= e($user['username']) ?></strong><br><?= e($user['email']) ?><br><span class="role-badge role-badge--<?= e($user['role']) ?>"><?= $user['role'] === 'admin' ? 'Administrátor' : 'Používateľ' ?></span></li>
              <li><a href="ucet">Môj účet</a></li>
              <?php if ($user['role'] === 'admin'): ?>
                <li><a href="admin">Administrácia</a></li>
                <li><a href="admin-fotky">Fotografie stránok</a></li>
                <li><a href="admin-nastavenia">Nastavenia webu</a></li>
                <li><a href="fotogaleria#sprava">Správa verejnej galérie</a></li>
                <li><a href="moja-galeria">Moja administrátorská galéria</a></li>
              <?php elseif (can_view_private_gallery()): // právo iba na prezeranie ?>
                <li><a href="moja-galeria">Administrátorská galéria</a></li>
              <?php endif; ?>
              <li><form method="post" action="odhlasenie"><?= csrf_field() ?><button type="submit" class="nav__logout">Odhlásiť sa</button></form></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="nav__login<?= $cur === 'prihlasenie' ? ' is-active' : '' ?>" href="prihlasenie">Prihlásiť sa</a>
        <?php endif; ?>
        <a class="btn btn--primary nav__cta" href="priame-kontaktovanie">Kontakt</a>
        <a class="nav__phone" href="<?= e($c['phones'][0]['href']) ?>"><?= e($c['phones'][0]['label']) ?></a>
      </div>
    </nav>
  </div>
</header>
<div class="toasts" id="toasts" role="region" aria-label="Oznámenia" aria-live="polite"<?= $flashes ? ' data-flash="' . e(json_encode($flashes, JSON_UNESCAPED_UNICODE)) . '"' : '' ?>></div>
<main id="obsah">
