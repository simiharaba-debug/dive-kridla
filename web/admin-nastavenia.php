<?php
// Administrácia – nastavenia webu (22. 11. 2026). Sekcie (?s=): uvod, oznamenie, seo, kontakt, vzhlad.
// Ukladá sa do tabuľky site_settings (includes/site_settings.php) – bez novej migrácie. Prázdne pole = pôvodný text.
// Každá zmena: POST + CSRF + require_admin, validácia na serveri, potom presmerovanie späť (PRG).
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/admin_ui.php';
$admin = require_admin();
$uid = (int)$admin['id'];

const NAST_SECTIONS = [
    'uvod' => ['Úvodná stránka', 'layout', 'Nadpis, perex a slogan v úvode webu a výzva v spodnej časti úvodnej stránky.'],
    'oznamenie' => ['Oznámenie na webe', 'megaphone', 'Krátky pruh nad hlavičkou na všetkých stránkach – napríklad sezóna, dovolenka alebo novinka.'],
    'seo' => ['Vyhľadávače (SEO)', 'search', 'Titulok a popis, ktoré zobrazuje Google vo výsledkoch vyhľadávania a sociálne siete pri zdieľaní odkazu.'],
    'kontakt' => ['Kontakt', 'phone', 'Telefón a e-mail v hlavičke, pätičke, na úvodnej stránke, na stránke Kontakt a v hláseniach formulárov.'],
    'farby' => ['Farby webu', 'palette', 'Farba pozadia, písma, nadpisov, tlačidiel, tmavých pásov a ďalších častí webu – zvlášť pre svetlý a tmavý režim.'],
    'vzhlad' => ['Vzhľad a efekty', 'sparkle', 'Dizajn webu, predvolený svetlý alebo tmavý režim a jednotlivé animácie nového dizajnu.'],
];
/** Hotové palety (vyplnia výber jedným kliknutím, uložia sa až tlačidlom Uložiť). Pôvodná = predvolené hodnoty. */
const PALETTE_PRESETS = [
    'Lesná' => ['light' => ['bg' => '#eef1ea', 'surface' => '#f8faf5', 'text' => '#24332a', 'heading' => '#1d2b22', 'muted' => '#5b6b5f', 'accent' => '#2f5d43', 'btn_bg' => '#2f5d43', 'btn_fg' => '#f3f6f0', 'panel' => '#233a2d', 'on_dark' => '#eef1ea'],
                'dark' => ['bg' => '#1c2620', 'surface' => '#26332b', 'text' => '#e6ece4', 'heading' => '#f0f4ee', 'muted' => '#a9b8ac', 'accent' => '#9cc4a8', 'btn_bg' => '#9cc4a8', 'btn_fg' => '#16201a', 'panel' => '#142019', 'on_dark' => '#e6ece4']],
    'Morská' => ['light' => ['bg' => '#eef2f5', 'surface' => '#f8fafc', 'text' => '#1f2d3a', 'heading' => '#172532', 'muted' => '#586877', 'accent' => '#1f5f8b', 'btn_bg' => '#1f4e73', 'btn_fg' => '#f2f6f9', 'panel' => '#1b3448', 'on_dark' => '#eef2f5'],
                 'dark' => ['bg' => '#141d26', 'surface' => '#1d2935', 'text' => '#e3eaf0', 'heading' => '#f1f5f8', 'muted' => '#9fb0bf', 'accent' => '#8cc0e6', 'btn_bg' => '#8cc0e6', 'btn_fg' => '#0f1820', 'panel' => '#0d1720', 'on_dark' => '#e3eaf0']],
    'Terakota' => ['light' => ['bg' => '#f6efe8', 'surface' => '#fcf8f4', 'text' => '#3a2a22', 'heading' => '#2f2019', 'muted' => '#74604f', 'accent' => '#a4512f', 'btn_bg' => '#a4512f', 'btn_fg' => '#fbf3ec', 'panel' => '#4a2c20', 'on_dark' => '#f6efe8'],
                   'dark' => ['bg' => '#2a1e19', 'surface' => '#362822', 'text' => '#f2e7de', 'heading' => '#f8efe8', 'muted' => '#c9b3a3', 'accent' => '#e59a76', 'btn_bg' => '#e59a76', 'btn_fg' => '#2a1a12', 'panel' => '#1f1511', 'on_dark' => '#f2e7de']],
];
$s = (string)($_GET['s'] ?? 'uvod');
if (!isset(NAST_SECTIONS[$s])) $s = 'uvod';

