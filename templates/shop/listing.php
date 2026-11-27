<?php
/**
 * Product listing (Work / Learn / Store).
 * @var string $store @var string $label @var array|null $banner @var array|null $cta
 * @var array $options @var array $ui @var bool $active @var string $sort
 * @var array $items @var int $total @var int $page @var int $pages
 */
$b = $banner['data'] ?? null;
$base = listing_url($store);
$checked = static fn(array $list, string $v) => in_array($v, $list, true) ? ' checked' : '';
$selectFilter = static function (string $name, string $label, array $terms, string $current): string {
    if (!$terms) return '';
    $html = '<label class="sr-only" for="f-' . $name . '">' . e($label) . '</label><span class="filter-select"><select id="f-' . $name . '" name="' . $name . '" data-autosubmit>';
    $html .= '<option value="">' . e($label) . '</option>';
    foreach ($terms as $t) {
        $html .= '<option value="' . e($t['slug']) . '"' . ($t['slug'] === $current ? ' selected' : '') . '>' . e($t['name']) . '</option>';
    }
    return $html . '</select>' . icon('chevron-down') . '</span>';
};
$pMin = $options['price_min'];
$pMax = max($options['price_max'], $pMin);
$curMin = $ui['min'] ?? $pMin;
$curMax = $ui['max'] ?? $pMax;
?>
<?php if ($b): ?>
<section class="shop-banner">
    <div class="shop-banner__bg"><?= picture($b['image'] ?? '', (string)($b['image_alt'] ?? ''), ['eager' => true]) ?></div>
    <div class="container shop-banner__inner">
        <?php if (!empty($b['tagline'])): ?><p class="shop-banner__tagline"><?= e($b['tagline']) ?></p><?php endif; ?>
        <h1 class="shop-banner__title"><?= lines(($b['heading'] ?? '') ?: $label . ' Products') ?></h1>
        <?php if (!empty($b['body'])): ?><div class="shop-banner__text"><?= paragraphs($b['body']) ?></div><?php endif; ?>
        <?php if (!empty($b['button_label'])): ?>
        <a class="shop-banner__link" href="<?= e(safe_url($b['button_url'] ?? '')) ?>"><?= e($b['button_label']) ?></a>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<h1 class="sr-only"><?= e($label) ?> Products</h1>
<?php endif; ?>

