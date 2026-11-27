<?php /** @var array $s */ $d = $s['data']; ?>
<section class="section section-projects">
    <div class="container projects-layout">
        <div class="projects-layout__intro">
            <?php if (!empty($d['tagline'])): ?><p class="eyebrow eyebrow--sm"><?= e($d['tagline']) ?></p><?php endif; ?>
            <?php if (!empty($d['heading'])): ?><h2 class="heading heading--lg"><?= lines($d['heading']) ?></h2><?php endif; ?>
            <div class="prose prose--muted prose--tight"><?= paragraphs($d['body'] ?? '') ?></div>
            <?php if (!empty($d['button_label'])): ?>
            <a class="btn btn--dark btn--sm" href="<?= e(safe_url($d['button_url'] ?? '')) ?>"><?= e($d['button_label']) ?></a>
            <?php endif; ?>
        </div>

        <?php if (!empty($s['items'])): ?>
        <div class="projects-layout__track scroller" data-drag-scroll tabindex="0" aria-label="Projects">
            <?php foreach ($s['items'] as $it): $p = $it['data']; ?>
            <article class="project-card">
                <div class="project-card__media media"><?= picture($p['image'] ?? '', (string)($p['image_alt'] ?? '')) ?></div>
                <h3 class="project-card__title"><?= e($p['title'] ?? '') ?></h3>
                <?php if (!empty($p['meta'])): ?><p class="project-card__meta"><?= e($p['meta']) ?></p><?php endif; ?>
                <?php if (!empty($p['url'])): ?>
                <a class="text-link text-link--xs" href="<?= e(safe_url($p['url'])) ?>"<?= link_attrs($p['url']) ?>><?= e(($p['link_label'] ?? '') ?: 'View Products') ?></a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