const HOME_FIELDS = [   // kľúč => [štítok, max. dĺžka, viacriadkové]
    'eyebrow' => ['Malý nadpis nad titulkom', 80, false],
    'title' => ['Hlavný nadpis', 120, false],
    'lead' => ['Perex (text pod nadpisom)', 400, true],
    'slogan' => ['Slogan pri telefóne', 120, false],
    'cta_title' => ['Výzva dole – nadpis', 120, false],
    'cta_text' => ['Výzva dole – text', 240, true],
];
$seoPages = array_filter($SITE['pages'], fn($p) => empty($p['hidden']));
$SITE_ORIG = require __DIR__ . '/data/site.php';   // pôvodné titulky a kontakt (bez úprav administrátora)

/** Bezpečný odkaz oznámenia: vnútorná adresa webu, https://, tel: alebo mailto: (nikdy javascript:). */
function nast_link_ok(string $l): bool {
    if ($l === '') return true;
    if (preg_match('#^https?://[^\s<>"]+$#i', $l)) return true;
    if (preg_match('#^(tel:\+?[0-9 ]{6,20}|mailto:[^\s<>"@]+@[^\s<>"]+)$#i', $l)) return true;
    return (bool)preg_match('#^[a-z0-9][a-z0-9\-/]*(\?[a-z0-9=&_\-]*)?(\#[a-z0-9\-_]*)?$|^\#[a-z0-9\-_]+$#i', $l);
}
function nast_len(string $v): int { return mb_strlen($v); }

