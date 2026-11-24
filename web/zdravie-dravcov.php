<?php
// Zdravie a veterinárna starostlivosť o dravce. Texty: data/zdravie.php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
$Z = require __DIR__ . '/data/zdravie.php';
page_start('zdravie-dravcov');
page_hero('Zdravie dravcov', 'Príznaky chorôb, prvá pomoc a prevencia', $Z['lead']);
$nav = ['priznaky' => 'Príznaky a prvá pomoc', 'vysetrenie' => 'Vyšetrenie', 'akutne' => 'Akútne stavy', 'sustavy' => 'Podľa sústav', 'lekarnicka' => 'Lekárnička', 'choroby' => 'Prehľad chorôb', 'prevencia' => 'Prevencia'];
?>
<nav class="subnav" aria-label="Obsah stránky"><ul class="container"><?php foreach ($nav as $id => $l): ?><li><a href="#<?= $id ?>"><?= e($l) ?></a></li><?php endforeach; ?></ul></nav>

<section class="band" id="priznaky">
  <div class="container">
    <?php section_head('Príznaky a prvá pomoc', 'Čo si všimne chovateľ a čo zistí veterinár'); ?>
    <div class="prose-media">
      <div class="reveal">
        <?php paragraphs($Z['intro']); ?>
        <h3><?= e($Z['nonspecific']['title']) ?></h3>
        <p><?= e($Z['nonspecific']['text']) ?></p>
        <ul class="checks"><?php foreach ($Z['nonspecific']['list'] as $l): ?><li><?= e(mb_strtoupper(mb_substr($l, 0, 1)) . mb_substr($l, 1)) ?></li><?php endforeach; ?></ul>
        <p><?= e($Z['nonspecific']['diag']) ?></p>
        <p class="bo-note"><?= e($Z['nonspecific']['warning']) ?></p>
      </div>
      <div class="reveal">
        <?php media_figure('rehab-05'); ?>
        <div class="card" style="margin-top:1.25rem">
          <h3>Prvá pomoc – stabilizácia</h3>
          <ol class="steps"><?php foreach ($Z['firstAid'] as $i => $t): ?><li><span class="steps__n"><?= $i + 1 ?></span><span><?= e($t) ?></span></li><?php endforeach; ?></ol>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="band band--tight" id="vysetrenie">
  <div class="container">
    <div class="prose-media prose-media--rev reveal">
      <div>
        <?php section_head('Vyšetrenie v praxi', 'Endoskopické vyšetrenie dravca'); ?>
        <?php paragraphs($Z['endoscopy']); ?>
        <a class="btn btn--outline" href="fotogaleria?kategoria=veterina">Fotografie z vyšetrení</a>
      </div>
      <div class="fig-stack"><?php media_figure('endoskopia-1'); media_figure('endoskopia-2'); ?></div>
    </div>
  </div>
</section>

<section class="band band--sage" id="akutne">
  <div class="container two-col">
    <div class="reveal">
      <?php section_head('Neodkladne k veterinárovi', 'Stavy, pri ktorých sa nečaká'); ?>
      <p>Pri týchto stavoch a zraneniach je potrebný okamžitý veterinárny zákrok. Ak ste našli poraneného voľne žijúceho dravca, kontaktujte nás – poradíme, ako postupovať.</p>
      <a class="btn btn--dark" href="<?= e($SITE['contact']['phones'][0]['href']) ?>">Zavolať <?= e($SITE['contact']['phones'][0]['label']) ?></a>
    </div>
    <div class="warn-card reveal">
      <h3>Okamžitý zákrok si vyžaduje</h3>
      <ul class="info-list"><?php foreach ($Z['urgent'] as $u): ?><li><?= e($u) ?></li><?php endforeach; ?></ul>
    </div>
  </div>
</section>

