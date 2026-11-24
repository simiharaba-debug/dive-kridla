<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
require_once __DIR__ . '/includes/page_photos.php';   // fotografie kariet a úvodu mení administrátor (admin-fotky)
page_start('index');
$cards = [
    ['odchyt-holubov', 'Živolovné pasce na vytipovaných strechách a odchyt na povalách, v kostolných vežiach a podkroviach.', 'Odchyt holubov', 'Z povál, veží a striech v meste'],
    ['vypustanie-sokolov', 'Sokoly rároh a sťahovavý ako prirodzený predátor holubov v meste – metodika vypúšťania metódou voľného letu.', 'Vypúšťanie sokolov', 'Prirodzený predátor holubov v meste'],
    ['biologicka-ochrana', 'Plašenie škorcov a iných vtákov v sadoch, viniciach a poliach pomocou sokoliarsky vycvičených dravcov.', 'Biologická ochrana', 'Dravce v sadoch, viniciach a na poliach'],
];
?>
<?php if (!design_fx()): require __DIR__ . '/includes/home_predosly.php'; else: $hero = page_photo('index', 'hero'); ?>
<section class="hero hx">
  <figure class="hx__photo"><span class="hx__move"><img src="<?= e($hero['full'] ?? '') ?>" alt="<?= e($hero['alt'] ?? '') ?>" fetchpriority="high" decoding="async"<?= page_photo_attr($hero) ?>></span></figure>
  <div class="container hero__text hx__text">
    <?php // texty úvodu môže zmeniť administrátor (admin-nastavenia?s=uvod), inak pôvodné z HOME_DEFAULTS ?>
    <p class="eyebrow"><?= e(site_home('eyebrow')) ?></p>
    <h1><?= e(site_home('title')) ?></h1>
    <p class="lead"><?= e(site_home('lead')) ?></p>
    <p class="hero__phone"><a href="<?= e($SITE['contact']['phones'][0]['href']) ?>"><?= e($SITE['contact']['phones'][0]['label']) ?></a><br><em><?= e(site_home('slogan')) ?></em></p>
  </div>
  <div class="container hx__deck">
    <ul class="deck" aria-label="Hlavné služby">
      <?php foreach ($cards as [$slug, , $label, $short]): ?>
        <li class="deck__item"><a class="deck__link" href="<?= e(url($slug)) ?>">
          <span><span class="deck__title"><?= e($label) ?></span><span class="deck__text"><?= e($short) ?></span></span>
          <span class="deck__arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="home-services" aria-labelledby="cesty">
  <div class="container">
    <h2 id="cesty" class="sr-only">Naše hlavné služby</h2>
    <ul class="xp">
      <?php foreach ($cards as $i => [$slug, $text, $label]): $ph = page_photo($slug, 'card'); ?>
        <li class="xp__item<?= $i === 0 ? ' is-active' : '' ?>"><a class="xp__link" href="<?= e(url($slug)) ?>" data-cursor="Otvoriť">
          <img src="<?= e($ph['full'] ?? '') ?>" alt="" loading="lazy" decoding="async"<?= page_photo_attr($ph) ?>>
          <span class="xp__body">
            <span class="xp__title"><?= e($label) ?></span>
            <span class="xp__more"><span><span class="xp__text"><?= e($text) ?></span><span class="xp__price">Cena dohodou</span></span></span>
          </span>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="band">
  <div class="container">
    <?php section_head('Na stránke nájdete', 'Odchov, sokoliarstvo, rehabilitácia a vzdelávanie'); ?>
    <?php
    $topics = [
        'odchov-sokolov' => 'Odchov dravých vtákov od vajca po vypustenie – fotografie a videá z projektu SOKOL.',
        'vseobecne-sokoliarstvo' => 'Sokol rároh a sťahovavý, ohrozenia dravcov a druhy spôsobujúce škody.',
        'rehabilitacia-vtakov' => 'Starostlivosť o handicapované živočíchy a nález poraneného dravca.',
        'anatomia-dravcov' => 'Perie, zmysly, trávenie a vtlačenie – ako je dravec stavaný a ako vníma svet.',
        'zdravie-dravcov' => 'Príznaky chorôb, prvá pomoc, sokoliarska lekárnička a prevencia.',
        'nieco-o-nas' => 'Občianske združenie OZ Divé krídla, jeho ciele a kontakt.',
        'priame-kontaktovanie' => 'Telefón, e-mail a formulár. Informácie a dokumenty na vyžiadanie.',
    ];
    if (design_fx()) {   // ôsma dlaždica – rovnomerná mriežka 4 × 2; Kontakt ostáva posledný
        $kontakt = array_pop($topics);
        $topics += ['fotogaleria' => 'Všetky fotografie a videá rozdelené podľa tém.', 'priame-kontaktovanie' => $kontakt];
    }
    ?>
    <ul class="bo-cards <?= design_fx() ? 'themes' : 'bo-cards--3' ?>">
      <?php foreach ($topics as $slug => $text): $ph = page_photo($slug, 'card'); ?>
        <li class="reveal">
          <a class="bo-card" href="<?= e(url($slug)) ?>">
            <span class="bo-card__media"><?php if ($ph): ?><img src="<?= e($ph['thumb']) ?>" alt="" loading="lazy" decoding="async"<?= page_photo_attr($ph) ?>><?php endif; ?></span>
            <span class="bo-card__body">
              <span class="bo-card__title"><?= e($SITE['pages'][$slug]['label']) ?></span>
              <span class="bo-card__text"><?= e($text) ?></span>
              <span class="bo-card__more">Zistiť viac</span>
            </span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php if (design_fx()): ?>
