<?php
require __DIR__ . '/includes/functions.php';
http_response_code(404);
$GLOBALS["SITE"]["pages"]["404"] = ['label' => '404', 'short' => '404', 'title' => 'Stránka sa nenašla | OZ Divé krídla', 'description' => 'Stránka sa nenašla.'];
page_start('404');
?>
<section class="page-hero"><div class="container"><p class="eyebrow eyebrow--sand">Chyba 404</p><h1>Táto stránka neexistuje</h1><p><a class="btn btn--primary" href="./">Späť na úvod</a></p></div></section>
<?php page_end();
