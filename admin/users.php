<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

$me = (int)$ADMIN['id'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $do = (string)($_POST['do'] ?? '');

    if ($do === 'password') {
        $row = Database::one('SELECT password_hash FROM admin_users WHERE id = ?', [$me]);
        $new = (string)($_POST['new_password'] ?? '');
        if (!password_verify((string)($_POST['current_password'] ?? ''), (string)$row['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 10) {
            flash('error', 'New password must be at least 10 characters.');
        } elseif ($new !== (string)($_POST['new_password2'] ?? '')) {
            flash('error', 'New passwords do not match.');
        } else {
            Database::run('UPDATE admin_users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
        }
    } elseif ($do === 'add') {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.\-]{3,60}$/', $u)) {
            flash('error', 'Username must be 3–60 characters (letters, numbers, _ . -).');
        } elseif (strlen($p) < 10) {
            flash('error', 'Password must be at least 10 characters.');
        } elseif (Database::value('SELECT id FROM admin_users WHERE username = ?', [$u])) {
            flash('error', 'That username is already taken.');
        } else {
            Database::run('INSERT INTO admin_users (username, display_name, password_hash, is_active, created_at) VALUES (?, ?, ?, 1, NOW())', [$u, $u, password_hash($p, PASSWORD_DEFAULT)]);
            flash('success', 'Administrator added.');
        }
    } elseif ($do === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $me) {
            flash('error', 'You cannot delete your own account.');
        } else {
            Database::run('DELETE FROM admin_users WHERE id = ?', [$id]);
            flash('success', 'Administrator removed.');
        }
    }
    redirect('users.php');
}

$users = Database::all('SELECT id, username, last_login_at, created_at FROM admin_users ORDER BY username');
admin_header('Administrators', 'users');
?>
<header class="adm-head"><div><h1>Administrators</h1><p class="muted">People who can log in to this CMS.</p></div></header>

<section class="adm-card adm-card--flush">
    <table class="adm-table">
        <thead><tr><th>Username</th><th>Last login</th><th class="col-actions"></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td class="adm-strong"><?= e($u['username']) ?><?= (int)$u['id'] === $me ? ' <span class="muted">(you)</span>' : '' ?></td>
                <td class="muted"><?= $u['last_login_at'] ? e(date('j M Y, g:ia', strtotime($u['last_login_at']))) : 'Never' ?></td>
                <td class="col-actions">
                    <?php if ((int)$u['id'] !== $me): ?>
                    <form method="post" class="inline" data-confirm="Remove <?= e($u['username']) ?>?">
                        <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button class="adm-btn adm-btn--sm adm-btn--danger">Remove</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<div class="adm-grid-2">
    <form method="post" class="adm-card adm-form">
        <?= csrf_field() ?><input type="hidden" name="do" value="password">
        <h2>Change my password</h2>
        <div class="field"><label for="cp">Current password</label><input id="cp" name="current_password" type="password" required autocomplete="current-password"></div>
        <div class="field"><label for="np">New password</label><input id="np" name="new_password" type="password" required minlength="10" autocomplete="new-password"></div>
        <div class="field"><label for="np2">Confirm new password</label><input id="np2" name="new_password2" type="password" required minlength="10" autocomplete="new-password"></div>
        <div class="adm-actions"><button class="adm-btn" type="submit">Change password</button></div>
    </form>

    <form method="post" class="adm-card adm-form">
        <?= csrf_field() ?><input type="hidden" name="do" value="add">
        <h2>Add administrator</h2>
        <div class="field"><label for="nu">Username</label><input id="nu" name="username" required autocomplete="off"></div>
        <div class="field"><label for="npw">Password</label><input id="npw" name="password" type="password" required minlength="10" autocomplete="new-password"></div>
        <div class="adm-actions"><button class="adm-btn" type="submit">Add administrator</button></div>
    </form>
</div>
<?php admin_footer(); ?>
