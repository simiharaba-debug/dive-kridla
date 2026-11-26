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
        'index' => ['label' => 'Úvod', 'short' => 'Úvod', 'title' => 'Odchyt holubov a biologická ochrana dravcami | Divé krídla', 'description' => 'Odchyt holubov – Divé krídla: odchyt mestských holubov do pascí, čistenie povál a veží, vypúšťanie sokolov, biologická ochrana dravcami. Tel. 0907 419 111.'],
        'odchyt-holubov' => ['main' => true, 'label' => 'Odchyt holubov', 'short' => 'Odchyt holubov', 'title' => 'Odchyt holubov – pasce, povaly, veže | OZ Divé krídla', 'description' => 'Odchyt mestských holubov do živolovných pascí, vyčistenie povál, podkroví a kostolných veží a uzavretie vletových otvorov. Celoročne, cena dohodou.'],
        'vypustanie-sokolov' => ['main' => true, 'label' => 'Vypúšťanie sokolov', 'short' => 'Vypúšťanie sokolov', 'title' => 'Vypúšťanie sokolov do mesta proti holubom | OZ Divé krídla', 'description' => 'Vypúšťanie sokolov sťahovavých a rárohov proti mestským holubom. Postup, metodika voľného letu, fotografie a videá. Od polovice mája, cena dohodou.'],
        'biologicka-ochrana' => ['main' => true, 'label' => 'Biologická ochrana', 'short' => 'Biologická ochrana', 'title' => 'Biologická ochrana dravcami – sady a vinice | Divé krídla', 'description' => 'Plašenie škorcov a iných vtákov v sadoch, viniciach a na poliach pomocou sokoliarsky vycvičených dravcov. Postup a výsledky, cena dohodou.'],
        'odchov-sokolov' => ['label' => 'SOKOL – edukácia odchovu dravých vtákov', 'short' => 'SOKOL – odchov', 'title' => 'Odchov sokolov a dravých vtákov – SOKOL | OZ Divé krídla', 'description' => 'Odchov dravých vtákov krok za krokom: rozmnožovanie, inkubácia, liahnutie, výchova mláďat a vypustenie do prírody vo fotografiách a videách.'],
        'vseobecne-sokoliarstvo' => ['label' => 'Všeobecné sokoliarstvo', 'short' => 'Sokoliarstvo', 'title' => 'Sokoliarstvo – sokol rároh a sokol sťahovavý | Divé krídla', 'description' => 'Všeobecné sokoliarstvo: sokol rároh a sokol sťahovavý, čo ohrozuje dravé vtáky a ktoré druhy vtákov spôsobujú škody.'],
        'rehabilitacia-vtakov' => ['label' => 'Rehabilitácia vtákov', 'short' => 'Rehabilitácia', 'title' => 'Poranený dravec – rehabilitácia vtákov | OZ Divé krídla', 'description' => 'Našli ste poraneného dravca? Ako postupovať, komu zavolať a ako prebieha starostlivosť o handicapované vtáky a ich návrat do prírody.'],
        'anatomia-dravcov' => ['label' => 'Anatómia a správanie dravcov', 'short' => 'Anatómia', 'title' => 'Anatómia dravcov – perie, zmysly, trávenie | Divé krídla', 'description' => 'Ako je dravec stavaný: perie a preperovanie, dýchanie, trávenie a vývržok, rozmnožovanie a vajce, zrak a ostatné zmysly, vtlačenie (imprinting).'],
        'zdravie-dravcov' => ['label' => 'Zdravie a choroby dravcov', 'short' => 'Zdravie dravcov', 'title' => 'Choroby dravcov – príznaky a prvá pomoc | OZ Divé krídla', 'description' => 'Príznaky chorôb dravých vtákov, stavy vyžadujúce okamžitý zákrok, prvá pomoc, sokoliarska lekárnička, prehľad chorôb a prevencia.'],
        'nieco-o-nas' => ['label' => 'Niečo o nás', 'short' => 'O nás', 'title' => 'O nás – Divé krídla, odchyt holubov a dravce', 'description' => 'Divé krídla sa venuje hlavne odchytu mestských holubov, ďalej vypúšťaniu sokolov a biologickej ochrane dravcami. Občianske združenie, Ing. Daniel Haraba.'],
        'fotogaleria' => ['nav' => true, 'label' => 'Fotogaléria', 'short' => 'Fotogaléria', 'title' => 'Fotogaléria dravcov, sokolov a odchytu | OZ Divé krídla', 'description' => 'Fotografie a videá z odchytu holubov, vypúšťania sokolov, odchovu a biologickej ochrany pomocou dravých vtákov.'],
        'priame-kontaktovanie' => ['label' => 'Priame kontaktovanie', 'short' => 'Kontakt', 'title' => 'Kontakt – odchyt holubov a plašenie vtákov | Divé krídla', 'description' => 'Kontakt: Ing. Daniel Haraba, OZ Divé krídla, tel. 0907 419 111. Odchyt holubov, vypúšťanie sokolov a plašenie vtákov – cena dohodou.'],
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
