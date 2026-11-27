<?php
/** @var array|null $s header section  @var array|null $nav navigation section */
$d = $s['data'] ?? [];
$navItems = $nav['items'] ?? [];

$parseSub = static function (?string $txt): array {
    $out = [];
    foreach (preg_split('/\R/', (string)$txt) as $line) {
        if (trim($line) === '') continue;
        [$label, $href] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        $out[] = ['label' => $label, 'url' => $href];
    }
    return $out;
};
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-header__brand" href="<?= e(url()) ?>" aria-label="<?= e(($d['logo_alt'] ?? '') ?: 'Home') ?>">
            <?= picture($d['logo'] ?? '', (string)($d['logo_alt'] ?? ''), ['class' => 'site-header__logo', 'eager' => true]) ?: '<span class="site-header__wordmark">' . e(Settings::get('site_name', 'ARKI')) . '</span>' ?>
        </a>

        <div class="site-header__right">
            <div class="site-header__utils">
                <?php if (!empty($d['login_label'])): ?>
                <a class="btn-login" href="<?= e(safe_url($d['login_url'] ?? '')) ?>">
                    <span class="icon icon--xs"><?= icon('user') ?></span><?= e($d['login_label']) ?><span class="icon icon--xxs"><?= icon('chevron-down') ?></span>
                </a>
                <?php endif; ?>
                <?php if (!empty($d['search_url'])): ?>
                <a class="icon-btn" href="<?= e(safe_url($d['search_url'])) ?>" aria-label="Search"><span class="icon"><?= icon('search') ?></span></a>
                <?php endif; ?>
                <?php if (!empty($d['cart_url'])): ?>
                <a class="icon-btn" href="<?= e(safe_url($d['cart_url'])) ?>" aria-label="Cart"><span class="icon"><?= icon('cart') ?></span></a>
                <?php endif; ?>
                <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Open menu">
                    <span class="icon nav-toggle__open"><?= icon('menu') ?></span>
                    <span class="icon nav-toggle__close"><?= icon('close') ?></span>
                </button>
            </div>

            <?php if ($navItems): ?>
            <nav class="main-nav" id="main-nav" aria-label="Main">
                <ul class="main-nav__list">
                    <?php foreach ($navItems as $i => $it): $n = $it['data']; $sub = $parseSub($n['submenu'] ?? ''); ?>
                    <li class="main-nav__item<?= $sub ? ' has-sub' : '' ?>">
                        <a class="main-nav__link" href="<?= e(safe_url($n['url'] ?? '')) ?>"><?= e($n['label'] ?? '') ?></a>
                        <?php if ($sub): ?>
                        <button class="main-nav__subtoggle" type="button" aria-expanded="false" aria-controls="sub-<?= (int)$i ?>" aria-label="Show <?= e($n['label'] ?? '') ?> menu">
                            <span class="icon icon--xs"><?= icon('chevron-down') ?></span>
                        </button>
                        <ul class="main-nav__sub" id="sub-<?= (int)$i ?>">
                            <?php foreach ($sub as $sl): ?>
                            <li><a href="<?= e(safe_url($sl['url'])) ?>"><?= e($sl['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</header>