<section class="band" id="sustavy">
  <div class="container">
    <?php section_head('Príznaky podľa sústav', 'Trávenie, dýchanie, perie, nohy a oči', 'Okrem nešpecifických príznakov sa ochorenia jednotlivých sústav prejavujú aj typickými zmenami.'); ?>
    <?php foreach ($Z['systems'] as $i => $s): ?>
      <article class="<?= $s['photo'] ? 'prose-media' . ($i % 2 ? ' prose-media--rev' : '') : 'prose-solo' ?> reveal" style="margin-bottom:2.5rem">
        <div>
          <h3><?= e($s['title']) ?></h3>
          <ul class="checks"><?php foreach ($s['list'] as $l): ?><li><?= e(mb_strtoupper(mb_substr($l, 0, 1)) . mb_substr($l, 1)) ?></li><?php endforeach; ?></ul>
          <?php paragraphs($s['text']); ?>
        </div>
        <?php if ($s['photo']): ?><div class="fig-stack"><?php foreach ((array)$s['photo'] as $pid) media_figure($pid); ?></div><?php endif; ?>
      </article>
    <?php endforeach; ?>
    <div class="card reveal">
      <h3>Náhly úhyn bez príznakov</h3>
      <p><?= e($Z['death']) ?></p>
    </div>
  </div>
</section>

<section class="band band--sage" id="lekarnicka">
  <div class="container">
    <?php section_head('Sokoliarska lekárnička', 'Čo mať vždy poruke'); ?>
    <div class="card-grid card-grid--2">
      <div class="card reveal"><h3>Základná výbava</h3><ul class="checks"><?php foreach ($Z['kit']['basic'] as $l): ?><li><?= e(mb_strtoupper(mb_substr($l, 0, 1)) . mb_substr($l, 1)) ?></li><?php endforeach; ?></ul></div>
      <div class="card reveal"><h3>Odporúčaná výbava</h3><ul class="checks"><?php foreach ($Z['kit']['extra'] as $l): ?><li><?= e(mb_strtoupper(mb_substr($l, 0, 1)) . mb_substr($l, 1)) ?></li><?php endforeach; ?></ul></div>
    </div>
    <ul class="card-grid card-grid--4" style="margin-top:1rem"><?php foreach ($Z['kit']['notes'] as $n): ?><li class="card reveal"><p class="small"><?= e($n) ?></p></li><?php endforeach; ?></ul>
    <div class="prose-media reveal" style="margin-top:2rem">
      <div><h3>Podávanie liečiv</h3><?php paragraphs($Z['meds']); ?></div>
      <div><?php media_figure('endoskopia-4'); ?></div>
    </div>
  </div>
</section>

<section class="band" id="choroby">
  <div class="container">
    <?php section_head('Prehľad chorôb', 'Najčastejšie ochorenia dravcov', 'Choroby delíme podľa príčiny. Diagnózu a liečbu vždy určuje veterinárny lekár – prehľad slúži na to, aby ste vedeli, čomu predchádzať.'); ?>
    <div class="table-wrap reveal">
      <table class="disease-table">
        <thead><tr><th scope="col">Skupina</th><th scope="col">Ochorenia</th><th scope="col">Čo je dobré vedieť</th></tr></thead>
        <tbody>
          <?php foreach ($Z['diseases'] as $d): ?><tr><th scope="row"><?= e($d['group']) ?></th><td data-label="Ochorenia"><?= e($d['items']) ?></td><td data-label="Čo je dobré vedieť"><?= e($d['note']) ?></td></tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="band band--sage" id="prevencia">
  <div class="container">
    <?php section_head('Prevencia', 'Čo môže urobiť každý chovateľ'); ?>
    <ul class="card-grid card-grid--2"><?php foreach ($Z['prevention'] as $p): ?><li class="card reveal"><p><?= e($p) ?></p></li><?php endforeach; ?></ul>
    <?php gallery_block('zdravie-dravcov', 'Veterinárna starostlivosť o dravce'); ?>
  </div>
</section>

<section class="band band--tight"><div class="container">
  <p class="bo-note reveal">Informácie na tejto stránke slúžia na lepšie pochopenie zdravia dravcov a nenahrádzajú vyšetrenie veterinárnym lekárom.</p>
  <div class="btn-row reveal" style="margin-top:1rem"><a class="btn btn--dark" href="rehabilitacia-vtakov">Rehabilitácia vtákov</a><a class="btn btn--outline" href="anatomia-dravcov">Anatómia dravcov</a></div>
</div></section>
<?php other_pages('zdravie-dravcov'); page_end();