$errors = [];
$old = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $back = 'admin-nastavenia?s=' . $s;
    if (!csrf_valid($_POST['csrf'] ?? null)) { flash('error', 'Neplatný bezpečnostný token. Obnovte stránku a skúste to znova.'); redirect($back); }
    $do = (string)($_POST['do'] ?? 'save');
    $p = fn($k) => trim(str_replace(["\r\n", "\r"], "\n", (string)($_POST[$k] ?? '')));

    if ($do === 'reset') {
        $keys = ['uvod' => ['home'], 'oznamenie' => ['banner'], 'seo' => ['seo'], 'kontakt' => ['contact'], 'farby' => ['palette'], 'vzhlad' => ['design', 'theme_default', 'fx_flags']][$s];
        foreach ($keys as $k) site_setting_delete($k);
        flash('success', NAST_SECTIONS[$s][0] . ': obnovené pôvodné nastavenie.');
        redirect($back);
    }

    switch ($s) {
        case 'uvod':
            $val = [];
            foreach (HOME_FIELDS as $k => [$label, $max]) {
                $v = $p($k);
                if (nast_len($v) > $max) $errors[$k] = 'Najviac ' . $max . ' znakov (teraz ' . nast_len($v) . ').';
                if ($v !== '' && $v !== HOME_DEFAULTS[$k]) $val[$k] = $v;
            }
            if (!$errors) { site_setting_json_set('home', $val, $uid); flash('success', 'Texty úvodnej stránky boli uložené.'); redirect($back); }
            break;

        case 'oznamenie':
            $val = ['on' => !empty($_POST['on']), 'text' => $p('text'), 'link' => $p('link'), 'link_label' => $p('link_label'), 'until' => $p('until')];
            if ($val['on'] && $val['text'] === '') $errors['text'] = 'Napíšte text oznámenia, alebo oznámenie vypnite.';
            if (nast_len($val['text']) > 240) $errors['text'] = 'Najviac 240 znakov.';
            if (!nast_link_ok($val['link'])) $errors['link'] = 'Použite adresu stránky webu (napr. priame-kontaktovanie), odkaz https://…, tel: alebo mailto:.';
            if (nast_len($val['link_label']) > 40) $errors['link_label'] = 'Najviac 40 znakov.';
            if ($val['until'] !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $val['until']) || !strtotime($val['until']))) $errors['until'] = 'Zadajte platný dátum.';
            if (!$errors) {
                site_setting_json_set('banner', $val, $uid);
                flash('success', $val['on'] ? 'Oznámenie je zapnuté a návštevníci ho vidia nad hlavičkou.' : 'Oznámenie bolo uložené ako vypnuté.');
                redirect($back);
            }
            break;

        case 'seo':
            $val = [];
            foreach ($seoPages as $slug => $pg) {
                $t = $p('t_' . $slug); $dsc = $p('d_' . $slug);
                if (nast_len($t) > 90) $errors['t_' . $slug] = 'Najviac 90 znakov.';
                if (nast_len($dsc) > 320) $errors['d_' . $slug] = 'Najviac 320 znakov.';
                $row = [];
                if ($t !== '' && $t !== $SITE_ORIG['pages'][$slug]['title']) $row['title'] = $t;
                if ($dsc !== '' && $dsc !== $SITE_ORIG['pages'][$slug]['description']) $row['description'] = $dsc;
                if ($row) $val[$slug] = $row;
            }
            if (!$errors) { site_setting_json_set('seo', $val, $uid); flash('success', 'Titulky a popisy pre vyhľadávače boli uložené (' . count($val) . ' upravených stránok).'); redirect($back); }
            break;

        case 'kontakt':
            $phone = $p('phone');
            $emails = array_values(array_filter(array_map('trim', preg_split('/[\n,;]+/', $p('emails')))));
            $note = $p('note');
            if (!preg_match('/^\+?[0-9][0-9 ()\/-]{5,24}$/', $phone)) $errors['phone'] = 'Zadajte telefónne číslo, napr. 0907 419 111.';
            if (!$emails) $errors['emails'] = 'Zadajte aspoň jeden e-mail.';
            elseif (count($emails) > 3) $errors['emails'] = 'Najviac 3 e-maily.';
            else foreach ($emails as $m) if (!filter_var($m, FILTER_VALIDATE_EMAIL)) { $errors['emails'] = 'Neplatný e-mail: ' . $m; break; }
            if (nast_len($note) > 1500) $errors['note'] = 'Najviac 1500 znakov.';
            if (!$errors) {
                site_setting_json_set('contact', ['phone' => $phone, 'emails' => $emails, 'note' => $note === $SITE_ORIG['contact']['note'] ? '' : $note], $uid);
                flash('success', 'Kontaktné údaje boli uložené – zmena sa prejavila na celom webe.');
                redirect($back);
            }
            break;

        case 'farby':
            $val = ['light' => [], 'dark' => []];
            foreach (['light', 'dark'] as $m) foreach (PALETTE_TOKENS as $k => $t) {
                $v = strtolower(trim((string)($_POST[$m][$k] ?? '')));
                if ($v === '') continue;
                if (!preg_match('/^#[0-9a-f]{6}$/', $v)) { $errors[$m . '_' . $k] = 'Farba musí byť v tvare #rrggbb.'; continue; }
                if ($v !== strtolower($t[$m === 'dark' ? 4 : 3])) $val[$m][$k] = $v;   // ukladajú sa iba zmenené farby
            }
            if (!$errors) {
                site_setting_json_set('palette', $val, $uid);
                $n = count($val['light']) + count($val['dark']);
                flash('success', $n ? 'Farby webu boli uložené (' . $n . ' zmenených farieb).' : 'Farby webu sú pôvodné.');
                redirect($back);
            }
            break;

        case 'vzhlad':
            $d = (string)($_POST['design'] ?? ''); $t = (string)($_POST['theme_default'] ?? '');
            if (!isset(SITE_DESIGNS[$d])) $errors['design'] = 'Neznámy vzhľad webu.';
            if (!isset(SITE_THEMES[$t])) $errors['theme_default'] = 'Neznámy režim.';
            if (!$errors) {
                $flags = [];
                foreach (SITE_FX as $k => $_) $flags[$k] = !empty($_POST['fx'][$k]);
                site_setting_set('design', $d, $uid);
                site_setting_set('theme_default', $t, $uid);
                site_setting_json_set('fx_flags', $flags, $uid);
                flash('success', 'Vzhľad webu: ' . SITE_DESIGNS[$d][0] . ' · ' . SITE_THEMES[$t][0] . '.');
                redirect($back);
            }
            break;
    }
    $old = $_POST;
    flash('error', 'Niektoré polia treba opraviť – sú označené nižšie.');
}

/** Aktuálna hodnota poľa: odoslaná (pri chybe) → uložená → ''. */
function nast_val(string $k, $saved) { global $old; return $old !== null ? (string)($old[$k] ?? '') : (string)$saved; }
function nast_err(string $k): string { global $errors; return isset($errors[$k]) ? '<p class="adm-field__err" id="err-' . e($k) . '">' . e($errors[$k]) . '</p>' : ''; }
function nast_inv(string $k): string { global $errors; return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : ''; }
function nast_savebar(string $hint, bool $reset = true): void {
    echo '<div class="adm-savebar"><span class="adm-savebar__msg">' . adm_icon('check', 16) . '<span data-adm-savemsg>' . e($hint) . '</span></span><div class="adm-savebar__btns">'
       . '<button type="button" class="adm-btn adm-btn--ghost" data-adm-reset>Zahodiť zmeny</button>'
       . '<button type="submit" class="adm-btn">' . adm_icon('check', 16) . 'Uložiť</button></div></div>';
}
function nast_reset_form(string $s, string $what): void {
    echo '<form method="post" action="admin-nastavenia?s=' . e($s) . '" class="ginline" data-confirm="Naozaj obnoviť pôvodné nastavenie (' . e($what) . ')? Vaše úpravy sa odstránia.">' . csrf_field()
       . '<input type="hidden" name="do" value="reset"><button type="submit" class="adm-btn adm-btn--quiet">' . adm_icon('restore', 16) . 'Obnoviť pôvodné</button></form>';
}

