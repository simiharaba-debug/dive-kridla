<?php
// Fotogaléria (verejná): kategórie fotografií a videí z YouTube. Administrátor tu spravuje celú galériu –
// nové kategórie, nahrávanie fotografií, pridávanie videí, náhľady, poradie, presuny a odstraňovanie.
// Logika je spoločná so súkromnou administrátorskou galériou: includes/gallery_page.php (+ includes/gallery.php).
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/gallery_page.php';
gallery_page('public');
