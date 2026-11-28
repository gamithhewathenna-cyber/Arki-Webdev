<?php /** @var array $s */ $d = $s['data']; ?>
<section class="section section-inspiration">
    <div class="container">
        <header class="inspiration-head">
            <div class="inspiration-head__title">
                <?php if (!empty($d['tagline'])): ?><p class="eyebrow eyebrow--sm"><?= e($d['tagline']) ?></p><?php endif; ?>
                <?php if (!empty($d['heading'])): ?><h2 class="heading heading--lg"><?= lines($d['heading']) ?></h2><?php endif; ?>
            </div>
            <div class="prose prose--muted prose--tight inspiration-head__text"><?= paragraphs($d['body'] ?? '') ?></div>
            <?php if (!empty($d['button_label'])): ?>
            <a class="btn btn--dark btn--sm inspiration-head__btn" href="<?= e(safe_url($d['button_url'] ?? '')) ?>"><?= e($d['button_label']) ?></a>
            <?php endif; ?>
        </header>

        <?php if (!empty($s['items'])): ?>
        <div class="inspiration-grid">
            <?php foreach ($s['items'] as $it): $p = $it['data']; $tag = !empty($p['url']) ? 'a' : 'figure'; ?>
            <<?= $tag ?> class="inspiration-grid__item media"<?= $tag === 'a' ? ' href="' . e(safe_url($p['url'])) . '"' . link_attrs($p['url']) : '' ?>>
                <?= picture($p['image'] ?? '', (string)($p['alt'] ?? '')) ?>
            </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($d['note_heading']) || !empty($d['note_text'])): /* About page: grey statement box */ ?>
        <div class="inspiration-note">
            <?php if (!empty($d['note_heading'])): ?><h3 class="inspiration-note__title"><?= e($d['note_heading']) ?></h3><?php endif; ?>
            <?php if (!empty($d['note_text'])): ?><div class="inspiration-note__text"><?= paragraphs($d['note_text']) ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
