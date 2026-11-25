<?php
// Spoločná stránka galérie pre verejnú fotogalériu (fotogaleria.php, scope 'public')
// a súkromnú administrátorskú galériu (moja-galeria.php, scope 'private').
// Návštevník vidí kategórie, fotografie (lightbox) a videá z YouTube (modálne okno).
// Administrátor navyše: nové kategórie fotografií aj videí, nahrávanie fotografií / pridávanie videí do existujúcej
// alebo novej kategórie, náhľad kategórie, poradie, premenovanie, presun a odstraňovanie položiek aj celých kategórií.
// Všetky zmeny idú cez POST s CSRF tokenom a oprávnenie sa overuje na serveri (rola z databázy).
require_once __DIR__ . '/bo_ui.php';

function gallery_base(string $scope): string { return $scope === 'public' ? 'fotogaleria' : 'moja-galeria'; }
function gallery_url(string $scope, string $slug = '', string $hash = ''): string {
    return gallery_base($scope) . ($slug !== '' ? '?kategoria=' . rawurlencode($slug) : '') . ($hash !== '' ? '#' . $hash : '');
}
function gallery_count_label(int $n, string $kind): string {
    if ($kind === 'video') return $n . ' ' . ($n === 1 ? 'video' : ($n >= 2 && $n <= 4 ? 'videá' : 'videí'));
    return $n . ' ' . sk_photos($n);
}

/* ======================= Spracovanie formulárov administrátora ======================= */

