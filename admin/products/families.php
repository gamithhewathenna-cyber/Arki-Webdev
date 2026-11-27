<?php
/**
 * Product families / collections (e.g. Luna Collection) and their members.
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';

$editId = (int)($_GET['edit'] ?? 0);
$editing = $editId ? Catalog::family($editId) : null;
$self = 'families.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $errors = [];

    if (!empty($_POST['delete'])) {
        $f = Catalog::family((int)$_POST['delete']);
        if ($f) {
            Database::run('UPDATE products SET family_id = NULL WHERE family_id = ?', [$f['id']]);
            Database::run('DELETE FROM product_families WHERE id = ?', [$f['id']]);
            if ($f['image']) Uploader::deleteIfUnused($f['image']);
            p_done([], '“' . $f['name'] . '” deleted. Its products are kept.', $self);
        }
        redirect($self);
    }

    $id = (int)($_POST['id'] ?? 0);
    $row = $id ? Catalog::family($id) : null;
    $name = p_str('name', 150);
    if ($name === '') {
        p_done(['Name is required.'], '', $self . ($row ? '?edit=' . $id : ''));
    }
    $slugIn = p_str('slug', 150);
    $slug = Catalog::uniqueSlug('product_families', $slugIn !== '' ? $slugIn : $name, $row ? $id : null);
    $image = $row['image'] ?? null;
    $oldImage = null;
    if ($new = p_image_upload('img_family', P_CROP['family'], $errors, 'Family image')) {
        $oldImage = $image;
        $image = $new;
    } elseif (!empty($_POST['remove']['image']) && $image) {
        $oldImage = $image;
        $image = null;
    }
    $vals = [$name, $slug, p_str('description', 2000), $image, !empty($_POST['is_active']) ? 1 : 0];
    if ($row) {
        Database::run('UPDATE product_families SET name = ?, slug = ?, description = ?, image = ?, is_active = ?, updated_at = NOW() WHERE id = ?', array_merge($vals, [$id]));
    } else {
        $next = (int)Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_families');
        Database::run('INSERT INTO product_families (name, slug, description, image, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())', array_merge($vals, [$next]));
        $id = Database::lastId();
    }
    if ($oldImage) Uploader::deleteIfUnused($oldImage);

    // Members: order as listed, removed ones leave the family, added one joins at the end.
    if ($row) {
        $order = 0;
        foreach (array_map('intval', (array)($_POST['members'] ?? [])) as $pid) {
            if (!empty($_POST['remove_member'][$pid])) {
                Database::run('UPDATE products SET family_id = NULL WHERE id = ? AND family_id = ?', [$pid, $id]);
                continue;
            }
            Database::run('UPDATE products SET family_order = ? WHERE id = ? AND family_id = ?', [++$order, $pid, $id]);
        }
        $add = (int)($_POST['add_member'] ?? 0);
        if ($add && ProductRepository::adminFind($add)) {
            Database::run('UPDATE products SET family_id = ?, family_order = ? WHERE id = ?', [$id, ++$order, $add]);
        }
    }
    p_done($errors, 'Family “' . $name . '” saved.', $self . '?edit=' . $id);
}

$families = Database::all('SELECT f.*, (SELECT COUNT(*) FROM products p WHERE p.family_id = f.id) AS n FROM product_families f ORDER BY f.sort_order, f.name');
$members = $editing ? Database::all('SELECT id, name, sku, status, image FROM products WHERE family_id = ? ORDER BY family_order, name', [$editing['id']]) : [];

admin_header('Product Families', 'p:families');
?>
<nav class="adm-crumbs"><a href="./">Products</a> / Product Families</nav>
<header class="adm-head">
    <div>
        <h1>Product Families</h1>
        <p class="muted">Collections such as the Luna Collection. Each member shows the others under “Family Of Products” on its product page.</p>
    </div>
</header>

<div class="adm-split">
    <form method="post" enctype="multipart/form-data" class="adm-card adm-form" action="<?= e($self . ($editing ? '?edit=' . (int)$editing['id'] : '')) ?>">
        <?= csrf_field() ?>
        <h2><?= $editing ? 'Edit family' : 'Add family' ?></h2>
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <?= p_field('Name', p_input('name', $editing['name'] ?? '', ['max' => 150, 'required' => true, 'placeholder' => 'e.g. Luna Collection']), '', 'f_name', true) ?>
        <?= p_field('Web name', p_input('slug', $editing['slug'] ?? '', ['max' => 150, 'placeholder' => 'Created from the name']), '', 'f_slug') ?>
        <?= p_field('Description', p_textarea('description', $editing['description'] ?? '', 3, ['max' => 2000]), 'Shown under the “Family Of Products” heading.', 'f_description') ?>
        <div class="field"><label for="img_family">Family image (optional)</label>
            <?= AdminForm::imageInput('img_family', 'img_family', P_CROP['family'], (string)($editing['image'] ?? ''), 'remove[image]', 'Family image') ?>
            <p class="help">A product's own collection image (Images & Media tab) is shown on its page.</p><?= AdminForm::imageHelp(P_CROP['family']) ?></div>
        <div class="field"><?= p_check('is_active', 'Show this family on product pages', (bool)($editing['is_active'] ?? true)) ?></div>

        <?php if ($editing): ?>
        <h2>Products in this family</h2>
        <?php if (!$members): ?><p class="muted small">No products yet.</p><?php endif; ?>
        <ul class="rep rep--compact" data-sortable>
            <?php foreach ($members as $m): ?>
            <li class="rep__row" data-sortable-item>
                <button type="button" class="adm-handle" data-drag-handle aria-label="Move <?= e($m['name']) ?>">⋮⋮</button>
                <input type="hidden" name="members[]" value="<?= (int)$m['id'] ?>">
                <?php if ($m['image']): ?><img class="rep__thumb" src="<?= e(upload_url($m['image'])) ?>" alt=""><?php endif; ?>
                <span class="rep__label"><a href="edit.php?id=<?= (int)$m['id'] ?>&amp;tab=family"><?= e($m['name']) ?></a> <span class="muted">(<?= e($m['sku']) ?>)</span>
                    <?php if ($m['status'] !== 'published'): ?><span class="adm-pill adm-pill--<?= e($m['status']) ?>"><?= e(ProductRepository::STATUSES[$m['status']]) ?></span><?php endif; ?></span>
                <label class="check check--danger"><input type="checkbox" name="remove_member[<?= (int)$m['id'] ?>]" value="1"> Remove</label>
            </li>
            <?php endforeach; ?>
        </ul>
        <?= p_field('Add a product', p_select('add_member', array_diff_key(ProductRepository::adminOptions(), array_flip(array_column($members, 'id'))), '', 'Choose a product…'), 'A product belongs to one family; adding it here moves it from any other family.', 'f_add_member') ?>
        <?php endif; ?>

        <div class="adm-actions">
            <button class="adm-btn" type="submit"><?= $editing ? 'Save family' : 'Add family' ?></button>
            <?php if ($editing): ?><a class="adm-link" href="<?= e($self) ?>">Cancel</a><?php endif; ?>
        </div>
    </form>

    <div class="adm-card adm-card--flush">
        <?php if (!$families): ?>
        <p class="adm-empty">No families yet.</p>
        <?php else: ?>
        <form method="post" action="<?= e($self) ?>" id="family-delete-form"><?= csrf_field() ?></form>
        <table class="adm-table">
            <thead><tr><th class="col-drag"></th><th>Family</th><th class="col-num">Products</th><th class="col-status">Status</th><th class="col-actions"></th></tr></thead>
            <tbody data-sortable data-sortable-save="action.php" data-sortable-table="families" data-csrf="<?= e(csrf_token()) ?>">
            <?php foreach ($families as $f): ?>
                <tr data-sortable-item data-id="<?= (int)$f['id'] ?>" class="<?= $f['is_active'] ? '' : 'is-off' ?>">
                    <td class="col-drag"><button type="button" class="adm-handle" data-drag-handle aria-label="Move <?= e($f['name']) ?>">⋮⋮</button></td>
                    <td><a class="adm-strong" href="<?= e($self . '?edit=' . (int)$f['id']) ?>"><?= e($f['name']) ?></a></td>
                    <td class="col-num"><?= (int)$f['n'] ?></td>
                    <td class="col-status"><span class="adm-pill<?= $f['is_active'] ? ' adm-pill--published' : '' ?>"><?= $f['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
                    <td class="col-actions">
                        <a class="adm-btn adm-btn--sm" href="<?= e($self . '?edit=' . (int)$f['id']) ?>">Edit</a>
                        <button class="adm-btn adm-btn--sm adm-btn--danger" type="submit" form="family-delete-form" name="delete" value="<?= (int)$f['id'] ?>" data-confirm-click="Delete “<?= e($f['name']) ?>”? Its products are kept.">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php admin_footer(); ?>
