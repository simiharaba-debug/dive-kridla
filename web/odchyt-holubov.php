<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/bo_ui.php';
page_start('odchyt-holubov');
$L = $SERVICES['legal'];
page_hero('Odchyt holubov', 'Odchyt mestských holubov do pascí a na povalách',
    'Živolovné pasce na vytipovaných strechách v meste a vyčistenie povál, zvoníc a podkroví vrátane zabezpečenia vletových otvorov.');
?>
<section class="band">
  <div class="container">
    <div class="two-col two-col--wide">
      <div class="reveal">
        <p class="eyebrow">Mestské holuby – Váš problém, naša starosť</p>
        <?php paragraphs(['Ak vám spôsobujú premnožené mestské holuby problémy, neváhajte nás kontaktovať. Zaoberáme sa odchytom premnožených mestských holubov z voľných priestranstiev v mestách a priemyselných areáloch, ako aj odchytom holubov z uzavretých priestorov, ako sú povaly, kostolné veže alebo haly.']); ?>
      </div>
      <div class="note-card reveal">
        <p class="eyebrow">Cena dohodou</p>
        <p>Rozsah a cenu odchytu dohodneme individuálne – podľa lokality, dĺžky odchytu a počtu odchytených holubov.</p>
        <a class="btn btn--dark" href="priame-kontaktovanie">Kontaktovať nás</a>
      </div>
    </div>
  </div>
</section>
<section class="band band--sage"><div class="container"><?php service_block(find_service('pasce')); ?></div></section>
<section class="band"><div class="container"><?php service_block(find_service('povaly')); ?>
  <?php gallery_block('odchyt-holubov', 'Ďalšie fotografie z odchytu a povál'); ?>
</div></section>

<section class="band band--forest">
  <div class="container two-col">
    <div class="section-head reveal light"><p class="eyebrow eyebrow--sand">Legislatíva</p><h2><?= e($L['title']) ?></h2></div>
    <div class="reveal">
      <?php paragraphs($L['paragraphs'], 'prose--light'); ?>
    </div>
  </div>
</section>
<?php faq_block('odchyt-holubov'); access_request($ACCESS_TOPICS[0]); other_pages('odchyt-holubov'); page_end();
