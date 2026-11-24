<?php
// Administrácia: hlavné fotografie (hero) a náhľady (karty na úvodnej stránke) jednotlivých podstránok.
// Výber z verejnej fotogalérie alebo nahratie novej fotografie (uloží sa do zvolenej kategórie galérie, takže je aj vo fotogalérii).
// Pôvodné nastavenie: data/page_photos.php · voľby administrátora: tabuľka page_photos · logika: includes/page_photos.php
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/gallery_page.php';
require_once __DIR__ . '/includes/page_photos.php';
require_once __DIR__ . '/includes/admin_ui.php';
$admin = require_admin();

/* ---------- uloženie (POST → presmerovanie späť) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $slug = (string)($_POST['slug'] ?? '');
    $slot = (string)($_POST['slot'] ?? '');
    $back = 'admin-fotky' . (preg_match('/^[a-z0-9-]{1,60}$/', $slug) ? '#p-' . $slug : '');
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new InvalidArgumentException('Neplatný bezpečnostný token. Obnovte stránku a skúste to znova.');
        if (!isset(page_photo_defaults()[$slug]) || !isset(PAGE_PHOTO_SLOTS[$slot])) throw new InvalidArgumentException('Vyberte stránku a miesto fotografie.');
        $label = $SITE['pages'][$slug]['label'] ?? $slug;
        $what = $slot === 'hero' ? 'Hlavná fotografia' : 'Náhľad';
        switch ((string)($_POST['pa'] ?? '')) {
            case 'set':
                $ref = (string)($_POST['ref'] ?? '');
                if ($ref === '') throw new InvalidArgumentException('Kliknutím vyberte fotografiu.');
                page_photo_set($slug, $slot, $ref, (int)$admin['id']);
                flash('success', $what . ' stránky „' . $label . '“ bola zmenená.');
                break;
            case 'upload':
                gallery_seed();
                $files = gallery_files('photo');
                if (!$files) throw new InvalidArgumentException('Vyberte fotografiu na nahratie.');
                $cid = gallery_resolve_category('public', 'photo', trim((string)($_POST['category'] ?? '')), trim((string)($_POST['new_title'] ?? '')));
                [$ok, $errors, $refs] = gallery_upload_photos('public', $cid, array_slice($files, 0, 1), (int)$admin['id']);
                if (!$ok) throw new InvalidArgumentException($errors[0] ?? 'Fotografiu sa nepodarilo nahrať.');
                $title = trim((string)($_POST['title'] ?? ''));
                if ($title !== '') gallery_item_update('public', $refs[0], $title, '');
                page_photo_set($slug, $slot, $refs[0], (int)$admin['id']);
                flash('success', 'Fotografia bola nahratá do fotogalérie a nastavená ako ' . mb_strtolower($what) . ' stránky „' . $label . '“.');
                break;
            case 'reset':
                page_photo_set($slug, $slot, null, (int)$admin['id']);
                flash('success', $what . ' stránky „' . $label . '“ je opäť pôvodná.');
                break;
            case 'pos':   // výrez: zaostrený bod v % + priblíženie (editor „Upraviť výrez“)
                $reset = !empty($_POST['pos_reset']);
                page_photo_pos_set($slug, $slot, $reset ? null : ['x' => $_POST['x'] ?? 50, 'y' => $_POST['y'] ?? 50, 'z' => $_POST['z'] ?? 1], (int)$admin['id']);
                flash('success', $reset ? 'Výrez fotografie „' . $label . '“ je opäť predvolený.' : 'Výrez fotografie „' . $label . '“ bol uložený.');
                break;
            default:
                throw new InvalidArgumentException('Neznáma akcia.');
        }
    } catch (InvalidArgumentException | GalleryException | MediaException $e) {
        flash('error', $e->getMessage());
    }
    redirect($back);
}

/* ---------- údaje pre stránku ---------- */
try { gallery_seed(); } catch (Throwable $e) { error_log('[fotky-stranok] ' . $e->getMessage()); }
$cats = [];
foreach (gallery_categories('public', 'photo') as $c) {
    $items = array_values(array_filter(gallery_items($c), fn($it) => preg_match('/^[sm]:/', $it['ref'])));
    if ($items) $cats[] = ['c' => $c, 'items' => $items];
}
$allCats = gallery_categories('public', 'photo');
$pages = page_photo_defaults();

