<?php
// Odhlásenie – iba metódou POST s platným CSRF tokenom (aby ho nešlo vyvolať odkazom z cudzej stránky).
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('./');
session_if_present();
if (csrf_valid($_POST['csrf'] ?? null)) {
    logout_user();
    session_open();            // nová prázdna relácia kvôli správe
    flash('success', 'Boli ste odhlásený.');
} else {
    flash('error', 'Odhlásenie sa nepodarilo (neplatný token). Skúste to znova.');
}
redirect('./');
