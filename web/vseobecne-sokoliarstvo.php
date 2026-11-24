<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
$S = require __DIR__ . '/data/sokoliarstvo.php';
$raroh = $S['raroh']; $stah = $S['stahovavy'];
$plasenie = find_service('plasenie');
page_start('vseobecne-sokoliarstvo');
page_hero('Všeobecné sokoliarstvo', 'Sokoly, dravce a ich využitie v biologickej ochrane', 'Dravé vtáky ako vrcholoví predátori – ich biológia, ohrozenia a sokoliarska práca v agrárnej krajine.');
$nav = ['ochrana' => 'Biologická ochrana', 'raroh' => 'Sokol rároh', 'stahovavy' => 'Sokol sťahovavý', 'ohrozenia' => 'Ohrozenia dravcov', 'skodcovia' => 'Druhy spôsobujúce škody'];
?>
<nav class="subnav" aria-label="Obsah stránky"><ul class="container"><?php foreach ($nav as $id => $l): ?><li><a href="#<?= $id ?>"><?= e($l) ?></a></li><?php endforeach; ?></ul></nav>

<section class="band" id="ochrana">
  <div class="container two-col two-col--wide">
    <div>
      <?php section_head('Biologická ochrana', 'Sokoliarsky vycvičené dravce v sadoch, vinohradoch a poliach'); ?>
      <div class="reveal"><?php paragraphs($plasenie['intro']); paragraphs($plasenie['outro'], 'prose--note'); ?></div>
    </div>
    <div class="reveal">
      <h3><?= e($plasenie['stepsTitle']) ?></h3>
      <ol class="steps"><?php foreach ($plasenie['steps'] as $i => $st): ?><li><span class="steps__n"><?= $i + 1 ?></span><span><?= e($st) ?></span></li><?php endforeach; ?></ol>
      <p><a class="ext" href="<?= e($SITE['doc']['href']) ?>" download>⬇ <?= e($SITE['doc']['label']) ?></a></p>
      <p><a class="link-card" href="biologicka-ochrana">Služba biologická ochrana <span aria-hidden="true">→</span></a></p>
    </div>
  </div>
</section>

<section class="band band--tight" id="v-praxi">
  <div class="container">
    <?php gallery_block('vseobecne-sokoliarstvo', 'Sokoly a sokoliarska práca'); ?>
  </div>
</section>

<section class="band band--sage" id="raroh">
  <div class="container">
    <?php section_head('Sokol rároh', 'Falco cherrug – najväčší stredoeurópsky sokol'); ?>
    <dl class="facts facts--light reveal">
      <?php foreach ($raroh['facts'] as $f): ?><div><dt><?= e($f['label']) ?></dt><dd><?= e($f['value']) ?></dd></div><?php endforeach; ?>
    </dl>
    <div class="card-grid card-grid--2">
      <?php foreach ($raroh['sections'] as $s): if ($s['title'] === 'Hniezdna biológia') continue; ?>
        <article class="card reveal"><h3><?= e($s['title']) ?></h3><p><?= e($s['text']) ?></p></article>
      <?php endforeach; ?>
    </div>
    <details class="disclosure reveal"><summary>Poddruhy sokola rároha</summary>
      <ul class="plain"><?php foreach ($raroh['subspecies'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
    </details>
  </div>
</section>

<section class="band" id="stahovavy">
  <div class="container two-col two-col--wide">
    <div>
      <?php section_head('Sokol sťahovavý', 'Falco peregrinus – najrýchlejšie zviera'); ?>
      <div class="reveal"><?php paragraphs($stah['paragraphs']); ?></div>
    </div>
    <div class="dark-card reveal">
      <h3><?= e($stah['usaTitle']) ?></h3>
      <?php paragraphs($stah['usa'], 'prose--light'); ?>
      <dl class="mini-stats"><?php foreach ($stah['usaStats'] as $s): ?><div><dt><?= e($s['value']) ?></dt><dd><?= e($s['label']) ?></dd></div><?php endforeach; ?></dl>
    </div>
  </div>
</section>

<section class="band band--sage" id="ohrozenia">
  <div class="container">
    <?php section_head('Ohrozenia dravcov', 'Čo ohrozuje dravé vtáky'); ?>
    <ul class="card-grid card-grid--4">
      <?php foreach ($S['threats'] as $t): ?><li class="card reveal"><h3><?= e($t['title']) ?></h3><p class="small"><?= e($t['text']) ?></p></li><?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="band" id="skodcovia">
  <div class="container">
    <?php section_head('Druhy spôsobujúce škody', 'Holuby, škorce a havrany', 'Poznať druh škodcu je základ správne zvoleného riešenia. Od druhu závisí aj to, aký dravec sa nasadí.'); ?>
    <?php foreach ($S['pests'] as $p): ?>
      <details class="disclosure reveal"><summary><span><?= e($p['name']) ?></span> <em><?= e($p['latin']) ?></em></summary>
        <p><?= e($p['lead']) ?></p><?php paragraphs($p['more']); ?>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<?php other_pages('vseobecne-sokoliarstvo'); page_end();
