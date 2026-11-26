<?php
// SEO – značky v <head> a štruktúrované údaje schema.org (volá header.php), zoznam adries pre sitemap.php, časté otázky.
// Adresa webu: SITE_URL v .env, inak https://divekridla.sk. Overenie Google / Bing: GOOGLE_VERIFICATION, BING_VERIFICATION v .env.
// Uvádzať iba overené fakty (žiadna adresa, hodnotenia ani ceny, ktoré nepoznáme).

function site_base(): string { return rtrim((string)cfg('SITE_URL', 'https://divekridla.sk'), '/'); }

/** Absolútna adresa: '' alebo 'index' = úvod. */
function abs_url(string $path = ''): string {
    $path = ltrim($path, './');
    return site_base() . '/' . ($path === 'index' ? '' : $path);
}

/** Kanonická adresa aktuálnej stránky (bez ?odoslane= a podobne; kategória galérie sa zachová). */
function seo_canonical(): string {
    return abs_url($GLOBALS['CURRENT'] . (isset($GLOBALS['SEO_QUERY']) ? '?' . $GLOBALS['SEO_QUERY'] : ''));
}

/** Časté otázky stránky z data/faq.php. */
function faq_items(string $slug): array {
    static $F = null;
    $F ??= require __DIR__ . '/../data/faq.php';
    return $F[$slug] ?? [];
}

function faq_block(string $slug): void {
    if (!$items = faq_items($slug)) return;
    echo '<section class="band" id="caste-otazky"><div class="container container--narrow">';
    section_head('Časté otázky', 'Na čo sa ľudia pýtajú');
    foreach ($items as $f) {
        echo '<details class="disclosure reveal"><summary>' . e($f['q']) . '</summary><p>' . e($f['a']);
        if (!empty($f['link'])) echo ' <a href="' . e($f['link'][0]) . '">' . e($f['link'][1]) . '</a>';
        echo '</p></details>';
    }
    echo '</div></section>';
}

/** Verejné adresy webu pre sitemap: [cesta => [obrázky]]. */
function seo_urls(): array {
    global $SITE;
    require_once __DIR__ . '/page_photos.php';
    require_once __DIR__ . '/gallery.php';
    $out = [];
    foreach ($SITE['pages'] as $slug => $p) {
        if (!empty($p['hidden']) || !empty($p['noindex'])) continue;
        $ph = page_photo($slug, 'hero');
        $out[$slug] = !empty($ph['full']) ? [$ph['full']] : [];
    }
    try {
        gallery_seed();
        $videos = false;
        foreach (gallery_categories('public') as $c) {
            if (!$items = gallery_items($c)) continue;
            $videos = $videos || $c['kind'] === 'video';
            $imgs = [];
            foreach ($items as $it) if (($it['type'] ?? '') !== 'video' && !empty($it['full'])) $imgs[] = $it['full'];
            $out['fotogaleria?kategoria=' . $c['slug']] = $imgs;
        }
        if ($videos) $out['fotogaleria?kategoria=videa'] = [];
    } catch (Throwable $e) { error_log('[seo] ' . $e->getMessage()); }
    return $out;
}

