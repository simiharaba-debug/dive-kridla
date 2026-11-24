<?php
// Moja administrátorská galéria – SÚKROMNÁ, samostatná od verejnej fotogalérie, funguje na rovnakom princípe
// (kategórie fotografií a videí z YouTube). Prístup: administrátor (správa) a používateľ s právom „prezerať“ (iba pozeranie) – overuje sa z databázy pri každej požiadavke.
// Súbory fotografií ležia mimo webu (storage/private-media) a čítajú sa iba cez private-media.php.
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/gallery_page.php';
require_gallery_viewer();          // admin alebo používateľ s právom iba na prezeranie; ostatní → prihlásenie / 403
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: private, no-store');
gallery_page('private');
