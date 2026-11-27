<?php /** @var array $s */ if (empty($s['items'])) return; ?>
<section class="section section-hero" aria-label="Featured">
    <div class="container">
        <div class="hero-grid hero-grid--<?= min(count($s['items']), 4) ?>">
            <?php foreach ($s['items'] as $i => $it): $d = $it['data']; $tag = !empty($d['url']) ? 'a' : 'div'; ?>
            <<?= $tag ?> class="hero-card"<?= $tag === 'a' ? ' href="' . e(safe_url($d['url'])) . '"' . link_attrs($d['url']) : '' ?>>
                <?= picture($d['image'] ?? '', (string)($d['image_alt'] ?? ''), ['class' => 'hero-card__img', 'eager' => $i < 3]) ?>
                <span class="hero-card__text">
                    <span class="hero-card__title"><?= e($d['title'] ?? '') ?></span>
                    <?php if (!empty($d['subtitle'])): ?><span class="hero-card__subtitle"><?= e($d['subtitle']) ?></span><?php endif; ?>
                </span>
            </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
</section>
