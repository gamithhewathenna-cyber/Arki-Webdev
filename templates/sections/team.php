<?php /** About page: "Our Team" – sliding portrait cards; the selected card shows the person's details. @var array $s */
$d = $s['data'];
if (empty($s['items'])) return;
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) $out .= mb_strtoupper(mb_substr($p, 0, 1));
    return $out;
}; ?>
<section class="section section-team" aria-labelledby="team-title">
    <div class="container" data-slider>
        <header class="team-head">
            <div>
                <?php if (!empty($d['tagline'])): ?><p class="team-head__tag"><?= e($d['tagline']) ?></p><?php endif; ?>
                <h2 class="heading heading--lg" id="team-title"><?= lines($d['heading'] ?? 'Our Team') ?></h2>
            </div>
            <div class="team-head__nav">
                <button class="team-nav" type="button" data-slider-prev aria-controls="team-track" aria-label="Previous team members"><?= icon('chevron-left') ?></button>
                <button class="team-nav" type="button" data-slider-next aria-controls="team-track" aria-label="Next team members"><?= icon('chevron-right') ?></button>
            </div>
        </header>

        <ul class="team-track scroller" id="team-track" data-slider-track data-drag-scroll data-team>
            <?php foreach (array_values($s['items']) as $i => $it):
                $m = $it['data'];
                $name = (string)($m['name'] ?? '');
                $hasPhoto = !empty($m['photo']) && is_file(ROOT_PATH . '/' . ltrim((string)$m['photo'], '/'));
                $socials = array_filter([
                    'linkedin'  => ['LinkedIn',  (string)($m['linkedin'] ?? '')],
                    'instagram' => ['Instagram', (string)($m['instagram'] ?? '')],
                    'facebook'  => ['Facebook',  (string)($m['facebook'] ?? '')],
                    'email'     => ['Email',     !empty($m['email']) ? 'mailto:' . trim((string)$m['email']) : ''],
                ], static fn($v) => $v[1] !== ''); ?>
            <li class="team-card<?= $i === 0 ? ' is-active' : '' ?>" tabindex="0" data-team-card aria-label="<?= e($name . (!empty($m['role']) ? ', ' . $m['role'] : '')) ?>">
                <div class="team-card__media<?= $hasPhoto ? '' : ' team-card__media--empty' ?>">
                    <?php if ($hasPhoto): ?><?= picture($m['photo'], (string)($m['photo_alt'] ?? '') ?: $name) ?>
                    <?php else: ?><span class="team-card__initials" aria-hidden="true"><?= e($initials($name)) ?></span><?php endif; ?>
                </div>
                <div class="team-card__caption" aria-hidden="true">
                    <p class="team-card__name"><?= e($name) ?></p>
                    <?php if (!empty($m['role'])): ?><p class="team-card__role"><?= e($m['role']) ?></p><?php endif; ?>
                </div>
                <div class="team-card__panel">
                    <h3 class="team-card__name"><?= e($name) ?></h3>
                    <?php if (!empty($m['role'])): ?><p class="team-card__role"><?= e($m['role']) ?></p><?php endif; ?>
                    <?php if (!empty($m['bio'])): ?><div class="team-card__bio"><?= paragraphs($m['bio']) ?></div><?php endif; ?>
                    <?php if ($socials): ?>
                    <ul class="team-card__social">
                        <?php foreach ($socials as $ico => [$label, $href]): ?>
                        <li><a href="<?= e(safe_url($href)) ?>"<?= $ico === 'email' ? '' : ' target="_blank" rel="noopener noreferrer"' ?> aria-label="<?= e($name . ' on ' . $label) ?>"><?= icon($ico) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
