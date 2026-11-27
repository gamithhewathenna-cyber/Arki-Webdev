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
        <?php /* One menu entry per page in app/schema/pages.php (Homepage, Work Page, … then Header & Footer). */
        $schemaPages = array_filter(ContentRepository::schema(), static fn($d) => ($d['nav'] ?? true) !== false);
        uksort($schemaPages, static fn($a, $b) => ($a === 'global') <=> ($b === 'global'));
        foreach ($schemaPages as $pKey => $pDef): ?>
        <a class="<?= $active === $pKey ? 'is-active' : '' ?>" href="<?= e(url($pKey === 'home' ? 'admin/' : 'admin/?page=' . rawurlencode($pKey))) ?>"><?= e($pDef['label']) ?></a>
        <?php endforeach; ?>

        <?php /* Products module */
        $pOpen = str_starts_with((string)$active, 'p:') || $active === 'shop';
        $pLinks = ['p:all' => ['All Products', 'admin/products/'], 'p:new' => ['Add New Product', 'admin/products/edit.php']];
        foreach (Catalog::TAXONOMIES as $tax => $def) {
            $pLinks['p:' . $tax] = [$def['label'], 'admin/products/terms.php?tax=' . $tax];
        }
        $pLinks['p:families']  = ['Product Families', 'admin/products/families.php'];
        $pLinks['p:inventory'] = ['Inventory Management', 'admin/products/inventory.php'];
        $pLinks['shop']        = ['Storefront Settings', 'admin/?page=shop']; ?>
        <div class="adm-nav__group<?= $pOpen ? ' is-open' : '' ?>">
            <a class="adm-nav__parent<?= $pOpen ? ' is-active' : '' ?>" href="<?= e(url('admin/products/')) ?>">Products</a>
            <div class="adm-nav__sub">
                <?php foreach ($pLinks as $k => [$lbl, $href]): ?>
                <a class="<?= $active === $k ? 'is-current' : '' ?>" href="<?= e(url($href)) ?>"><?= e($lbl) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
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
