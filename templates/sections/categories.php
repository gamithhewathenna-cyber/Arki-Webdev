<?php /** @var array $s */ $d = $s['data']; if (empty($s['items'])) return;
$hasHead = !empty($d['heading']);
$more = '';
if (!empty($d['link_label'])) {
    $more = '<a class="more-link" href="' . e(safe_url($d['link_url'] ?? '')) . '"' . link_attrs($d['link_url'] ?? '') . '>'
        . e($d['link_label']) . more_arrow() . '</a>';
} ?>
<section class="section section-categories<?= $hasHead ? ' section-categories--head' : '' ?>" aria-label="<?= e($hasHead ? $d['heading'] : 'Product categories') ?>">
    <div class="container">
        <?php if ($hasHead): ?>
        <header class="section-headrow">
            <h2 class="heading heading--lg"><?= e($d['heading']) ?></h2>
            <?= $more ?>
        </header>
        <?php endif; ?>
        <ul class="category-grid">
            <?php foreach ($s['items'] as $it): $c = $it['data']; $tag = !empty($c['url']) ? 'a' : 'div'; ?>
            <li>
                <<?= $tag ?> class="category-card"<?= $tag === 'a' ? ' href="' . e(safe_url($c['url'])) . '"' . link_attrs($c['url']) : '' ?>>
                    <span class="category-card__media"><?= picture($c['image'] ?? '', (string)($c['image_alt'] ?? '')) ?></span>
                    <span class="category-card__title"><?= e($c['title'] ?? '') ?></span>
                </<?= $tag ?>>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$hasHead && $more !== ''): ?>
        <p class="section-categories__more"><?= $more ?></p>
        <?php endif; ?>
    </div>
</section>
