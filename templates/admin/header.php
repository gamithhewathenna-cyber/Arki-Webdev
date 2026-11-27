<?php /** @var string $title @var string $active @var array|null $user */ ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – <?= e(Settings::get('site_name', 'CMS')) ?> CMS</title>
<link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>?v=<?= (int)@filemtime(ROOT_PATH . '/admin/assets/admin.css') ?>">
<script src="<?= e(url('admin/assets/admin.js')) ?>?v=<?= (int)@filemtime(ROOT_PATH . '/admin/assets/admin.js') ?>" defer></script>
</head>
<body class="adm">
<?php if ($user): ?>
<aside class="adm-side">
    <a class="adm-brand" href="<?= e(url('admin/')) ?>"><?= e(Settings::get('site_name', 'Website')) ?><small>Content Manager</small></a>
    <nav class="adm-nav">
        <a class="<?= $active === 'home' ? 'is-active' : '' ?>" href="<?= e(url('admin/')) ?>">Homepage</a>
        <a class="<?= $active === 'global' ? 'is-active' : '' ?>" href="<?= e(url('admin/?page=global')) ?>">Header &amp; Footer</a>
        <a class="<?= $active === 'settings' ? 'is-active' : '' ?>" href="<?= e(url('admin/settings.php')) ?>">Settings</a>
        <a class="<?= $active === 'users' ? 'is-active' : '' ?>" href="<?= e(url('admin/users.php')) ?>">Administrators</a>
    </nav>
    <div class="adm-side__foot">
        <a class="adm-btn adm-btn--ghost adm-btn--block" href="<?= e(url()) ?>" target="_blank" rel="noopener">View website ↗</a>
        <form method="post" action="<?= e(url('admin/logout.php')) ?>">
            <?= csrf_field() ?>
            <button class="adm-link" type="submit">Log out (<?= e($user['username']) ?>)</button>
        </form>
    </div>
</aside>
<?php endif; ?>
<main class="adm-main<?= $user ? '' : ' adm-main--full' ?>">
<?php foreach (take_flashes() as $f): ?>
    <div class="adm-flash adm-flash--<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
