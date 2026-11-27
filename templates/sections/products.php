<?php
/** Featured products (Store page) – loaded from the product database. @var array $s @var string $pageKey */
$d = $s['data'];
$store = in_array($pageKey ?? '', ['work', 'learn'], true) ? $pageKey : 'store';
$limit = in_array((string)($d['limit'] ?? ''), ['4', '8', '12'], true) ? (int)$d['limit'] : 8;
try {
    $items = ProductRepository::highlighted($store, (string)($d['source'] ?? 'featured'), $limit);
} catch (Throwable $e) {
    $items = []; // product tables not created yet
}
if (!$items) return;
?>
<section class="section section-products" aria-label="<?= e(($d['heading'] ?? '') ?: 'Products') ?>">
    <div class="container">
        <?php if (!empty($d['heading']) || !empty($d['link_label'])): ?>
        <header class="section-headrow">
            <?php if (!empty($d['heading'])): ?><h2 class="heading heading--lg"><?= e($d['heading']) ?></h2><?php endif; ?>
            <?php if (!empty($d['link_label'])): ?>
            <a class="more-link" href="<?= e(safe_url($d['link_url'] ?? '')) ?>"<?= link_attrs($d['link_url'] ?? '') ?>><?= e($d['link_label']) ?><?= more_arrow() ?></a>
            <?php endif; ?>
        </header>
        <?php endif; ?>
        <ul class="product-grid">
            <?php foreach ($items as $p) render('shop/card', ['p' => $p, 'from' => $store]); ?>
        </ul>
    </div>
</section>
