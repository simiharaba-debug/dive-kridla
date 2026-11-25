<?php
// Časté otázky podstránok (zobrazí faq_block() z includes/seo.php + schema.org FAQPage).
// Iba fakty, ktoré už na webe sú (data/services.php, data/edukacia.php, data/rehabilitacia.php). 'link' => [adresa, text] je voliteľné.
return [
    'odchyt-holubov' => [
        ['q' => 'Koľko stojí odchyt holubov?', 'a' => 'Cena je dohodou. Rozsah a cenu odchytu určujeme individuálne podľa lokality, dĺžky odchytu a počtu odchytených holubov.', 'link' => ['priame-kontaktovanie', 'Opísať problém']],
        ['q' => 'Ako dlho trvá odchyt holubov do pascí?', 'a' => 'Samotný odchyt trvá jeden mesiac, podľa potreby aj niekoľko mesiacov. Populáciu mestských holubov tak môžeme znížiť až o niekoľko stoviek kusov.'],
        ['q' => 'Kedy sa dá odchyt holubov robiť?', 'a' => 'Odchyt do živolovných pascí aj odchyt na povalách realizujeme celoročne.'],
        ['q' => 'Ako sa zbaviť holubov na povale, v podkroví alebo v kostolnej veži?', 'a' => 'Zmapujeme strechu a vletové otvory, holuby odchytíme, odstránime hniezda a otvory uzavrieme, aby sa holuby nevrátili. Do jedného týždňa strechu a zabezpečenie skontrolujeme.'],
        ['q' => 'Prečo je holubí trus na povale problém?', 'a' => 'Jeden holub vyprodukuje priemerne 2,5 kg trusu za rok. Z vlhkých vrstiev trusu sa uvoľňujú kyseliny, ktoré môžu po rokoch narušiť drevené trámy, a zamorené priestory sú liahňou roztočov a parazitov.'],
        ['q' => 'Aké povolenia sú na odchyt mestských holubov potrebné?', 'a' => 'Potrebné sú osvedčenia o odbornej spôsobilosti na odchyt túlavých zvierat, osvedčenia na prepravu zvierat a povolenia veterinárnej správy. OZ Divé krídla ich má a na vyžiadanie ich predložíme.'],
    ],
    'vypustanie-sokolov' => [
        ['q' => 'Prečo vypúšťať sokoly proti holubom v meste?', 'a' => 'Veľké druhy sokolov sú vrcholoví predátori holubov. V lokalite, kde sokoly lovia, sa holuby nezdržiavajú alebo sa jej snažia vyhýbať.'],
        ['q' => 'Ktoré sokoly sa do mesta vypúšťajú?', 'a' => 'Sokol sťahovavý a sokol rároh – naše pôvodné druhy, z umelého odchovu s platným potvrdením o pôvode.', 'link' => ['odchov-sokolov', 'Ako prebieha odchov']],
        ['q' => 'V ktorom období sa sokoly vypúšťajú?', 'a' => 'Vypúšťanie sa dá realizovať od polovice mája do začiatku augusta.'],
        ['q' => 'Ako dlho trvá, kým sa sokoly osamostatnia?', 'a' => 'Zariadenie sa otvorí po desiatich až štrnástich dňoch. Väčšina sokolov sa osamostatní v priebehu štvrtého až šiesteho týždňa a počas celej doby sú kŕmené raz až dvakrát denne.'],
        ['q' => 'Stačí sokoly vypustiť raz?', 'a' => 'Vhodné je vypúšťať ich viac rokov po sebe. Po dosiahnutí pohlavnej zrelosti je pravdepodobné, že sa sokoly vrátia a založia si v lokalite hniezdo.'],
    ],
    'biologicka-ochrana' => [
        ['q' => 'Čo je biologická ochrana pomocou dravých vtákov?', 'a' => 'Plašenie škorcov a iných vtákov pomocou sokoliarsky vycvičených dravcov. Vtáky získajú dojem, že miesto je loviskom dravcov, a preto sa mu vyhýbajú.'],
        ['q' => 'Ako ochrániť vinicu alebo čerešňový sad pred škorcami?', 'a' => 'S dravcami prelietavame nad plochou ešte pred dozrievaním a počas celého dozrievania, od východu do západu slnka a v nepravidelných intervaloch, aby si na ne vtáky nezvykli.'],
        ['q' => 'O koľko sa dajú znížiť škody od škorcov?', 'a' => 'Straty na kôstkovinách alebo viniči môžu dosiahnuť až 70 %, pri veľkých kŕdľoch škorcov až 90 %. Pomocou dravých vtákov sa darí škody znížiť do únosných 15 %.'],
        ['q' => 'Akú veľkú plochu dravce ochránia?', 'a' => 'Jeden človek s 2 – 3 dravcami pri 12 – 14 hodinách denne ochráni približne 20 ha. Chrániť sa dajú aj plochy niekoľkých desiatok hektárov.'],
        ['q' => 'Kde všade sa dá plašenie vtákov dravcami použiť?', 'a' => 'V sadoch, viniciach a na poliach, na letiskách, golfových ihriskách, v lesných škôlkach aj v mestách proti holubím kŕdľom.', 'link' => ['odchyt-holubov', 'Odchyt holubov v meste']],
        ['q' => 'Je plašenie vtákov dravcami v súlade so zákonom?', 'a' => 'Naše riešenia sú v súlade s legislatívou Slovenskej republiky. Väčšina voľne žijúcich vtákov je celoročne chránená podľa zákona č. 543/2002 Z. z. o ochrane prírody a krajiny.'],
    ],
    'rehabilitacia-vtakov' => [
        ['q' => 'Našiel som poraneného dravca, čo mám robiť?', 'a' => 'Zbytočne s ním nemanipulujte, zabezpečte ho pred psami a mačkami a čo najskôr nás kontaktujte. Poradíme vám, ako postupovať.', 'link' => ['priame-kontaktovanie', 'Kontakt']],
        ['q' => 'Ako spoznám chorého alebo poraneného dravca?', 'a' => 'Príznaky, stavy vyžadujúce okamžitý zákrok a prvú pomoc sme spísali na samostatnej stránke.', 'link' => ['zdravie-dravcov', 'Zdravie a choroby dravcov']],
    ],
];
