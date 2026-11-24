<?php
// Logo webu – pierko (vlastná kresba, 22. 11. 2026). Rovnaký tvar je aj v assets/favicon.svg.
// Farby: --hk-bg (pozadie dlaždice a rezy) a --hk-fg (pierko); predvolene dve farby webu.
function logo_svg(int $size = 38, string $class = '', bool $tile = true): string {
    return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100" aria-hidden="true" focusable="false">'
        . ($tile ? '<rect width="100" height="100" rx="24" fill="var(--hk-bg,#3b3329)"/>' : '')
        . '<g transform="rotate(38 50 50)"><path fill="var(--hk-fg,#efe7d8)" d="M50 9 C62.5 17 67 33 65 49 C64 59 60.5 68 54.5 75.5 L50 80 L45.5 75.5 C39.5 68 36 59 35 49 C33 33 37.5 17 50 9 Z"/><g fill="var(--hk-bg,#3b3329)"><path d="M66 36 L56.5 41.5 L66 39.5 Z"/><path d="M65 56 L55 60 L63.5 60.5 Z"/><path d="M34 28 L44 34.5 L34.6 32 Z"/><path d="M35 50 L44.5 55.5 L35.8 54 Z"/></g><path fill="none" stroke="var(--hk-bg,#3b3329)" stroke-width="1.9" stroke-linecap="round" d="M50 15 L50 80"/><path fill="none" stroke="var(--hk-fg,#efe7d8)" stroke-width="3.2" stroke-linecap="round" d="M50 79 L50 93"/></g>'
        . '</svg>';
}
/** Starší názov (logo bolo krátko hlava jastraba). */
function hawk_svg(int $size = 38, string $class = '', bool $tile = true): string { return logo_svg($size, $class, $tile); }
