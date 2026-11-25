<?php
// Vypúšťanie sokolov + Metodika vypúšťania sokolov v mestskom prostredí (od 22. 11. 2026 jedna stránka;
// stará adresa edukacia-v-odchove-dravych-vtakov presmeruje sem na #metodika). Texty: data/services.php, data/edukacia.php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
$E = require __DIR__ . '/data/edukacia.php';
$metodika = $E['metodika']; $metoda = $E['metoda'];
page_start('vypustanie-sokolov');
page_hero('Vypúšťanie sokolov', 'Vypúšťanie sokolov do mesta – prirodzený predátor holubov',
    'Biologická ochrana pomocou vypustených sokolov sťahovavých alebo sokolov rárohov je založená na prírodnom zákone dravec a jeho korisť.');
$nav = ['sluzba' => 'Služba a postup', 'metodika' => 'Metodika vypúšťania', 'priebeh' => 'Priebeh vypúšťania', 'videa' => 'Videá', 'volny-let' => 'Voľný let'];
?>
<nav class="subnav" aria-label="Obsah stránky"><ul class="container"><?php foreach ($nav as $id => $l): ?><li><a href="#<?= $id ?>"><?= e($l) ?></a></li><?php endforeach; ?><li><a href="odchov-sokolov">SOKOL – edukácia odchovu →</a></li></ul></nav>

<section class="band" id="sluzba"><div class="container"><?php service_block(find_service('vypustanie')); ?>
  <?php gallery_block('vypustanie-sokolov', 'Mladé sokoly pred vypustením', 3, 3); ?>
</div></section>

<section class="band band--sage" id="metodika">
  <div class="container">
    <?php section_head($metodika['subtitle'], $metodika['title'], 'Šesť krokov, ktoré rozhodujú o tom, či sa mladé sokoly v meste uchytia a naučia samostatne loviť – od výberu miesta po otvorenie vypúšťacieho zariadenia.'); ?>
    <ol class="method">
      <?php foreach ($metodika['steps'] as $i => $s): ?>
        <li class="method__step reveal">
          <div>
            <p class="eyebrow">Krok <?= sprintf('%02d', $i + 1) ?></p>
            <h3><?= e($s['title']) ?></h3>
            <?php paragraphs($s['paragraphs']); ?>
          </div>
          <?php photo_grid($s['photos'], $s['title'], $s['title'], 'm-' . $s['id'], count($s['photos']) > 2 ? 'photo-grid--first-big' : ''); ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="band" id="priebeh">
  <div class="container">
    <?php section_head('Metóda voľného letu', 'Priebeh vypúšťania – od vloženia do náhradného hniezda po osamostatnenie', $metoda['methodIntro']); ?>
    <ol class="timeline">
      <?php foreach ($metoda['methodTimeline'] as $t): ?>
        <li class="card reveal"><p class="eyebrow"><?= e($t['when']) ?></p><h3><?= e($t['title']) ?></h3><p><?= e($t['text']) ?></p></li>
      <?php endforeach; ?>
    </ol>
    <p class="prose--note reveal"><?= e($metoda['methodNote']) ?></p>
  </div>
</section>

<section class="band band--dark" id="videa">
  <div class="container">
    <?php section_head('Videozáznamy', 'Metóda voľného letu vo videu', 'Mladé sokoly vo vypúšťacom zariadení a počas prvých letov. Video sa prehrá po kliknutí v samostatnom okne.', true); ?>
    <?php bo_videos(videos_data()['edukacia'], 'grid', 'Metóda voľného letu – videá'); ?>
  </div>
</section>

<section class="band" id="volny-let">
  <div class="container">
    <?php section_head('Voľný let', 'Zábery z fotopascí pri vypúšťacom zariadení', 'Mladé sokoly pri vypúšťacom zariadení a na okolitých strechách po jeho otvorení.'); ?>
    <div data-reveal><?php bo_gallery(array_map(fn($id) => ['id' => $id, 'caption' => 'Mladý sokol pri vypúšťacom zariadení – záber z fotopasce'], $metodika['freeFlight']), 8, 6, 'Voľný let – zábery z fotopasce'); ?></div>
    <p><a class="ext" href="<?= e($SITE['doc']['href']) ?>" download>⬇ <?= e($SITE['doc']['label']) ?></a></p>
    <div class="btn-row reveal"><a class="btn btn--outline" href="odchov-sokolov">Projekt SOKOL a odchov</a></div>
  </div>
</section>
<?php faq_block('vypustanie-sokolov'); access_request($ACCESS_TOPICS[1], 'informacie'); other_pages('vypustanie-sokolov'); page_end();
