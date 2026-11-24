<?php
require __DIR__ . '/includes/functions.php';
page_start('priame-kontaktovanie');
$c = $SITE['contact'];
$L = $SERVICES['legal'];
page_hero('Priame kontaktovanie', 'Povedzte nám, aký problém s vtákmi riešite', $c['note']);
?>
<section class="band">
  <div class="container two-col">
    <div class="reveal contact-list">
      <p class="display"><?= e($c['name']) ?></p>
      <p class="small muted"><?= e($c['org']) ?></p>
      <?php foreach ($c['phones'] as $p): ?><a class="contact-item" href="<?= e($p['href']) ?>"><span class="ci">☎</span><strong><?= e($p['label']) ?></strong></a><?php endforeach; ?>
      <?php foreach ($c['emails'] as $m): ?><a class="contact-item" href="mailto:<?= e($m) ?>"><span class="ci">✉</span><strong><?= e($m) ?></strong></a><?php endforeach; ?>
      <p class="small muted">Po dohode prídeme za vami, oboznámime sa s lokalitou priamo na mieste a navrhneme konkrétne riešenie.</p>
    </div>
    <form class="form-card reveal" method="post" action="kontakt-odoslat" data-ajax data-validate novalidate aria-label="Kontaktný formulár" id="formular">
      <h2>Napíšte nám</h2>
      <p class="small muted">Cena dohodou – cenu dohodneme podľa lokality a rozsahu služby.</p>
      <input type="hidden" name="type" value="contact">
      <input type="hidden" name="back" value="priame-kontaktovanie#formular">
      <div class="hp" aria-hidden="true"><label>Nechajte prázdne <input name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="form-row">
        <label>Meno a priezvisko * <input name="name" required minlength="2" maxlength="100" autocomplete="name"></label>
        <label>E-mail * <input name="email" type="email" required maxlength="190" autocomplete="email" inputmode="email"></label>
      </div>
      <div class="form-row">
        <label>Telefón (nepovinné) <input name="phone" type="tel" maxlength="20" autocomplete="tel" inputmode="tel"></label>
        <label>Služba
          <select name="topic">
            <?php foreach ($SERVICES['services'] as $s): ?><option><?= e($s['title']) ?></option><?php endforeach; ?>
            <option>Iné</option>
          </select>
        </label>
      </div>
      <label>Predmet správy * <input name="subject" required minlength="3" maxlength="150" placeholder="Napríklad: Odchyt holubov – bytový dom"></label>
      <label>Text správy * <textarea name="message" rows="5" required minlength="10" maxlength="5000" placeholder="Napríklad: holuby na streche administratívnej budovy, ochrana sadu, vinica…"></textarea></label>
      <p class="small muted">Polia označené * sú povinné. Odpoveď pošleme na zadaný e-mail.</p>
      <button class="btn btn--dark" type="submit">Odoslať správu</button>
      <?php form_status(); ?>
    </form>
  </div>
</section>

<section class="band band--sage">
  <div class="container">
    <?php section_head('Služby po dohode', 'Cena dohodou', 'Ceny služieb nezverejňujeme. Rozsah a cenu dohodneme individuálne – podľa lokality, dĺžky odchytu a spôsobu riešenia.'); ?>
    <ul class="card-grid card-grid--2">
      <?php foreach ($SERVICES['services'] as $s): ?>
        <li class="card reveal"><p class="eyebrow"><?= e($SERVICES['priceNote']) ?></p><h3><?= e($s['title']) ?></h3><p><?= e($s['short']) ?></p><p class="small muted"><?= e($s['season']) ?></p></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="band">
  <div class="container two-col">
    <div class="section-head reveal"><p class="eyebrow">Legislatíva</p><h2><?= e($L['title']) ?></h2></div>
    <div class="reveal">
      <?php paragraphs($L['paragraphs']); ?>
    </div>
  </div>
</section>

<?php access_request($ACCESS_TOPICS[0]); ?>

<?php other_pages('priame-kontaktovanie'); page_end();
