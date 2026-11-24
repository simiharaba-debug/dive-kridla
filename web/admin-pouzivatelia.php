<?php
// Administrácia – používatelia (22. 11. 2026): prehľad účtov, hľadanie, zmena roly, odhlásenie zo zariadení, odstránenie.
// Ochrany: vlastnú rolu ani vlastný účet nezmeníte, posledného administrátora nemožno odobrať ani odstrániť,
// účet s nahratými súbormi sa neodstráni (súbory by ostali bez autora – najprv ich treba zmazať v galérii).
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/admin_ui.php';
$admin = require_admin();
$pdo = db();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) throw new InvalidArgumentException('Neplatný bezpečnostný token. Obnovte stránku a skúste to znova.');
        $st = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?'); $st->execute([$id]);
        $u = $st->fetch();
        if (!$u) throw new InvalidArgumentException('Používateľ neexistuje.');
        $self = (int)$u['id'] === (int)$admin['id'];
        $admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        switch ((string)($_POST['do'] ?? '')) {
            case 'role':
                $role = (string)($_POST['role'] ?? '');
                if (!in_array($role, ['user', 'admin'], true)) throw new InvalidArgumentException('Neznáma rola.');
                if ($self) throw new InvalidArgumentException('Vlastnú rolu zmeniť nemôžete.');
                if ($u['role'] === 'admin' && $role === 'user' && $admins <= 1) throw new InvalidArgumentException('Na webe musí ostať aspoň jeden administrátor.');
                $pdo->prepare('UPDATE users SET role = ?, gallery_view = 0, updated_at = ? WHERE id = ?')->execute([$role, gmdate('c'), $id]);
                flash('success', $u['username'] . ': ' . ($role === 'admin' ? 'je teraz administrátor.' : 'je teraz bežný používateľ.'));
                break;
            case 'gallery_view':   // právo iba na prezeranie súkromnej administrátorskej galérie
                if ($u['role'] === 'admin') throw new InvalidArgumentException('Administrátor má prístup do galérie vždy.');
                $on = (string)($_POST['on'] ?? '') === '1' ? 1 : 0;
                $pdo->prepare('UPDATE users SET gallery_view = ?, updated_at = ? WHERE id = ?')->execute([$on, gmdate('c'), $id]);
                flash('success', $u['username'] . ($on ? ': môže prezerať administrátorskú galériu (bez úprav).' : ': už nemá prístup do administrátorskej galérie.'));
                break;
            case 'logout':
                $pdo->prepare('DELETE FROM sessions WHERE user_id = ?' . ($self ? ' AND id <> ?' : ''))->execute($self ? [$id, session_id()] : [$id]);
                $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$id]);
                flash('success', $u['username'] . ': odhlásený zo všetkých ' . ($self ? 'ostatných ' : '') . 'zariadení.');
                break;
            case 'delete':
                if ($self) throw new InvalidArgumentException('Vlastný účet tu odstrániť nemôžete.');
                if ($u['role'] === 'admin' && $admins <= 1) throw new InvalidArgumentException('Posledného administrátora nemožno odstrániť.');
                $files = 0;
                foreach (['media', 'private_media'] as $t) { $c = $pdo->prepare("SELECT COUNT(*) FROM $t WHERE uploaded_by = ?"); $c->execute([$id]); $files += (int)$c->fetchColumn(); }
                if ($files) throw new InvalidArgumentException($u['username'] . ' má nahraté súbory (' . $files . '). Najprv ich odstráňte v galérii.');
                $pdo->beginTransaction();
                try {
                    $pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$id]);
                    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);   // remember_tokens: ON DELETE CASCADE
                    $pdo->commit();
                } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
                flash('success', 'Účet ' . $u['username'] . ' bol odstránený.');
                break;
            default:
                throw new InvalidArgumentException('Neznáma akcia.');
        }
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (PDOException $e) {
        error_log('[pouzivatelia] ' . $e->getMessage());
        flash('error', 'Zmenu sa nepodarilo uložiť (databáza). Skúste to znova.');
    }
    redirect('admin-pouzivatelia');
}

