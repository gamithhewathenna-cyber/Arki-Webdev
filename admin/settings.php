<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $name  = trim((string)($_POST['site_name'] ?? ''));
    $title = trim((string)($_POST['home_title'] ?? ''));
    Settings::set('site_name', mb_substr($name !== '' ? $name : 'Website', 0, 120));
    Settings::set('home_title', mb_substr($title, 0, 200));

    // Favicon
    $fav = $_FILES['favicon'] ?? null;
    if ($fav && ($fav['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $old = (string)Settings::get('favicon', '');
            Settings::set('favicon', Uploader::image($fav));
            if ($old !== '') {
                Uploader::deleteIfUnused($old);
            }
        } catch (Throwable $ex) {
            flash('error', 'Favicon: ' . $ex->getMessage());
        }
    } elseif (!empty($_POST['remove_favicon'])) {
        $old = (string)Settings::get('favicon', '');
        Settings::set('favicon', '');
        Uploader::deleteIfUnused($old);
    }

    // Development lock
    $newPass = (string)($_POST['dev_password'] ?? '');
    $enable  = !empty($_POST['dev_lock_enabled']);
    if ($newPass !== '') {
        if (strlen($newPass) < 6) {
            flash('error', 'Preview password must be at least 6 characters – it was not changed.');
        } else {
            Settings::set('dev_lock_password', password_hash($newPass, PASSWORD_DEFAULT));
        }
    }
    if ($enable && (string)Settings::get('dev_lock_password', '') === '') {
        flash('error', 'Set a preview password before enabling password protection.');
        $enable = false;
    }
    Settings::set('dev_lock_enabled', $enable ? '1' : '0');

    flash('success', 'Settings saved.');
    redirect('settings.php');
}

$fav = (string)Settings::get('favicon', '');
$hasPass = (string)Settings::get('dev_lock_password', '') !== '';
admin_header('Settings', 'settings');
?>
<header class="adm-head"><div><h1>Settings</h1><p class="muted">General website settings.</p></div></header>

<form method="post" enctype="multipart/form-data" class="adm-form">
    <?= csrf_field() ?>
    <section class="adm-card">
        <h2>General</h2>
        <div class="field"><label for="site_name">Site name</label><input id="site_name" name="site_name" value="<?= e(Settings::get('site_name', '')) ?>" required></div>
        <div class="field"><label for="home_title">Homepage browser title</label><input id="home_title" name="home_title" value="<?= e(Settings::get('home_title', '')) ?>"><p class="help">Shown in the browser tab.</p></div>
        <div class="field field--image">
            <label for="favicon">Favicon</label>
            <?php if ($fav !== ''): ?><div class="img-current img-current--sm"><img src="<?= e(upload_url($fav)) ?>" alt=""><label class="check"><input type="checkbox" name="remove_favicon" value="1"> Remove</label></div><?php endif; ?>
            <input id="favicon" name="favicon" type="file" accept="image/png,image/gif,image/webp,image/jpeg">
            <p class="help">Square PNG, 512 × 512 px recommended.</p>
        </div>
    </section>

    <section class="adm-card">
        <h2>Development stage</h2>
        <div class="field">
            <label class="check"><input type="checkbox" name="dev_lock_enabled" value="1" <?= Settings::get('dev_lock_enabled') === '1' ? 'checked' : '' ?>> Password-protect the public website</label>
            <p class="help">Visitors must enter the preview password to see the site. Logged-in administrators are let through automatically.</p>
        </div>
        <div class="field">
            <label for="dev_password"><?= $hasPass ? 'Change preview password' : 'Preview password' ?></label>
            <input id="dev_password" name="dev_password" type="password" minlength="6" autocomplete="new-password" placeholder="<?= $hasPass ? 'Leave blank to keep the current password' : '' ?>">
        </div>
        <div class="adm-note">
            <strong>Search engines:</strong> indexing is disabled. Every page sends <code>noindex, nofollow</code> as a meta tag and an <code>X-Robots-Tag</code> header.
            Remove these at launch (see README → “Going live”).
        </div>
    </section>

    <div class="adm-actions"><button class="adm-btn" type="submit">Save settings</button></div>
</form>
<?php admin_footer(); ?>
