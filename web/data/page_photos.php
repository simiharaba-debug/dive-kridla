<?php
// Hlavné fotografie (hero) a náhľady (karty na úvodnej stránke) jednotlivých stránok – PÔVODNÉ nastavenie.
// Administrátor ich mení na stránke „Fotky stránok“ (admin-fotky); jeho voľba sa ukladá do databázy (tabuľka page_photos)
// a má prednosť pred týmto súborom. „Obnoviť pôvodnú“ v administrácii vráti hodnotu z tohto súboru.
//
//   'hero' => ID fotografie z data/media.php (súbor assets/img/ID.webp)
//   'card' => náhľad na úvodnej stránke; null = rovnaká fotografia ako hlavná (hero)
//   'cardWhere' => kde sa náhľad zobrazuje (iba popis pre administráciu)
// Poradie = poradie v administrácii.
return [
    'index'                  => ['hero' => 'kanada-15'],
    'odchyt-holubov'         => ['hero' => 'odchyt-6',          'card' => null, 'cardWhere' => 'Úvodná stránka – hlavné služby'],
    'vypustanie-sokolov'     => ['hero' => 'pp-12',             'card' => null, 'cardWhere' => 'Úvodná stránka – hlavné služby'],
    'biologicka-ochrana'     => ['hero' => 'farma-3',           'card' => null, 'cardWhere' => 'Úvodná stránka – hlavné služby'],
    'odchov-sokolov'         => ['hero' => 'sokol-23',          'card' => null, 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],
    'vseobecne-sokoliarstvo' => ['hero' => 'kanada-13',         'card' => null, 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],
    'rehabilitacia-vtakov'   => ['hero' => 'kanada-33',         'card' => null, 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],
    'anatomia-dravcov'       => ['hero' => 'odchov-39',         'card' => null, 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],
    'zdravie-dravcov'        => ['hero' => 'kanada-22',         'card' => null, 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],
    'nieco-o-nas'            => ['hero' => 'vypustenie-sokola', 'card' => null, 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],
    'fotogaleria'            => ['card' => 'kanada-07', 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],   // stránka nemá hlavnú fotografiu
    'priame-kontaktovanie'   => ['hero' => 'pp-13',             'card' => null, 'cardWhere' => 'Úvodná stránka – „Na stránke nájdete“'],
];
