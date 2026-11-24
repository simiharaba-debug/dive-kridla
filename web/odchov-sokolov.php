<?php
// SOKOL – edukácia odchovu dravých vtákov (OZ Divé krídla). Texty: data/sokol.php (zo stránky sokol-edukacia1.webnode.sk),
// chovný pár a hniezdna biológia sokola rároha: data/odchov.php a data/sokoliarstvo.php (pôvodný web).
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
$SOKOL = require __DIR__ . '/data/sokol.php';
$O = require __DIR__ . '/data/odchov.php';
$S = require __DIR__ . '/data/sokoliarstvo.php';
$par = $O['chovnyPar']; $raroh = $S['raroh'];
$hniezdna = null; foreach ($raroh['sections'] as $s) if ($s['title'] === 'Hniezdna biológia') $hniezdna = $s;
page_start('odchov-sokolov');
page_hero('Odchov a edukácia', $SOKOL['title'], $SOKOL['perex'][0]);
$nav = ['sokol' => 'O projekte', 'podmienky' => 'Podmienky chovu', 'odchov' => 'Prirodzený a umelý odchov', 'etapy' => 'Etapy odchovu', 'mladata' => 'Mláďatá', 'pozor' => 'Na čo si dať pozor', 'chovny-par' => 'Chovný pár', 'hniezdenie' => 'Hniezdna biológia'];
$P = $SOKOL['prep']; $B = $SOKOL['breeding'];
?>
<nav class="subnav" aria-label="Obsah stránky"><ul class="container"><?php foreach ($nav as $id => $l): ?><li><a href="#<?= $id ?>"><?= e($l) ?></a></li><?php endforeach; ?></ul></nav>

