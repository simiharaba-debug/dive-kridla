<?php
// Diagnostika v prehliadači – IBA na počítači, kde web beží (http://localhost). Na hostingu sa nezobrazí.
require __DIR__ . '/includes/config.php';
$local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && in_array(strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]), ['localhost', '127.0.0.1', '[::1]'], true)
    && !isset($_SERVER['HTTP_X_FORWARDED_FOR']) && cfg('DIAG_WEB', '1') === '1';
if (!$local) { http_response_code(404); include __DIR__ . '/404.php'; exit; }
require __DIR__ . '/includes/diagnostics.php';
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
$r = diagnostics_run();
$col = ['ok' => '#3e6b3a', 'warn' => '#9a6a12', 'bad' => '#a32d2d', 'info' => '#6b655a'];
$lab = ['ok' => 'OK', 'warn' => 'POZOR', 'bad' => 'CHYBA', 'info' => 'INFO'];
?><!doctype html><html lang="sk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Diagnostika</title>
<style>body{font:15px/1.55 system-ui,sans-serif;background:#f6f1e7;color:#2b2a26;margin:0;padding:1.25rem}main{max-width:52rem;margin:0 auto}h1{font-size:1.6rem}li{list-style:none;background:#fbf8f2;border:1px solid #e3d9c6;border-radius:12px;padding:.7rem 1rem;margin:.5rem 0}b{display:inline-block;min-width:4.5rem}small{display:block;color:#6b655a;word-break:break-word}ul{padding:0}.sum{font-weight:600;font-size:1.1rem}</style></head>
<body><main><h1>Diagnostika databázy a prihlasovania</h1><p class="sum"><?= htmlspecialchars(diagnostics_summary($r)) ?></p><ul>
<?php foreach ($r as [$l, $t, $d]): ?><li><b style="color:<?= $col[$l] ?>"><?= $lab[$l] ?></b> <?= htmlspecialchars($t) ?><?php if ($d !== ''): ?><small><?= htmlspecialchars($d) ?></small><?php endif; ?></li><?php endforeach; ?>
</ul><p><small>Táto stránka je dostupná iba na tomto počítači (localhost). Heslá sa nevypisujú. <a href="./">Späť na web</a></small></p></main></body></html>
