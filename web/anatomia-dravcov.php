<?php
// Anatómia, morfológia a psychické schopnosti dravcov. Texty: data/anatomia.php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
$A = require __DIR__ . '/data/anatomia.php';
page_start('anatomia-dravcov');
page_hero('Anatómia a správanie', 'Ako je dravec stavaný a ako vníma svet', $A['lead']);
?>
<nav class="subnav" aria-label="Obsah stránky"><ul class="container"><?php foreach ($A['sections'] as $s): ?><li><a href="#<?= e($s['id']) ?>"><?= e($s['title']) ?></a></li><?php endforeach; ?></ul></nav>

<?php foreach ($A['sections'] as $i => $s): $alt = $i % 2 === 1; ?>
<section class="band<?= $alt ? ' band--sage' : '' ?>" id="<?= e($s['id']) ?>">
  <div class="container">
    <?php section_head(sprintf('%02d', $i + 1), $s['title']); ?>
    <div class="prose-media<?= $alt ? ' prose-media--rev' : '' ?>">
      <div class="reveal">
        <?php paragraphs($s['intro']); ?>
        <?php if (!empty($s['list'])): ?>
          <h3><?= e($s['listTitle']) ?></h3>
          <ul class="checks"><?php foreach ($s['list'] as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (!empty($s['note'])): ?><p class="bo-note"><?= e($s['note']) ?></p><?php endif; ?>
      </div>
      <div class="reveal">
        <?php if ($s['id'] === 'zmysly'): ?>
          <div class="bo-fact" style="margin-bottom:1.25rem">
            <p class="bo-fact__label">Vedeli ste?</p>
            <p class="bo-fact__main"><?= e($A['fact']['main']) ?></p>
            <p><?= e($A['fact']['text']) ?></p>
          </div>
        <?php endif; ?>
        <?php if (!empty($s['photo'])) media_figure($s['photo']); ?>
        <?php if (!empty($s['box'])): ?>
          <div class="card" style="margin-top:1.25rem">
            <h3><?= e($s['boxTitle']) ?></h3>
            <?php paragraphs($s['box']); ?>
          </div>
        <?php endif; ?>
        <?php if ($s['id'] === 'striekanec'): ?>
          <div class="card">
            <h3>Kedy spozornieť</h3>
            <p>Zmena farby, pachu alebo konzistencie striekanca môže byť prvým príznakom ochorenia tráviacej sústavy, pečene či obličiek.</p>
            <a class="link-card" href="zdravie-dravcov#priznaky">Príznaky ochorení a prvá pomoc <span aria-hidden="true">→</span></a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php if (!empty($s['senses'])): ?>
      <ul class="card-grid card-grid--4" style="margin-top:1.5rem">
        <?php foreach ($s['senses'] as $x): ?><li class="card reveal"><h3><?= e($x['title']) ?></h3><p class="small"><?= e($x['text']) ?></p></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="band band--forest band--tight"><div class="container cta">
  <div><p class="eyebrow eyebrow--sand">Súvisiace témy</p><p class="display">Ako spoznať chorého dravca a ako prebieha odchov mláďat</p></div>
  <div class="btn-row"><a class="btn btn--primary" href="zdravie-dravcov">Zdravie dravcov</a><a class="btn btn--ghost" href="odchov-sokolov">Odchov mláďat</a></div>
</div></section>
<?php other_pages('anatomia-dravcov'); page_end();
