<?php /** @var array $s */ $d = $s['data']; if (empty($s['items'])) return; ?>
<section class="section section-categories" aria-label="Product categories">
    <div class="container">
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
        <?php if (!empty($d['link_label'])): ?>
        <p class="section-categories__more">
            <a class="more-link" href="<?= e(safe_url($d['link_url'] ?? '')) ?>"<?= link_attrs($d['link_url'] ?? '') ?>>
                <?= e($d['link_label']) ?><svg viewBox="0 0 34 10" aria-hidden="true"><path d="M0 5h32M28 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </p>
        <?php endif; ?>
    </div>
</section>
