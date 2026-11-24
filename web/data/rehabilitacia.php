<?php
// Obsah stránky – texty uprav priamo v tomto súbore.
return [
    'rehabilitacia' => [
        'lead' => 'Jedným z cieľov združenia je odborná a komplexná starostlivosť spojená s odchytom živočíchov a v prípade potreby aj liečenie handicapovaných živočíchov a ich príprava na návrat do prírody.',
        'goals' => [
            'Ochrana a podpora dravých vtákov a sov na území Slovenskej republiky a zvyšovanie ich počtu vo voľnej prírode.',
            'Odborná a komplexná starostlivosť spojená s odchytom živočíchov a v prípade potreby aj liečenie handicapovaných živočíchov a ich príprava na návrat do prírody.',
            'Spolupráca s podobne zameranými domácimi a zahraničnými inštitúciami, organizáciami a orgánmi štátnej správy.',
        ],
        'found' => [
            'title' => 'Našli ste poraneného dravca?',
            'text' => 'Ak ste našli poraneného dravca, nemanipulujte s ním zbytočne, zabezpečte ho pred psami a mačkami a čo najskôr nás kontaktujte. Poradíme vám, ako postupovať.',
            'link' => [
                'href' => $GLOBALS['SITE']['contact']['phones'][0]['href'] ?? 'tel:+421907419111',   // telefón mení administrátor (Kontakt)
                'label' => 'Zavolať ' . ($GLOBALS['SITE']['contact']['phones'][0]['label'] ?? '0907 419 111'),
            ],
        ],
    ],
];
