<?php
/** @var array|null $s header section  @var array|null $nav navigation section  @var bool $overlay header over a banner image */
$d = $s['data'] ?? [];
$navItems = $nav['items'] ?? [];
$overlay = !empty($overlay);
// Over a dark banner: use the white logo if uploaded, otherwise the normal logo turned white by CSS.
$lightLogo = $overlay && !empty($d['logo_light']) && is_file(ROOT_PATH . '/' . ltrim((string)$d['logo_light'], '/'));
$logoPath  = $lightLogo ? $d['logo_light'] : ($d['logo'] ?? '');
$logoClass = 'site-header__logo' . ($overlay && !$lightLogo ? ' site-header__logo--invert' : '');

$parseSub = static function (?string $txt): array {
    $out = [];
    foreach (preg_split('/\R/', (string)$txt) as $line) {
        if (trim($line) === '') continue;
        [$label, $href] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        $out[] = ['label' => $label, 'url' => $href];
    }
    return $out;
};

/** Work / Learn / Store category trees for the "Product categories" mega menu (active categories only). */
$megaTrees = static function (): array {
    static $trees = null;
    if ($trees === null) {
        $trees = [];
        try {
            foreach (['work', 'learn', 'store'] as $store) {
                $tree = [];
                foreach (Catalog::terms(Catalog::categoryTax($store), true) as $t) {
                    if ($t['parent_id'] === null) {
                        $tree[(int)$t['id']] = $t + ['children' => []];
                    } elseif (isset($tree[(int)$t['parent_id']])) {
                        $tree[(int)$t['parent_id']]['children'][] = $t;
                    }
                }
                if ($tree) $trees[$store] = array_values($tree);
            }
        } catch (Throwable $e) {
            $trees = []; // product tables not installed yet
        }
    }
    return $trees;
};
?>
<header class="site-header<?= $overlay ? ' site-header--overlay' : '' ?>">
    <div class="container site-header__inner">
        <a class="site-header__brand" href="<?= e(url()) ?>" aria-label="<?= e(($d['logo_alt'] ?? '') ?: 'Home') ?>">
            <?= picture($logoPath, (string)($d['logo_alt'] ?? ''), ['class' => $logoClass, 'eager' => true]) ?: '<span class="site-header__wordmark">' . e(Settings::get('site_name', 'ARKI')) . '</span>' ?>
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
                    <?php foreach ($navItems as $i => $it): $n = $it['data'];
                        $mega = ($n['mega'] ?? '') === 'products' ? $megaTrees() : [];
                        $sub = $mega ? [] : $parseSub($n['submenu'] ?? ''); ?>
                    <li class="main-nav__item<?= ($sub || $mega) ? ' has-sub' : '' ?><?= $mega ? ' has-mega' : '' ?>">
                        <a class="main-nav__link" href="<?= e(safe_url($n['url'] ?? '')) ?>"><?= e($n['label'] ?? '') ?></a>
                        <?php if ($mega): ?>
                        <button class="main-nav__subtoggle" type="button" aria-expanded="false" aria-controls="sub-<?= (int)$i ?>" aria-label="Show <?= e($n['label'] ?? '') ?> categories">
                            <span class="icon icon--xs"><?= icon('chevron-down') ?></span>
                        </button>
                        <div class="main-nav__sub mega" id="sub-<?= (int)$i ?>">
                            <div class="container mega__inner">
                                <?php foreach ($mega as $store => $tree): ?>
                                <div class="mega__store">
                                    <a class="mega__title" href="<?= e(listing_url($store)) ?>"><?= e(ProductRepository::STOREFRONTS[$store]) ?></a>
                                    <div class="mega__cols">
                                        <?php foreach ($tree as $cat): ?>
                                        <div class="mega__group">
                                            <a class="mega__cat" href="<?= e(listing_url($store, ['cat' => [$cat['slug']]])) ?>"><?= e($cat['name']) ?></a>
                                            <?php if ($cat['children']): ?>
                                            <ul>
                                                <?php foreach ($cat['children'] as $ch): ?>
                                                <li><a href="<?= e(listing_url($store, ['cat' => [$ch['slug']]])) ?>"><?= e($ch['name']) ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php elseif ($sub): ?>
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
