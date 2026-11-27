<?php /** @var array $s */ $d = $s['data']; ?>
<section class="section section-brands band band--grey">
    <div class="container text-center">
        <?php if (!empty($d['heading'])): ?><h2 class="heading heading--lg"><?= lines($d['heading']) ?></h2><?php endif; ?>
        <div class="prose prose--muted prose--wide"><?= paragraphs($d['body'] ?? '') ?></div>

        <?php if (!empty($s['items'])): ?>
        <div class="brand-slider" data-slider>
            <button class="brand-slider__nav" type="button" data-slider-prev aria-label="Previous brands"><span class="icon"><?= icon('chevron-left') ?></span></button>
            <ul class="brand-slider__track scroller" data-slider-track>
                <?php foreach ($s['items'] as $it): $b = $it['data']; $logo = picture($b['logo'] ?? '', (string)($b['name'] ?? ''), ['class' => 'brand-slider__logo']); ?>
                <li class="brand-slider__item">
                    <?php if (!empty($b['url'])): ?><a href="<?= e(safe_url($b['url'])) ?>"<?= link_attrs($b['url']) ?>><?php endif; ?>
                    <?= $logo ?: '<span class="brand-slider__name">' . e($b['name'] ?? '') . '</span>' ?>
                    <?php if (!empty($b['url'])): ?></a><?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <button class="brand-slider__nav" type="button" data-slider-next aria-label="Next brands"><span class="icon"><?= icon('chevron-right') ?></span></button>
        </div>
        <?php endif; ?>
    </div>
</section>
