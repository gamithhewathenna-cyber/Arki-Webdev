<?php
/**
 * Promo popup (CMS → Promo Popup). Hidden until main.js opens it, once the
 * visitor has scrolled the chosen share of the page. Off = section not Visible.
 */
try {
    $popup = ContentRepository::page('popup')['popup'] ?? null;
} catch (Throwable $e) {
    $popup = null;
}
if (!$popup) return;
$d = $popup['data'];
if (empty($d['image']) || !is_file(ROOT_PATH . '/' . ltrim((string)$d['image'], '/'))) return;

$url   = trim((string)($d['button_url'] ?? ''));
$freq  = in_array($d['frequency'] ?? '', ['session', 'day', 'week', 'always'], true) ? $d['frequency'] : 'session';
$at    = max(1, min(90, (int)($d['scroll'] ?? 15)));
$key   = substr(md5((string)json_encode($d)), 0, 10); // changes when the popup is edited → shown again
$img   = picture((string)$d['image'], (string)($d['image_alt'] ?? ''), ['class' => 'promo__img']);
?>
<dialog class="promo" data-promo data-promo-key="<?= e($key) ?>" data-promo-at="<?= $at ?>" data-promo-freq="<?= e($freq) ?>" aria-label="<?= e(($d['image_alt'] ?? '') ?: 'Special offer') ?>">
    <div class="promo__box">
        <button class="promo__close" type="button" data-promo-close aria-label="Close"><?= icon('close') ?></button>
        <?php if ($url !== ''): /* the whole image is the link */ ?>
        <a class="promo__media" href="<?= e(safe_url($url)) ?>"<?= link_attrs($url) ?>><?= $img ?></a>
        <?php else: ?>
        <div class="promo__media"><?= $img ?></div>
        <?php endif; ?>
    </div>
</dialog>
