<?php
// Spoločné údaje webu: kontakty, zoznam stránok (menu + SEO), odkazy.
return [
    'contact' => [
        'name' => 'Ing. Daniel Haraba',
        'org' => 'OZ Divé krídla',
        // predvolený adresát správ z formulárov (kontakt-odoslat.php); v .env ho môže prepísať MAIL_TO
        'form_email' => 'daniel.haraba@gmail.com',
        'phones' => [
            ['label' => '0907 419 111', 'href' => 'tel:+421907419111'],
        ],
        'emails' => ['daniel.haraba@gmail.com', 'acipiter@pobox.sk'],
        'note' => 'V prípade akýchkoľvek otázok ma kontaktujte telefonicky alebo e-mailom, aby som Vám objasnil problematiku spojenú s odchytom premnožených mestských holubov. Môžeme sa dohodnúť aj na stretnutí, kedy by som prišiel k Vám, priamo na mieste sa oboznámil s lokalitou a navrhol konkrétne riešenie na zníženie počtu premnožených mestských holubov.',
    ],

    // slug = názov súboru bez .php; 'main' = tri hlavné témy zobrazené v menu a na úvode
    'pages' => [
        'index' => ['label' => 'Úvod', 'short' => 'Úvod', 'title' => 'Odchyt holubov, vypúšťanie sokolov, biologická ochrana | OZ Divé krídla', 'description' => 'Odchyt holubov do živolovných pascí, vypúšťanie sokolov do mesta a biologická ochrana sadov a viníc pomocou dravých vtákov. OZ Divé krídla, Ing. Daniel Haraba, tel. 0907 419 111. Cena dohodou.'],
        'odchyt-holubov' => ['main' => true, 'label' => 'Odchyt holubov', 'short' => 'Odchyt holubov', 'title' => 'Odchyt holubov do pascí a na povalách | OZ Divé krídla', 'description' => 'Odchyt mestských holubov do živolovných pascí na strechách a na povalách, v kostolných vežiach a podkroviach. Povolenia, postup a kontakt. Cena dohodou.'],
        'vypustanie-sokolov' => ['main' => true, 'label' => 'Vypúšťanie sokolov', 'short' => 'Vypúšťanie sokolov', 'title' => 'Vypúšťanie sokolov do mesta – metodika vypúšťania v mestskom prostredí | OZ Divé krídla', 'description' => 'Biologická ochrana pomocou vypustených sokolov sťahovavých a rárohov – prirodzený predátor mestských holubov. Metodika vypúšťania sokolov v mestskom prostredí metódou voľného letu: miesto, vypúšťacie zariadenie, postup krok za krokom, fotografie a videá. Cena dohodou.'],
        'biologicka-ochrana' => ['main' => true, 'label' => 'Biologická ochrana', 'short' => 'Biologická ochrana', 'title' => 'Biologická ochrana pomocou dravých vtákov – sady, vinice, polia | OZ Divé krídla', 'description' => 'Plašenie škorcov a iných vtákov v sadoch, viniciach a poliach pomocou sokoliarsky vycvičených dravcov. Postup, využitie a výsledky biologickej ochrany. Cena dohodou.'],
        'odchov-sokolov' => ['label' => 'SOKOL – edukácia odchovu dravých vtákov', 'short' => 'SOKOL – odchov', 'title' => 'SOKOL – edukácia odchovu dravých vtákov | OZ Divé krídla', 'description' => 'Projekt SOKOL – edukácia odchovu dravých vtákov: rozmnožovanie, inkubácia, liahnutie, výchova mláďat a vypustenie do prírody vo fotografiách a videách. Chovný pár sokola rároha.'],
        'vseobecne-sokoliarstvo' => ['label' => 'Všeobecné sokoliarstvo', 'short' => 'Sokoliarstvo', 'title' => 'Všeobecné sokoliarstvo – sokoly, ohrozenia dravcov | OZ Divé krídla', 'description' => 'Sokol rároh a sokol sťahovavý, ohrozenia dravcov a druhy vtákov spôsobujúce škody.'],
        'rehabilitacia-vtakov' => ['label' => 'Rehabilitácia vtákov', 'short' => 'Rehabilitácia', 'title' => 'Rehabilitácia vtákov | OZ Divé krídla', 'description' => 'Starostlivosť o handicapované živočíchy a ich príprava na návrat do prírody – ciele OZ Divé krídla. Ako postupovať pri náleze poraneného dravca.'],
        'anatomia-dravcov' => ['label' => 'Anatómia a správanie dravcov', 'short' => 'Anatómia', 'title' => 'Anatómia a správanie dravcov – perie, zmysly, trávenie, vtlačenie | OZ Divé krídla', 'description' => 'Ako je dravec stavaný: koža a perie, preperovanie, dýchanie, trávenie a vývržok, striekanec, rozmnožovanie a stavba vajca, zrak a ostatné zmysly, učenie a vtlačenie (imprinting).'],
        'zdravie-dravcov' => ['label' => 'Zdravie a choroby dravcov', 'short' => 'Zdravie dravcov', 'title' => 'Zdravie dravcov – príznaky chorôb, prvá pomoc, prevencia | OZ Divé krídla', 'description' => 'Nešpecifické príznaky, stavy vyžadujúce okamžitý zákrok, príznaky podľa sústav, sokoliarska lekárnička, prehľad chorôb dravcov a prevencia.'],
        'nieco-o-nas' => ['label' => 'Niečo o nás', 'short' => 'O nás', 'title' => 'Niečo o nás – OZ Divé krídla | občianske združenie', 'description' => 'OZ Divé krídla je občianske združenie na ochranu a podporu dravých vtákov na Slovensku. Ciele združenia a kontakt – Ing. Daniel Haraba.'],
        'fotogaleria' => ['nav' => true, 'label' => 'Fotogaléria', 'short' => 'Fotogaléria', 'title' => 'Fotogaléria – fotografie a videá | OZ Divé krídla', 'description' => 'Fotografie a videá z odchytu holubov, vypúšťania sokolov, odchovu a biologickej ochrany pomocou dravých vtákov.'],
        'priame-kontaktovanie' => ['label' => 'Priame kontaktovanie', 'short' => 'Kontakt', 'title' => 'Priame kontaktovanie | OZ Divé krídla', 'description' => 'Kontakt na Ing. Daniela Harabu, OZ Divé krídla, tel. 0907 419 111. Odchyt holubov a vypúšťanie sokolov – cena dohodou. Špecifické informácie a dokumenty na vyžiadanie.'],
        // prihlasovanie a administrácia (v pätičke a v zozname stránok sa nezobrazujú)
        'prihlasenie' => ['hidden' => true, 'noindex' => true, 'label' => 'Prihlásenie', 'short' => 'Prihlásenie', 'title' => 'Prihlásenie | OZ Divé krídla', 'description' => 'Prihlásenie do účtu.'],
        'registracia' => ['hidden' => true, 'noindex' => true, 'label' => 'Registrácia', 'short' => 'Registrácia', 'title' => 'Registrácia | OZ Divé krídla', 'description' => 'Vytvorenie používateľského účtu.'],
        'ucet' => ['hidden' => true, 'noindex' => true, 'label' => 'Môj účet', 'short' => 'Môj účet', 'title' => 'Môj účet | OZ Divé krídla', 'description' => 'Používateľský účet.'],
        'admin' => ['hidden' => true, 'noindex' => true, 'label' => 'Administrácia', 'short' => 'Administrácia', 'title' => 'Administrácia | OZ Divé krídla', 'description' => 'Správa webu a galérie.'],
        'admin-nastavenia' => ['hidden' => true, 'noindex' => true, 'label' => 'Nastavenia webu', 'short' => 'Nastavenia', 'title' => 'Nastavenia webu | OZ Divé krídla', 'description' => 'Texty, kontakt, oznámenie, SEO a vzhľad webu.'],
        'admin-pouzivatelia' => ['hidden' => true, 'noindex' => true, 'label' => 'Používatelia', 'short' => 'Používatelia', 'title' => 'Používatelia | OZ Divé krídla', 'description' => 'Správa používateľských účtov.'],
        'admin-fotky' => ['hidden' => true, 'noindex' => true, 'label' => 'Fotografie stránok', 'short' => 'Fotky stránok', 'title' => 'Fotografie stránok | OZ Divé krídla', 'description' => 'Hlavné fotografie a náhľady podstránok.'],
        // súkromná galéria administrátora – nikdy v menu, pätičke, zozname stránok ani vo vyhľadávačoch
        'moja-galeria' => ['hidden' => true, 'noindex' => true, 'label' => 'Moja administrátorská galéria', 'short' => 'Moja galéria', 'title' => 'Moja administrátorská galéria | OZ Divé krídla', 'description' => 'Súkromná galéria administrátora.'],
    ],

    'doc' => ['href' => 'assets/docs/Biologicka_ochrana_pomocou_dravych_vtakov.pdf', 'label' => 'Biologická ochrana pomocou dravých vtákov (PDF)'],
];
