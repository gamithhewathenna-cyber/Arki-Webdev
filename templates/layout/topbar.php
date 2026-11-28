<?php
/**
 * Top notification bar (CMS → Marketing → Notification Bar).
 * One set of messages is printed; main.js repeats it so the row loops seamlessly.
 */
try {
    $bar = ContentRepository::page('topbar')['topbar'] ?? null;
} catch (Throwable $e) {
    $bar = null;
}
$messages = array_values(array_filter($bar['items'] ?? [], static fn($it) => trim((string)($it['data']['text'] ?? '')) !== ''));
if (!$messages) return;
$speed = ['slow' => 35, 'normal' => 60, 'fast' => 95][$bar['data']['speed'] ?? 'slow'] ?? 35; // pixels per second
?>
<div class="topbar" data-topbar data-speed="<?= (int)$speed ?>" role="region" aria-label="Offers and news">
    <div class="topbar__track">
        <ul class="topbar__group">
            <?php foreach ($messages as $it): $m = $it['data']; $u = trim((string)($m['url'] ?? '')); ?>
            <li class="topbar__item"><?php if ($u !== ''): ?><a href="<?= e(safe_url($u)) ?>"<?= link_attrs($u) ?>><?= e($m['text']) ?></a><?php else: ?><span><?= e($m['text']) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
