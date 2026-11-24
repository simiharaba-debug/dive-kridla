<?php
// Projekt SOKOL – edukácia odchovu dravých vtákov (OZ Divé krídla).
// Zdroj textov: pôvodná stránka sokol-edukacia1.webnode.sk (prejdené 13. 11. 2026). Nič nedomýšľať.
// Videá sú na YouTube kanáli Daniela Harabu; na stránke sa prehrávač načíta až po kliknutí.
return [
    'title' => 'SOKOL – edukácia odchovu dravých vtákov',
    'perex' => [
        'Projekt SOKOL – edukácia odchovu dravých vtákov približuje deťom a verejnosti vznik života od úplného začiatku: rozmnožovanie, liahnutie, výchovu mláďat a ich vypustenie do voľnej prírody.',
        'Realizuje ho občianske združenie Divé krídla, ktoré sa venuje ochrane dravých vtákov a ich prostredia.',
    ],
    'why' => [
        'Od 18. storočia počty dravých vtákov postupne klesali. V druhej polovici 20. storočia ich však najviac zasiahlo používanie vysoko jedovatých prípravkov na ochranu rastlín, hnojív a ďalších chemikálií. Dravce stoja na konci potravového reťazca, preto ich počty klesli až na hranicu prežitia.',
        'Po zákaze niektorých látok sa stavy časti druhov zlepšili, populácie iných sú však také malé, že potrebujú pomoc aj formou umelého odchovu a následného vypustenia do voľnej prírody.',
    ],
    'services' => [
        'Informačné a odborné poradenstvo o dravých vtákoch a ich ochrane',
        'Vzdelávacie materiály a príručky pre školy a verejnosť',
        'Exkurzie a výlety do prírody za dravými vtákmi',
        'Výstavy a prezentácie',
        'Edukačné workshopy a prednášky o význame dravcov v ekosystéme',
        'Workshopy o ochrane dravých vtákov pre deti a mládež',
    ],
    'stages' => [
        [
            'title' => 'Rozmnožovanie',
            'text' => 'Odchov sa začína už pri rozmnožovaní. Jeho súčasťou je aj práca s mikroskopom.',
            'photos' => ['sokol-03', 'sokol-04', 'sokol-01', 'sokol-02'],
            'videos' => ['spermie-mikroskop'],
        ],
        [
            'title' => 'Znáška a inkubácia',
            'text' => 'Vajcia v hniezde a v inkubátore. Väčšina dravcov začne vajcia zahrievať na 36,5 – 37,5 °C až po znesení celej znášky, dovtedy ich len temperuje na 14 – 16 °C. Vývoj života vo vajci sa priebežne kontroluje digitálnym monitorom.',
            'photos' => ['sokol-05', 'sokol-07', 'odchov-15', 'sokol-06', 'odchov-16', 'sokol-08'],
            'videos' => ['monitoring-vajca'],
        ],
        [
            'title' => 'Liahnutie',
            'text' => 'Keďže sa vajcia začínajú zahrievať naraz, mláďatá sa vyliahnu v priebehu jedného dňa. Pri niektorých druhoch, napríklad orlovi skalnom či krikľavom, sedí samica na vajciach už od prvého vajca, a tak medzi mláďatami vzniká citeľný rozdiel vo veľkosti.',
            'photos' => ['odchov-17', 'sokol-09', 'odchov-19', 'odchov-18', 'odchov-20'],
            'videos' => ['liahnutie-sokola', 'mlada-v-liahni', 'odchov-naklovane-vajce'],
        ],
        [
            'title' => 'Prvé dni mláďat',
            'text' => 'Čerstvo vyliahnuté mláďatá sa vyhrievajú pod lampou alebo na vyhrievacej podložke v odchovni a dokrmujú sa pinzetou.',
            'photos' => ['odchov-01', 'sokol-10', 'sokol-12', 'odchov-21', 'odchov-23', 'sokol-13', 'sokol-11', 'odchov-22', 'sokol-14', 'odchov-24', 'sokol-15', 'odchov-03'],
            'videos' => [],
        ],
        [
            'title' => 'Výchova rodičmi',
            'text' => 'O mláďatá sa ďalej starajú rodičia – samica jastraba ich zahrieva a kŕmi kúskami mäsa.',
            'photos' => ['odchov-25', 'sokol-24', 'odchov-26', 'odchov-27', 'odchov-04'],
            'videos' => ['krmenie-rodicmi', 'jastrabica-krmi'],
        ],
        [
            'title' => 'Vypustenie do prírody',
            'text' => 'Cieľom odchovu je vypustenie dravých vtákov do voľnej prírody.',
            'photos' => ['vypustenie-sokola'],
            'videos' => ['volny-let-1', 'volny-let-5'],
        ],
    ],
    'fact' => [
        'main' => 'Sokol sťahovavý je najrýchlejší stavovec na svete pri strmhlavom lete.',
        'text' => 'Pri strmhlavom útoku na korisť dosahuje rýchlosť nad 320 km/h; najvyššia nameraná hodnota je približne 389 km/h. V bežnom vodorovnom lete je výrazne pomalší, takže titul platí práve pre strmhlavý let.',
    ],
    // Fotografie zo stránky sokol-edukacia1.webnode.sk (stiahnuté 13. 11. 2026). Na fotkách je jastrab lesný.
    // Mláďatá z odchovu – samostatná galéria pod etapami.
    'photos' => ['sokol-16', 'odchov-28', 'sokol-17', 'odchov-29', 'sokol-18', 'odchov-31', 'sokol-19', 'odchov-30', 'sokol-20', 'odchov-32', 'sokol-21', 'odchov-33', 'sokol-22', 'odchov-34'],
    // Príprava a podmienky chovu (fotografie a videá z vlastného chovu)
    'prep' => [
        'intro' => 'Úspešný odchov sa začína dávno pred znesením prvého vajca. Pri chovných dravcoch sledujeme najmä ich vek, kondíciu (lietanie) a stravu.',
        'points' => ['Vek', 'Lietanie a kondícia', 'Strava'],
        'falcons' => ['title' => 'Podmienky pre sokoly', 'text' => 'Sokoly hniezdia v priestranných voliérach s odsadávkami. Hniezdnu podložku alebo búdku majú vysypanú štrkom – sokoly si hniezdo nestavajú.', 'photos' => ['odchov-05', 'odchov-07', 'odchov-06', 'odchov-08']],
        'hawks' => ['title' => 'Podmienky pre jastraby', 'text' => 'Jastraby dostanú hniezdnu podložku alebo starú pneumatiku vystlanú čečinou zo smreka.', 'photos' => ['odchov-09', 'odchov-10', 'odchov-11', 'odchov-12', 'odchov-13', 'odchov-14']],
        'cameras' => 'Hniezda sledujeme kamerami, aby sme vtáky počas sedenia na vajciach a kŕmenia mláďat zbytočne nevyrušovali.',
    ],
    // Prirodzený a umelý odchov
    'breeding' => [
        'natural' => ['title' => 'Prirodzený odchov', 'text' => 'Pár sa spáruje sám, samica znesie vajcia, sedí na nich a obaja rodičia sa potom starajú o mláďatá.', 'videos' => ['odchov-par-sokolov', 'odchov-znaska']],
        'artificial' => ['title' => 'Umelý odchov a inseminácia', 'text' => 'Dravce odchované človekom sa naňho v prvých troch týždňoch vtlačia (imprinting) a niekedy sa nedokážu prirodzene rozmnožovať s partnerom vlastného druhu. Vtedy sa od samca odoberá semeno, kontroluje sa pod mikroskopom a samica sa umelo oplodní.', 'videos' => ['odchov-inseminacia', 'odchov-vtlacena-samica']],
    ],
    // Na čo si dať pozor (zaujímavosti z odchovu)
    'tips' => [
        ['title' => 'Kŕmenie mláďat', 'photo' => 'odchov-24', 'text' => 'Mláďatá dostávajú čerstvé, správne rozmrazené mäso v malých kúskoch. Holuby a zver pred kŕmením zmrazujeme a neskrmujeme hlavy holubov, ktoré môžu prenášať infekcie.'],
        ['title' => 'Vývržok', 'photo' => null, 'text' => 'Nestrávené zvyšky potravy dravec vyvrhne ako vývržok. Kým ho nevyvrhne, ďalšiu potravu nedostane.'],
        ['title' => 'Rachitída', 'photo' => 'odchov-32', 'text' => 'Nedostatok vitamínu D spôsobuje rachitídu mláďat. Vitamín D vzniká v tele pôsobením slnečného žiarenia, preto je pre mláďatá dôležitý pobyt na slnku.'],
        ['title' => 'Vtlačenie', 'photo' => 'odchov-22', 'text' => 'V prvých troch týždňoch si mláďa zafixuje, kto sa o neho stará. Ak ho v tom čase kŕmi človek, naviaže sa naňho a považuje ho za svoj druh.'],
        ['title' => 'Označenie krúžkami', 'photo' => 'odchov-35', 'text' => 'Mláďatá z odchovu sa označujú krúžkami, podľa ktorých sa dá každý jedinec spoľahlivo identifikovať.'],
    ],
    'thanks' => 'Projektový zámer je realizovaný s finančnou podporou Zeleného vzdelávacieho fondu 5/2023 formou dotácie z Environmentálneho fondu.',
];
