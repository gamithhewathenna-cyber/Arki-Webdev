<?php
/** @var array|null $s @var array|null $links @var array|null $contacts */
$d = $s['data'] ?? [];
$cols = ['links' => [], 'products' => []];
foreach ($links['items'] ?? [] as $it) {
    $col = ($it['data']['column'] ?? 'links') === 'products' ? 'products' : 'links';
    $cols[$col][] = $it['data'];
}
$copyright = str_replace('{year}', date('Y'), (string)($d['copyright'] ?? ''));
?>
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__col site-footer__col--brand">
                <?= picture($d['brand_logo'] ?? '', Settings::get('site_name', ''), ['class' => 'site-footer__logo']) ?>
                <?= paragraphs($d['brand_text'] ?? '') ?>
            </div>

            <div class="site-footer__col site-footer__col--dealer">
                <?= picture($d['dealer_logo'] ?? '', (string)($d['dealer_title'] ?? ''), ['class' => 'site-footer__logo site-footer__logo--dealer']) ?>
                <?php if (!empty($d['dealer_title'])): ?><h2 class="site-footer__title"><?= e($d['dealer_title']) ?></h2><?php endif; ?>
                <?= paragraphs($d['dealer_text'] ?? '') ?>
            </div>

            <?php foreach (['links' => 'links_title', 'products' => 'products_title'] as $col => $titleKey): ?>
            <nav class="site-footer__col" aria-label="<?= e($d[$titleKey] ?? $col) ?>">
                <?php if (!empty($d[$titleKey])): ?><h2 class="site-footer__title"><?= e($d[$titleKey]) ?></h2><?php endif; ?>
                <ul class="site-footer__links">
                    <?php foreach ($cols[$col] as $l): ?>
                    <li><a href="<?= e(safe_url($l['url'] ?? '')) ?>"<?= link_attrs($l['url'] ?? '') ?>><?= e($l['label'] ?? '') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endforeach; ?>

            <div class="site-footer__col site-footer__col--payments">
                <?php if (!empty($d['payments_title'])): ?><h2 class="site-footer__title"><?= e($d['payments_title']) ?></h2><?php endif; ?>
                <?= paragraphs($d['payments_text'] ?? '') ?>
                <?= picture($d['payments_image'] ?? '', 'Accepted payment methods', ['class' => 'site-footer__payments']) ?>
            </div>
        </div>

        <?php if (!empty($contacts['items'])): ?>
        <ul class="site-footer__contacts">
            <?php foreach ($contacts['items'] as $c): $cd = $c['data']; ?>
            <li>
                <a class="pill" href="<?= e(safe_url($cd['url'] ?? '')) ?>"<?= link_attrs($cd['url'] ?? '') ?>>
                    <span class="icon icon--xs"><?= icon((string)($cd['icon'] ?? 'link')) ?></span><?= e($cd['label'] ?? '') ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="site-footer__bottom">
            <p class="site-footer__copy"><?= e($copyright) ?></p>
            <ul class="site-footer__legal">
                <?php if (!empty($d['terms_label'])): ?><li><a href="<?= e(safe_url($d['terms_url'] ?? '')) ?>"><?= e($d['terms_label']) ?></a></li><?php endif; ?>
                <?php if (!empty($d['privacy_label'])): ?><li><a href="<?= e(safe_url($d['privacy_url'] ?? '')) ?>"><?= e($d['privacy_label']) ?></a></li><?php endif; ?>
            </ul>
        </div>
    </div>
</footer>