$GLOBALS['ADMIN_ACTIVE'] = 'nast-' . $s;
page_start('admin-nastavenia');
[$secTitle, $secIcon, $secLead] = NAST_SECTIONS[$s];
ob_start(); nast_reset_form($s, $secTitle); $resetHtml = ob_get_clean();
adm_head($secTitle, $secLead, $resetHtml, $secIcon);
?>
<nav class="adm-subnav" aria-label="Sekcie nastavení">
  <?php foreach (NAST_SECTIONS as $k => [$label, $icon]): ?>
    <a href="admin-nastavenia?s=<?= e($k) ?>"<?= $k === $s ? ' aria-current="page"' : '' ?>><?= adm_icon($icon, 16) ?><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($s === 'uvod'): $home = site_setting_json('home'); ?>
<form method="post" action="admin-nastavenia?s=uvod" class="adm-form" data-adm-dirty novalidate>
  <?= csrf_field() ?>
  <div class="adm-grid adm-grid--main">
    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('text') ?>Texty</h2><p>Prázdne pole = pôvodný text (zobrazuje sa sivo). Zmenu vidíte hneď v náhľade vpravo.</p></div>
      <div class="adm-fields">
        <?php foreach (HOME_FIELDS as $k => [$label, $max, $multi]): $id = 'h-' . $k; ?>
          <div class="adm-field<?= $multi || in_array($k, ['title', 'eyebrow'], true) ? ' adm-field--full' : '' ?>">
            <label for="<?= $id ?>"><?= e($label) ?> <span class="adm-count" id="c-<?= $id ?>"></span></label>
            <?php if ($multi): ?><textarea id="<?= $id ?>" name="<?= $k ?>" rows="3" maxlength="<?= $max + 50 ?>" placeholder="<?= e(HOME_DEFAULTS[$k]) ?>" data-pv="<?= $k ?>" data-adm-count="c-<?= $id ?>" data-max="<?= $max ?>"<?= nast_inv($k) ?>><?= e(nast_val($k, $home[$k] ?? '')) ?></textarea>
            <?php else: ?><input type="text" id="<?= $id ?>" name="<?= $k ?>" maxlength="<?= $max + 50 ?>" placeholder="<?= e(HOME_DEFAULTS[$k]) ?>" value="<?= e(nast_val($k, $home[$k] ?? '')) ?>" data-pv="<?= $k ?>" data-adm-count="c-<?= $id ?>" data-max="<?= $max ?>"<?= nast_inv($k) ?>><?php endif; ?>
            <?= nast_err($k) ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <aside class="adm-card" aria-label="Náhľad úvodnej stránky" style="position:sticky;top:calc(var(--adm-top) + 1rem)">
      <div class="adm-card__head"><h2><?= adm_icon('eye') ?>Náhľad</h2><a class="adm-card__link" href="./" target="_blank" rel="noopener">Otvoriť web <?= adm_icon('external', 14) ?></a></div>
      <div class="adm-preview">
        <div class="adm-preview__bar"><i></i><i></i><i></i><span>Úvodná stránka</span></div>
        <div class="adm-preview__body adm-pv-hero">
          <p class="adm-pv-hero__eyebrow" data-pv-out="eyebrow"></p>
          <h3 data-pv-out="title"></h3>
          <p data-pv-out="lead"></p>
          <p><strong><?= e($SITE['contact']['phones'][0]['label']) ?></strong> · <em data-pv-out="slogan"></em></p>
          <div class="adm-pv-cta"><strong data-pv-out="cta_title"></strong><span data-pv-out="cta_text"></span></div>
        </div>
      </div>
    </aside>
  </div>
  <?php nast_savebar('Zmeny sa na webe prejavia po uložení.'); ?>
</form>