/** Dlaždica jednej fotografie stránky (hlavná / náhľad). */
function pp_tile(string $slug, string $slot, string $label): void {
    $ph = page_photo($slug, $slot);
    $custom = !empty($ph['custom']);
    $follows = $slot === 'card' && !$custom && page_photo_default_ref($slug, 'card') === null;
    $state = !$ph ? 'Bez fotografie' : ($custom ? 'Zmenená administrátorom' : ($follows ? 'Rovnaká ako hlavná fotografia' : 'Pôvodná'));
    ?>
    <div class="pp-tile">
      <p class="pp-tile__label"><?= e($label) ?></p>
      <?php
        // pomery strán rámčekov na webe (počítač, mobil) – editor výrezu ich zobrazí vedľa seba
        // nový dizajn: služby sú rozťahovacie panely a úvod široká fotka (assets/css/home.css), témy 4:3 / 1:1
        $isService = in_array($slug, ['odchyt-holubov', 'vypustanie-sokolov', 'biologicka-ochrana'], true);
        if (design_fx() && $slot === 'card' && $isService) $ratios = '5/4,6/5';
        elseif (design_fx() && $slot === 'hero' && $slug === 'index') $ratios = '4/3,16/10';
        else $ratios = $slot === 'card' ? ($slug === 'odchyt-holubov' || $slug === 'vypustanie-sokolov' || $slug === 'biologicka-ochrana' ? '16/9,4/3' : '4/3,1/1') : ($slug === 'index' ? '4/5,4/3' : '4/3,3/2');
        $defY = ($slot === 'hero' && $slug !== 'index') ? 30 : 50;   // podstránky majú predvolene object-position center 30 %
        $pos = $ph['pos'] ?? ['x' => 50, 'y' => $defY, 'z' => 1, 'custom' => false];
        if (empty($pos['custom'])) $pos['y'] = $defY;
      ?>
      <div class="pp-tile__img<?= $slot === 'card' ? ' pp-tile__img--card' : '' ?>">
        <?php if ($ph): ?><img src="<?= e($ph['thumb']) ?>" alt="<?= e($ph['alt']) ?>" loading="lazy" decoding="async" data-fp style="--fp-x:<?= round($pos['x'], 2) ?>%;--fp-y:<?= round($pos['y'], 2) ?>%;--fp-z:<?= round($pos['z'], 3) ?>"><?php endif; ?>
        <span class="pp-badge<?= $custom ? ' is-custom' : '' ?>"><?= e($state) ?></span>
        <?php if (!empty($pos['custom'])): ?><span class="pp-badge pp-badge--crop">Vlastný výrez</span><?php endif; ?>
      </div>
      <?php if ($ph): ?><p class="pp-tile__alt"><?= e($ph['alt']) ?></p><?php endif; ?>
      <div class="pp-tile__actions">
        <a class="gbtn-primary" href="#vyber" data-pp-pick data-slug="<?= e($slug) ?>" data-slot="<?= e($slot) ?>" data-current="<?= e($ph['ref'] ?? '') ?>"><?= gsvg('image', 16) ?>Zmeniť</a>
        <?php if ($ph): ?>
          <button type="button" class="gbtn" data-crop data-slug="<?= e($slug) ?>" data-slot="<?= e($slot) ?>" data-src="<?= e($ph['full']) ?>" data-label="<?= e($label . ' · ' . ($slug === 'index' ? 'Úvodná stránka' : ($GLOBALS['SITE']['pages'][$slug]['label'] ?? $slug))) ?>"
            data-x="<?= round($pos['x'], 2) ?>" data-y="<?= round($pos['y'], 2) ?>" data-z="<?= round($pos['z'], 3) ?>" data-def-y="<?= $defY ?>" data-ratios="<?= e($ratios) ?>"><?= adm_icon('layout', 16) ?>Upraviť výrez</button>
        <?php endif; ?>
        <?php if ($custom): ?>
          <form method="post" action="admin-fotky" class="ginline"><?= csrf_field() ?><input type="hidden" name="pa" value="reset"><input type="hidden" name="slug" value="<?= e($slug) ?>"><input type="hidden" name="slot" value="<?= e($slot) ?>">
            <button type="submit" class="gbtn"><?= gsvg('restore', 16) ?>Obnoviť pôvodnú</button></form>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

require_once __DIR__ . '/includes/admin_ui.php';
$GLOBALS['ADMIN_ACTIVE'] = 'admin-fotky';
page_start('admin-fotky');
adm_head('Fotografie stránok', 'Hlavná fotografia sa zobrazuje v úvode podstránky, náhľad na karte na úvodnej stránke. Vyberte fotografiu z fotogalérie alebo nahrajte novú – uloží sa aj do fotogalérie. Ak náhľad nezmeníte, používa sa hlavná fotografia stránky.',
    '<a class="adm-btn adm-btn--ghost" href="fotogaleria#sprava">' . adm_icon('grid', 16) . 'Správa fotogalérie</a>', 'image');
$customN = 0; foreach (page_photo_overrides() as $sl) $customN += count($sl);
?>
<section class="gadm">
  <div class="container">
    <div class="adm-tools">
      <label class="adm-input-icon" style="flex:1 1 260px"><?= adm_icon('search', 16) ?><span class="sr-only">Hľadať stránku</span><input type="search" placeholder="Hľadať stránku…" data-pp-pagefilter autocomplete="off"></label>
      <span class="adm-badge"><?= count($pages) ?> stránok · <?= $customN ?> zmenených fotografií</span>
    </div>

    <div class="pp-list">
      <?php foreach ($pages as $slug => $d): $p = $SITE['pages'][$slug] ?? null; if (!$p) continue; ?>
        <article class="gcard pp-page" id="p-<?= e($slug) ?>">
          <header class="pp-page__head">
            <h2><?= e($slug === 'index' ? 'Úvodná stránka' : $p['label']) ?></h2>
            <a class="pp-page__link" href="<?= e(url($slug)) ?>" target="_blank" rel="noopener">Zobraziť stránku ↗</a>
          </header>
          <div class="pp-page__tiles">
            <?php if (isset($d['hero'])) pp_tile($slug, 'hero', $slug === 'index' ? 'Veľká fotografia v úvode' : 'Hlavná fotografia (úvod podstránky)'); ?>
            <?php if (array_key_exists('card', $d)) pp_tile($slug, 'card', 'Náhľad · ' . ($d['cardWhere'] ?? 'úvodná stránka')); ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="gadm gadm--items pp-picker" id="vyber" aria-labelledby="vyber-h">
  <div class="container">
    <div class="pp-picker__box gcard" role="document">
      <div class="pp-picker__top">
        <h2 id="vyber-h" class="gcard__h"><?= gsvg('image') ?><span data-pp-title>Výber fotografie</span></h2>
        <button type="button" class="gicon pp-picker__close" data-pp-close aria-label="Zavrieť výber" hidden><?= gsvg('x') ?></button>
      </div>

      <div class="ggrid2 pp-target">
        <div class="gfield"><label for="pp-slug">Stránka</label><div class="gselect"><select id="pp-slug" name="slug" form="pp-form-set" data-pp-slug>
          <?php foreach ($pages as $slug => $d): if (empty($SITE['pages'][$slug])) continue; ?><option value="<?= e($slug) ?>" data-hero="<?= isset($d['hero']) ? '1' : '0' ?>" data-card="<?= array_key_exists('card', $d) ? '1' : '0' ?>"><?= e($slug === 'index' ? 'Úvodná stránka' : $SITE['pages'][$slug]['label']) ?></option><?php endforeach; ?>
        </select></div></div>
        <div class="gfield"><label for="pp-slot">Miesto</label><div class="gselect"><select id="pp-slot" name="slot" form="pp-form-set" data-pp-slot>
          <?php foreach (PAGE_PHOTO_SLOTS as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
        </select></div></div>
      </div>

      <div class="gcard--tabs" data-gtabs>
        <div class="gtabs" role="tablist" aria-label="Zdroj fotografie">
          <button type="button" role="tab" id="pp-tab-g" aria-controls="pp-panel-g" aria-selected="true"><?= gsvg('grid', 16) ?>Z fotogalérie</button>
          <button type="button" role="tab" id="pp-tab-u" aria-controls="pp-panel-u" aria-selected="false" tabindex="-1"><?= gsvg('upload', 16) ?>Nahrať novú</button>
        </div>

        <div class="gtabpanel" id="pp-panel-g" role="tabpanel" aria-labelledby="pp-tab-g">
          <h3 class="gtabpanel__h"><?= gsvg('grid') ?>Z fotogalérie</h3>
          <form method="post" action="admin-fotky" id="pp-form-set" class="gform">
            <?= csrf_field() ?><input type="hidden" name="pa" value="set">
            <div class="ggrid2">
              <div class="gfield"><label for="pp-cat">Kategória</label><div class="gselect"><select id="pp-cat" data-pp-cat>
                <option value="">Všetky kategórie</option>
                <?php foreach ($cats as $x): ?><option value="<?= (int)$x['c']['id'] ?>"><?= e($x['c']['title']) ?> (<?= count($x['items']) ?>)</option><?php endforeach; ?>
              </select></div></div>
              <div class="gfield"><label for="pp-q">Hľadať v popise</label><div class="ginput-icon"><svg class="gsvg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg><input type="search" id="pp-q" placeholder="napr. sokol, mláďatá, sad" data-pp-q autocomplete="off"></div></div>
            </div>
            <?php foreach ($cats as $x): ?>
              <fieldset class="pp-cat" data-pp-catbox="<?= (int)$x['c']['id'] ?>">
                <legend><?= e($x['c']['title']) ?></legend>
                <div class="pp-grid">
                  <?php foreach ($x['items'] as $it): ?>
                    <label class="pp-item" data-pp-text="<?= e(mb_strtolower($it['caption'] . ' ' . $x['c']['title'])) ?>" title="<?= e($it['caption']) ?>">
                      <input type="radio" name="ref" value="<?= e($it['ref']) ?>">
                      <img src="<?= e($it['thumb']) ?>" alt="<?= e($it['caption']) ?>" loading="lazy" decoding="async">
                      <span class="pp-item__check"><?= gsvg('check', 16) ?></span>
                      <span class="pp-item__now">Aktuálna</span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </fieldset>
            <?php endforeach; ?>
            <p class="muted" data-pp-empty hidden>Žiadna fotografia nezodpovedá hľadaniu.</p>
            <div class="gform__foot pp-foot"><span class="pp-foot__sel" data-pp-sel>Kliknutím vyberte fotografiu.</span><button type="submit" class="gbtn-primary"><?= gsvg('check', 16) ?>Použiť vybranú fotografiu</button></div>
          </form>
        </div>

        <div class="gtabpanel" id="pp-panel-u" role="tabpanel" aria-labelledby="pp-tab-u" hidden>
          <h3 class="gtabpanel__h"><?= gsvg('upload') ?>Nahrať novú</h3>
          <form method="post" action="admin-fotky" enctype="multipart/form-data" class="gform" data-pp-upload>
            <?= csrf_field() ?><input type="hidden" name="pa" value="upload">
            <input type="hidden" name="slug" value="<?= e(array_key_first($pages)) ?>" data-pp-slug-copy><input type="hidden" name="slot" value="hero" data-pp-slot-copy>
            <div class="gfield"><label for="pp-file">Fotografia <span class="gopt">JPG, PNG alebo WebP</span></label><input type="file" id="pp-file" name="photo" accept="image/jpeg,image/png,image/webp" required></div>
            <div class="pp-preview" data-pp-preview hidden><img alt=""></div>
            <div class="ggrid2">
              <div class="gfield"><label for="pp-title">Popis fotografie <span class="gopt">nepovinné</span></label><input type="text" id="pp-title" name="title" maxlength="120" placeholder="napr. Sokol sťahovavý na rukavici"></div>
              <div class="gfield"><label for="pp-ucat">Uložiť do kategórie galérie</label><div class="gselect"><select id="pp-ucat" name="category" data-gcat-select>
                <?php $pre = null; foreach ($allCats as $c) if ($c['slug'] === 'fotografie-stranok') $pre = (int)$c['id']; ?>
                <?php foreach ($allCats as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$c['id'] === $pre ? ' selected' : '' ?>><?= e($c['title']) ?></option><?php endforeach; ?>
                <option value="new"<?= $pre ? '' : ' selected' ?>>+ Nová kategória…</option>
              </select></div></div>
            </div>
            <div class="gnew" data-gnew hidden><div class="gfield"><label for="pp-newcat">Názov novej kategórie</label><input type="text" id="pp-newcat" name="new_title" maxlength="80" value="Fotografie stránok"></div></div>
            <div class="gform__foot"><button type="submit" class="gbtn-primary"><?= gsvg('upload', 16) ?>Nahrať a použiť</button></div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<dialog class="crop" id="crop" aria-labelledby="crop-h">
  <form method="post" action="admin-fotky" class="crop__box" data-crop-form>
    <?= csrf_field() ?><input type="hidden" name="pa" value="pos"><input type="hidden" name="slug"><input type="hidden" name="slot">
    <input type="hidden" name="x"><input type="hidden" name="y"><input type="hidden" name="z">
    <header class="crop__head">
      <div><p class="adm-head__eyebrow">Upraviť výrez</p><h2 id="crop-h" data-crop-title>Fotografia</h2></div>
      <button type="button" class="adm-iconbtn" data-crop-close aria-label="Zavrieť"><?= adm_icon('x') ?></button>
    </header>
    <p class="crop__hint"><?= adm_icon('arrow', 14) ?>Fotografiu chyťte a posuňte v rámčeku. Priblížite ju posuvníkom alebo kolieskom myši. Šípky na klávesnici posúvajú po malých krokoch.</p>
    <div class="crop__stage">
      <figure class="crop__frame crop__frame--main" tabindex="0" aria-label="Rámček fotografie – počítač. Posúvajte šípkami." data-crop-main>
        <img alt="" draggable="false"><span class="crop__grid" aria-hidden="true"></span><figcaption>Počítač</figcaption>
      </figure>
      <figure class="crop__frame crop__frame--side" aria-hidden="true" data-crop-side><img alt="" draggable="false"><figcaption>Mobil</figcaption></figure>
    </div>
    <div class="crop__tools">
      <label class="crop__zoom"><?= adm_icon('search', 16) ?><span class="sr-only">Priblíženie</span><input type="range" min="1" max="3" step="0.01" value="1" data-crop-zoom><output data-crop-zoomout>100 %</output></label>
      <span class="crop__pos" data-crop-pos>50 % · 50 %</span>
    </div>
    <footer class="crop__foot">
      <button type="submit" name="pos_reset" value="1" class="adm-btn adm-btn--quiet"><?= adm_icon('restore', 16) ?>Predvolený výrez</button>
      <span class="crop__spacer"></span>
      <button type="button" class="adm-btn adm-btn--ghost" data-crop-close>Zrušiť</button>
      <button type="submit" class="adm-btn"><?= adm_icon('check', 16) ?>Uložiť výrez</button>
    </footer>
  </form>
</dialog>
<script defer src="<?= e(asset('assets/js/bo-page-photos.js')) ?>"></script>
<?php page_end();
