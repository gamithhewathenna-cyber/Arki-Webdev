<?php /** @var array $s */ $d = $s['data']; ?>
<section class="section section-solutions band band--grey">
    <div class="container">
        <header class="section-head">
            <?php if (!empty($d['tagline'])): ?><p class="eyebrow"><?= e($d['tagline']) ?></p><?php endif; ?>
            <?php if (!empty($d['heading'])): ?><h2 class="heading heading--lg"><?= lines($d['heading']) ?></h2><?php endif; ?>
            <div class="prose prose--strong"><?= paragraphs($d['body'] ?? '') ?></div>
        </header>

        <?php if (!empty($s['items'])): ?>
        <div class="solutions-grid">
            <?php foreach ($s['items'] as $it): $c = $it['data']; ?>
            <article class="solution-card">
                <h3 class="solution-card__title"><?= lines($c['title'] ?? '') ?></h3>
                <div class="solution-card__text"><?= paragraphs($c['text'] ?? '') ?></div>
                <?php if (!empty($c['url'])): ?>
                <a class="arrow-link" href="<?= e(safe_url($c['url'])) ?>"<?= link_attrs($c['url']) ?>>
                    <?= e(($c['link_label'] ?? '') ?: 'Explore More') ?><span class="icon icon--sm"><?= icon('arrow-up-right') ?></span>
                </a>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