<?php elseif ($s === 'oznamenie'): $b = site_setting_json('banner') + ['on' => false, 'text' => '', 'link' => '', 'link_label' => '', 'until' => '']; $on = $old !== null ? !empty($old['on']) : !empty($b['on']); $live = site_banner(); ?>
<form method="post" action="admin-nastavenia?s=oznamenie" class="adm-form" data-adm-dirty novalidate>
  <?= csrf_field() ?>
  <div class="adm-grid adm-grid--main">
    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('megaphone') ?>Oznámenie</h2>
        <?= $live ? '<span class="adm-badge adm-badge--on"><span class="adm-dot"></span>Návštevníci ho práve vidia</span>' : '<span class="adm-badge adm-badge--off">Teraz sa nezobrazuje</span>' ?></div>
      <div class="adm-form">
        <label class="adm-switch"><input type="checkbox" name="on" value="1"<?= $on ? ' checked' : '' ?> data-pv="on"><span class="adm-switch__ui"></span>
          <span class="adm-switch__txt"><strong>Zobraziť oznámenie na webe</strong><small>Pruh sa ukáže nad hlavičkou na všetkých stránkach. Návštevník ho môže zavrieť krížikom.</small></span></label>
        <div class="adm-field"><label for="b-text">Text oznámenia <span class="adm-count" id="c-b-text"></span></label>
          <textarea id="b-text" name="text" rows="2" maxlength="300" placeholder="napr. V sezóne 2027 prijímame objednávky na ochranu viníc už od marca." data-pv="text" data-adm-count="c-b-text" data-max="240"<?= nast_inv('text') ?>><?= e(nast_val('text', $b['text'])) ?></textarea><?= nast_err('text') ?></div>
        <div class="adm-fields">
          <div class="adm-field"><label for="b-link">Odkaz <span class="gopt">nepovinné</span></label>
            <input type="text" id="b-link" name="link" maxlength="300" placeholder="priame-kontaktovanie" value="<?= e(nast_val('link', $b['link'])) ?>" data-pv="link"<?= nast_inv('link') ?>>
            <p class="adm-field__hint">Stránka webu (napr. <code>odchyt-holubov</code>), https://…, tel: alebo mailto:</p><?= nast_err('link') ?></div>
          <div class="adm-field"><label for="b-label">Text odkazu</label>
            <input type="text" id="b-label" name="link_label" maxlength="60" placeholder="Viac informácií" value="<?= e(nast_val('link_label', $b['link_label'])) ?>" data-pv="link_label"<?= nast_inv('link_label') ?>><?= nast_err('link_label') ?></div>
          <div class="adm-field"><label for="b-until">Zobrazovať do <span class="gopt">nepovinné</span></label>
            <input type="date" id="b-until" name="until" value="<?= e(nast_val('until', $b['until'])) ?>"<?= nast_inv('until') ?>>
            <p class="adm-field__hint">Po tomto dni sa oznámenie samo skryje.</p><?= nast_err('until') ?></div>
        </div>
      </div>
    </section>
    <aside class="adm-card" aria-label="Náhľad oznámenia">
      <div class="adm-card__head"><h2><?= adm_icon('eye') ?>Náhľad</h2></div>
      <div class="adm-preview">
        <div class="adm-preview__bar"><i></i><i></i><i></i><span>Každá stránka webu</span></div>
        <div class="adm-pv-banner" data-pv-out="on"<?= $on ? '' : ' data-off' ?>><span data-pv-out="text" data-default="Text oznámenia…"></span> <a href="#" data-pv-out="link_label" data-default="Viac informácií" tabindex="-1"></a></div>
        <div class="adm-pv-site">Divé krídla <span></span></div>
        <div class="adm-preview__body"><p class="muted small" style="margin:0">Obsah stránky…</p></div>
      </div>
      <p class="adm-field__hint" style="margin-top:.75rem">Vypnuté oznámenie je v náhľade bledé. Text sa uloží aj vtedy, takže ho neskôr len zapnete.</p>
    </aside>
  </div>
  <?php nast_savebar('Oznámenie platí pre všetkých návštevníkov.'); ?>
</form>

