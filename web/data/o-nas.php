<?php
// Obsah stránky – texty uprav priamo v tomto súbore.
return [
    'about' => [
        'title' => 'OZ Divé krídla',
        'intro' => 'Sme občianske združenie, ktorého cieľom je podporovať a rozvíjať aktivity smerujúce k pozitívnemu prístupu obyvateľstva k ochrane živočíchov a životného prostredia so zvýšeným zameraním na dravé vtáky.',
        'goals' => [
            'Ochrana a podpora dravých vtákov a sov na území Slovenskej republiky a zvyšovanie ich počtu vo voľnej prírode.',
            'Odborná a komplexná starostlivosť spojená s odchytom živočíchov a v prípade potreby aj liečenie handicapovaných živočíchov a ich príprava na návrat do prírody.',
            'Spolupráca s podobne zameranými domácimi a zahraničnými inštitúciami, organizáciami a orgánmi štátnej správy.',
            'Poradenstvo, vzdelávanie a osvetová činnosť, vrátane vzdelávacích prezentácií pre verejnosť na internete.',
            'Organizovanie kultúrnych, spoločenských a voľnočasových podujatí pre členov a priateľov.',
            'Hospodárska činnosť v súlade so všeobecne záväznými právnymi predpismi a poslaním združenia.',
        ],
    ],
    'team' => [
        [
            'name' => 'Ing. Daniel Haraba',
            'role' => 'Vedúci projektu, kontaktná osoba',
            'photo' => 'daniel-2024',
            'phone' => [
                'label' => $GLOBALS['SITE']['contact']['phones'][0]['label'] ?? '0907 419 111',   // telefón mení administrátor (Kontakt)
                'href' => $GLOBALS['SITE']['contact']['phones'][0]['href'] ?? 'tel:+421907419111',
            ],
            'email' => 'acipiter@pobox.sk',
            'facts' => [
                'Člen SKS od roku 1998',
                'Stredná priemyselná škola banícka v Banskej Štiavnici; Stavebná fakulta Bratislava – vodné hospodárstvo krajiny',
                'Sokoliarske vystúpenia v rámci Slovenska (1996 – 1999)',
                'Kanada – biologická ochrana sadov (2006)',
            ],
        ],
    ],
];
