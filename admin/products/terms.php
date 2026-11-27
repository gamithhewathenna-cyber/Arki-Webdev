<?php
/**
 * Centrally managed product lists: categories (+ subcategories), product
 * types, brands, materials, colours, sizes and environmental ratings.
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';

$tax = (string)($_GET['tax'] ?? 'cat_work');
if (!Catalog::valid($tax)) {
    http_response_code(404);
    exit('Unknown list.');
}
$def = Catalog::TAXONOMIES[$tax];
$editId = (int)($_GET['edit'] ?? 0);
$editing = $editId ? Catalog::term($editId) : null;
if ($editing && $editing['taxonomy'] !== $tax) {
    $editing = null;
}
$self = 'terms.php?tax=' . $tax;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $errors = [];

    if (!empty($_POST['delete'])) {
        $t = Catalog::term((int)$_POST['delete']);
        if ($t && $t['taxonomy'] === $tax) {
            Catalog::deleteTerm((int)$t['id']);
            if ($t['image']) Uploader::deleteIfUnused($t['image']);
            p_done([], '“' . $t['name'] . '” deleted.', $self);
        }
        redirect($self);
    }

    $id = (int)($_POST['id'] ?? 0);
    $row = $id ? Catalog::term($id) : null;
    if ($row && $row['taxonomy'] !== $tax) {
        $row = null;
        $id = 0;
    }
    $name = p_str('name', 150);
    if ($name === '') {
        p_done(['Name is required.'], '', $self . ($id ? '&edit=' . $id : ''));
    }
    $parent = null;
    if (!empty($def['nested']) && !empty($_POST['parent_id'])) {
        $pt = Catalog::term((int)$_POST['parent_id']);
        // Only one level of subcategories, and never its own parent.
        if ($pt && $pt['taxonomy'] === $tax && $pt['parent_id'] === null && (int)$pt['id'] !== $id) {
            $hasChildren = $id && (int)Database::value('SELECT COUNT(*) FROM product_terms WHERE parent_id = ?', [$id]) > 0;
            if ($hasChildren) {
                $errors[] = 'This category has subcategories, so it stays a top-level category.';
            } else {
                $parent = (int)$pt['id'];
            }
        }
    }
    $hex = null;
    if (!empty($def['hex'])) {
        $hex = strtolower(p_str('hex', 7));
        if ($hex !== '' && !preg_match('/^#[0-9a-f]{6}$/', $hex)) {
            $errors[] = 'HEX colour code must look like #4a4a4a.';
            $hex = $row['hex'] ?? null;
        }
        $hex = $hex ?: null;
    }
    $image = $row['image'] ?? null;
    $oldImage = null;
    if (!empty($def['image'])) {
        if ($new = p_image_upload('img_term', $def['image'], $errors, $tax === 'colour' ? 'Swatch image' : 'Logo')) {
            $oldImage = $image;
            $image = $new;
        } elseif (!empty($_POST['remove']['image']) && $image) {
            $oldImage = $image;
            $image = null;
        }
    }
    $savedId = Catalog::saveTerm($id ?: null, $tax, [
        'name' => $name, 'slug' => p_str('slug', 150), 'parent_id' => $parent, 'hex' => $hex,
        'image' => $image, 'is_active' => !empty($_POST['is_active']),
    ]);
    if ($oldImage) Uploader::deleteIfUnused($oldImage);
    p_done($errors, $def['single'] . ' “' . $name . '” saved.', $self);
}

$terms = Catalog::terms($tax);
$usage = Catalog::usage($tax);
$parents = !empty($def['nested']) ? Catalog::options($tax, true) : [];

admin_header($def['label'], 'p:' . $tax);
?>
<nav class="adm-crumbs"><a href="./">Products</a> / <?= e($def['label']) ?></nav>
<header class="adm-head">
    <div>
        <h1><?= e($def['label']) ?></h1>
        <p class="muted">Used in product forms and as filters on the website. Drag ⋮⋮ to set the order.</p>
    </div>
</header>

<div class="adm-split">
    <form method="post" enctype="multipart/form-data" class="adm-card adm-form" action="<?= e($self . ($editing ? '&edit=' . (int)$editing['id'] : '')) ?>">
        <?= csrf_field() ?>
        <h2><?= $editing ? 'Edit ' . e(strtolower($def['single'])) : 'Add ' . e(strtolower($def['single'])) ?></h2>
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <?= p_field('Name', p_input('name', $editing['name'] ?? '', ['max' => 150, 'required' => true]), '', 'f_name', true) ?>
        <?= p_field('Web name', p_input('slug', $editing['slug'] ?? '', ['max' => 150, 'placeholder' => 'Created from the name']), 'Used in filter links. Leave blank to create it from the name.', 'f_slug') ?>
        <?php if (!empty($def['nested'])): ?>
        <?= p_field('Parent category', p_select('parent_id', array_diff_key($parents, [(int)($editing['id'] ?? 0) => 1]), $editing['parent_id'] ?? '', '— Top-level category —'), 'Choose a parent to make this a subcategory.', 'f_parent_id') ?>
        <?php endif; ?>
        <?php if (!empty($def['hex'])): ?>
        <div class="field"><label for="f_hex">HEX colour code</label>
            <div class="adm-hex"><input type="color" value="<?= e($editing['hex'] ?? '#cccccc') ?>" data-hex-picker aria-label="Pick colour"><?= p_input('hex', $editing['hex'] ?? '', ['max' => 7, 'placeholder' => '#4a4a4a']) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($def['image'])): ?>
        <div class="field"><label for="img_term"><?= $tax === 'colour' ? 'Swatch image (optional – e.g. a fabric photo)' : 'Logo' ?></label>
            <?= AdminForm::imageInput('img_term', 'img_term', $def['image'], (string)($editing['image'] ?? ''), 'remove[image]', $tax === 'colour' ? 'Swatch image' : 'Logo') ?><?= AdminForm::imageHelp($def['image']) ?></div>
        <?php endif; ?>
        <div class="field"><?= p_check('is_active', 'Active (shown in product forms and filters)', (bool)($editing['is_active'] ?? true)) ?></div>
        <div class="adm-actions">
            <button class="adm-btn" type="submit"><?= $editing ? 'Save changes' : 'Add ' . e(strtolower($def['single'])) ?></button>
            <?php if ($editing): ?><a class="adm-link" href="<?= e($self) ?>">Cancel</a><?php endif; ?>
        </div>
    </form>

    <div class="adm-card adm-card--flush">
        <?php if (!$terms): ?>
        <p class="adm-empty">Nothing here yet.</p>
        <?php else: ?>
        <form method="post" action="<?= e($self) ?>" id="term-delete-form"><?= csrf_field() ?></form>
        <table class="adm-table">
            <thead><tr><th class="col-drag"></th><th><?= e($def['single']) ?></th><th class="col-num">Products</th><th class="col-status">Status</th><th class="col-actions"></th></tr></thead>
            <tbody data-sortable data-sortable-save="action.php" data-sortable-table="terms" data-csrf="<?= e(csrf_token()) ?>">
            <?php foreach ($terms as $t): ?>
                <tr data-sortable-item data-id="<?= (int)$t['id'] ?>" class="<?= $t['is_active'] ? '' : 'is-off' ?>">
                    <td class="col-drag"><button type="button" class="adm-handle" data-drag-handle aria-label="Move <?= e($t['name']) ?>">⋮⋮</button></td>
                    <td>
                        <span class="<?= !empty($t['depth']) ? 'adm-indent' : '' ?>">
                        <?php if (!empty($def['hex'])): ?><span class="adm-swatch" data-hex="<?= e((string)$t['hex']) ?>"><?php if ($t['image']): ?><img src="<?= e(upload_url($t['image'])) ?>" alt=""><?php endif; ?></span><?php endif; ?>
                        <?php if ($tax === 'brand' && $t['image']): ?><img class="adm-logo" src="<?= e(upload_url($t['image'])) ?>" alt=""><?php endif; ?>
                        <a class="adm-strong" href="<?= e($self . '&edit=' . (int)$t['id']) ?>"><?= e($t['name']) ?></a>
                        <span class="muted small"><?= e($t['slug']) ?><?= $t['hex'] ? ' · ' . e($t['hex']) : '' ?></span></span>
                    </td>
                    <td class="col-num"><?= (int)($usage[$t['id']] ?? 0) ?></td>
                    <td class="col-status"><span class="adm-pill<?= $t['is_active'] ? ' adm-pill--published' : '' ?>"><?= $t['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td class="col-actions">
                        <a class="adm-btn adm-btn--sm" href="<?= e($self . '&edit=' . (int)$t['id']) ?>">Edit</a>
                        <button class="adm-btn adm-btn--sm adm-btn--danger" type="submit" form="term-delete-form" name="delete" value="<?= (int)$t['id'] ?>"
                            data-confirm-click="Delete “<?= e($t['name']) ?>”?<?= ($usage[$t['id']] ?? 0) ? ' It is removed from ' . (int)$usage[$t['id']] . ' product(s).' : '' ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php admin_footer(); ?>
