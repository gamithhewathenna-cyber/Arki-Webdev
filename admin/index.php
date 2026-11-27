<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

$schema = ContentRepository::schema();
$page   = isset($_GET['page']) && isset($schema[$_GET['page']]) ? (string)$_GET['page'] : 'home';
$pageSchema = $schema[$page];

admin_sync_sections($page);

$rows = Database::all(
    'SELECT s.*, (SELECT COUNT(*) FROM section_items i WHERE i.section_id = s.id) AS item_count
     FROM sections s WHERE s.page = ? ORDER BY s.sort_order, s.id',
    [$page]
);

admin_header($pageSchema['label'], $page);
?>
<header class="adm-head">
    <div>
        <h1><?= e($pageSchema['label']) ?></h1>
        <p class="muted"><?= $page === 'home'
            ? 'Pick a section from the tabs to edit it, or reorder and show/hide sections below. Changes are live as soon as you save.'
            : 'Content shared by every page of the website. Pick a section from the tabs to edit it.' ?></p>
    </div>
    <?php if ($page === 'home'): ?><a class="adm-btn adm-btn--ghost" href="<?= e(url()) ?>" target="_blank" rel="noopener">Preview homepage ↗</a><?php endif; ?>
</header>
<?php admin_section_tabs($page, 'overview'); ?>

<div class="adm-card adm-card--flush">
    <table class="adm-table">
        <thead><tr>
            <?php if ($pageSchema['sortable']): ?><th class="col-order">Order</th><?php endif; ?>
            <th>Section</th><th class="col-num">Items</th><th class="col-status">Status</th><th class="col-actions"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $i => $r):
            $def = $pageSchema['sections'][$r['section_key']] ?? null;
            if (!$def) continue; ?>
            <tr class="<?= $r['is_active'] ? '' : 'is-off' ?>">
                <?php if ($pageSchema['sortable']): ?>
                <td class="col-order">
                    <form method="post" action="action.php" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="do" value="move_section"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="adm-icon" name="dir" value="-1" title="Move up" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                        <button class="adm-icon" name="dir" value="1" title="Move down" <?= $i === count($rows) - 1 ? 'disabled' : '' ?>>↓</button>
                    </form>
                </td>
                <?php endif; ?>
                <td>
                    <a class="adm-strong" href="section.php?id=<?= (int)$r['id'] ?>"><?= e($def['label']) ?></a>
                    <?php if (!empty($def['description'])): ?><div class="muted small"><?= e($def['description']) ?></div><?php endif; ?>
                </td>
                <td class="col-num"><?= isset($def['items']) ? (int)$r['item_count'] : '–' ?></td>
                <td class="col-status">
                    <form method="post" action="action.php" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="do" value="toggle_section"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="adm-badge <?= $r['is_active'] ? 'adm-badge--on' : '' ?>" title="Click to <?= $r['is_active'] ? 'hide' : 'show' ?>"><?= $r['is_active'] ? 'Visible' : 'Hidden' ?></button>
                    </form>
                </td>
                <td class="col-actions"><a class="adm-btn adm-btn--sm" href="section.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php admin_footer(); ?>
