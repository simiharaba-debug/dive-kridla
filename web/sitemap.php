<?php
// Mapa stránok pre vyhľadávače, adresa /sitemap.xml (.htaccess, router.php). Tvorí sa sama z data/site.php a verejnej galérie.
require __DIR__ . '/includes/functions.php';
$x = fn($s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
   . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach (seo_urls() as $path => $imgs) {
    echo '<url><loc>' . $x(abs_url($path)) . '</loc>';
    foreach ($imgs as $img) echo '<image:image><image:loc>' . $x(abs_url($img)) . '</image:loc></image:image>';
    echo "</url>\n";
}
echo "</urlset>\n";
