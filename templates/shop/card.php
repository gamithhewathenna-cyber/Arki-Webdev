<?php
/**
 * Product card (listing grid, Store page, related products).
 * @var array $p card row from ProductRepository  @var string $from storefront the link keeps
 */
$from = $from ?? 'store';
$price = price_html($p);
?>
<li class="product-card">
    <a class="product-card__link" href="<?= e(product_url($p['slug'], $from)) ?>"><span class="sr-only"><?= e($p['name']) ?></span></a>
    <div class="product-card__media"><?= picture($p['image'] ?? '', (string)$p['name']) ?></div>
    <?php if (!empty($p['is_new']) || !empty($p['is_bestseller'])): ?>
    <span class="product-card__badge"><?= !empty($p['is_new']) ? 'New' : 'Best Seller' ?></span>
    <?php endif; ?>
    <?php /* Wishlist arrives in a later phase – shown but not active yet. */ ?>
    <button class="product-card__fav" type="button" disabled aria-disabled="true" title="Wishlist – coming soon"><span class="sr-only">Add <?= e($p['name']) ?> to wishlist (coming soon)</span><?= icon('heart') ?></button>
    <div class="product-card__body">
        <div class="product-card__row">
            <h3 class="product-card__name"><?= e($p['name']) ?></h3>
            <?php if ($price !== ''): ?><p class="product-card__price"><?= $price ?></p><?php endif; ?>
        </div>
        <div class="product-card__row product-card__row--meta">
            <p class="product-card__type"><?= e($p['type_name'] ?? '') ?></p>
            <p class="product-card__sku"><?= e($p['sku']) ?></p>
        </div>
    </div>
</li>
