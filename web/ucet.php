<?php
require __DIR__ . '/includes/functions.php';
$user = require_login();
$isAdmin = $user['role'] === 'admin';
page_start('ucet');
?>
<section class="band">
  <div class="container container--mid">
    <div class="section-head reveal"><p class="eyebrow">Môj účet</p><h1>Ahoj, <?= e($user['username']) ?></h1>
      <p class="lead">Ste prihlásený. Nižšie vidíte stav svojho účtu a možnosti, ktoré máte k dispozícii.</p></div>
    <div class="account-grid">
      <div class="card reveal">
        <h2 class="h3">Stav účtu</h2>
        <dl class="kv">
          <div><dt>Používateľské meno</dt><dd><?= e($user['username']) ?></dd></div>
          <div><dt>E-mail</dt><dd><?= e($user['email']) ?></dd></div>
          <div><dt>Rola</dt><dd><span class="role-badge role-badge--<?= e($user['role']) ?>"><?= $isAdmin ? 'Administrátor' : 'Používateľ' ?></span></dd></div>
          <div><dt>Registrácia</dt><dd><?= e(date('j. n. Y', strtotime($user['created_at']))) ?></dd></div>
        </dl>
        <form method="post" action="odhlasenie"><?= csrf_field() ?><button class="btn btn--outline" type="submit">Odhlásiť sa</button></form>
      </div>
      <div class="card reveal">
        <h2 class="h3">Čo môžete robiť</h2>
        <ul class="checks">
          <li>Prezerať <a href="fotogaleria">fotogalériu</a> – fotografie a videá</li>
          <li>Napísať nám cez <a href="priame-kontaktovanie">kontaktný formulár</a></li>
          <li>Požiadať o <a href="odchyt-holubov#na-vyziadanie">informácie na vyžiadanie</a></li>
          <?php if ($isAdmin): ?>
            <li><strong>Administrácia:</strong> <a href="admin">prehľad webu a používateľov</a> · <a href="admin-fotky">fotografie stránok</a></li>
            <li><strong>Galéria:</strong> <a href="fotogaleria#sprava">nahrávať, upravovať a mazať fotografie a videá</a></li>
            <li><strong>Súkromné:</strong> <a href="moja-galeria">Moja administrátorská galéria</a></li>
          <?php elseif (can_view_private_gallery()): ?>
            <li><strong>Súkromné:</strong> <a href="moja-galeria">Administrátorská galéria</a> (iba prezeranie)</li>
          <?php endif; ?>
        </ul>
        <?php if (!$isAdmin): ?><p class="small muted">Správa galérie je dostupná iba administrátorom.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php page_end();
