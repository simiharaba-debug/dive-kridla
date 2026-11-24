<?php
require __DIR__ . '/includes/functions.php';
if (current_user()) redirect('ucet');
$errors = []; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_open();
    $email = trim((string)($_POST['email'] ?? ''));
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Platnosť formulára vypršala. Skúste to prosím znova.';
    } elseif ($email === '' || ($_POST['password'] ?? '') === '') {
        $errors['form'] = 'Zadajte e-mail aj heslo.';
    } else {
        [$msg, $uid] = attempt_login($email, (string)$_POST['password']);
        if ($msg) { $errors['form'] = $msg; }
        else {
            login_user($uid, !empty($_POST['remember']));
            $u = db()->prepare('SELECT username, role FROM users WHERE id = ?'); $u->execute([$uid]); $u = $u->fetch();
            flash('success', 'Vitajte späť, ' . $u['username'] . '.');
            redirect($u['role'] === 'admin' ? 'admin' : 'ucet');
        }
    }
}
page_start('prihlasenie');
auth_shell_start('Prihlásenie', 'Prihláste sa', 'Zadajte e-mail a heslo k svojmu účtu.');
?>
<form method="post" action="prihlasenie" class="auth-form" data-validate novalidate>
  <?= csrf_field() ?>
  <?php form_alert($errors['form'] ?? ''); ?>
  <?php form_field(['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'value' => $email, 'autocomplete' => 'email', 'required' => true]); ?>
  <?php form_field(['name' => 'password', 'label' => 'Heslo', 'type' => 'password', 'autocomplete' => 'current-password', 'required' => true]); ?>
  <label class="check"><input type="checkbox" name="remember" value="1"> <span>Zapamätať si ma na tomto zariadení (30 dní)</span></label>
  <button class="btn btn--primary btn--block" type="submit"><span class="btn__label">Prihlásiť sa</span></button>
</form>
<p class="auth__alt">Ešte nemáte účet? <a href="registracia">Zaregistrujte sa</a></p>
<?php auth_shell_end(); page_end();
