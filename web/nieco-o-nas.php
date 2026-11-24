<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
$N = require __DIR__ . '/data/o-nas.php';
$about = $N['about'];
page_start('nieco-o-nas');
page_hero('Niečo o nás', $about['title'], $about['intro']);
?>
<section class="band">
  <div class="container">
    <?php section_head('Kto sme', 'Ciele občianskeho združenia'); ?>
    <ol class="card-grid card-grid--2 goals">
      <?php foreach ($about['goals'] as $i => $g): ?><li class="card reveal"><span class="steps__n"><?= $i + 1 ?></span><span><?= e($g) ?></span></li><?php endforeach; ?>
    </ol>
  </div>
</section>
<section class="band band--sage">
  <div class="container">
    <?php section_head('Kontakt', 'Kto za projektom stojí'); ?>
    <ul class="card-grid card-grid--2 card-grid--single">
      <?php foreach ($N['team'] as $m): ?>
        <li class="card team reveal">
          <img src="<?= e(img_thumb($m['photo'])) ?>" alt="<?= e($m['name']) ?>" loading="lazy">
          <div>
            <h3><?= e($m['name']) ?></h3><p class="small eyebrow-text"><?= e($m['role']) ?></p>
            <ul class="plain small"><?php foreach ($m['facts'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
            <p class="small"><a class="strong" href="<?= e($m['phone']['href']) ?>"><?= e($m['phone']['label']) ?></a><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php gallery_block('nieco-o-nas', 'Z našej činnosti – biologická ochrana sadov v Kanade (2006)'); ?>
  </div>
</section>
<section class="band band--forest band--tight"><div class="container cta">
  <div><p class="eyebrow eyebrow--sand">Máte otázku?</p><p class="display">Ing. Daniel Haraba · <?= e($SITE['contact']['phones'][0]['label']) ?></p></div>
  <a class="btn btn--primary" href="priame-kontaktovanie">Kontaktovať</a>
</div></section>
<?php other_pages('nieco-o-nas'); page_end();
