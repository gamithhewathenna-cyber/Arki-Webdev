<?php
declare(strict_types=1);
define('ADMIN_PUBLIC', true);
require __DIR__ . '/_init.php';

if ($ADMIN) {
    redirect(url('admin/'));
}

$error = '';
$username = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $username = trim((string)($_POST['username'] ?? ''));
    if (!throttle_allowed('admin', 5, 15)) {
        $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
    } elseif (Auth::attempt($username, (string)($_POST['password'] ?? ''))) {
        throttle_clear('admin');
        if ((string)($_POST['password'] ?? '') === 'ArkiAdmin@2026') {
            flash('error', 'You are using the default password from database.sql. Please change it now under Administrators.');
        }
        if (password_verify('arki-preview', (string)Settings::get('dev_lock_password', ''))) {
            flash('error', 'The website preview password is still the default (arki-preview). Change it under Settings.');
        }
        redirect(url('admin/'));
    } else {
        throttle_hit('admin');
        $error = 'Incorrect username or password.';
    }
}

admin_header('Log in');
?>
<div class="adm-login">
    <h1><?= e(Settings::get('site_name', 'Website')) ?></h1>
    <p class="muted">Content Manager</p>
    <?php if ($error): ?><div class="adm-flash adm-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="adm-card">
        <?= csrf_field() ?>
        <div class="field"><label for="u">Username</label><input id="u" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username"></div>
        <div class="field"><label for="p">Password</label><input id="p" name="password" type="password" required autocomplete="current-password"></div>
        <button class="adm-btn adm-btn--block" type="submit">Log in</button>
    </form>
</div>
<?php admin_footer(); ?>
