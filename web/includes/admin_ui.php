<?php
// Rozhranie administrácie (22. 11. 2026): navigácia bočného panela, ikony, hlavička stránky, karty.
// Vykresľuje includes/admin_header.php + admin_footer.php (page_start/page_end pre stránky z ADMIN_LAYOUT_PAGES).
// Štýly: assets/css/admin.css · skripty: assets/js/admin.js (bočný panel, rýchle hľadanie Ctrl+K, prednačítanie stránok).

/** Ikony administrácie (vlastné SVG, 24×24, ťah = currentColor). */
function adm_icon(string $name, int $size = 18): string {
    static $P = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M9.5 20v-6h5v6"/>',
        'layout' => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 9h18M9 9v11"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-9 9"/>',
        'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.6"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.6"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.6"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.6"/>',
        'video' => '<rect x="3" y="6" width="13" height="12" rx="2.5"/><path d="m16 10.5 5-3v9l-5-3"/>',
        'lock' => '<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'phone' => '<path d="M5 4h3.5l1.7 4.3-2.2 1.4a11 11 0 0 0 6.3 6.3l1.4-2.2L20 15.5V19a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1Z"/>',
        'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.7-.9 1.4-1.8-.4-1.2.4-2.2 1.6-2.2H17a4 4 0 0 0 4-4c0-5.5-4-10-9-10Z"/><circle cx="7.5" cy="11" r="1.2"/><circle cx="10.5" cy="7" r="1.2"/><circle cx="15" cy="7.5" r="1.2"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14a6 6 0 0 1 3 6"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'logout' => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'sidebar' => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M9 4v16"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'upload' => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.4 7.5 9.5 4.3-1.1 7.5-4.9 7.5-9.5V6Z"/><path d="m9 12 2 2 4-4"/>',
        'trash' => '<path d="M4 7h16M9 7V4.5h6V7M18 7l-.8 12.2a1 1 0 0 1-1 .8H7.8a1 1 0 0 1-1-.8L6 7M10 11v5.5M14 11v5.5"/>',
        'restore' => '<path d="M4 4v5h5"/><path d="M4.5 9A8 8 0 1 1 4 13"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'sparkle' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
        'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'database' => '<ellipse cx="12" cy="5.5" rx="7.5" ry="2.5"/><path d="M4.5 5.5v13c0 1.4 3.4 2.5 7.5 2.5s7.5-1.1 7.5-2.5v-13M4.5 12c0 1.4 3.4 2.5 7.5 2.5s7.5-1.1 7.5-2.5"/>',
        'command' => '<path d="M9 6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3Z"/>',
        'text' => '<path d="M5 6V4.5h14V6M12 4.5v15M9 19.5h6"/>',
    ];
    $p = $P[$name] ?? $P['sparkle'];
    return '<svg class="adm-ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

/** Navigácia: skupina => [[kľúč, odkaz, názov, ikona, popis pre rýchle hľadanie]]. Kľúč určuje aktívnu položku. */
function adm_nav(): array {
    return [
        'Prehľad' => [
            ['admin', 'admin', 'Nástenka', 'home', 'prehľad štatistiky rýchle akcie knižnica médií nahrané súbory'],
        ],
        'Obsah webu' => [
            ['nast-uvod', 'admin-nastavenia?s=uvod', 'Úvodná stránka', 'layout', 'nadpis perex slogan texty úvod hero výzva'],
            ['admin-fotky', 'admin-fotky', 'Fotografie stránok', 'image', 'hlavná fotografia náhľad karta hero podstránky'],
            ['nast-oznamenie', 'admin-nastavenia?s=oznamenie', 'Oznámenie na webe', 'megaphone', 'pruh banner oznam správa návštevníkom'],
            ['nast-seo', 'admin-nastavenia?s=seo', 'Vyhľadávače (SEO)', 'search', 'titulok popis google meta description'],
        ],
        'Médiá' => [
            ['galeria', 'fotogaleria#sprava', 'Verejná galéria', 'grid', 'fotografie kategórie nahrať presunúť náhľad'],
            ['videa', 'fotogaleria?kategoria=videa', 'Videá z YouTube', 'video', 'youtube pridať video odkaz'],
            ['moja-galeria', 'moja-galeria', 'Moja galéria', 'lock', 'súkromné fotografie administrátor'],
        ],
        'Nastavenia' => [
            ['nast-kontakt', 'admin-nastavenia?s=kontakt', 'Kontakt', 'phone', 'telefón e-mail kontaktné údaje'],
            ['nast-farby', 'admin-nastavenia?s=farby', 'Farby webu', 'palette', 'farebná paleta písmo pozadie tlačidlá farba nadpisy'],
            ['nast-vzhlad', 'admin-nastavenia?s=vzhlad', 'Vzhľad a efekty', 'sparkle', 'dizajn tmavý režim kurzor animácie prechody'],
            ['admin-pouzivatelia', 'admin-pouzivatelia', 'Používatelia', 'users', 'účty role administrátor odstrániť'],
        ],
    ];
}

/** Aktívna položka navigácie (stránka ju môže určiť cez $GLOBALS['ADMIN_ACTIVE']). */
function adm_active(): string { return (string)($GLOBALS['ADMIN_ACTIVE'] ?? $GLOBALS['CURRENT'] ?? 'admin'); }

/** [skupina, položka] aktívnej stránky – pre drobčekovú navigáciu. */
function adm_current(): array {
    foreach (adm_nav() as $g => $items) foreach ($items as $it) if ($it[0] === adm_active()) return [$g, $it];
    return ['', null];
}

/** Hlavička obsahu stránky administrácie. $actions = HTML tlačidiel vpravo. */
function adm_head(string $title, string $lead = '', string $actions = '', ?string $icon = null): void {
    [$g, $it] = adm_current();
    $icon ??= $it[3] ?? 'sparkle';
    echo '<header class="adm-head">'
       . '<div class="adm-head__ic">' . adm_icon($icon, 22) . '</div>'
       . '<div class="adm-head__txt">' . ($g ? '<p class="adm-head__eyebrow">' . e($g) . '</p>' : '') . '<h1>' . e($title) . '</h1>'
       . ($lead !== '' ? '<p class="adm-head__lead">' . e($lead) . '</p>' : '') . '</div>'
       . ($actions !== '' ? '<div class="adm-head__actions">' . $actions . '</div>' : '')
       . '</header>';
}

/** Ľudsky čitateľný čas zmeny („pred 5 min“, „včera“, „12. 11. 2026“). */
function adm_ago(?string $iso): string {
    if (!$iso) return '';
    $t = strtotime($iso); if (!$t) return '';
    $d = time() - $t;
    if ($d < 60) return 'práve teraz';
    if ($d < 3600) return 'pred ' . floor($d / 60) . ' min';
    if ($d < 86400) return 'pred ' . floor($d / 3600) . ' h';
    if ($d < 172800) return 'včera';
    if ($d < 604800) return 'pred ' . floor($d / 86400) . ' dňami';
    return date('j. n. Y', $t);
}

function fmt_size($b) { return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' kB'; }
