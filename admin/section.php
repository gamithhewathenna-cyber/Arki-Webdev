<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

$section = ContentRepository::section((int)($_GET['id'] ?? 0));
$def = $section ? ContentRepository::sectionSchema($section['page'], $section['section_key']) : null;
if (!$section || !$def) {
    http_response_code(404);
    exit('Section not found.');
}
$pageSchema = ContentRepository::schema()[$section['page']];
$self = 'section.php?id=' . (int)$section['id'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    [$data, $errors, $orphans] = AdminForm::collect($def['fields'], $section['data']);
    ContentRepository::saveSectionData((int)$section['id'], $data);
    foreach ($orphans as $o) {
        Uploader::deleteIfUnused($o);
    }
    foreach ($errors as $er) {
        flash('error', $er);
    }
    flash('success', $def['label'] . ' saved.');
    redirect($self);
}

$items = isset($def['items']) ? ContentRepository::items((int)$section['id']) : [];

admin_header($def['label'], $section['page']);
?>
<nav class="adm-crumbs"><a href="./?page=<?= e($section['page']) ?>"><?= e($pageSchema['label']) ?></a> / <?= e($def['label']) ?></nav>
<header class="adm-head">
    <div>
        <h1><?= e($def['label']) ?></h1>
        <?php if (!empty($def['description'])): ?><p class="muted"><?= e($def['description']) ?></p><?php endif; ?>
    </div>
    <?php if (!$section['is_active']): ?><span class="adm-badge">Hidden on website</span><?php endif; ?>
</header>

<?php if ($def['fields']): ?>
<form method="post" enctype="multipart/form-data" class="adm-card adm-form">
    <?= csrf_field() ?>
    <h2>Section content</h2>
    <?= AdminForm::render($def['fields'], $section['data']) ?>
    <div class="adm-actions"><button class="adm-btn" type="submit">Save changes</button></div>
</form>
<?php endif; ?>

<?php if (isset($def['items'])): $it = $def['items']; ?>
<section class="adm-card adm-card--flush">
    <div class="adm-card__head">
        <h2><?= e($it['label']) ?>s <span class="muted">(<?= count($items) ?>)</span></h2>
        <a class="adm-btn adm-btn--sm" href="item.php?section=<?= (int)$section['id'] ?>">+ Add <?= e(strtolower($it['label'])) ?></a>
    </div>
    <?php if (!$items): ?>
        <p class="adm-empty">Nothing here yet.</p>
    <?php else: ?>
    <table class="adm-table">
        <thead><tr><th class="col-order">Order</th><?php if (!empty($it['thumb'])): ?><th class="col-thumb"></th><?php endif; ?><th><?= e($it['label']) ?></th><th class="col-status">Status</th><th class="col-actions"></th></tr></thead>
        <tbody>
        <?php foreach ($items as $i => $row):
            $titleKey = $it['title'] ?? array_key_first($it['fields']);
            $titleTxt = trim(preg_replace('/\s+/', ' ', (string)($row['data'][$titleKey] ?? ''))) ?: '(untitled)';
            if (isset($it['fields']['column'])) {
                $titleTxt .= ' — ' . ($it['fields']['column']['options'][$row['data']['column'] ?? ''] ?? '');
            } ?>
            <tr class="<?= $row['is_active'] ? '' : 'is-off' ?>">
                <td class="col-order">
                    <form method="post" action="action.php" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="do" value="move_item"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="adm-icon" name="dir" value="-1" title="Move up" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                        <button class="adm-icon" name="dir" value="1" title="Move down" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>↓</button>
                    </form>
                </td>
                <?php if (!empty($it['thumb'])): $th = (string)($row['data'][$it['thumb']] ?? ''); ?>
                <td class="col-thumb"><?php if ($th !== ''): ?><img src="<?= e(upload_url($th)) ?>" alt="" loading="lazy"><?php endif; ?></td>
                <?php endif; ?>
                <td><a class="adm-strong" href="item.php?section=<?= (int)$section['id'] ?>&amp;id=<?= (int)$row['id'] ?>"><?= e($titleTxt) ?></a></td>
                <td class="col-status">
                    <form method="post" action="action.php" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="do" value="toggle_item"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="adm-badge <?= $row['is_active'] ? 'adm-badge--on' : '' ?>"><?= $row['is_active'] ? 'Visible' : 'Hidden' ?></button>
                    </form>
                </td>
                <td class="col-actions">
                    <a class="adm-btn adm-btn--sm" href="item.php?section=<?= (int)$section['id'] ?>&amp;id=<?= (int)$row['id'] ?>">Edit</a>
                    <form method="post" action="action.php" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="do" value="duplicate_item"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="adm-btn adm-btn--sm adm-btn--ghost" title="Duplicate">Copy</button>
                    </form>
                    <form method="post" action="action.php" class="inline" data-confirm="Delete this <?= e(strtolower($it['label'])) ?>? This cannot be undone.">
                        <?= csrf_field() ?><input type="hidden" name="do" value="delete_item"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="adm-btn adm-btn--sm adm-btn--danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php admin_footer(); ?>