<section class="band" id="sokol">
  <div class="container two-col two-col--wide">
    <div>
      <?php section_head('Projekt SOKOL', 'Vznik života od úplného začiatku'); ?>
      <div class="reveal"><?php paragraphs($SOKOL['perex']); ?></div>
      <h3 class="reveal">Prečo je odchov potrebný</h3>
      <div class="reveal"><?php paragraphs($SOKOL['why']); ?></div>
    </div>
    <aside class="reveal">
      <div class="bo-fact">
        <p class="bo-fact__label">Vedeli ste?</p>
        <p class="bo-fact__main"><?= e($SOKOL['fact']['main']) ?></p>
        <p><?= e($SOKOL['fact']['text']) ?></p>
      </div>
      <h3>Čo robíme</h3>
      <ul class="checks">
        <?php foreach ($SOKOL['services'] as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
      </ul>
    </aside>
  </div>
</section>

<section class="band band--sage" id="podmienky">
  <div class="container">
    <?php section_head('Príprava a podmienky chovu', 'Ako sa pripravujú chovné páry', $P['intro']); ?>
    <ul class="chips-row reveal" aria-label="Čo sledujeme pri chovných dravcoch"><?php foreach ($P['points'] as $pt): ?><li class="tag"><?= e($pt) ?></li><?php endforeach; ?></ul>
    <div class="card-grid card-grid--2" style="margin-top:1.5rem">
      <?php foreach (['falcons', 'hawks'] as $k): $x = $P[$k]; ?>
        <article class="card reveal">
          <h3><?= e($x['title']) ?></h3>
          <p><?= e($x['text']) ?></p>
          <?php bo_gallery($x['photos'], 3, 3, $x['title'], 'bo-gallery--feature'); ?>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="bo-note reveal" style="margin-top:1.5rem"><?= e($P['cameras']) ?></p>
  </div>
</section>

<section class="band" id="odchov">
  <div class="container">
    <?php section_head('Prirodzený a umelý odchov', 'Keď sa pár postará sám – a keď mu musí pomôcť človek'); ?>
    <div class="card-grid card-grid--2">
      <?php foreach (['natural', 'artificial'] as $k): $x = $B[$k]; ?>
        <article class="card reveal">
          <h3><?= e($x['title']) ?></h3>
          <p><?= e($x['text']) ?></p>
          <?php bo_videos($x['videos'], 'row', $x['title'] . ' – videá', 'bo-videos--pair'); ?>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="reveal" style="margin-top:1.25rem"><a class="link-card" href="anatomia-dravcov#psychika">Viac o vtlačení a o tom, ako sa dravce učia <span aria-hidden="true">→</span></a></p>
  </div>
</section>

<section class="band band--sage" id="etapy">
  <div class="container">
    <?php section_head('Etapy odchovu', 'Od rozmnožovania po vypustenie do prírody', 'Fotografie sa otvoria v galérii, videá sa prehrajú po kliknutí v samostatnom okne.'); ?>
    <?php // časová os: každá etapa = riadok (text vľavo, fotografie + videá vpravo), rovnaké výšky radov – žiadne prázdne miesta (22. 11. 2026) ?>
    <ol class="bo-timeline">
      <?php foreach ($SOKOL['stages'] as $i => $st): $np = count($st['photos'] ?? []); $nv = count($st['videos'] ?? []); ?>
        <li class="bo-tl" data-reveal>
          <div class="bo-tl__text">
            <span class="bo-tl__num" aria-hidden="true"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <h3><?= e($st['title']) ?></h3>
            <p><?= e($st['text']) ?></p>
            <p class="bo-tl__meta"><?= $np ? $np . ' ' . e(sk_photos($np)) : '' ?><?= $np && $nv ? ' · ' : '' ?><?= $nv ? $nv . ' ' . ($nv === 1 ? 'video' : ($nv <= 4 ? 'videá' : 'videí')) : '' ?></p>
          </div>
          <div class="bo-tl__media">
            <?php if ($np): ?>
              <?php bo_gallery($st['photos'], min($np, 3), 3, $st['title'] . ' – fotografie', $np >= 3 ? 'bo-gallery--tl3' : ($np === 2 ? 'bo-gallery--tl2' : 'bo-gallery--tl1')); ?>
            <?php endif; ?>
            <?php if ($nv): // zarovnaný rad: rovnaká výška, šírka podľa pomeru strán, rad presne vyplní šírku ?>
              <?php bo_videos($st['videos'], 'row', $st['title'] . ' – videá', 'bo-tl__videos'); ?>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="band" id="mladata">
  <div class="container">
    <?php section_head('Mláďatá z odchovu', 'Ako mláďatá rastú', 'Mladé jastraby a sokoly od prvého páperia po odrastené mláďatá pripravené opustiť hniezdo.'); ?>
    <div data-reveal><?php bo_gallery($SOKOL['photos'], 4, 3, 'Mláďatá z odchovu'); ?></div>
  </div>
</section>

<section class="band band--sage" id="pozor">
  <div class="container">
    <?php section_head('Na čo si dať pozor', 'Zaujímavosti z odchovu', 'Drobnosti, ktoré rozhodujú o tom, či z mláďaťa vyrastie zdravý dravec.'); ?>
    <?php $tipsPhoto = array_filter($SOKOL['tips'], fn($t) => $t['photo']); $tipsText = array_filter($SOKOL['tips'], fn($t) => !$t['photo']); ?>
    <ul class="tips">
      <?php foreach ($tipsPhoto as $t): ?>
        <li class="tip reveal"><?php media_figure($t['photo'], 'tip__media'); ?><div class="tip__body"><h3><?= e($t['title']) ?></h3><p><?= e($t['text']) ?></p></div></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($tipsText): // zaujímavosti bez fotografie (napr. Vývržok) – široký pruh „Dobré vedieť“, nie karta s prázdnym miestom ?>
    <ul class="tip-notes">
      <?php foreach ($tipsText as $t): ?>
        <li class="tip-note reveal"><span class="tip-note__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6v.1"/></svg></span>
          <div class="tip-note__body"><span class="tip-note__label">Dobré vedieť</span><h3><?= e($t['title']) ?></h3><p><?= e($t['text']) ?></p></div></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p class="reveal" style="margin-top:1.25rem"><a class="link-card" href="zdravie-dravcov#prevencia">Prevencia chorôb a správne kŕmenie <span aria-hidden="true">→</span></a></p>
  </div>
</section>

<section class="band" id="chovny-par">
  <div class="container">
    <?php section_head('Chovný pár', $par['title']); ?>
    <div class="two-col">
      <ul class="card-grid">
        <?php foreach ($par['items'] as $t): ?><li class="card reveal"><p><?= e($t) ?></p></li><?php endforeach; ?>
      </ul>
      <div class="reveal"><?php bo_gallery([
        ['id' => 'raroh-par', 'caption' => 'Chovný pár sokolov rárohov'],
        ['id' => 'par-1', 'caption' => 'Chovný pár sokolov rárohov'],
        ['id' => 'par-2', 'caption' => 'Chovný pár sokolov rárohov'],
      ], 3, 3, 'Chovný pár sokolov rárohov', 'bo-gallery--feature'); ?></div>
    </div>
  </div>
</section>

<section class="band band--forest" id="hniezdenie">
  <div class="container two-col">
    <div>
      <?php section_head('Sokol rároh', 'Hniezdna biológia', '', true); ?>
      <?php if ($hniezdna): ?><p class="prose--light reveal"><?= e($hniezdna['text']) ?></p><?php endif; ?>
    </div>
    <dl class="facts reveal">
      <?php foreach ($raroh['facts'] as $f): ?><div><dt><?= e($f['label']) ?></dt><dd><?= e($f['value']) ?></dd></div><?php endforeach; ?>
    </dl>
  </div>
</section>

<section class="band band--tight">
  <div class="container">
    <p class="bo-note reveal"><?= e($SOKOL['thanks']) ?></p>
    <div class="cta cta--light reveal" style="margin-top:1.5rem">
      <p class="display">Ako prebieha samotné vypúšťanie mladých sokolov v meste?</p>
      <a class="btn btn--dark" href="vypustanie-sokolov#metodika">Metodika vypúšťania</a>
    </div>
  </div>
</section>
<?php other_pages('odchov-sokolov'); page_end();
