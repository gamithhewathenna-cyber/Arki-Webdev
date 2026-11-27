<?php /** @var array $s */ if (empty($s['items'])) return; ?>
<section class="section section-gallery" aria-label="Gallery">
    <div class="container">
        <div class="gallery-grid">
            <?php foreach ($s['items'] as $it): $d = $it['data']; $tag = !empty($d['url']) ? 'a' : 'figure'; ?>
            <<?= $tag ?> class="gallery-grid__item media"<?= $tag === 'a' ? ' href="' . e(safe_url($d['url'])) . '"' . link_attrs($d['url']) : '' ?>>
                <?= picture($d['image'] ?? '', (string)($d['alt'] ?? '')) ?>
            </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
</section>
