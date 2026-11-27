<?php /** @var array $s */ $d = $s['data']; if (empty($s['items'])) return; ?>
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
            <?php foreach ($s['items'] as $it): $p = $it['data']; $href = !empty($p['url']) ? safe_url($p['url']) : ''; ?>
            <li class="product-card">
                <?php if ($href !== ''): ?><a class="product-card__link" href="<?= e($href) ?>"<?= link_attrs($p['url']) ?>><span class="sr-only"><?= e($p['name'] ?? '') ?></span></a><?php endif; ?>
                <div class="product-card__media"><?= picture($p['image'] ?? '', (string)($p['image_alt'] ?? '')) ?></div>
                <button class="product-card__fav" type="button" aria-pressed="false" data-fav="<?= (int)$it['id'] ?>" aria-label="Save <?= e($p['name'] ?? 'product') ?> to favourites"><?= icon('heart') ?></button>
                <div class="product-card__body">
                    <div class="product-card__row">
                        <h3 class="product-card__name"><?= e($p['name'] ?? '') ?></h3>
                        <?php if (!empty($p['price'])): ?>
                        <p class="product-card__price"><?php if (!empty($p['price_prefix'])): ?><small><?= e($p['price_prefix']) ?></small> <?php endif; ?><?= e($p['price']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="product-card__row product-card__row--meta">
                        <?php if (!empty($p['type'])): ?><p class="product-card__type"><?= e($p['type']) ?></p><?php endif; ?>
                        <?php if (!empty($p['sku'])): ?><p class="product-card__sku"><?= e($p['sku']) ?></p><?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