<section class="shop" id="products" aria-label="<?= e($label) ?> products">
    <div class="container shop__layout">

        <aside class="shop-filters" id="shop-filters" aria-label="Filters">
            <form class="shop-filters__form" id="shop-filter-form" method="get" action="<?= e($base) ?>" data-shop-filters>
                <div class="shop-filters__head">
                    <h2 class="shop-filters__heading">Filters</h2>
                    <button class="shop-filters__close" type="button" data-filters-close aria-label="Close filters"><?= icon('close') ?></button>
                </div>

                <div class="filter-group">
                    <h3 class="filter-group__title"><label for="f-q">Search Product</label></h3>
                    <div class="filter-search">
                        <input id="f-q" type="search" name="q" value="<?= e($ui['q']) ?>" placeholder="Search Products..." maxlength="100">
                        <button type="submit" aria-label="Search"><?= icon('search') ?></button>
                    </div>
                </div>

                <?php if ($options['categories']): ?>
                <fieldset class="filter-group">
                    <legend class="filter-group__title">Categories</legend>
                    <ul class="filter-list">
                        <?php foreach ($options['categories'] as $c):
                            $open = array_intersect(array_column($c['children'], 'slug'), $ui['cat']) !== []; ?>
                        <li class="filter-item<?= $c['children'] ? ' has-children' : '' ?><?= $open ? ' is-open' : '' ?>">
                            <div class="filter-row">
                                <?php if ($c['children']): ?>
                                <button class="filter-row__toggle" type="button" aria-expanded="<?= $open ? 'true' : 'false' ?>" data-filter-toggle aria-label="Show <?= e($c['name']) ?> subcategories"></button>
                                <?php else: ?><span class="filter-row__dot" aria-hidden="true">•</span><?php endif; ?>
                                <label class="filter-check filter-check--right"><span><?= e($c['name']) ?></span><input type="checkbox" name="cat[]" value="<?= e($c['slug']) ?>"<?= $checked($ui['cat'], $c['slug']) ?> data-autosubmit></label>
                            </div>
                            <?php if ($c['children']): ?>
                            <ul class="filter-sublist">
                                <?php foreach ($c['children'] as $ch): ?>
                                <li><label class="filter-check filter-check--right"><span><?= e($ch['name']) ?></span><input type="checkbox" name="cat[]" value="<?= e($ch['slug']) ?>"<?= $checked($ui['cat'], $ch['slug']) ?> data-autosubmit></label></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </fieldset>
                <?php endif; ?>

                <?php if ($options['types']): ?>
                <fieldset class="filter-group">
                    <legend class="filter-group__title">Product Type</legend>
                    <ul class="filter-list">
                        <?php foreach ($options['types'] as $t): ?>
                        <li><label class="filter-check filter-check--right"><span><?= e($t['name']) ?></span><input type="checkbox" name="type[]" value="<?= e($t['slug']) ?>"<?= $checked($ui['type'], $t['slug']) ?> data-autosubmit></label></li>
                        <?php endforeach; ?>
                    </ul>
                </fieldset>
                <?php endif; ?>

                <?php if ($pMax > $pMin): ?>
                <fieldset class="filter-group">
                    <legend class="filter-group__title">Price Range</legend>
                    <div class="price-range" data-price-range data-min="<?= (int)$pMin ?>" data-max="<?= (int)$pMax ?>" data-currency="<?= e(explode(' ', money(0))[0]) ?>">
                        <output class="price-range__value" data-price-output><?= e(money($curMin)) ?> - <?= e(money($curMax)) ?><?= $curMax >= $pMax ? '+' : '' ?></output>
                        <div class="price-range__track">
                            <input type="range" name="min" min="<?= (int)$pMin ?>" max="<?= (int)$pMax ?>" step="10" value="<?= (int)$curMin ?>" aria-label="Minimum price" data-price-min>
                            <input type="range" name="max" min="<?= (int)$pMin ?>" max="<?= (int)$pMax ?>" step="10" value="<?= (int)$curMax ?>" aria-label="Maximum price" data-price-max>
                        </div>
                    </div>
                </fieldset>
                <?php endif; ?>

                <fieldset class="filter-group">
                    <legend class="filter-group__title">Availability</legend>
                    <ul class="filter-list">
                        <li><label class="filter-check"><input type="checkbox" name="avail[]" value="in_stock"<?= $checked($ui['avail'], 'in_stock') ?> data-autosubmit><span>In Stock</span></label></li>
                        <li><label class="filter-check"><input type="checkbox" name="avail[]" value="made_to_order"<?= $checked($ui['avail'], 'made_to_order') ?> data-autosubmit><span>Made To Order</span></label></li>
                    </ul>
                </fieldset>

                <?php $other = $selectFilter('material', 'Material', $options['materials'], $ui['material'])
                    . $selectFilter('colour', 'Colour', $options['colours'], $ui['colour'])
                    . $selectFilter('brand', 'Brand', $options['brands'], $ui['brand'])
                    . $selectFilter('env', 'Environmental Rating', $options['env'], $ui['env']); ?>
                <?php if ($other !== ''): ?>
                <fieldset class="filter-group">
                    <legend class="filter-group__title">Other Filters</legend>
                    <div class="filter-selects"><?= $other ?></div>
                </fieldset>
                <?php endif; ?>

                <div class="shop-filters__actions">
                    <button class="btn btn--dark btn--sm shop-filters__apply" type="submit">Apply Filters</button>
                    <a class="shop-filters__clear" href="<?= e($base) ?>">Clear All Filters</a>
                </div>
            </form>
        </aside>

        <div class="shop__main">
            <div class="shop-toolbar">
                <button class="shop-toolbar__filters" type="button" data-filters-open aria-controls="shop-filters"><?= icon('menu') ?> Filters<?= $active ? ' •' : '' ?></button>
                <p class="shop-toolbar__count" aria-live="polite"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></p>
                <label class="shop-sort"><span>Sort By</span>
                    <span class="filter-select"><select name="sort" form="shop-filter-form" data-autosubmit>
                        <?php foreach (ProductRepository::SORTS as $k => $l): ?>
                        <option value="<?= e($k) ?>"<?= $k === $sort ? ' selected' : '' ?>><?= e($l) ?></option>
                        <?php endforeach; ?>
                    </select><?= icon('chevron-down') ?></span>
                </label>
            </div>

            <?php if ($items): ?>
            <ul class="product-grid product-grid--3">
                <?php foreach ($items as $p) render('shop/card', ['p' => $p, 'from' => $store]); ?>
            </ul>
            <?php else: ?>
            <div class="shop-empty">
                <p class="shop-empty__title">No products match these filters.</p>
                <a class="text-link" href="<?= e($base) ?>">Clear all filters</a>
            </div>
            <?php endif; ?>

            <?php if ($pages > 1):
                $q = Shop::query($ui, $sort);
                $link = static fn(int $n) => listing_url($store, $n > 1 ? $q + ['page' => $n] : $q) . '#products';
                $show = array_unique(array_filter([1, 2, $page - 1, $page, $page + 1, $pages - 1, $pages], static fn($n) => $n >= 1 && $n <= $pages));
                sort($show); ?>
            <nav class="pagination" aria-label="Pages">
                <?php if ($page > 1): ?><a class="pagination__step" href="<?= e($link($page - 1)) ?>" aria-label="Previous page"><?= icon('chevron-left') ?></a>
                <?php else: ?><span class="pagination__step is-disabled" aria-hidden="true"><?= icon('chevron-left') ?></span><?php endif; ?>
                <?php $prev = 0; foreach ($show as $n): ?>
                    <?php if ($n - $prev > 1): ?><span class="pagination__gap">....</span><?php endif; ?>
                    <?php if ($n === $page): ?><span class="pagination__num is-current" aria-current="page"><?= $n ?></span>
                    <?php else: ?><a class="pagination__num" href="<?= e($link($n)) ?>"><?= $n ?></a><?php endif; ?>
                <?php $prev = $n; endforeach; ?>
                <?php if ($page < $pages): ?><a class="pagination__step" href="<?= e($link($page + 1)) ?>" aria-label="Next page"><?= icon('chevron-right') ?></a>
                <?php else: ?><span class="pagination__step is-disabled" aria-hidden="true"><?= icon('chevron-right') ?></span><?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($cta) render('sections/cta', ['s' => $cta]); ?>