function gallery_handle_post(string $scope): never {
    $ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    $back = preg_match('/^[a-z0-9-]{0,80}$/', (string)($_POST['back'] ?? '')) ? (string)($_POST['back'] ?? '') : '';
    $hash = $back === '' ? 'sprava' : 'sprava-kategorie';
    $reply = function (bool $ok, string $msg, array $extra = []) use ($ajax, $scope, &$back, &$hash): never {
        if ($ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($ok ? 200 : 422);
            echo json_encode(['ok' => $ok, 'message' => $msg, 'redirect' => gallery_url($scope, $back, $hash)] + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if ($msg !== '') flash($ok ? 'success' : 'error', $msg);
        redirect(gallery_url($scope, $back, $hash));
    };
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') { http_response_code(403); $reply(false, 'Na správu galérie nemáte oprávnenie.'); }
    if (!csrf_valid($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) $reply(false, 'Neplatný bezpečnostný token. Obnovte stránku a skúste to znova.');
    gallery_seed();
    $act = (string)($_POST['ga'] ?? '');
    $p = fn(string $k) => trim((string)($_POST[$k] ?? ''));
    try {
        switch ($act) {
            case 'upload': {
                $files = gallery_files('files');
                if (!$files) throw new GalleryException('Vyberte aspoň jednu fotografiu.');
                $cid = gallery_resolve_category($scope, 'photo', $p('category'), $p('new_title'), $p('new_desc'));
                $c = gallery_category($cid);
                [$ok, $errors, $refs] = gallery_upload_photos($scope, $cid, $files, (int)$u['id']);
                $pick = $p('cover_pick');
                if ($pick !== '' && ctype_digit($pick) && isset($refs[(int)$pick])) gallery_category_set_cover($cid, $refs[(int)$pick]);
                $back = $c['slug']; $hash = 'polozky';
                $msg = $ok ? 'Do kategórie „' . $c['title'] . '“ ' . ($ok === 1 ? 'pribudla 1 fotografia.' : ($ok <= 4 ? 'pribudli ' : 'pribudlo ') . gallery_count_label($ok, 'photo') . '.') : 'Nenahrala sa žiadna fotografia.';
                if ($errors) $msg .= ' Chyby: ' . implode(' · ', $errors);
                $reply($ok > 0, $ajax ? '' : $msg, ['category_id' => $cid, 'uploaded' => $ok, 'errors' => $errors]);
            }
            case 'add_video': {
                $cid = gallery_resolve_category($scope, 'video', $p('category'), $p('new_title'), $p('new_desc'));
                try { $r = video_add($p('link'), $p('title'), $p('description'), $p('orientation') ?: 'auto', (int)$u['id'], $scope, $cid); }
                catch (VideoException $e) {
                    $c0 = gallery_category($cid);   // novú prázdnu kategóriu po chybe neponecháme
                    if ($c0 && !$c0['static_key'] && $p('category') === 'new') db()->prepare('DELETE FROM gallery_categories WHERE id = ?')->execute([$cid]);
                    $_SESSION['video_form'] = ['link' => $p('link'), 'title' => $p('title'), 'description' => $p('description')];
                    throw $e;
                }
                $c = gallery_category($cid);
                $back = $c['slug']; $hash = 'polozky';
                $reply(true, 'Video „' . $r['title'] . '“ bolo pridané do kategórie „' . $c['title'] . '“ (' . ($r['orientation'] === 'portrait' ? 'na výšku' : 'na šírku') . ').'
                    . ($r['meta_ok'] ? '' : ' Údaje z YouTube sa nepodarilo načítať – skontrolujte názov a orientáciu.'));
            }
            case 'cat_create': {
                $kind = $p('kind') === 'video' ? 'video' : 'photo';
                $cid = gallery_category_create($scope, $kind, $p('title'), $p('description'));
                $back = gallery_category($cid)['slug']; $hash = 'sprava-kategorie';
                $reply(true, 'Kategória bola vytvorená. ' . ($kind === 'video' ? 'Pridajte do nej videá z YouTube.' : 'Nahrajte do nej fotografie a vyberte náhľad.'));
            }
            case 'cat_update': {
                $c = gallery_category((int)$p('id'), $scope) ?? throw new GalleryException('Kategória neexistuje.');
                gallery_category_update($c['id'], $p('title'), $p('description'));
                $reply(true, 'Kategória bola uložená.');
            }
            case 'cat_move': {
                $c = gallery_category((int)$p('id'), $scope) ?? throw new GalleryException('Kategória neexistuje.');
                gallery_category_move($c['id'], $p('dir') === 'up' ? -1 : 1);
                $hash = 'kat-' . $c['id'];
                $reply(true, '');
            }
            case 'cat_delete': {
                $c = gallery_category((int)$p('id'), $scope) ?? throw new GalleryException('Kategória neexistuje.');
                $n = gallery_category_delete($c['id']);
                $back = ''; $hash = 'sprava';
                $reply(true, 'Kategória „' . $c['title'] . '“ bola odstránená' . ($n ? ' spolu s ' . $n . ' ' . ($n === 1 ? 'položkou' : 'položkami') : '') . '.');
            }
            case 'cat_cover': {
                $c = gallery_category((int)$p('id'), $scope) ?? throw new GalleryException('Kategória neexistuje.');
                gallery_category_set_cover($c['id'], $p('ref'));
                $hash = $back === '' ? 'kat-' . $c['id'] : 'polozky';
                $reply(true, 'Náhľad kategórie „' . $c['title'] . '“ bol zmenený.');
            }
            case 'item_delete': {
                gallery_item_delete($scope, $p('ref'));
                $hash = 'polozky';
                $reply(true, 'Položka bola odstránená z galérie.');
            }
            case 'item_move': {
                gallery_item_move($scope, $p('ref'), (int)$p('category'));
                $hash = 'polozky';
                $reply(true, 'Položka bola presunutá do kategórie „' . gallery_category((int)$p('category'))['title'] . '“.');
            }
            case 'item_update': {
                gallery_item_update($scope, $p('ref'), $p('title'), $p('description'), $p('orientation'));
                $hash = 'polozky';
                $reply(true, 'Popis bol uložený.');
            }
            case 'restore': {
                if ($scope !== 'public') throw new GalleryException('Neplatná akcia.');
                gallery_restore_static();
                $back = '';
                $reply(true, 'Pôvodné kategórie, fotografie a videá boli obnovené.');
            }
            case 'legacy_delete': {
                media_delete((int)$p('id'), $scope) ?: throw new GalleryException('Súbor neexistuje.');
                $back = '';
                $reply(true, 'Starý videosúbor bol zmazaný.');
            }
        }
        throw new GalleryException('Neznáma akcia.');
    } catch (GalleryException | VideoException | MediaException $e) {
        $reply(false, $e->getMessage());
    }
}

/* ======================= Ikony (vlastné SVG, čiary 1,75 px, farba podľa textu) ======================= */

function gsvg(string $name, int $size = 18): string {
    static $P = [
        'upload'   => '<path d="M12 15V4"/><path d="m7.5 8.5 4.5-4.5 4.5 4.5"/><path d="M4 15v3.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V15"/>',
        'image'    => '<rect x="3" y="3.5" width="18" height="17" rx="3"/><circle cx="8.75" cy="9" r="1.75"/><path d="m21 15.5-4.6-4.6a1 1 0 0 0-1.4 0L5.5 20.5"/>',
        'video'    => '<rect x="2.5" y="4.5" width="19" height="15" rx="4"/><path d="M10 9.2v5.6a.5.5 0 0 0 .76.43l4.6-2.8a.5.5 0 0 0 0-.86l-4.6-2.8a.5.5 0 0 0-.76.43z"/>',
        'folder'   => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H9l2 2h7.5A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M12 10.5v6M9 13.5h6"/>',
        'move'     => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H9l2 2h7.5A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M9 13.5h6M12.5 11l2.5 2.5-2.5 2.5"/>',
        'pencil'   => '<path d="M4 20h4.2L19.1 9.1a2.6 2.6 0 0 0-3.7-3.7L4.5 16.3V20z"/><path d="m14 7 3 3"/>',
        'trash'    => '<path d="M4 7h16"/><path d="M9.5 7V5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/><path d="M6.5 7l.8 11.6A2 2 0 0 0 9.3 20.5h5.4a2 2 0 0 0 2-1.9L17.5 7"/><path d="M10.25 11v5.5M13.75 11v5.5"/>',
        'left'     => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
        'right'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'star'     => '<path d="m12 3.5 2.6 5.3 5.9.85-4.25 4.15 1 5.85L12 16.9l-5.25 2.75 1-5.85L3.5 9.65l5.9-.85z"/>',
        'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x'        => '<path d="M6.5 6.5l11 11M17.5 6.5l-11 11"/>',
        'lock'     => '<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'shield'   => '<path d="M12 3.2 5 6v5.6c0 4.4 3 7.7 7 9.2 4-1.5 7-4.8 7-9.2V6z"/><path d="m9.2 12.2 2 2 3.8-4"/>',
        'restore'  => '<path d="M4 12a8 8 0 1 0 2.4-5.7"/><path d="M4 4.5V9h4.5"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'grid'     => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'film'     => '<rect x="3" y="3.5" width="18" height="17" rx="3"/><path d="M7.5 3.5v17M16.5 3.5v17M3 9h4.5M3 15h4.5M16.5 9H21M16.5 15H21"/>',
    ];
    return '<svg class="gsvg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($P[$name] ?? '') . '</svg>';
}

/* ======================= Časti stránky ======================= */

/** Výber kategórie: existujúce + „Nová kategória…“ (polia pre názov sa ukážu po výbere). */
function gallery_cat_select(string $scope, string $kind, string $id, ?int $selected = null): void {
    $cats = gallery_categories($scope, $kind);
    echo '<div class="gfield"><label for="' . e($id) . '">Kategória</label><div class="gselect"><select id="' . e($id) . '" name="category" data-cat-select>';
    foreach ($cats as $c) echo '<option value="' . $c['id'] . '"' . ($selected === $c['id'] ? ' selected' : '') . '>' . e($c['title']) . '</option>';
    echo '<option value="new"' . (!$cats ? ' selected' : '') . '>+ Nová kategória…</option></select></div></div>';
    echo '<div class="gnew" data-new-fields><div class="gfield"><label for="' . e($id) . '-t">Názov novej kategórie</label><input id="' . e($id) . '-t" name="new_title" maxlength="80" data-required-new placeholder="napr. ' . ($kind === 'video' ? 'Výcvik sokolov' : 'Odchov 2026') . '"></div>'
       . '<div class="gfield"><label for="' . e($id) . '-d">Popis <span class="gopt">nepovinné</span></label><input id="' . e($id) . '-d" name="new_desc" maxlength="300" placeholder="Krátka veta o kategórii"></div></div>';
}

function gallery_form_upload(string $scope, ?array $cat = null): void {
    $id = 'up-' . ($cat['id'] ?? 'x');
    echo '<form class="gform" method="post" action="' . e(gallery_url($scope)) . '" enctype="multipart/form-data" data-gallery-upload>'
       . csrf_field() . '<input type="hidden" name="ga" value="upload"><input type="hidden" name="back" value="' . e($cat['slug'] ?? '') . '">';
    if ($cat) echo '<input type="hidden" name="category" value="' . $cat['id'] . '">';
    else gallery_cat_select($scope, 'photo', $id . '-cat');
    echo '<label class="gdrop" for="' . e($id) . '-f" data-drop><input class="gdrop__input" id="' . e($id) . '-f" type="file" name="files[]" multiple required accept="image/jpeg,image/png,image/webp" data-upload-files>'
       . '<span class="gdrop__icon">' . gsvg('upload', 22) . '</span><span class="gdrop__title">Pretiahnite fotografie sem</span>'
       . '<span class="gdrop__sub">alebo <span class="gdrop__link">vyberte zo zariadenia</span> · JPG, PNG, WebP do ' . intdiv(MAX_IMAGE_BYTES, 1048576) . ' MB</span>'
       . '<span class="gdrop__count" data-drop-count></span></label>'
       . '<div class="gup" data-upload-preview hidden><p class="gup__hint">' . gsvg('star', 14) . ' Kliknutím vyberiete <strong>náhľad kategórie</strong></p><ul class="gup__list"></ul></div>'
       . '<noscript><div class="gfield"><label for="' . e($id) . '-c">Náhľad kategórie</label><select id="' . e($id) . '-c" name="cover_pick"><option value="">nemeniť</option><option value="0">prvá nahratá fotografia</option></select></div></noscript>'
       . '<ul class="gprog" data-upload-progress hidden></ul>'
       . '<div class="gform__foot"><button type="submit" class="gbtn-primary">' . gsvg('upload', 16) . '<span>Nahrať fotografie</span></button></div></form>';
}

function gallery_form_video(string $scope, ?array $cat = null): void {
    $vf = $_SESSION['video_form'] ?? []; unset($_SESSION['video_form']);
    $id = 'vd-' . ($cat['id'] ?? 'x');
    echo '<form class="gform" method="post" action="' . e(gallery_url($scope)) . '">' . csrf_field()
       . '<input type="hidden" name="ga" value="add_video"><input type="hidden" name="back" value="' . e($cat['slug'] ?? '') . '">';
    if ($cat) echo '<input type="hidden" name="category" value="' . $cat['id'] . '">';
    else gallery_cat_select($scope, 'video', $id . '-cat');
    echo '<div class="gfield"><label for="' . e($id) . '-l">Odkaz na YouTube</label><div class="ginput-icon">' . gsvg('video', 18)
       . '<input id="' . e($id) . '-l" name="link" type="url" inputmode="url" required maxlength="300" placeholder="https://youtu.be/…  alebo  …/shorts/…" value="' . e($vf['link'] ?? '') . '"></div></div>'
       . '<div class="ggrid2"><div class="gfield"><label for="' . e($id) . '-t">Názov <span class="gopt">prevezme sa z YouTube</span></label><input id="' . e($id) . '-t" name="title" maxlength="120" value="' . e($vf['title'] ?? '') . '"></div>'
       . '<div class="gfield"><label for="' . e($id) . '-o">Orientácia</label><div class="gselect"><select id="' . e($id) . '-o" name="orientation"><option value="auto">Zistiť automaticky</option><option value="landscape">Na šírku · 16 : 9</option><option value="portrait">Na výšku · Shorts</option></select></div></div></div>'
       . '<div class="gfield"><label for="' . e($id) . '-d">Popis <span class="gopt">nepovinné</span></label><textarea id="' . e($id) . '-d" name="description" rows="2" maxlength="1000" placeholder="Čo je na videu – zobrazí sa v okne prehrávača">' . e($vf['description'] ?? '') . '</textarea></div>'
       . '<div class="gform__foot"><button type="submit" class="gbtn-primary">' . gsvg('plus', 16) . '<span>Pridať video</span></button></div></form>';
}

function gallery_form_new_category(string $scope): void {
    echo '<form class="gform" method="post" action="' . e(gallery_url($scope)) . '">' . csrf_field() . '<input type="hidden" name="ga" value="cat_create">'
       . '<div class="gfield"><span class="glabel">Typ kategórie</span><div class="gseg">'
       . '<label><input type="radio" name="kind" value="photo" checked><span>' . gsvg('image', 16) . 'Fotografie</span></label>'
       . '<label><input type="radio" name="kind" value="video"><span>' . gsvg('video', 16) . 'Videá z YouTube</span></label></div></div>'
       . '<div class="ggrid2"><div class="gfield"><label for="nc-t-' . $scope . '">Názov</label><input id="nc-t-' . $scope . '" name="title" required maxlength="80"></div>'
       . '<div class="gfield"><label for="nc-d-' . $scope . '">Popis <span class="gopt">nepovinné</span></label><input id="nc-d-' . $scope . '" name="description" maxlength="300"></div></div>'
       . '<div class="gform__foot"><button type="submit" class="gbtn-primary">' . gsvg('folder', 16) . '<span>Vytvoriť kategóriu</span></button></div></form>';
}

/** Tlačidlo-formulár (POST s CSRF). $label môže obsahovať SVG. */
function gallery_btn(string $scope, array $fields, string $label, string $class = 'gbtn', string $confirm = '', string $title = ''): string {
    $h = '<form method="post" action="' . e(gallery_url($scope)) . '" class="ginline">' . csrf_field();
    foreach ($fields as $k => $v) $h .= '<input type="hidden" name="' . e($k) . '" value="' . e((string)$v) . '">';
    return $h . '<button type="submit" class="' . e($class) . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . ($title ? ' title="' . e($title) . '" aria-label="' . e($title) . '"' : '') . '>' . $label . '</button></form>';
}

/** Hlavička administrátorského panelu. */
function gallery_adm_head(string $title, string $text = '', string $id = ''): string {
    return '<div class="gadm__head"><span class="gadm__badge">' . gsvg('shield', 14) . 'Administrácia</span><h2' . ($id ? ' id="' . e($id) . '"' : '') . '>' . e($title) . '</h2>' . ($text ? '<p>' . $text . '</p>' : '') . '</div>';
}

/** Karty kategórií (prehľad) + nástroje admina. */
function gallery_cards(string $scope, array $cats, bool $admin): void {
    if (!$cats) return;
    $n = count($cats);
    echo '<ul class="gcats' . ($admin ? ' gcats--admin' : '') . '">';
    foreach ($cats as $i => $c) {
        $cover = $c['_cover']; $cnt = count($c['_items']);
        echo '<li data-reveal id="kat-' . $c['id'] . '"><a class="gcat" href="' . e(gallery_url($scope, $c['slug'])) . '"><span class="gcat__media">'
           . ($cover ? '<img src="' . e($cover) . '" alt="" loading="lazy" decoding="async">' : '<span class="gcat__empty">' . gsvg($c['kind'] === 'video' ? 'film' : 'image', 28) . '<span>Prázdna kategória</span></span>')
           . ($c['kind'] === 'video' && $cover ? '<span class="gcat__play" aria-hidden="true"></span>' : '')
           . '<span class="gcat__count">' . e(gallery_count_label($cnt, $c['kind'])) . '</span></span>'
           . '<span class="gcat__body"><span class="gcat__title">' . e($c['title']) . '</span>' . ($c['description'] !== '' ? '<span class="gcat__text">' . e($c['description']) . '</span>' : '') . '</span></a>';
        if ($admin) {
            echo '<div class="gtools" role="group" aria-label="Úpravy kategórie ' . e($c['title']) . '">'
               . '<span class="gtools__group">'
               . ($i > 0 ? gallery_btn($scope, ['ga' => 'cat_move', 'id' => $c['id'], 'dir' => 'up'], gsvg('left', 16), 'gicon', '', 'Posunúť dopredu') : '<span class="gicon is-off" aria-hidden="true">' . gsvg('left', 16) . '</span>')
               . ($i < $n - 1 ? gallery_btn($scope, ['ga' => 'cat_move', 'id' => $c['id'], 'dir' => 'down'], gsvg('right', 16), 'gicon', '', 'Posunúť dozadu') : '<span class="gicon is-off" aria-hidden="true">' . gsvg('right', 16) . '</span>')
               . '</span><span class="gtools__group">'
               . '<a class="gicon" href="' . e(gallery_url($scope, $c['slug'], 'sprava-kategorie')) . '" title="Upraviť názov a popis" aria-label="Upraviť názov a popis">' . gsvg('pencil', 16) . '</a>'
               . '<a class="gicon" href="' . e(gallery_url($scope, $c['slug'], 'polozky')) . '" title="Vybrať náhľad kategórie" aria-label="Vybrať náhľad kategórie">' . gsvg('star', 16) . '</a>'
               . gallery_btn($scope, ['ga' => 'cat_delete', 'id' => $c['id']], gsvg('trash', 16), 'gicon gicon--danger',
                   'Odstrániť kategóriu „' . $c['title'] . '“ aj so všetkými položkami (' . gallery_count_label($cnt, $c['kind']) . ')?' . ($scope === 'public' ? ' Nahraté súbory sa zmažú natrvalo, pôvodné sa dajú obnoviť.' : ' Súbory sa zmažú natrvalo.'), 'Odstrániť kategóriu')
               . '</span></div>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

/** Správa položiek kategórie (iba admin): náhľad, presun, popis, odstránenie. */
function gallery_manage_items(string $scope, array $cat, array $items): void {
    $targets = array_filter(gallery_categories($scope, $cat['kind']), fn($c) => $c['id'] !== $cat['id']);
    $coverRef = $cat['cover'] !== '' ? $cat['cover'] : ($items[0]['ref'] ?? '');
    echo '<section class="gadm gadm--items" id="polozky" aria-labelledby="polozky-h"><div class="container container--wide">'
       . gallery_adm_head('Položky kategórie · ' . count($items), 'Hviezdička nastaví náhľad, ktorý sa zobrazuje na karte kategórie. Odstránené položky zmiznú zo stránky' . ($scope === 'public' ? ' – pôvodné fotografie a videá webu sa dajú obnoviť v prehľade galérie.' : '.'), 'polozky-h');
    if (!$items) { echo '<div class="gempty">' . gsvg($cat['kind'] === 'video' ? 'film' : 'image', 28) . '<p>Kategória je zatiaľ prázdna.</p></div></div></section>'; return; }
    echo '<ul class="gitems">';
    foreach ($items as $it) {
        $isCover = $it['ref'] === $coverRef;
        $video = $it['type'] === 'video';
        $name = $it['title'] ?: ($video ? 'Video' : 'Fotografia');
        echo '<li class="gitem' . ($isCover ? ' is-cover' : '') . '"><div class="gitem__media"><img src="' . e($it['thumb']) . '" alt="" loading="lazy" decoding="async">'
           . ($isCover ? '<span class="gitem__badge">' . gsvg('star', 13) . 'Náhľad</span>' : '')
           . ($video ? '<span class="gitem__kind">' . gsvg('video', 14) . ($it['video']['portrait'] ? 'na výšku' : 'video') . '</span>' : '')
           . '<div class="gitem__tools">'
           . ($isCover ? '' : gallery_btn($scope, ['ga' => 'cat_cover', 'id' => $cat['id'], 'ref' => $it['ref'], 'back' => $cat['slug']], gsvg('star', 16), 'gicon gicon--glass', '', 'Nastaviť ako náhľad kategórie'))
           . gallery_btn($scope, ['ga' => 'item_delete', 'ref' => $it['ref'], 'back' => $cat['slug']], gsvg('trash', 16), 'gicon gicon--glass gicon--danger',
                'Odstrániť „' . $name . '“ zo stránky?' . ($it['source'] === 'db' && !$video ? ' Súbor sa zmaže natrvalo.' : ''), 'Odstrániť zo stránky')
           . '</div></div>'
           . '<div class="gitem__body"><p class="gitem__title" title="' . e($name) . '">' . e($name) . '</p>'
           . '<p class="gitem__meta">' . ($it['source'] === 'static' ? 'Pôvodná položka webu' : ($video ? 'Pridané cez web' : 'Nahratá cez web')) . '</p>';
        if ($targets) {
            echo '<form method="post" action="' . e(gallery_url($scope)) . '" class="gitem__move">' . csrf_field()
               . '<input type="hidden" name="ga" value="item_move"><input type="hidden" name="ref" value="' . e($it['ref']) . '"><input type="hidden" name="back" value="' . e($cat['slug']) . '">'
               . '<label class="sr-only" for="mv-' . e($it['ref']) . '">Presunúť do kategórie</label><div class="gselect gselect--sm"><select id="mv-' . e($it['ref']) . '" name="category" required><option value="" disabled selected>Presunúť do…</option>';
            foreach ($targets as $t) echo '<option value="' . $t['id'] . '">' . e($t['title']) . '</option>';
            echo '</select></div><button type="submit" class="gicon" title="Presunúť do vybranej kategórie" aria-label="Presunúť do vybranej kategórie">' . gsvg('move', 16) . '</button></form>';
        }
        if ($it['source'] === 'db') {
            echo '<details class="gitem__edit"><summary>' . gsvg('pencil', 14) . 'Upraviť popis</summary><form method="post" action="' . e(gallery_url($scope)) . '">' . csrf_field()
               . '<input type="hidden" name="ga" value="item_update"><input type="hidden" name="ref" value="' . e($it['ref']) . '"><input type="hidden" name="back" value="' . e($cat['slug']) . '">'
               . '<label class="sr-only" for="et-' . e($it['ref']) . '">Názov</label><input id="et-' . e($it['ref']) . '" name="title" maxlength="120" value="' . e($it['title']) . '" placeholder="Názov">'
               . '<label class="sr-only" for="ed-' . e($it['ref']) . '">Popis</label><textarea id="ed-' . e($it['ref']) . '" name="description" rows="2" maxlength="1000" placeholder="Popis">' . e($it['desc']) . '</textarea>';
            if ($video) echo '<div class="gselect gselect--sm"><select name="orientation" aria-label="Orientácia"><option value="landscape"' . ($it['video']['portrait'] ? '' : ' selected') . '>Na šírku</option><option value="portrait"' . ($it['video']['portrait'] ? ' selected' : '') . '>Na výšku</option></select></div>';
            echo '<button type="submit" class="gbtn">' . gsvg('check', 15) . '<span>Uložiť</span></button></form></details>';
        }
        echo '</div></li>';
    }
    echo '</ul></div></section>';
}

/** Panel s kartami (záložky) – bez JavaScriptu sa zobrazia všetky pod sebou. */
function gallery_tabs(array $tabs): void {
    $uid = 'gt' . substr(md5(implode('', array_keys($tabs))), 0, 5);
    echo '<div class="gcard gcard--tabs" data-gtabs><div class="gtabs" role="tablist" aria-label="Pridať do galérie">';
    $i = 0;
    foreach ($tabs as $k => [$icon, $label, $fn]) {
        echo '<button type="button" role="tab" id="' . $uid . '-t-' . $k . '" aria-controls="' . $uid . '-p-' . $k . '" aria-selected="' . ($i === 0 ? 'true' : 'false') . '"' . ($i ? ' tabindex="-1"' : '') . '>' . gsvg($icon, 17) . '<span>' . e($label) . '</span></button>';
        $i++;
    }
    echo '</div>';
    foreach ($tabs as $k => [$icon, $label, $fn]) {
        echo '<div class="gtabpanel" role="tabpanel" id="' . $uid . '-p-' . $k . '" aria-labelledby="' . $uid . '-t-' . $k . '"><h3 class="gtabpanel__h">' . gsvg($icon, 17) . e($label) . '</h3>';
        $fn();
        echo '</div>';
    }
    echo '</div>';
}

/* ======================= Stránka ======================= */

function gallery_page(string $scope): void {
    $admin = is_admin();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['ga'])) gallery_handle_post($scope);
    try { gallery_seed(); } catch (Throwable $e) { error_log('[galeria] ' . $e->getMessage()); }

    $cats = gallery_categories($scope);
    foreach ($cats as &$c) { $c['_items'] = gallery_items($c); $c['_cover'] = gallery_cover_src($c, $c['_items']); }
    unset($c);
    $visible = $admin ? $cats : array_values(array_filter($cats, fn($c) => $c['_items']));
    $photoCats = array_values(array_filter($visible, fn($c) => $c['kind'] === 'photo'));
    $videoCats = array_values(array_filter($visible, fn($c) => $c['kind'] === 'video'));

    $slug = (string)($_GET['kategoria'] ?? '');
    $cat = null; $notFound = false;
    if ($slug !== '' && $slug !== 'videa') {
        foreach ($visible as $c) if ($c['slug'] === $slug) $cat = $c;
        if (!$cat) { $notFound = true; $slug = ''; http_response_code(404); }
    }
    $private = $scope === 'private';
    if (!$private && $slug !== '') {   // vlastný titulok, popis a kanonická adresa kategórie
        global $SITE;
        $label = $cat ? $cat['title'] : 'Videá';
        $what = !$cat || $cat['kind'] === 'video' ? 'videá' : 'fotografie';
        $SITE['pages']['fotogaleria']['title'] = $label . ($cat ? ' – ' . $what : ' zo sokoliarstva a odchovu dravcov') . ' | OZ Divé krídla';
        $SITE['pages']['fotogaleria']['description'] = ($cat && $cat['description'] !== '' ? $cat['description'] . ' ' : '') . ($cat ? $label . ' – ' . $what . ' OZ Divé krídla.' : 'Videá z odchovu dravých vtákov, liahnutia a vypúšťania sokolov. OZ Divé krídla.');
        $GLOBALS['SEO_QUERY'] = 'kategoria=' . $slug;
        $GLOBALS['SEO_LABEL'] = $label;
    }
    page_start($private ? 'moja-galeria' : 'fotogaleria');

    if ($slug === '') {
        /* ---------- prehľad ---------- */
        echo '<section class="page-top"><div class="container"><div class="section-head reveal">';
        if ($private) {
            echo '<p class="eyebrow geyebrow-lock">' . gsvg('lock', 14) . 'Súkromné · ' . ($admin ? 'iba pre administrátora a vybraných používateľov' : 'iba na prezeranie') . '</p><h1>' . ($admin ? 'Moja administrátorská galéria' : 'Administrátorská galéria') . '</h1>'
               . '<p class="lead">Súkromné fotografie a videá v kategóriách. Nezobrazujú sa návštevníkom ani ostatným používateľom a súbory sú uložené mimo verejne dostupných priečinkov.</p>'
               . '<div class="btn-row"><a class="btn btn--outline" href="fotogaleria">Verejná fotogaléria</a>' . ($admin ? '<a class="btn btn--outline" href="admin">Administrácia</a>' : '<a class="btn btn--outline" href="ucet">Môj účet</a>') . '</div>';
        } else {
            echo '<p class="eyebrow">Fotogaléria</p><h1>Fotografie a videá z našej práce</h1>'
               . '<p class="lead">Vyberte si tému. Po otvorení kategórie zobrazíte fotografie vo väčšom náhľade a môžete medzi nimi listovať šípkami alebo potiahnutím prsta.</p>';
        }
        if ($notFound) echo '<p class="alert alert--error" role="alert">Hľadaná kategória neexistuje. Vyberte si niektorú z kategórií nižšie.</p>';
        echo '</div></div></section>';

        if ($admin) {
            echo '<section class="gadm" id="sprava" aria-labelledby="sprava-h"><div class="container container--wide">'
               . gallery_adm_head('Správa ' . ($private ? 'mojej galérie' : 'galérie'), 'Pridajte fotografie alebo video do existujúcej kategórie, alebo rovno do novej. Poradie, názov, náhľad a odstránenie nájdete pri každej kategórii nižšie.', 'sprava-h');
            gallery_tabs([
                'foto'  => ['image', 'Nahrať fotografie', fn() => gallery_form_upload($scope)],
                'video' => ['video', 'Pridať video', fn() => gallery_form_video($scope)],
                'kat'   => ['folder', 'Nová kategória', fn() => gallery_form_new_category($scope)],
            ]);
            if (!$private) {
                $rm = gallery_removed_static();
                if ($rm['photos'] || $rm['videos'] || $rm['categories']) {
                    $parts = array_filter([count($rm['categories']) ? count($rm['categories']) . ' ' . (count($rm['categories']) === 1 ? 'kategória' : (count($rm['categories']) <= 4 ? 'kategórie' : 'kategórií')) : '', $rm['photos'] ? gallery_count_label($rm['photos'], 'photo') : '', $rm['videos'] ? gallery_count_label($rm['videos'], 'video') : '']);
                    echo '<div class="gnote"><span class="gnote__icon">' . gsvg('restore', 18) . '</span><p><strong>Odstránené pôvodné položky webu</strong><span>' . e(implode(' · ', $parts)) . ' – súbory zostali na disku, dajú sa vrátiť.</span></p>'
                       . gallery_btn($scope, ['ga' => 'restore'], gsvg('restore', 15) . '<span>Obnoviť všetko</span>', 'gbtn', 'Vrátiť na stránku všetky odstránené pôvodné kategórie, fotografie a videá webu?') . '</div>';
                }
            }
            $legacy = gallery_legacy_videos($scope);
            if ($legacy) {
                echo '<div class="gnote gnote--list"><span class="gnote__icon">' . gsvg('film', 18) . '</span><p><strong>Staré nahraté videosúbory · ' . count($legacy) . '</strong><span>Na stránke sa nezobrazujú. Ak ich chcete v galérii, nahrajte ich na YouTube a pridajte odkazom.</span></p><ul class="glegacy">';
                foreach ($legacy as $m) echo '<li><span>' . e($m['title'] ?: $m['filename']) . ' <span class="gopt">' . round($m['size'] / 1048576, 1) . ' MB</span></span>'
                    . gallery_btn($scope, ['ga' => 'legacy_delete', 'id' => $m['id']], gsvg('trash', 15), 'gicon gicon--danger', 'Natrvalo zmazať videosúbor „' . ($m['title'] ?: $m['filename']) . '“?', 'Zmazať súbor') . '</li>';
                echo '</ul></div>';
            }
            echo '</div></section>';
        }

        echo '<section class="band band--sage band--gallery"><div class="container">';
        if ($photoCats || $admin) {
            $np = count($photoCats);
            echo '<div class="gsection__head"><h2>Fotografie</h2><span>' . $np . ' ' . ($np === 1 ? 'kategória' : ($np >= 2 && $np <= 4 ? 'kategórie' : 'kategórií')) . '</span></div>';
            $photoCats ? gallery_cards($scope, $photoCats, $admin) : print('<div class="gempty">' . gsvg('image', 28) . '<p>Zatiaľ tu nie je žiadna kategória fotografií.</p></div>');
        }
        if ($videoCats || $admin) {
            echo '<div class="gsection__head gsection__head--next"><h2>Videá</h2>' . ($videoCats ? '<a href="' . e(gallery_url($scope, 'videa')) . '">Všetky videá na jednej stránke →</a>' : '') . '</div>';
            $videoCats ? gallery_cards($scope, $videoCats, $admin) : print('<div class="gempty">' . gsvg('film', 28) . '<p>Zatiaľ tu nie je žiadna kategória videí.</p></div>');
        }
        if (!$photoCats && !$videoCats && !$admin) echo '<p class="muted center">Galéria je zatiaľ prázdna.</p>';
        echo '</div></section>';

    } elseif ($slug === 'videa') {
        /* ---------- všetky videá ---------- */
        $all = []; foreach ($videoCats as $c) foreach ($c['_items'] as $it) $all[] = $it;
        echo '<section class="page-top page-top--gallery"><div class="container"><nav class="gback" aria-label="Navigácia v galérii"><a class="gback__all" href="' . e(gallery_url($scope)) . '">← Všetky kategórie</a><span class="gback__crumb" aria-hidden="true">' . ($private ? 'Moja galéria' : 'Fotogaléria') . ' / Videá</span></nav>'
           . '<div class="section-head"><p class="eyebrow">' . e(gallery_count_label(count($all), 'video')) . '</p><h1>Videá</h1><p class="lead">Video sa prehrá po kliknutí v samostatnom okne.</p></div></div></section>';
        if ($admin) {
            echo '<section class="gadm" id="sprava"><div class="container">' . gallery_adm_head('Pridať video z YouTube') . '<div class="gcard">';
            gallery_form_video($scope);
            echo '</div></div></section>';
        }
        echo '<section class="band band--dark band--gallery"><div class="container container--wide">';
        foreach ($videoCats as $c) {
            if (!$c['_items']) continue;
            echo '<div class="vgroup" id="videa-' . e($c['slug']) . '"><div class="vgroup__head"><h2><a href="' . e(gallery_url($scope, $c['slug'])) . '">' . e($c['title']) . '</a></h2><span>' . e(gallery_count_label(count($c['_items']), 'video')) . '</span></div>';
            bo_videos(array_column($c['_items'], 'video'), 'grid', $c['title']);
            echo '</div>';
        }
        if (!$all) echo '<p class="center">Zatiaľ tu nie sú žiadne videá.</p>';
        echo '</div></section>';

    } else {
        /* ---------- jedna kategória ---------- */
        $same = array_values(array_filter($visible, fn($c) => $c['kind'] === $cat['kind']));
        $ids = array_column($same, 'id'); $i = array_search($cat['id'], $ids, true); $n = count($same);
        $prev = $same[($i - 1 + $n) % $n]; $next = $same[($i + 1) % $n];
        $items = $cat['_items'];
        echo '<section class="page-top page-top--gallery"><div class="container"><nav class="gback" aria-label="Navigácia v galérii"><a class="gback__all" href="' . e(gallery_url($scope)) . '">← Všetky kategórie</a>'
           . '<span class="gback__crumb" aria-hidden="true">' . ($private ? 'Moja galéria' : 'Fotogaléria') . ' / ' . e($cat['title']) . '</span></nav>'
           . '<div class="section-head"><p class="eyebrow">' . ($private ? 'Moja galéria' : 'Fotogaléria') . ' · ' . e(gallery_count_label(count($items), $cat['kind'])) . '</p><h1>' . e($cat['title']) . '</h1>'
           . ($cat['description'] !== '' ? '<p class="lead">' . e($cat['description']) . '</p>' : '') . '</div></div></section>';

        if ($admin) {
            $isPhoto = $cat['kind'] === 'photo';
            echo '<section class="gadm" id="sprava-kategorie" aria-labelledby="sk-h"><div class="container container--wide">'
               . gallery_adm_head('Správa kategórie', '', 'sk-h') . '<div class="gadm__grid">';
            echo '<div class="gcard"><h3 class="gcard__h">' . gsvg($isPhoto ? 'image' : 'video', 17) . ($isPhoto ? 'Pridať fotografie' : 'Pridať video z YouTube') . '</h3>';
            $isPhoto ? gallery_form_upload($scope, $cat) : gallery_form_video($scope, $cat);
            echo '</div>';
            echo '<div class="gcard"><h3 class="gcard__h">' . gsvg('pencil', 17) . 'Nastavenia kategórie</h3>'
               . '<form class="gform" method="post" action="' . e(gallery_url($scope)) . '">' . csrf_field()
               . '<input type="hidden" name="ga" value="cat_update"><input type="hidden" name="id" value="' . $cat['id'] . '"><input type="hidden" name="back" value="' . e($cat['slug']) . '">'
               . '<div class="gfield"><label for="ct">Názov</label><input id="ct" name="title" required maxlength="80" value="' . e($cat['title']) . '"></div>'
               . '<div class="gfield"><label for="cd">Popis <span class="gopt">nepovinné</span></label><textarea id="cd" name="description" rows="2" maxlength="300">' . e($cat['description']) . '</textarea></div>'
               . '<div class="gform__foot"><button type="submit" class="gbtn-primary">' . gsvg('check', 16) . '<span>Uložiť zmeny</span></button></div></form>'
               . '<div class="gdanger"><div><strong>Odstrániť kategóriu</strong><span>Spolu s ' . e(gallery_count_label(count($items), $cat['kind'])) . '.' . ($private ? ' Súbory sa zmažú natrvalo.' : ' Nahraté súbory sa zmažú natrvalo, pôvodné sa dajú obnoviť.') . '</span></div>'
               . gallery_btn($scope, ['ga' => 'cat_delete', 'id' => $cat['id'], 'back' => $cat['slug']], gsvg('trash', 15) . '<span>Odstrániť</span>', 'gbtn gbtn--danger', 'Naozaj odstrániť kategóriu „' . $cat['title'] . '“ aj so všetkými položkami?') . '</div>'
               . '</div>';
            echo '</div></div></section>';
        }

        if ($cat['kind'] === 'photo') {
            echo '<section class="band band--sage band--gallery"><div class="container container--wide">';
            $items ? gallery_grid($items, $cat['title'] . ' – fotografie') : print('<p class="muted center">V tejto kategórii zatiaľ nie sú žiadne fotografie.</p>');
            echo '</div></section>';
        } else {
            echo '<section class="band band--dark band--gallery"><div class="container container--wide">';
            $items ? bo_videos(array_column($items, 'video'), 'grid', $cat['title']) : print('<p class="center">V tejto kategórii zatiaľ nie sú žiadne videá.</p>');
            echo '</div></section>';
        }
        if ($admin) gallery_manage_items($scope, $cat, $items);

        echo '<section class="band band--tight"><div class="container gnav">';
        if ($n > 1) echo '<a class="btn btn--outline" href="' . e(gallery_url($scope, $prev['slug'])) . '">‹ ' . e($prev['title']) . '</a>';
        echo '<a class="btn btn--dark" href="' . e(gallery_url($scope)) . '">Všetky kategórie</a>';
        if ($n > 1) echo '<a class="btn btn--outline" href="' . e(gallery_url($scope, $next['slug'])) . '">' . e($next['title']) . ' ›</a>';
        echo '</div></section>';
    }
    if ($admin) echo '<script defer src="' . e(asset('assets/js/bo-gallery-admin.js')) . '"></script>';
    page_end();
}
