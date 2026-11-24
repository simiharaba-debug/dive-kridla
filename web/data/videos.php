<?php
// Videá webu – VŠETKY sú na YouTube (na webe nie sú žiadne vlastné MP4 súbory).
// Prehrávajú sa až po kliknutí v modálnom okne (youtube-nocookie.com), náhľad je z i.ytimg.com.
// Ďalšie videá a kategórie pridáva administrátor cez web vo Fotogalérii (ukladajú sa do databázy, tabuľky videos a gallery_categories).
//
// kľúč => [
//   'yt'        => ID videa na YouTube,
//   'title'     => názov na webe,  'desc' => krátky popis (metadáta pre vyhľadávače a čítačky),
//   'w', 'h'    => rozmery videa (určujú, či je na výšku alebo na šírku),
//   'duration'  => dĺžka v sekundách,  'date' => dátum zverejnenia na YouTube,
//   'topic'     => téma (poradie v galérii),
// ]
return [
    'videos' => [
        // --- Metóda voľného letu (vypúšťanie sokolov) ---
        'volny-let-1' => ['yt' => 'W6eX6ASuujM', 'title' => 'Mladý sokol vo vypúšťacom zariadení',
            'desc' => 'Pohľad do vypúšťacieho zariadenia, v ktorom mladé sokoly pred prvým letom privykajú na okolie. Metóda voľného letu.',
            'w' => 576, 'h' => 1024, 'duration' => 81, 'date' => '2026-11-17', 'topic' => 'vypustanie'],
        'volny-let-2' => ['yt' => 'i8Ys_Tj_18U', 'title' => 'Vypúšťacie zariadenie na streche budovy',
            'desc' => 'Príprava vypúšťacieho zariadenia na streche – miesta, odkiaľ mladé sokoly vzlietnu do voľnej prírody.',
            'w' => 1280, 'h' => 720, 'duration' => 30, 'date' => '2026-11-17', 'topic' => 'vypustanie'],
        'volny-let-4' => ['yt' => 'SMH8NrJ-jhE', 'title' => 'Okolie vypúšťacieho zariadenia – záber z fotopasce',
            'desc' => 'Fotopasca na streche sleduje okolie vypúšťacieho zariadenia a zaznamenáva pohyb mladých sokolov po jeho otvorení.',
            'w' => 1280, 'h' => 720, 'duration' => 10, 'date' => '2026-11-17', 'topic' => 'vypustanie'],
        'volny-let-5' => ['yt' => 'cBU4MpqhO9s', 'title' => 'Mladý sokol na streche – záber z fotopasce',
            'desc' => 'Mladý sokol na streche pri vypúšťacom zariadení počas voľného letu, zaznamenaný fotopascou.',
            'w' => 1280, 'h' => 720, 'duration' => 10, 'date' => '2026-11-17', 'topic' => 'vypustanie'],

        // --- Rozmnožovanie a odchov ---
        'odchov-par-sokolov' => ['yt' => 'ky5AweMWgXg', 'title' => 'Pár sokolov na hniezdnej podložke – záber z kamery',
            'desc' => 'Chovný pár sokolov na hniezdnej podložke, ktorý sa spároval sám – prirodzený odchov.',
            'w' => 1280, 'h' => 720, 'duration' => 23, 'date' => '2026-11-17', 'topic' => 'odchov'],
        'odchov-znaska' => ['yt' => 'XF_BZfITRQM', 'title' => 'Samica jastraba pri znáške – záber z kamery',
            'desc' => 'Samica jastraba lesného na hniezde s vajcami, záber z kamery v odchovnom zariadení.',
            'w' => 640, 'h' => 360, 'duration' => 6, 'date' => '2026-11-17', 'topic' => 'odchov'],
        'odchov-inseminacia' => ['yt' => 'I_Z6SjC4UtY', 'title' => 'Vtlačený samec jastraba na kopulačnom klobúku – odber semena',
            'desc' => 'Pri vtlačených (imprintovaných) dravcoch sa od samca odoberá semeno pomocou kopulačného klobúka – umelý odchov.',
            'w' => 352, 'h' => 640, 'duration' => 15, 'date' => '2026-11-17', 'topic' => 'odchov'],
        'odchov-vtlacena-samica' => ['yt' => 'InR4r_idoLw', 'title' => 'Vtlačená samica jastraba na hniezde s vajcami',
            'desc' => 'Samica jastraba vtlačená na človeka sedí na vajciach a nechá sa pohladiť chovateľom.',
            'w' => 854, 'h' => 480, 'duration' => 56, 'date' => '2026-11-17', 'topic' => 'odchov'],
        'spermie-mikroskop' => ['yt' => 'TNCoq5Ty0bk', 'title' => 'Jastrabie spermie pod mikroskopom (zväčšenie 400×)',
            'desc' => 'Kontrola odobratého semena jastraba pod mikroskopom pri 400-násobnom zväčšení.',
            'w' => 1920, 'h' => 1080, 'duration' => 7, 'date' => '2023-10-24', 'topic' => 'odchov'],
        'monitoring-vajca' => ['yt' => 'Ig3TxHy4cDo', 'title' => 'Monitoring života vo vajci',
            'desc' => 'Vývoj zárodku vo vajci sa počas inkubácie kontroluje digitálnym monitorom.',
            'w' => 360, 'h' => 640, 'duration' => 25, 'date' => '2023-10-24', 'topic' => 'odchov'],

        // --- Liahnutie a mláďatá ---
        'odchov-naklovane-vajce' => ['yt' => 'V8ZwIruMICE', 'title' => 'Nakľuvnuté vajce krátko pred liahnutím',
            'desc' => 'Mláďa dravca nakľuvlo škrupinu – liahnutie sa začína.',
            'w' => 368, 'h' => 656, 'duration' => 20, 'date' => '2026-11-17', 'topic' => 'liahnutie'],
        'liahnutie-sokola' => ['yt' => 'YRY4FU_DHNA', 'title' => 'Liahnutie sokola sťahovavého',
            'desc' => 'Mláďa sokola sťahovavého sa vyslobodzuje z vajca.',
            'w' => 626, 'h' => 360, 'duration' => 14, 'date' => '2023-10-24', 'topic' => 'liahnutie'],
        'mlada-v-liahni' => ['yt' => '21skAUGT90g', 'title' => 'Mláďa a vajcia v liahni',
            'desc' => 'Čerstvo vyliahnuté mláďa a ďalšie vajcia v liahni.',
            'w' => 360, 'h' => 640, 'duration' => 16, 'date' => '2023-10-24', 'topic' => 'liahnutie'],
        'krmenie-rodicmi' => ['yt' => 'PkKg_nvnZec', 'title' => 'Kŕmenie mláďat rodičmi',
            'desc' => 'Rodičia kŕmia mláďatá v hniezde kúskami mäsa.',
            'w' => 626, 'h' => 360, 'duration' => 18, 'date' => '2023-10-24', 'topic' => 'liahnutie'],
        'jastrabica-krmi' => ['yt' => 'TWzW27N_Hkk', 'title' => 'Jastrabica kŕmi mláďatá',
            'desc' => 'Samica jastraba lesného kŕmi mláďatá v hniezde.',
            'w' => 1280, 'h' => 720, 'duration' => 11, 'date' => '2023-10-30', 'topic' => 'liahnutie'],
    ],
    // Pôvodné kategórie videí (téma => názov). Pri prvom spustení sa z nich vytvoria kategórie galérie (tabuľka
    // gallery_categories); potom ich admin premenúva, triedi a maže priamo v galérii – zmena tu sa už neprejaví.
    'topics' => [
        'vypustanie' => 'Vypúšťanie sokolov a voľný let',
        'odchov'     => 'Rozmnožovanie a odchov',
        'liahnutie'  => 'Liahnutie a mláďatá',
    ],
    // Videá na stránke Edukácia (metóda voľného letu)
    'edukacia' => ['volny-let-1', 'volny-let-2', 'volny-let-4', 'volny-let-5'],
];