<?php elseif ($s === 'seo'): $seo = site_setting_json('seo'); ?>
<form method="post" action="admin-nastavenia?s=seo" class="adm-form" data-adm-dirty novalidate>
  <?= csrf_field() ?>
  <div class="adm-note"><?= adm_icon('search') ?><p>Odporúčaná dĺžka: titulok do 60 znakov, popis do 160 znakov – dlhší text Google skráti. Prázdne pole = pôvodný text z webu (sivo).</p></div>
  <div>
    <?php foreach ($seoPages as $slug => $pg): $o = $SITE_ORIG['pages'][$slug]; $cur = $seo[$slug] ?? []; $custom = !empty($cur);
      $hasErr = isset($errors['t_' . $slug]) || isset($errors['d_' . $slug]); ?>
      <details class="adm-acc"<?= $hasErr ? ' open' : '' ?>>
        <summary><span class="adm-list__ic"><?= adm_icon($slug === 'index' ? 'home' : 'layout', 16) ?></span><span class="adm-list__txt"><strong><?= e($slug === 'index' ? 'Úvodná stránka' : $pg['label']) ?></strong><small>/<?= e($slug === 'index' ? '' : $slug) ?></small></span>
          <?= $custom ? '<span class="adm-badge adm-badge--on">Upravené</span>' : '<span class="adm-badge adm-badge--off">Pôvodné</span>' ?></summary>
        <div class="adm-acc__body">
          <div class="adm-serp" aria-hidden="true">
            <span class="adm-serp__url"><?= e(preg_replace('/[^a-z0-9.:-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'web'))) ?> ›<?= e($slug === 'index' ? '' : $slug) ?></span>
            <span class="adm-serp__title" data-pv-out="t_<?= e($slug) ?>"></span>
            <span class="adm-serp__desc" data-pv-out="d_<?= e($slug) ?>"></span>
          </div>
          <div class="adm-field"><label for="t_<?= e($slug) ?>">Titulok <span class="adm-count" id="c-t-<?= e($slug) ?>"></span></label>
            <input type="text" id="t_<?= e($slug) ?>" name="t_<?= e($slug) ?>" maxlength="120" placeholder="<?= e($o['title']) ?>" value="<?= e(nast_val('t_' . $slug, $cur['title'] ?? '')) ?>" data-pv="t_<?= e($slug) ?>" data-adm-count="c-t-<?= e($slug) ?>" data-max="60"<?= nast_inv('t_' . $slug) ?>><?= nast_err('t_' . $slug) ?></div>
          <div class="adm-field"><label for="d_<?= e($slug) ?>">Popis <span class="adm-count" id="c-d-<?= e($slug) ?>"></span></label>
            <textarea id="d_<?= e($slug) ?>" name="d_<?= e($slug) ?>" rows="3" maxlength="400" placeholder="<?= e($o['description']) ?>" data-pv="d_<?= e($slug) ?>" data-adm-count="c-d-<?= e($slug) ?>" data-max="160"<?= nast_inv('d_' . $slug) ?>><?= e(nast_val('d_' . $slug, $cur['description'] ?? '')) ?></textarea><?= nast_err('d_' . $slug) ?></div>
        </div>
      </details>
    <?php endforeach; ?>
  </div>
  <?php nast_savebar('Uložia sa všetky stránky naraz.'); ?>
</form>

<?php elseif ($s === 'kontakt'): $c = site_setting_json('contact'); $oc = $SITE_ORIG['contact']; ?>
<form method="post" action="admin-nastavenia?s=kontakt" class="adm-form" data-adm-dirty novalidate>
  <?= csrf_field() ?>
  <div class="adm-grid adm-grid--main">
    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('phone') ?>Kontaktné údaje</h2><p>Na webe je uvedený iba <?= e($oc['name']) ?>. Adresát správ z formulárov (<?= e((string)cfg('MAIL_TO', $oc['form_email'])) ?>) sa tu nemení – nastavuje sa v súbore .env (MAIL_TO).</p></div>
      <div class="adm-fields">
        <div class="adm-field"><label for="k-phone">Telefón</label>
          <input type="tel" id="k-phone" name="phone" maxlength="25" required value="<?= e(nast_val('phone', $c['phone'] ?? $oc['phones'][0]['label'])) ?>" data-pv="phone" autocomplete="off"<?= nast_inv('phone') ?>>
          <p class="adm-field__hint">Odkaz na volanie sa vytvorí sám (0907… → +421907…).</p><?= nast_err('phone') ?></div>
        <div class="adm-field"><label for="k-emails">Verejné e-maily <span class="gopt">každý na nový riadok, najviac 3</span></label>
          <textarea id="k-emails" name="emails" rows="3" data-pv="emails"<?= nast_inv('emails') ?>><?= e(nast_val('emails', implode("\n", $c['emails'] ?? $oc['emails']))) ?></textarea>
          <p class="adm-field__hint">Prvý e-mail sa zobrazuje v pätičke a v hláseniach formulárov.</p><?= nast_err('emails') ?></div>
        <div class="adm-field adm-field--full"><label for="k-note">Text na stránke Kontakt <span class="adm-count" id="c-k-note"></span></label>
          <textarea id="k-note" name="note" rows="5" maxlength="1600" placeholder="<?= e($oc['note']) ?>" data-adm-count="c-k-note" data-max="1500"<?= nast_inv('note') ?>><?= e(nast_val('note', $c['note'] ?? '')) ?></textarea><?= nast_err('note') ?></div>
      </div>
    </section>
    <aside class="adm-card" aria-label="Náhľad kontaktu">
      <div class="adm-card__head"><h2><?= adm_icon('eye') ?>Náhľad pätičky</h2></div>
      <div class="adm-pv-cta" style="margin:0">
        <strong>Divé krídla</strong>
        <span style="display:block;margin-top:.4rem"><b data-pv-out="phone"></b></span>
        <span style="display:block;white-space:pre-line" data-pv-out="emails"></span>
      </div>
      <p class="adm-field__hint" style="margin-top:.75rem">Telefón sa zmení aj v hlavičke, na úvodnej stránke, na stránkach O nás, Rehabilitácia a Zdravie dravcov.</p>
    </aside>
  </div>
  <?php nast_savebar('Zmena sa prejaví na celom webe.'); ?>
</form>

<?php elseif ($s === 'farby'):
  $defaults = []; foreach (['light', 'dark'] as $m) foreach (PALETTE_TOKENS as $k => $t) $defaults[$m][$k] = $t[$m === 'dark' ? 4 : 3];
  $presets = ['Pôvodná' => $defaults] + PALETTE_PRESETS; ?>
<form method="post" action="admin-nastavenia?s=farby" class="adm-form" data-adm-dirty data-pal>
  <?= csrf_field() ?>
  <div class="adm-grid adm-grid--main">
    <section class="adm-card">
      <div class="adm-card__head"><h2><?= adm_icon('palette') ?>Farby</h2><p>Kliknite na farebný krúžok – otvorí sa výber farby, v ktorom ťahaním vyberiete odtieň. Môžete aj vpísať kód (#rrggbb). Odtiene, čiary a priehľadnosti sa dopočítajú samé. Farby platia pre verejný web, administrácia ostáva v pôvodných farbách.</p></div>
      <div class="pal-presets" role="group" aria-label="Hotové palety">
        <?php foreach ($presets as $name => $pp): ?>
          <button type="button" class="pal-preset" data-pal-preset="<?= e(json_encode($pp)) ?>"><span class="pal-preset__sw" aria-hidden="true"><?php foreach (['bg', 'panel', 'btn_bg', 'accent'] as $k): ?><i style="background:<?= e($pp['light'][$k]) ?>"></i><?php endforeach; ?></span><?= e($name) ?></button>
        <?php endforeach; ?>
      </div>
      <div class="adm-subnav pal-modes" role="tablist" aria-label="Režim">
        <a href="#pal-light" role="tab" aria-selected="true" aria-current="true" data-pal-mode="light"><?= adm_icon('sun', 16) ?>Svetlý režim</a>
        <a href="#pal-dark" role="tab" aria-selected="false" data-pal-mode="dark"><?= adm_icon('moon', 16) ?>Tmavý režim</a>
      </div>
      <?php foreach (['light' => 'Svetlý režim', 'dark' => 'Tmavý režim'] as $m => $mLabel): ?>
        <fieldset class="pal-set" id="pal-<?= $m ?>" data-pal-set="<?= $m ?>">
          <legend class="sr-only"><?= e($mLabel) ?></legend>
          <?php foreach (PALETTE_TOKENS as $k => [$var, $label, $desc]): $cur = $old !== null ? (string)($old[$m][$k] ?? '') : palette_value($m, $k); $def = $defaults[$m][$k]; $id = 'pal-' . $m . '-' . $k; ?>
            <div class="pal-row">
              <label class="pal-sw" title="Vybrať farbu"><input type="color" value="<?= e($cur) ?>" data-pal-color="<?= $m ?>.<?= $k ?>" aria-label="<?= e($label) ?> – výber farby"><span style="background:<?= e($cur) ?>"></span></label>
              <div class="pal-row__txt"><label for="<?= $id ?>"><?= e($label) ?></label><small><?= e($desc) ?></small></div>
              <input type="text" class="pal-hex" id="<?= $id ?>" name="<?= $m ?>[<?= $k ?>]" value="<?= e($cur) ?>" maxlength="7" spellcheck="false" autocomplete="off" pattern="#[0-9a-fA-F]{6}" data-pal-hex="<?= $m ?>.<?= $k ?>" data-def="<?= e($def) ?>"<?= nast_inv($m . '_' . $k) ?>>
              <button type="button" class="adm-iconbtn pal-undo" data-pal-undo="<?= $m ?>.<?= $k ?>" title="Pôvodná farba (<?= e($def) ?>)" aria-label="Vrátiť pôvodnú farbu: <?= e($label) ?>"><?= adm_icon('restore', 16) ?></button>
              <?= nast_err($m . '_' . $k) ?>
            </div>
          <?php endforeach; ?>
        </fieldset>
      <?php endforeach; ?>
    </section>
    <aside class="adm-card pal-side" aria-label="Náhľad farieb">
      <div class="adm-card__head"><h2><?= adm_icon('eye') ?>Náhľad</h2><span class="adm-badge" data-pal-modelabel>Svetlý režim</span></div>
      <div class="pal-pv" data-pal-pv>
        <div class="pal-pv__head"><strong>Divé krídla</strong><span>Služby · Galéria</span><i>Kontakt</i></div>
        <div class="pal-pv__hero">
          <small>OZ Divé krídla</small>
          <b>Prirodzená ochrana pred premnoženými vtákmi</b>
          <p>Odchyt holubov a vypúšťanie sokolov. <u>Zistiť viac</u></p>
          <i>Odchyt holubov</i>
        </div>
        <div class="pal-pv__card"><b>Karta</b><p>Text na karte a vo formulári.</p><em>Tlmený popis</em></div>
        <div class="pal-pv__band"><b>Tmavý pás a pätička</b><p>Text na tmavom podklade.</p><i>Tlačidlo</i></div>
      </div>
      <ul class="pal-contrast" data-pal-contrast aria-live="polite"></ul>
      <p class="adm-field__hint">Kontrast aspoň 4,5 : 1 je dobre čitateľný pre všetkých (WCAG AA).</p>
    </aside>
  </div>
  <?php nast_savebar('Uložia sa oba režimy naraz.'); ?>
</form>

<?php else: /* vzhlad */ $flags = site_fx_flags(); $curD = $old['design'] ?? site_design(); $curT = $old['theme_default'] ?? site_theme_default(); ?>
<form method="post" action="admin-nastavenia?s=vzhlad" class="adm-form" data-adm-dirty>
  <?= csrf_field() ?>
  <section class="adm-card">
    <div class="adm-card__head"><h2><?= adm_icon('palette') ?>Dizajn webu</h2><p>Platí pre všetkých návštevníkov. Predošlú verziu súborov nájdete aj v priečinku <code>_zaloha/2026-11-22_2200_pred-redizajnom</code>.</p></div>
    <div class="adm-choices">
      <?php foreach (SITE_DESIGNS as $key => [$label, $note]): ?>
        <label class="adm-choice"><input type="radio" name="design" value="<?= e($key) ?>"<?= $curD === $key ? ' checked' : '' ?>>
          <span class="adm-choice__tick"><?= adm_icon('check', 14) ?></span><strong><?= e($label) ?></strong><small><?= e($note) ?></small></label>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="adm-card">
    <div class="adm-card__head"><h2><?= adm_icon('moon') ?>Predvolený režim</h2><p>Čo uvidí návštevník, ktorý si režim sám neprepol tlačidlom v hlavičke (jeho voľba má vždy prednosť).</p></div>
    <div class="adm-choices">
      <?php foreach (SITE_THEMES as $key => [$label, $note]): ?>
        <label class="adm-choice"><input type="radio" name="theme_default" value="<?= e($key) ?>"<?= $curT === $key ? ' checked' : '' ?>>
          <span class="adm-choice__tick"><?= adm_icon('check', 14) ?></span>
          <span class="adm-swatch adm-swatch--<?= e($key) ?>"><span>Aa</span><span>Aa</span></span>
          <strong><?= e($label) ?></strong><small><?= e($note) ?></small></label>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="adm-card">
    <div class="adm-card__head"><h2><?= adm_icon('sparkle') ?>Efekty nového dizajnu</h2><p>Platia iba pri „Nový dizajn (2026)“. Návštevníci s nastaveným „obmedziť pohyb“ animácie nevidia nikdy; na dotykových zariadeniach sa kurzor, magnet a náklon nepoužívajú.</p></div>
    <div class="adm-switches">
      <?php foreach (SITE_FX as $k => [$label, $note]): $on = $old !== null ? !empty($old['fx'][$k]) : $flags[$k]; ?>
        <label class="adm-switch"><input type="checkbox" name="fx[<?= e($k) ?>]" value="1"<?= $on ? ' checked' : '' ?>><span class="adm-switch__ui"></span>
          <span class="adm-switch__txt"><strong><?= e($label) ?></strong><small><?= e($note) ?></small></span></label>
      <?php endforeach; ?>
    </div>
  </section>
  <?php nast_savebar('Vzhľad sa zmení hneď po uložení.'); ?>
</form>
<?php endif; ?>
<?php page_end();
