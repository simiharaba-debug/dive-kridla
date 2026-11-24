<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
page_start('biologicka-ochrana');
page_hero('Biologická ochrana', 'Dravce ako prirodzený prostriedok proti škodám',
    'Ochrana sadov, viníc a polí pred náletmi škorcov a iných vtákov pomocou sokoliarsky vycvičených dravcov.');
?>
<section class="band">
  <div class="container two-col two-col--wide">
    <div class="reveal">
      <?php paragraphs([
        'Biologická ochrana slúži ako jeden z prirodzených prostriedkov, pomocou ktorého sa dajú eliminovať škody spôsobované premnoženými vtákmi (holuby, škorce, havrany…) na poľnohospodárskych plodinách, budovách, sochách i na ľudskom zdraví. Naše riešenia sú v súlade s platnou legislatívou Slovenskej republiky, keďže väčšina voľne žijúcich vtákov je celoročne chránená podľa zákona č. 543/2002 Z. z. o ochrane prírody a krajiny.',
        'Naša spoločnosť poskytuje jednoduché riešenie, ako eliminovať škody, ktoré vám spôsobujú kŕdle holubov v mestách, prípadne kŕdle škorcov či iných vtákov v sadoch a viniciach.',
      ]); ?>
      <h3>Biologická ochrana sa využíva najmä na:</h3>
      <ul class="checks">
        <?php foreach ($SERVICES['usage'] as $u): ?><li><?= e(mb_strtoupper(mb_substr($u, 0, 1)) . mb_substr($u, 1)) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <ul class="stats stats--grid reveal">
      <?php foreach ($SERVICES['stats'] as $s): ?><li><strong><?= e($s['value']) ?></strong><span><?= e($s['label']) ?></span></li><?php endforeach; ?>
    </ul>
  </div>
</section>
<section class="band band--sage"><div class="container">
  <?php service_block(find_service('plasenie')); ?>
  <p class="reveal"><a class="ext" href="<?= e($SITE['doc']['href']) ?>" download>⬇ <?= e($SITE['doc']['label']) ?></a></p>
</div></section>
<section class="band" id="v-praxi">
  <div class="container">
    <?php section_head('Biologická ochrana v praxi', 'Ochrana čučoriedkových polí a ovocných sadov v Kanade', 'Sokoliarsky vycvičené dravce pri ochrane úrody pred kŕdľami vtákov. Kliknutím na fotografiu otvoríte celú galériu.'); ?>
    <?php gallery_block('biologicka-ochrana', 'Kanada – polia a sady', 8, 6); ?>
  </div>
</section>
<section class="band band--tight"><div class="container btn-row">
  <a class="btn btn--dark" href="vseobecne-sokoliarstvo#skodcovia">Druhy spôsobujúce škody</a>
  <a class="btn btn--outline" href="priame-kontaktovanie">Dohodnúť ochranu</a>
</div></section>
<?php other_pages('biologicka-ochrana'); page_end();
