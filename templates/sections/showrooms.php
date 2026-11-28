<?php /** "Come And See Us" showroom cards – dark image band (Contact page) or light (About page). @var array $s */
$d = $s['data'];
$light = ($d['style'] ?? 'dark') === 'light'; ?>
<section class="showrooms<?= $light ? ' showrooms--light' : '' ?>">
    <?php if (!$light): ?><div class="showrooms__bg"><?= picture($d['image'] ?? '', (string)($d['image_alt'] ?? '')) ?></div><?php endif; ?>
    <div class="container">
        <?php if (!empty($d['heading'])): ?><h2 class="showrooms__title"><?= lines($d['heading']) ?></h2><?php endif; ?>
        <?php if (!empty($d['body'])): ?><div class="showrooms__text"><?= paragraphs($d['body']) ?></div><?php endif; ?>

        <?php if ($s['items']): ?>
        <ul class="showroom-grid">
            <?php foreach ($s['items'] as $it): $r = $it['data'];
                $map = trim((string)($r['map_address'] ?? ''));
                $mapUrl = $map !== '' ? 'https://www.google.com/maps?q=' . rawurlencode($map) . '&output=embed' : '';
                $directions = $map !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($map) : ''; ?>
            <li class="showroom">
                <div class="showroom__info">
                    <h3 class="showroom__city"><?= e($r['city'] ?? '') ?></h3>
                    <?php if (!empty($r['address'])): ?>
                    <p class="showroom__label">Address</p>
                    <address class="showroom__value"><?= lines($r['address']) ?></address>
                    <?php endif; ?>
                    <?php if (!empty($r['hours'])): ?>
                    <p class="showroom__label">Hours</p>
                    <p class="showroom__value"><?= lines($r['hours']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="showroom__map">
                    <?php if ($mapUrl): ?>
                    <iframe src="<?= e($mapUrl) ?>" title="Map – <?= e(($r['city'] ?? '') . ' showroom') ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    <a class="showroom__directions" href="<?= e($directions) ?>" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>
                    <?php else: ?>
                    <?= picture($r['map_image'] ?? '', 'Map – ' . ($r['city'] ?? '')) ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($r['button_label'])): ?>
                <a class="showroom__btn" href="<?= e(safe_url($r['button_url'] ?? '')) ?>"<?= link_attrs($r['button_url'] ?? '') ?>><?= e($r['button_label']) ?></a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if ($light && (!empty($d['note_title']) || !empty($d['note_text']))): ?>
        <p class="showrooms__footnote"><span aria-hidden="true">*</span> <?php if (!empty($d['note_title'])): ?><strong><?= e($d['note_title']) ?></strong> <?php endif; ?><?= e($d['note_text'] ?? '') ?></p>
        <?php elseif (!empty($d['note_title']) || !empty($d['note_text'])): ?>
        <div class="showrooms__note">
            <span class="showrooms__note-icon"><?= icon('truck') ?></span>
            <div class="showrooms__note-text">
                <?php if (!empty($d['note_title'])): ?><p class="showrooms__note-title"><?= e($d['note_title']) ?></p><?php endif; ?>
                <?php if (!empty($d['note_text'])): ?><?= paragraphs($d['note_text']) ?><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