$users = $pdo->query("SELECT u.id, u.username, u.email, u.role, u.gallery_view, u.created_at,
    (SELECT MAX(s.last_activity) FROM sessions s WHERE s.user_id = u.id) AS seen,
    (SELECT COUNT(*) FROM media m WHERE m.uploaded_by = u.id) + (SELECT COUNT(*) FROM private_media p WHERE p.uploaded_by = u.id) AS files
    FROM users u ORDER BY (u.role = 'admin') DESC, u.created_at DESC, u.id DESC LIMIT 500")->fetchAll();
$nAdmins = count(array_filter($users, fn($u) => $u['role'] === 'admin'));

$GLOBALS['ADMIN_ACTIVE'] = 'admin-pouzivatelia';
page_start('admin-pouzivatelia');
adm_head('Používatelia', 'Registrované účty webu. Administrátor vidí administráciu a môže spravovať galériu; bežný používateľ iba svoj účet. Tlačidlom „Povoliť galériu“ mu dáte právo prezerať administrátorskú galériu bez možnosti úprav.', '', 'users');
?>
<ul class="adm-stats">
  <li><div class="adm-stat"><span class="adm-stat__ic"><?= adm_icon('users', 17) ?></span><span class="adm-stat__n"><?= count($users) ?></span><span class="adm-stat__l">účtov spolu</span></div></li>
  <li><div class="adm-stat"><span class="adm-stat__ic"><?= adm_icon('shield', 17) ?></span><span class="adm-stat__n"><?= $nAdmins ?></span><span class="adm-stat__l">administrátorov</span></div></li>
  <li><div class="adm-stat"><span class="adm-stat__ic"><?= adm_icon('user', 17) ?></span><span class="adm-stat__n"><?= count($users) - $nAdmins ?></span><span class="adm-stat__l">bežných používateľov</span></div></li>
</ul>

<section class="adm-card">
  <div class="adm-card__head"><h2><?= adm_icon('users') ?>Účty</h2><span class="adm-badge" data-adm-filter-count="#adm-users"><?= count($users) ?></span></div>
  <div class="adm-tools">
    <label class="adm-input-icon"><?= adm_icon('search', 16) ?><span class="sr-only">Hľadať používateľa</span><input type="search" placeholder="Hľadať podľa mena alebo e-mailu…" data-adm-filter="#adm-users" autocomplete="off"></label>
    <div class="adm-select"><label class="sr-only" for="adm-role">Rola</label><select id="adm-role" data-adm-filter-role="#adm-users"><option value="">Všetky roly</option><option value="admin">Administrátori</option><option value="user">Používatelia</option></select></div>
  </div>
  <div class="adm-tablewrap"><table class="adm-table adm-table--cards" id="adm-users">
    <thead><tr><th>Používateľ</th><th>Rola</th><th>Registrácia</th><th>Naposledy aktívny</th><th><span class="sr-only">Akcie</span></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $self = (int)$u['id'] === (int)$admin['id']; $isA = $u['role'] === 'admin'; $last = $isA && $nAdmins <= 1; ?>
      <tr data-role="<?= e($u['role']) ?>" data-q="<?= e($u['username'] . ' ' . $u['email']) ?>">
        <td><div class="adm-user"><span class="adm-me__av" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['username'], 0, 1))) ?></span>
          <span><strong><?= e($u['username']) ?><?= $self ? ' <span class="adm-badge">vy</span>' : '' ?></strong><small><?= e($u['email']) ?></small></span></div></td>
        <td data-l="Rola"><?= $isA ? '<span class="adm-badge adm-badge--on">' . adm_icon('shield', 12) . 'Administrátor</span>' : '<span class="adm-badge">Používateľ</span>' . ((int)$u['gallery_view'] ? ' <span class="adm-badge adm-badge--on">' . adm_icon('eye', 12) . 'Prezerá galériu</span>' : '') ?></td>
        <td data-l="Registrácia"><?= e(date('j. n. Y', strtotime($u['created_at']))) ?></td>
        <td data-l="Aktívny"><?= $u['seen'] ? e(adm_ago(gmdate('c', (int)$u['seen']))) : '<span class="muted">–</span>' ?><?= (int)$u['files'] ? ' · ' . (int)$u['files'] . ' súb.' : '' ?></td>
        <td><div class="adm-table__actions">
          <?php if (!$isA): $gv = (int)$u['gallery_view'] === 1; ?>
            <form method="post" action="admin-pouzivatelia"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="do" value="gallery_view"><input type="hidden" name="on" value="<?= $gv ? 0 : 1 ?>">
              <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm" title="Právo iba prezerať administrátorskú galériu, bez úprav" data-confirm="<?= e($gv ? 'Odobrať používateľovi ' . $u['username'] . ' prístup do administrátorskej galérie?' : 'Povoliť používateľovi ' . $u['username'] . ' prezerať administrátorskú galériu? Nič v nej nebude môcť meniť.') ?>"><?= adm_icon($gv ? 'lock' : 'eye', 14) ?><?= $gv ? 'Zakázať galériu' : 'Povoliť galériu' ?></button></form>
          <?php endif; ?>
          <?php if (!$self): ?>
            <form method="post" action="admin-pouzivatelia"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="do" value="role"><input type="hidden" name="role" value="<?= $isA ? 'user' : 'admin' ?>">
              <button type="submit" class="adm-btn adm-btn--ghost adm-btn--sm"<?= $last ? ' disabled title="Posledný administrátor"' : '' ?> data-confirm="<?= e($isA ? 'Odobrať používateľovi ' . $u['username'] . ' práva administrátora?' : 'Urobiť z používateľa ' . $u['username'] . ' administrátora? Získa prístup k celej administrácii.') ?>"><?= adm_icon($isA ? 'user' : 'shield', 14) ?><?= $isA ? 'Odobrať admina' : 'Urobiť adminom' ?></button></form>
          <?php endif; ?>
          <form method="post" action="admin-pouzivatelia"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="do" value="logout">
            <button type="submit" class="adm-btn adm-btn--quiet adm-btn--sm" title="Odhlásiť zo všetkých zariadení" data-confirm="<?= e('Odhlásiť ' . ($self ? 'vás zo všetkých ostatných zariadení' : $u['username'] . ' zo všetkých zariadení') . '?') ?>"><?= adm_icon('logout', 14) ?>Odhlásiť</button></form>
          <?php if (!$self): ?>
            <form method="post" action="admin-pouzivatelia"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="do" value="delete">
              <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm"<?= $last || (int)$u['files'] ? ' disabled title="' . ($last ? 'Posledný administrátor' : 'Má nahraté súbory') . '"' : '' ?> data-confirm="<?= e('Natrvalo odstrániť účet ' . $u['username'] . '? Túto akciu nemožno vrátiť.') ?>" aria-label="Odstrániť účet <?= e($u['username']) ?>"><?= adm_icon('trash', 14) ?></button></form>
          <?php endif; ?>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php page_end();
