<?php /** About page: "Our Story – Who We Are" (text + beliefs list, image on the right). @var array $s */ $d = $s['data']; ?>
<section class="section section-story">
    <div class="container story">
        <div class="story__text">
            <?php if (!empty($d['tagline'])): ?><p class="eyebrow eyebrow--sm"><?= e($d['tagline']) ?></p><?php endif; ?>
            <?php if (!empty($d['heading'])): ?><h2 class="heading heading--lg"><?= lines($d['heading']) ?></h2><?php endif; ?>
            <?php if (!empty($d['body'])): ?><div class="prose prose--muted prose--tight story__body"><?= paragraphs($d['body']) ?></div><?php endif; ?>

            <?php if (!empty($s['items'])): ?>
            <?php if (!empty($d['beliefs_heading'])): ?><h3 class="story__list-title"><?= e($d['beliefs_heading']) ?></h3><?php endif; ?>
            <ul class="beliefs">
                <?php foreach ($s['items'] as $it): $b = $it['data']; ?>
                <li class="beliefs__item">
                    <span class="beliefs__icon"><?= icon('check-circle') ?></span>
                    <div>
                        <p class="beliefs__title"><?= e($b['title'] ?? '') ?></p>
                        <?php if (!empty($b['text'])): ?><p class="beliefs__text"><?= e($b['text']) ?></p><?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php if (!empty($d['image'])): ?>
        <figure class="story__media media"><?= picture($d['image'], (string)($d['image_alt'] ?? '')) ?></figure>
        <?php endif; ?>
    </div>
</section>
