<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
$R = (require __DIR__ . '/data/rehabilitacia.php')['rehabilitacia'];
$S = require __DIR__ . '/data/sokoliarstvo.php';
$titles = ['Elektrické vedenia', 'Ilegálny odstrel a pasce', 'Jedy a chemizácia', 'Vyrušovanie a doprava'];
page_start('rehabilitacia-vtakov');
page_hero('Rehabilitácia vtákov', 'Starostlivosť o handicapované živočíchy a návrat do prírody', $R['lead']);
?>
<section class="band">
  <div class="container two-col">
    <div>
      <?php section_head('Naše ciele', 'Čomu sa združenie venuje'); ?>
      <ul class="card-grid"><?php foreach ($R['goals'] as $g): ?><li class="card reveal"><p><?= e($g) ?></p></li><?php endforeach; ?></ul>
    </div>
    <div class="dark-card reveal">
      <h2><?= e($R['found']['title']) ?></h2>
      <p class="prose--light"><?= e($R['found']['text']) ?></p>
      <a class="btn btn--primary" href="<?= e($R['found']['link']['href']) ?>"><?= e($R['found']['link']['label']) ?></a>
    </div>
  </div>
</section>
<section class="band band--tight">
  <div class="container">
    <div class="cta cta--light cta--photo reveal">
      <?php media_figure('oko-2'); ?>
      <div><p class="eyebrow">Veterinárna starostlivosť</p><p class="display">Ako spoznať chorého dravca, čo patrí do lekárničky a ktorým chorobám predchádzať</p></div>
      <a class="btn btn--dark" href="zdravie-dravcov">Zdravie dravcov</a>
    </div>
  </div>
</section>
<section class="band band--sage">
  <div class="container">
    <?php section_head('Prečo dravce potrebujú pomoc', 'Najčastejšie ohrozenia, ktorým čelia'); ?>
    <ul class="card-grid card-grid--4">
      <?php foreach ($S['threats'] as $t): if (!in_array($t['title'], $titles)) continue; ?>
        <li class="card reveal"><h3><?= e($t['title']) ?></h3><p class="small"><?= e($t['text']) ?></p></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php access_request($ACCESS_TOPICS[3], 'informacie'); other_pages('rehabilitacia-vtakov'); page_end();
