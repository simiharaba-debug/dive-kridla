<?php // Úvod a tri služby v predošlom dizajne (admin → Vzhľad webu). Premenné $cards a $SITE nastavuje index.php. ?>
<section class="hero">
  <div class="container hero__grid">
    <div class="hero__text">
      <?php // texty úvodu môže zmeniť administrátor (admin-nastavenia?s=uvod), inak pôvodné z HOME_DEFAULTS ?>
      <p class="eyebrow"><?= e(site_home('eyebrow')) ?></p>
      <h1><?= e(site_home('title')) ?></h1>
      <p class="lead"><?= e(site_home('lead')) ?></p>
      <div class="btn-row">
        <a class="btn btn--primary" href="odchyt-holubov">Odchyt holubov</a>
        <a class="btn btn--dark" href="vypustanie-sokolov">Vypúšťanie sokolov</a>
        <a class="btn btn--outline" href="biologicka-ochrana">Biologická ochrana</a>
      </div>
      <p class="hero__phone"><a href="<?= e($SITE['contact']['phones'][0]['href']) ?>"><?= e($SITE['contact']['phones'][0]['label']) ?></a> · <em><?= e(site_home('slogan')) ?></em></p>
    </div>
    <figure class="frame hero__photo">
      <?php $hero = page_photo('index', 'hero'); ?>
      <img src="<?= e($hero['full'] ?? '') ?>" alt="<?= e($hero['alt'] ?? '') ?>" fetchpriority="high" decoding="async"<?= page_photo_attr($hero) ?>>
      <figcaption><?= e($hero['alt'] ?? '') ?></figcaption>
    </figure>
  </div>
</section>

<section class="paths" aria-labelledby="cesty">
  <div class="container">
    <h2 id="cesty" class="sr-only">Naše hlavné služby</h2>
    <ul class="path-grid">
      <?php foreach ($cards as $i => [$slug, $text, $label]): $ph = page_photo($slug, 'card'); ?>
        <li class="reveal">
          <a class="path-card" href="<?= e(url($slug)) ?>">
            <img src="<?= e($ph['thumb'] ?? '') ?>" alt="" loading="<?= $i ? 'lazy' : 'eager' ?>" decoding="async" width="800" height="450"<?= page_photo_attr($ph) ?>>
            <span class="path-card__body">
              <span class="path-card__num">0<?= $i + 1 ?></span>
              <span class="path-card__title"><?= e($label) ?></span>
              <span class="path-card__text"><?= e($text) ?></span>
              <span class="path-card__foot"><span class="tag">Cena dohodou</span><span class="path-card__go">Zistiť viac →</span></span>
            </span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