<section class="law" aria-label="Prírodný zákon dravec a jeho korisť">
  <figure class="law__photo"><img src="<?= e(img_full('kanada-13')) ?>" alt="<?= e(media_alt('kanada-13')) ?>" loading="lazy" decoding="async"></figure>
  <div class="law__body">
    <p class="law__say">Dravec a jeho korisť. Na tomto prírodnom zákone stojí naša práca v mestách, sadoch aj viniciach.</p>
    <ul class="law__stats stats-num">
      <?php foreach ($SERVICES['stats'] as $st): ?><li><strong><?= e($st['value']) ?></strong><span><?= e($st['label']) ?></span></li><?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<section class="band band--sage" id="galeria">
  <div class="container">
    <?php section_head('Fotogaléria', 'Z našej práce', 'Fotografie sú rozdelené podľa tém – od odchytu holubov cez vypúšťanie sokolov až po odchov mláďat.'); ?>
    <ul class="gcats">
      <?php   // kategórie spravuje admin vo fotogalérii (názov, náhľad, poradie, odstránenie)
        $gcats = [];
        try { gallery_seed(); foreach (gallery_categories('public', 'photo') as $gc) { $its = gallery_items($gc); if ($its) $gcats[] = [$gc, $its]; if (count($gcats) === (design_fx() ? 8 : 6)) break; } }
        catch (Throwable $e) { error_log('[galeria] ' . $e->getMessage()); }
        foreach ($gcats as [$c, $its]): $n = count($its); ?>
        <li data-reveal>
          <a class="gcat" href="fotogaleria?kategoria=<?= e($c['slug']) ?>">
            <span class="gcat__media"><img src="<?= e(gallery_cover_src($c, $its)) ?>" alt="" loading="lazy" decoding="async"><span class="gcat__count"><?= $n ?> <?= e(sk_photos($n)) ?></span></span>
            <span class="gcat__body"><span class="gcat__title"><?= e($c['title']) ?></span><?php if ($c['description'] !== ''): ?><span class="gcat__text"><?= e($c['description']) ?></span><?php endif; ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="center btn-row btn-row--center" style="margin-top:1.5rem"><a class="btn btn--primary" href="fotogaleria">Všetky kategórie vrátane videí →</a></p>
  </div>
</section>

<section class="band band--forest band--tight">
  <div class="container cta">
    <div>
      <h2><?= e(site_home('cta_title')) ?></h2>
      <p><?= e(site_home('cta_text')) ?></p>
    </div>
    <div class="btn-row">
      <a class="btn btn--primary" href="<?= e($SITE['contact']['phones'][0]['href']) ?>"><?= e($SITE['contact']['phones'][0]['label']) ?></a>
      <a class="btn btn--ghost" href="priame-kontaktovanie">Priame kontaktovanie</a>
    </div>
  </div>
</section>
<?php page_end();