function seo_json(array $data): string {
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

/** Značky do <head> verejnej stránky. */
function seo_head(string $title, string $desc, bool $noindex): void {
    global $SITE, $SERVICES;
    foreach (['GOOGLE_VERIFICATION' => 'google-site-verification', 'BING_VERIFICATION' => 'msvalidate.01'] as $k => $n) {
        if ($v = cfg($k)) echo '  <meta name="' . $n . '" content="' . e($v) . '">' . "\n";
    }
    if ($noindex) return;
    $url = seo_canonical(); $home = abs_url(); $cur = $GLOBALS['CURRENT']; $c = $SITE['contact'];
    echo '  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">' . "\n";
    echo '  <link rel="canonical" href="' . e($url) . '">' . "\n";
    echo '  <meta property="og:url" content="' . e($url) . '">' . "\n";
    echo '  <meta name="twitter:card" content="summary_large_image">' . "\n";

    // Hlavná činnosť = odchyt holubov (tak ju má Google a AI odpovede predstavovať); logo PNG 512 px pre výsledky vyhľadávania
    $org = ['@type' => 'Organization', '@id' => $home . '#org', 'name' => $c['org'], 'alternateName' => ['Divé krídla', 'Divé krídla – odchyt holubov'], 'url' => $home,
        'description' => 'Divé krídla sa zaoberá predovšetkým odborným odchytom premnožených mestských holubov – odchyt do živolovných pascí, '
            . 'vyčistenie povál, podkroví a kostolných veží a uzavretie vletových otvorov. Ďalej ponúka vypúšťanie sokolov do miest proti holubom '
            . 'a biologickú ochranu sadov, viníc a polí pred škorcami pomocou sokoliarsky vycvičených dravcov. Pôsobí na Slovensku, cena dohodou.',
        'slogan' => 'Mestské holuby – Váš problém, naša starosť.',
        'logo' => ['@type' => 'ImageObject', 'url' => abs_url('assets/icon-512.png'), 'width' => 512, 'height' => 512],
        'image' => abs_url('assets/og.jpg'),
        'telephone' => str_replace('tel:', '', $c['phones'][0]['href']), 'email' => $c['emails'][0] ?? null,
        'areaServed' => ['@type' => 'Country', 'name' => 'Slovensko'],
        'contactPoint' => ['@type' => 'ContactPoint', 'name' => $c['name'], 'contactType' => 'customer service',
            'telephone' => str_replace('tel:', '', $c['phones'][0]['href']), 'availableLanguage' => 'sk'],
        'knowsAbout' => ['odchyt holubov', 'vypúšťanie sokolov', 'biologická ochrana', 'plašenie škorcov', 'sokoliarstvo', 'odchov dravých vtákov']];
    $graph = [array_filter($org),
        ['@type' => 'WebSite', '@id' => $home . '#web', 'url' => $home, 'name' => 'Divé krídla', 'alternateName' => 'Divé krídla – odchyt holubov', 'inLanguage' => 'sk', 'publisher' => ['@id' => $home . '#org']],
        ['@type' => 'WebPage', '@id' => $url . '#stranka', 'url' => $url, 'name' => $title, 'description' => $desc, 'inLanguage' => 'sk',
            'isPartOf' => ['@id' => $home . '#web'], 'about' => ['@id' => $home . '#org']]];
    if ($cur !== 'index') {
        $crumbs = [['Úvod', $home], [$SITE['pages'][$cur]['label'], abs_url($cur)]];
        if (isset($GLOBALS['SEO_QUERY'])) $crumbs[] = [$GLOBALS['SEO_LABEL'] ?? $title, $url];
        $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(
            fn($x, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $x[0], 'item' => $x[1]], $crumbs, array_keys($crumbs))];
    }
    $ids = ['index' => ['pasce', 'povaly', 'vypustanie', 'plasenie'], 'odchyt-holubov' => ['pasce', 'povaly'],
        'vypustanie-sokolov' => ['vypustanie'], 'biologicka-ochrana' => ['plasenie']][$cur] ?? [];
    $where = ['pasce' => 'odchyt-holubov', 'povaly' => 'odchyt-holubov', 'vypustanie' => 'vypustanie-sokolov', 'plasenie' => 'biologicka-ochrana'];
    foreach ($SERVICES['services'] as $s) {
        if (!in_array($s['id'], $ids, true)) continue;
        $graph[] = ['@type' => 'Service', 'name' => $s['title'], 'description' => $s['short'], 'serviceType' => $s['title'],
            'url' => abs_url($where[$s['id']]) . '#' . ($s['id'] === 'vypustanie' ? 'sluzba' : $s['id']),
            'provider' => ['@id' => $home . '#org']];
    }
    if ($faq = faq_items($cur)) {
        $graph[] = ['@type' => 'FAQPage', '@id' => $url . '#faq', 'mainEntity' => array_map(
            fn($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faq)];
    }
    echo '  ' . seo_json(['@context' => 'https://schema.org', '@graph' => $graph]) . "\n";
}
