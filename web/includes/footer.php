</main>
<?php $c = $SITE['contact']; ?>
<footer class="site-footer">
  <div class="container">
    <div class="site-footer__grid">
      <div>
        <p class="logo-text"><?= logo_svg(44, 'logo-text__mark') ?>Divé krídla</p>
        <p>Biologická ochrana pomocou dravých vtákov. OZ Divé krídla – občianske združenie zamerané na ochranu a podporu dravých vtákov na Slovensku.</p>
        <p><a class="strong" href="<?= e($c['phones'][0]['href']) ?>"><?= e($c['phones'][0]['label']) ?></a><br><a href="mailto:<?= e($c['emails'][0]) ?>"><?= e($c['emails'][0]) ?></a></p>
      </div>
      <nav aria-label="Pätička">
        <p class="strong">Stránky</p>
        <ul>
          <?php foreach ($SITE['pages'] as $slug => $p): if (!empty($p['hidden'])) continue; ?>
            <li><a href="<?= e(url($slug)) ?>"><?= e($p['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div>
        <p class="strong">Účet</p>
        <ul>
          <li><a href="priame-kontaktovanie#formular">Kontaktný formulár</a></li>
          <?php if (current_user()): ?>
            <li><a href="ucet">Môj účet</a></li>
          <?php else: ?>
            <li><a href="prihlasenie">Prihlásiť sa</a></li>
            <li><a href="registracia">Registrovať sa</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <?php if (function_exists('design_fx') && design_fx()): ?><p class="fx-wordmark" aria-hidden="true">Divé krídla</p><?php endif; ?>
    <p class="small">© <?= date('Y') ?> OZ Divé krídla · Ing. Daniel Haraba, <a href="<?= e($c['phones'][0]['href']) ?>"><?= e($c['phones'][0]['label']) ?></a>. Služby po dohode – cena dohodou.</p>
  </div>
</footer>
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Zväčšená fotografia" hidden>
  <button class="lightbox__close" aria-label="Zavrieť">×</button>
  <button class="lightbox__prev" aria-label="Predchádzajúca fotografia">‹</button>
  <figure><img alt=""><figcaption></figcaption></figure>
  <button class="lightbox__next" aria-label="Nasledujúca fotografia">›</button>
</div>
</body>
</html>
