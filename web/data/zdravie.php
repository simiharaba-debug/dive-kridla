<?php
// Zdravie a veterinárna starostlivosť o dravce – texty uprav priamo v tomto súbore.
// Konkrétne liečivá a dávky zámerne neuvádzame – určuje ich vždy veterinárny lekár.
return [
    'lead' => 'Dravce, ako všetky vtáky, dlho skrývajú príznaky choroby. Úlohou chovateľa je včas spozorovať zmenu a vyhľadať odborníka, ktorý určí diagnózu a nasadí správnu liečbu.',
    'intro' => [
        'Klinický príznak je prejav, ktorý chovateľ na svojom dravcovi vidí a vie ho popísať. Rovnaké príznaky však môžu znamenať rôzne choroby, preto diagnózu a pátranie po príčine vždy prenecháme veterinárnemu lekárovi.',
        'Ak miestny veterinár nemá s dravcami skúsenosti, je nevyhnutné, aby prípad konzultoval so špecialistom na vtáky.',
    ],
    'nonspecific' => [
        'title' => 'Nešpecifické príznaky',
        'text' => 'Väčšina chorôb sa u dravcov začína prejavmi, ktoré nesmerujú ku konkrétnemu ochoreniu. Môžu nastúpiť náhle alebo postupne:',
        'list' => ['apatia', 'triaška', 'našuchorené perie', 'privreté, mandľovité oči', 'strata apetítu', 'náhle zhoršenie kondície a pokles hmotnosti napriek výdatnému kŕmeniu'],
        'diag' => 'Ak sa nepridá iný, konkrétnejší príznak, príčinu odhalí len vyšetrenie – rozbor krvi (hematológia a biochémia), cytologické a parazitologické vyšetrenie trusu, výtery z hrvoľa či zobákovej dutiny, prípadne celotelový röntgen alebo endoskopia. Zistí sa tak stav orgánov, hydratácia aj prebiehajúca infekcia.',
        'warning' => 'Liečba naslepo („skúsim toto“) môže dravcovi uškodiť. Nešpecifické príznaky sa objavujú aj pri zlyhávaní obličiek či pečene a mnohé prípravky, ktoré sa medzi sokoliarmi používajú, zaťažujú pečeň.',
    ],
    'firstAid' => [
        'Umiestnite dravca do tepla.',
        'Kŕmte ho niekoľkokrát denne po malých dávkach, aby nabral silu na ďalšiu diagnostiku.',
        'Pri zníženej kondícii pomôže glukóza podaná ústami ako rýchly zdroj energie. Hydratácia ústami sa však nevyrovná infúziám, ktoré podá veterinár.',
        'Vo veterinárnej nemocnici špecializovanej na vtáky možno dravca hospitalizovať a podávať mu roztoky do žily alebo pod kožu. Diagnostika a hospitalizácia môžu byť finančne náročné.',
    ],
    'endoscopy' => [
        'Ak sa k nešpecifickým príznakom nepridá iný, konkrétnejší príznak, príčinu odhalí len vyšetrenie. Jednou z možností je endoskopia, pri ktorej veterinár vidí stav orgánov na obrazovke.',
        'Na fotografiách je dravec počas vyšetrenia v anestézii.',
    ],
    'urgent' => [
        'popálenie na elektrickom vedení',
        'pohryznutie psom, mačkou alebo korisťou',
        'zrážka s autom alebo náraz do skla, po ktorom dravec nereaguje ako obvykle',
        'zrazenie („chytenie sa“) dvoch dravcov',
        'veľká strata krvi z akejkoľvek príčiny',
        'požitie jedu alebo niečoho, čo otravu pripomína',
        'zlomenina, náhla strata pohyblivosti končatiny alebo náhla dýchavičnosť',
        'kŕče a iné náhle nervové príznaky',
        'zadržané vajce',
        'nízka kondícia, ktorú sa nedarí do 2 dní upraviť intenzívnym kŕmením a teplom',
        'náhle problémy s okom – silný výtok alebo poranenie očnej gule',
    ],
    'systems' => [
        [
            'title' => 'Tráviaca sústava', 'photo' => 'endoskopia-3',
            'list' => ['nálepy alebo iné zmeny v zobákovej dutine', 'výtok, cudzie teleso', 'zvracanie', 'znížený apetít', 'zástava trávenia', 'zmena vzhľadu trusu'],
            'text' => [
                'Nálepy v zobáku bránia v prijímaní potravy a bývajú bolestivé – sú dôvodom na výter. Hrvoľ by mal byť vždy naplnený len toľko, aby dravec stíhal tráviť. Pri zástave trávenia v hrvoli sa rýchlo premnožia baktérie a obsah začne kvasiť až hniť. Preto dravca nekŕmime jednorazovo veľkým množstvom potravy (doplna), ani po úspešnom love.',
                'Zelený trus po hladovaní je normálny – súvisí so zvýšeným vylučovaním žlče. Vodnatý alebo inak sfarbený trus však môže signalizovať, že zlyháva pečeň alebo pankreas.',
                'Vzorku trusu na parazitologické vyšetrenie zbierame 3 dni a v rôznych denných hodinách, pretože niektoré parazity vylučujú vajíčka len v určitú dennú dobu. Zbiera sa iba hnedá časť bez moču, do vrecka alebo skúmavky, aby nevyschla – vyschnutá vzorka môže byť falošne negatívna. Odčervenie má predchádzať vyšetrenie trusu.',
            ],
        ],
        [
            'title' => 'Dýchacia sústava', 'photo' => 'rehab-03',
            'list' => ['výtok z nozdier (hlienovitý až žltozelený)', 'kýchanie', 'dýchanie s otvoreným zobákom počas celého dňa bez námahy', 'strata hlasu', 'zadýchanie sa po miernej záťaži'],
            'text' => [
                'Pri každej poruche dýchania je prvou pomocou okamžitá návšteva veterinára. Najčastejšou príčinou sú bakteriálne a plesňové infekcie, vdýchnutie cudzieho predmetu a niektoré parazity. Zadýchavanie môžu spôsobiť aj choroby srdca alebo tráviacej sústavy, ktoré utláčajú vzdušné vaky.',
                'Vtáky nemajú bránicu, a preto nevedia kašľať. Zmeny na dýchacom aparáte bývajú do istej miery trvalé aj napriek zdanlivému zlepšeniu.',
                'Rizikovou skupinou pre aspergilózu sú bez ohľadu na podmienky dravce zo severných oblastí – sokol poľovnícky, sova snežná, severný poddruh jastraba lesného – a mladé dravce v prvom roku života. Prevenciou je čisté prostredie bez vlhkosti a prachu.',
            ],
        ],
        [
            'title' => 'Koža, operenie a nohy', 'photo' => 'rehab-06',
            'list' => ['poškodené alebo zlomené perie', 'vonkajšie parazity – napríklad švoly a roztoče', 'otlaky na spodnej strane chodidiel'],
            'text' => [
                'Poruchy operenia spôsobujú vonkajšie parazity, nevhodná kŕmna dávka, mechanické opotrebovanie pier či stres v období ich rastu. Proti parazitom sa používajú výhradne prípravky určené pre vtáky – silné prípravky pre hospodárske zvieratá môžu vtákovi poškodiť perie, vyvolať nervové príznaky až úhyn.',
                'Kvalitné krmivo v čase preperovania predchádza vzniku hladových čiar, v ktorých sa perá najčastejšie lámu.',
                'Nevhodný materiál odsadávky a nadváha vedú k otlakom na chodidlách (pododermatitíde). Pri prvých náznakoch je vhodné kontaktovať veterinára.',
            ],
        ],
        [
            'title' => 'Oči', 'photo' => ['oko-1', 'oko-2'],
            'list' => ['silný výtok z oka', 'poranenie očnej gule', 'privreté, mandľovité oči'],
            'text' => [
                'Náhle problémy s okom – silný výtok alebo poranenie očnej gule – patria medzi stavy, ktoré si vyžadujú okamžitý veterinárny zákrok. Pri výtoku, ktorý trvá dlhšie ako 3 dni, je vhodná návšteva veterinára.',
            ],
        ],
    ],
    'death' => 'Vtáky sú predisponované skrývať chorobu – chorý jedinec je v prírode ľahkou korisťou. Preto sa u nich pomerne často vyskytuje náhly úhyn bez predchádzajúcich príznakov. Je jasnou indikáciou na pitvu, najmä ak chovateľ chová viac dravcov. Uhynutého vtáka je najlepšie doručiť v chlade do 2 dní aviárnemu veterinárovi alebo do Štátneho veterinárneho ústavu, ktorý vystaví protokol s popisom zmien.',
    'kit' => [
        'basic' => ['dezinfekčný prostriedok (napr. jódová tinktúra)', 'peroxid vodíka 3 % (50 ml)', '4 malé náplasti', 'malý sterilný obväz', 'malé nožnice'],
        'extra' => ['dezinfekčný zásyp', 'bórová voda na výplach oka (50 ml)', 'kvapkadlo alebo malá striekačka', 'fyziologický roztok (100 ml)', 'samolepiaci obväz', 'skúmavky na trus a podložné sklíčka', 'sterilné výterové tyčinky', 'glukóza na podanie ústami', 'vitamíny skupiny B', 'živočíšne uhlie (aspoň 5 tabliet)'],
        'notes' => [
            'Peroxid vodíka sa nesmie použiť do otvorených, silno krvácajúcich rán.',
            'Malé neinfikované rany a odreniny dezinfikujeme zriedeným jódovým roztokom.',
            'Pri výtoku z oka, ktorý trvá dlhšie ako 3 dni, je vhodná návšteva veterinára.',
            'Zásyp a peroxid poslúžia aj sokoliarovi – na rany od pazúrov dravca či od koristi.',
        ],
    ],
    'meds' => [
        'Spôsob podania aj dávku liečiva určuje veterinár. Nie je vhodné riadiť sa príbalovým letákom pre iné zvieratá alebo pre človeka – dávka pre dravce môže byť odlišná a nesprávna dávka môže byť toxická alebo neúčinná.',
        'Ak dravec prijíma potravu, liečivo sa ukryje do malého kúska mäsa, ktorý sa navlhčí vodou. Dravce majú slabý čuch, liek však dokážu odmietnuť podľa chuti. Podávanie do hrvoľa alebo sondou zvládne len skúsený človek – zasunutie tablety či sondy do priedušnice môže spôsobiť náhly úhyn.',
        'Injekcie podáva veterinár; do žily najčastejšie v inhalačnej anestézii. Aj masti a kvapky pôsobia na vtáky inak ako na cicavce – niektoré môžu oslabenému jedincovi poškodiť obličky.',
    ],
    'diseases' => [
        ['group' => 'Bakteriálne', 'items' => 'klostrídie, salmonelóza, tuberkulóza, cholera', 'note' => 'Klostrídie sa premnožia v nesprávne skladovanej či rozmrazenej potrave; potravu zmrazujeme vychladenú a vyvrhnutú, rozmrazujeme v chladničke. Salmonelózu prenášajú najmä mestské holuby, kačice a vrabce a je prenosná aj na človeka.'],
        ['group' => 'Vírusové', 'items' => 'West Nile vírus, vtáčia chrípka (influenza A), diftéria – vtáčie kiahne, ornitóza', 'note' => 'West Nile vírus prenášajú komáre od leta do prvých mrazov; najnáchylnejšie sú jastraby. Prejavuje sa nervovými príznakmi – dravec nevládze stáť na rukavici, stráca koordináciu či zrak. Pri vtáčej chrípke je prevenciou zabrániť kontaktu s voľne žijúcimi vtákmi a ich trusom. Vtáčie kiahne sa vyskytujú koncom leta a na jeseň v kožnej a slizničnej forme.'],
        ['group' => 'Plesňové', 'items' => 'aspergilóza', 'note' => 'Spóry plesní sa dostanú do dýchacích ciest vo vlhkom, tmavom a prašnom prostredí. Choroba sa v ranom štádiu ťažko rozpoznáva, preto je rozhodujúca prevencia – suché, vetrané a čisté priestory.'],
        ['group' => 'Parazitárne – vnútorné', 'items' => 'trichomonóza, škrkavky, kokcídie, syngamóza', 'note' => 'Trichomonóza sa prejavuje bielymi tvarohovitými povlakmi v zobáku a prenáša sa najmä z holubov a hrdličiek. Holuby na kŕmenie preto vypitveme, hlavu, krk a tráviaci trakt neskrmujeme a mäso premrazíme na dva týždne.'],
        ['group' => 'Parazitárne – vonkajšie', 'items' => 'klieštikovec, kliešťovec, svrab, periarky', 'note' => 'Proti vonkajším parazitom sa používajú len prípravky určené pre vtáky a pri opakovanom výskyte treba vyriešiť aj prostredie.'],
        ['group' => 'Poruchy metabolizmu', 'items' => 'avitaminózy A, B, C, D, E, K, H, nedostatok minerálnych látok, dna', 'note' => 'Nedostatok vitamínu D spôsobuje rachitídu mláďat (vitamín D vzniká v tele pôsobením slnečného žiarenia), nedostatok vitamínu H zhoršuje kvalitu peria. Pri dne sa soli kyseliny močovej ukladajú v kĺboch – vták namáhavo lieta a má opuchnuté kĺby.'],
    ],
    'prevention' => [
        'Kŕmte čerstvým, správne rozmrazeným mäsom. Holuby a zver pred kŕmením zmrazte.',
        'Neskrmujte hlavy, predovšetkým od holubov a bažantov – môžu byť zdrojom bakteriálnych, trichomonádových a parazitárnych infekcií.',
        'Pri kŕmení korisťou z lovu použite čistú svalovinu neznečistenú črevným obsahom a dávajte pozor na broky a postreky na srsti zveri.',
        'Nekŕmte krkavcovité vtáky – môžu prenášať West Nile vírus.',
        'Udržiavajte voliéry a ubikácie čisté, suché, vetrané a bez prachu.',
        'Ak chováte viac dravcov, poznajte parazity vo svojom chove a prerušujte ich vývojový cyklus aj v prostredí.',
    ],
];
