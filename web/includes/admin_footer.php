    </main>
    <footer class="adm-foot">
      <span>© <?= date('Y') ?> OZ Divé krídla · administrácia</span>
      <span class="adm-foot__keys"><kbd class="adm-kbd">Ctrl K</kbd> hľadať · <kbd class="adm-kbd">[</kbd> panel · <kbd class="adm-kbd">G</kbd> potom <kbd class="adm-kbd">N</kbd> nástenka</span>
    </footer>
  </div>
</div>

<div class="adm-cmd" id="adm-cmd" role="dialog" aria-modal="true" aria-label="Rýchle hľadanie" hidden>
  <div class="adm-cmd__box">
    <div class="adm-cmd__in">
      <?= adm_icon('search', 18) ?>
      <input type="search" placeholder="Kam chcete prejsť? Napr. kontakt, fotografie, SEO…" autocomplete="off" spellcheck="false" aria-label="Hľadať sekciu alebo stránku" aria-controls="adm-cmd-list" data-adm-cmd-q>
      <kbd class="adm-kbd">Esc</kbd>
    </div>
    <ul class="adm-cmd__list" id="adm-cmd-list" role="listbox">
      <?php foreach (adm_nav() as $group => $items): foreach ($items as [$key, $href, $label, $icon, $kw]): ?>
        <li role="option"><a href="<?= e($href) ?>" data-k="<?= e(mb_strtolower($label . ' ' . $group . ' ' . $kw)) ?>"><?= adm_icon($icon) ?><span><?= e($label) ?></span><small><?= e($group) ?></small></a></li>
      <?php endforeach; endforeach; ?>
      <li role="option"><button type="button" data-k="tmavý svetlý režim téma prepnúť noc deň" data-adm-action="theme"><?= adm_icon('moon') ?><span>Prepnúť tmavý / svetlý režim</span><small>Akcia</small></button></li>
      <li role="option"><button type="button" data-k="zbaliť rozbaliť bočný panel menu" data-adm-action="collapse"><?= adm_icon('sidebar') ?><span>Zbaliť / rozbaliť bočný panel</span><small>Akcia</small></button></li>
      <?php foreach ($GLOBALS['SITE']['pages'] as $slug => $p): if (!empty($p['hidden'])) continue; ?>
        <li role="option"><a href="<?= e(url($slug)) ?>" data-k="<?= e(mb_strtolower($p['label'] . ' web stránka ' . $p['short'])) ?>"><?= adm_icon('external') ?><span><?= e($p['label']) ?></span><small>Stránka webu</small></a></li>
      <?php endforeach; ?>
      <li role="option"><a href="ucet" data-k="môj účet profil"><?= adm_icon('user') ?><span>Môj účet</span><small>Účet</small></a></li>
    </ul>
    <p class="adm-cmd__empty" data-adm-cmd-empty hidden>Nič sa nenašlo. Skúste iné slovo.</p>
    <p class="adm-cmd__foot"><span><kbd class="adm-kbd">↑</kbd><kbd class="adm-kbd">↓</kbd> výber</span><span><kbd class="adm-kbd">Enter</kbd> otvoriť</span></p>
  </div>
</div>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Zväčšená fotografia" hidden>
  <button class="lightbox__close" aria-label="Zavrieť">×</button>
  <button class="lightbox__prev" aria-label="Predchádzajúca fotografia">‹</button>
  <figure><img alt=""><figcaption></figcaption></figure>
  <button class="lightbox__next" aria-label="Nasledujúca fotografia">›</button>
</div>
</body>
</html>
