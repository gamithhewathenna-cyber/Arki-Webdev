<?php /** @var array $s */ $d = $s['data']; ?>
<section class="section-cta">
    <div class="section-cta__bg"><?= picture($d['image'] ?? '', (string)($d['image_alt'] ?? '')) ?></div>
    <div class="container section-cta__inner">
        <div class="cta-card">
            <?php if (!empty($d['tagline'])): ?><p class="eyebrow eyebrow--sm"><?= e($d['tagline']) ?></p><?php endif; ?>
            <?php if (!empty($d['heading'])): ?><h2 class="heading heading--lg"><?= lines($d['heading']) ?></h2><?php endif; ?>
            <div class="prose prose--muted prose--tight"><?= paragraphs($d['body'] ?? '') ?></div>
            <div class="cta-card__actions">
                <?php if (!empty($d['primary_label'])): ?>
                <a class="btn btn--dark btn--sm" href="<?= e(safe_url($d['primary_url'] ?? '')) ?>"><?= e($d['primary_label']) ?></a>
                <?php endif; ?>
                <?php if (!empty($d['secondary_label'])): ?>
                <a class="text-link text-link--xs" href="<?= e(safe_url($d['secondary_url'] ?? '')) ?>"><?= e($d['secondary_label']) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
