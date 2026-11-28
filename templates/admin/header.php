<?php /** @var string $title @var string $active @var array|null $user */ ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – <?= e(Settings::get('site_name', 'CMS')) ?> CMS</title>
<link rel="preload" href="<?= e(url('admin/assets/fonts/poppins-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('admin/assets/fonts/poppins-latin-500-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>?v=<?= (int)@filemtime(ROOT_PATH . '/admin/assets/admin.css') ?>">
<script src="<?= e(url('admin/assets/admin.js')) ?>?v=<?= (int)@filemtime(ROOT_PATH . '/admin/assets/admin.js') ?>" defer></script>
<?php $fav = Settings::get('favicon'); if ($fav): ?><link rel="icon" href="<?= e(upload_url($fav)) ?>"><?php endif; ?>
</head>
<body class="adm">
<?php if ($user):
    $navLink = static function (string $key, string $label, string $href, string $icon) use ($active): string {
        $on = $active === $key;
        return '<a class="adm-nav__link' . ($on ? ' is-active' : '') . '" href="' . e(url($href)) . '"' . ($on ? ' aria-current="page"' : '') . '>'
            . admin_icon($icon) . '<span>' . e($label) . '</span></a>';
    };
?>
<aside class="adm-side">
    <a class="adm-brand" href="<?= e(url('admin/')) ?>" aria-label="Content Manager home">
        <?= admin_logo(true) ?>
        <small>Content Manager</small>
    </a>
    <nav class="adm-nav" aria-label="CMS">
        <p class="adm-nav__label" id="nav-content">Website Content</p>
        <div role="group" aria-labelledby="nav-content">
        <?php /* One menu entry per page in app/schema/pages.php (Homepage, Work Page, … then Header & Footer). */
        $schemaPages = array_filter(ContentRepository::schema(), static fn($d) => ($d['nav'] ?? true) !== false);
        uksort($schemaPages, static fn($a, $b) => ($a === 'global') <=> ($b === 'global'));
        foreach ($schemaPages as $pKey => $pDef) {
            echo $navLink($pKey, $pDef['label'], $pKey === 'home' ? 'admin/' : 'admin/?page=' . rawurlencode($pKey),
                $pKey === 'home' ? 'home' : ($pKey === 'global' ? 'layout' : 'page'));
        } ?>
        </div>

        <?php /* Products module */
        $pOpen = str_starts_with((string)$active, 'p:') || $active === 'shop';
        $pLinks = ['p:all' => ['All Products', 'admin/products/'], 'p:new' => ['Add New Product', 'admin/products/edit.php']];
        foreach (Catalog::TAXONOMIES as $tax => $def) {
            $pLinks['p:' . $tax] = [$def['label'], 'admin/products/terms.php?tax=' . $tax];
        }
        $pLinks['p:families']  = ['Product Families', 'admin/products/families.php'];
        $pLinks['p:inventory'] = ['Inventory Management', 'admin/products/inventory.php'];
        $pLinks['shop']        = ['Storefront Settings', 'admin/?page=shop']; ?>
        <p class="adm-nav__label" id="nav-catalogue">Catalogue</p>
        <div role="group" aria-labelledby="nav-catalogue">
            <a class="adm-nav__link<?= $pOpen ? ' is-active' : '' ?>" href="<?= e(url('admin/products/')) ?>"<?= $pOpen ? ' aria-current="page"' : '' ?>>
                <?= admin_icon('box') ?><span>Products</span>
            </a>
        </div>

        <?php $newEnquiries = 0;
        try { $newEnquiries = (int)Database::value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"); } catch (Throwable $e) { /* table not created yet */ } ?>
        <p class="adm-nav__label" id="nav-inbox">Inbox</p>
        <div role="group" aria-labelledby="nav-inbox">
            <a class="adm-nav__link<?= $active === 'enquiries' ? ' is-active' : '' ?>" href="<?= e(url('admin/enquiries.php')) ?>"<?= $active === 'enquiries' ? ' aria-current="page"' : '' ?>>
                <?= admin_icon('mail') ?><span>Enquiries</span>
                <?php if ($newEnquiries): ?><em class="adm-nav__count" title="<?= $newEnquiries ?> new"><?= $newEnquiries ?></em><?php endif; ?>
            </a>
        </div>

        <?php $pendingAgents = 0;
        try { $pendingAgents = (int)Database::value("SELECT COUNT(*) FROM users WHERE account_type = 'agent' AND status = 'pending'"); } catch (Throwable $e) { /* table not created yet */ } ?>
        <p class="adm-nav__label" id="nav-accounts">Accounts</p>
        <div role="group" aria-labelledby="nav-accounts">
            <?= $navLink('acc:customer', 'Customers', 'admin/accounts.php?type=customer', 'users') ?>
            <a class="adm-nav__link<?= $active === 'acc:agent' ? ' is-active' : '' ?>" href="<?= e(url('admin/accounts.php?type=agent')) ?>"<?= $active === 'acc:agent' ? ' aria-current="page"' : '' ?>>
                <?= admin_icon('badge') ?><span>Agents</span>
                <?php if ($pendingAgents): ?><em class="adm-nav__count" title="<?= $pendingAgents ?> waiting for approval"><?= $pendingAgents ?></em><?php endif; ?>
            </a>
        </div>

        <p class="adm-nav__label" id="nav-system">System Management</p>
        <div role="group" aria-labelledby="nav-system">
            <?= $navLink('settings', 'Website Settings', 'admin/settings.php', 'settings') ?>
            <?= $navLink('users', 'Administrators', 'admin/users.php', 'users') ?>
        </div>
    </nav>
    <div class="adm-side__foot">
        <a class="adm-side__site" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= admin_icon('external') ?><span>View website</span></a>
        <div class="adm-user">
            <span class="adm-user__avatar" aria-hidden="true"><?= e(strtoupper(mb_substr((string)($user['display_name'] ?: $user['username']), 0, 1))) ?></span>
            <span class="adm-user__name"><?= e($user['display_name'] ?: $user['username']) ?><small>Administrator</small></span>
            <form method="post" action="<?= e(url('admin/logout.php')) ?>">
                <?= csrf_field() ?>
                <button class="adm-user__out" type="submit" title="Log out" aria-label="Log out"><?= admin_icon('logout') ?></button>
            </form>
        </div>
    </div>
</aside>
<?php endif; ?>
<main class="adm-main<?= $user ? '' : ' adm-main--full' ?>">
<?php if ($user && $pOpen):
    /* Products module: one horizontal menu across the top of every product screen.
       The managed lists (categories, colours …) sit in a "Lists" dropdown. */
    $listKeys = array_map(static fn($t) => 'p:' . $t, array_keys(Catalog::TAXONOMIES));
    $lists = array_intersect_key($pLinks, array_flip($listKeys));
    $inLists = isset($lists[$active]);
    $topLink = static function (string $k) use ($pLinks, $active): string {
        [$lbl, $href] = $pLinks[$k];
        $on = $active === $k;
        return '<a class="adm-topnav__link' . ($on ? ' is-active' : '') . ($k === 'p:new' ? ' adm-topnav__link--add' : '') . '" href="' . e(url($href)) . '"'
            . ($on ? ' aria-current="page"' : '') . '>' . ($k === 'p:new' ? '+ ' : '') . e($lbl) . '</a>';
    }; ?>
<nav class="adm-topnav" aria-label="Products menu">
    <?= $topLink('p:all') ?>
    <?= $topLink('p:new') ?>
    <details class="adm-topnav__menu" data-dropdown>
        <summary class="adm-topnav__link<?= $inLists ? ' is-active' : '' ?>">Lists<?= $inLists ? ': ' . e($lists[$active][0]) : '' ?> <?= admin_icon('chevron') ?></summary>
        <div class="adm-topnav__drop">
            <?php foreach ($lists as $k => [$lbl, $href]): ?>
            <a class="<?= $active === $k ? 'is-active' : '' ?>" href="<?= e(url($href)) ?>"<?= $active === $k ? ' aria-current="page"' : '' ?>><?= e($lbl) ?></a>
            <?php endforeach; ?>
        </div>
    </details>
    <?= $topLink('p:families') ?>
    <?= $topLink('p:inventory') ?>
    <?= $topLink('shop') ?>
</nav>
<?php endif; ?>
<?php foreach (take_flashes() as $f): ?>
    <div class="adm-flash adm-flash--<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
