<?php /** @var array $s */ $d = $s['data']; ?>
<section class="section-banner<?= ($d['align'] ?? '') === 'left' ? ' section-banner--left' : '' ?>">
    <div class="section-banner__bg"><?= picture($d['image'] ?? '', (string)($d['image_alt'] ?? ''), ['eager' => true]) ?></div>
    <div class="container section-banner__inner">
        <?php if (!empty($d['tagline'])): ?><p class="section-banner__tagline"><?= e($d['tagline']) ?></p><?php endif; ?>
        <h1 class="section-banner__title"><?= lines($d['heading'] ?? '') ?></h1>
        <?php if (!empty($d['body'])): ?><div class="section-banner__text"><?= paragraphs($d['body']) ?></div><?php endif; ?>
        <?php if (!empty($d['link_label'])): ?>
        <a class="section-banner__link" href="<?= e(safe_url($d['link_url'] ?? '')) ?>"<?= link_attrs($d['link_url'] ?? '') ?>><?= e($d['link_label']) ?></a>
        <?php endif; ?>
    </div>
</section>
