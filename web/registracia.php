<?php
require __DIR__ . '/includes/functions.php';
if (current_user()) redirect('ucet');
$errors = []; $old = ['username' => '', 'email' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_open();
    $old = ['username' => trim((string)($_POST['username'] ?? '')), 'email' => trim((string)($_POST['email'] ?? ''))];
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Platnosť formulára vypršala. Skúste to prosím znova.';
    } else {
        [$errors, $uid] = register_user($old['username'], $old['email'], (string)($_POST['password'] ?? ''), (string)($_POST['password2'] ?? ''));
        if ($uid) {
            login_user($uid, false);
            flash('success', 'Účet bol vytvorený. Vitajte, ' . $old['username'] . '!');
            redirect('ucet');
        }
    }
}
page_start('registracia');
auth_shell_start('Registrácia', 'Vytvorte si účet', 'Registrácia je bezplatná. Po prihlásení uvidíte svoj účet a môžete nás kontaktovať.');
?>
<form method="post" action="registracia" class="auth-form" data-validate novalidate>
  <?= csrf_field() ?>
  <?php form_alert($errors['form'] ?? ''); ?>
  <?php form_field(['name' => 'username', 'label' => 'Používateľské meno', 'value' => $old['username'], 'autocomplete' => 'username', 'required' => true, 'error' => $errors['username'] ?? '', 'hint' => '3 – 30 znakov', 'attrs' => 'minlength="3" maxlength="30" pattern="[\p{L}\p{N}_.\- ]{3,30}"']); ?>
  <?php form_field(['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'value' => $old['email'], 'autocomplete' => 'email', 'required' => true, 'error' => $errors['email'] ?? '', 'attrs' => 'maxlength="190"']); ?>
  <?php form_field(['name' => 'password', 'label' => 'Heslo', 'type' => 'password', 'autocomplete' => 'new-password', 'required' => true, 'error' => $errors['password'] ?? '', 'hint' => 'Aspoň 10 znakov, písmeno a číslo.', 'strength' => true, 'attrs' => 'minlength="10" maxlength="200" data-policy="1"']); ?>
  <?php form_field(['name' => 'password2', 'label' => 'Potvrdenie hesla', 'type' => 'password', 'autocomplete' => 'new-password', 'required' => true, 'error' => $errors['password2'] ?? '', 'attrs' => 'data-match="f-password"']); ?>
  <button class="btn btn--primary btn--block" type="submit"><span class="btn__label">Vytvoriť účet</span></button>
</form>
<p class="auth__alt">Už máte účet? <a href="prihlasenie">Prihláste sa</a></p>
<?php auth_shell_end(); page_end();
