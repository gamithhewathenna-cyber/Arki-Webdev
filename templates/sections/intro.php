<?php /** @var array $s @var bool $hasBanner */ $d = $s['data'];
// The page title (H1) is in the banner when the page has one.
$hTag = !empty($hasBanner) ? 'h2' : 'h1'; ?>
<section class="section section-intro">
    <div class="container container--narrow text-center">
        <?php if (!empty($d['tagline'])): ?><p class="eyebrow"><?= e($d['tagline']) ?></p><?php endif; ?>
        <?php if (!empty($d['heading'])): ?><<?= $hTag ?> class="heading heading--lg"><?= lines($d['heading']) ?></<?= $hTag ?>><?php endif; ?>
        <div class="prose prose--muted"><?= paragraphs($d['body'] ?? '') ?></div>
        <?php if (!empty($d['link_label'])): ?>
        <a class="text-link text-link--sm" href="<?= e(safe_url($d['link_url'] ?? '')) ?>"><?= e($d['link_label']) ?></a>
        <?php endif; ?>
    </div>
</section>
