<?php /** About page: "How We Work" – dark image band with step cards. @var array $s */ $d = $s['data']; ?>
<section class="section-process">
    <div class="process__bg"><?= picture($d['image'] ?? '', (string)($d['image_alt'] ?? '')) ?></div>
    <div class="container process">
        <div class="process__intro">
            <?php if (!empty($d['tagline'])): ?><p class="process__tagline"><?= e($d['tagline']) ?></p><?php endif; ?>
            <?php if (!empty($d['heading'])): ?><h2 class="process__title"><?= lines($d['heading']) ?></h2><?php endif; ?>
            <?php if (!empty($d['body'])): ?><div class="process__text"><?= paragraphs($d['body']) ?></div><?php endif; ?>
        </div>
        <?php if (!empty($s['items'])): ?>
        <ol class="process-grid">
            <?php foreach ($s['items'] as $it): $c = $it['data']; ?>
            <li class="process-card">
                <span class="process-card__icon"><?= icon((string)($c['icon'] ?? 'discover')) ?></span>
                <h3 class="process-card__title"><?= e($c['title'] ?? '') ?></h3>
                <?php if (!empty($c['text'])): ?><p class="process-card__text"><?= e($c['text']) ?></p><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </div>
</section>
