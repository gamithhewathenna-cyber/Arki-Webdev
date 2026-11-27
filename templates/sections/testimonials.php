<?php /** @var array $s */ $d = $s['data']; ?>
<section class="section section-testimonials">
    <div class="container">
        <div class="testimonial-panel">
            <?php if (!empty($d['heading'])): ?><h2 class="heading heading--xl text-center"><?= lines($d['heading']) ?></h2><?php endif; ?>

            <?php if (!empty($s['items'])): ?>
            <div class="testimonial-track scroller" data-drag-scroll tabindex="0" aria-label="Customer testimonials">
                <?php foreach ($s['items'] as $it): $t = $it['data']; ?>
                <figure class="testimonial-card">
                    <figcaption class="testimonial-card__head">
                        <?= picture($t['photo'] ?? '', '', ['class' => 'testimonial-card__photo']) ?>
                        <span>
                            <span class="testimonial-card__name"><?= e($t['name'] ?? '') ?></span>
                            <?php if (!empty($t['role'])): ?><span class="testimonial-card__role"><?= e($t['role']) ?></span><?php endif; ?>
                        </span>
                    </figcaption>
                    <blockquote class="testimonial-card__quote"><?= paragraphs($t['quote'] ?? '') ?></blockquote>
                </figure>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
