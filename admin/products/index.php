<?php
/**
 * All Products – listing with search, filters, pagination, bulk actions
 * and drag-and-drop display order.
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';

$f = [
    'q'        => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100),
    'store'    => in_array($_GET['store'] ?? '', ['work', 'learn', 'store_only'], true) ? $_GET['store'] : '',
    'category' => (int)($_GET['category'] ?? 0),
    'brand'    => (int)($_GET['brand'] ?? 0),
    'stock'    => in_array($_GET['stock'] ?? '', ['in_stock', 'out_of_stock', 'made_to_order', 'low'], true) ? $_GET['stock'] : '',
    'status'   => in_array($_GET['status'] ?? '', ['published', 'draft', 'hidden', 'featured'], true) ? $_GET['status'] : '',
];
$filtered = (bool)array_filter($f);
$per = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
[$rows, $total] = ProductRepository::adminList($f, $page, $per);
$pages = max(1, (int)ceil($total / $per));
$qs = static fn(array $extra = []) => '?' . http_build_query(array_filter($extra + $f));
$counts = Database::one("SELECT COUNT(*) AS total, SUM(status = 'published') AS published, SUM(status = 'draft') AS draft, SUM(status = 'hidden') AS hidden FROM products");

admin_header('All Products', 'p:all');
?>
<header class="adm-head">
    <div>
        <h1>All Products</h1>
        <p class="muted"><?= (int)$counts['total'] ?> products · <?= (int)$counts['published'] ?> published · <?= (int)$counts['draft'] ?> draft · <?= (int)$counts['hidden'] ?> hidden</p>
    </div>
    <a class="adm-btn" href="edit.php">+ Add New Product</a>
</header>

<form class="adm-card adm-filters" method="get" action="">
    <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search name or SKU…" aria-label="Search products">
    <?= p_select('store', ['work' => 'Work', 'learn' => 'Learn', 'store_only' => 'Store only'], $f['store'], 'All storefronts', 'flt_store') ?>
    <?= p_select('category', Catalog::options('category'), $f['category'] ?: '', 'All categories', 'flt_category') ?>
    <?= p_select('brand', Catalog::options('brand'), $f['brand'] ?: '', 'All brands', 'flt_brand') ?>
    <?= p_select('stock', ['in_stock' => 'In stock', 'out_of_stock' => 'Out of stock', 'made_to_order' => 'Made to order', 'low' => 'Low stock'], $f['stock'], 'Any stock', 'flt_stock') ?>
    <?= p_select('status', ['published' => 'Published', 'draft' => 'Draft', 'hidden' => 'Hidden', 'featured' => '★ Featured'], $f['status'], 'Any status', 'flt_status') ?>
    <button class="adm-btn adm-btn--sm" type="submit">Filter</button>
    <?php if ($filtered): ?><a class="adm-link" href="./">Clear</a><?php endif; ?>
</form>

<form method="post" action="action.php" id="bulk-form" class="adm-card adm-card--flush">
    <?= csrf_field() ?>
    <div class="adm-card__head adm-bulkbar">
        <div class="adm-bulkbar__left">
            <label class="sr-only" for="bulk-action">Bulk action</label>
            <select id="bulk-action" name="bulk">
                <option value="">Bulk actions…</option>
                <option value="publish">Publish</option>
                <option value="draft">Set to draft</option>
                <option value="hide">Hide</option>
                <option value="feature">Mark featured</option>
                <option value="unfeature">Remove featured</option>
                <option value="new">Mark new arrival</option>
                <option value="unnew">Remove new arrival</option>
                <option value="bestseller">Mark best seller</option>
                <option value="unbestseller">Remove best seller</option>
                <option value="work">Add to Work</option>
                <option value="unwork">Remove from Work</option>
                <option value="learn">Add to Learn</option>
                <option value="unlearn">Remove from Learn</option>
                <option value="delete">Delete</option>
            </select>
            <button class="adm-btn adm-btn--sm adm-btn--ghost" type="submit" data-bulk-apply>Apply</button>
        </div>
        <p class="muted small"><?= $filtered ? 'Clear the filters to drag products into a new order.' : 'Drag ⋮⋮ to change the display order on the website.' ?></p>
    </div>
    <?php if (!$rows): ?>
        <p class="adm-empty"><?= $filtered ? 'No products match these filters.' : 'No products yet – <a href="edit.php">add the first one</a>.' ?></p>
    <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table adm-table--products">
        <thead><tr>
            <th class="col-check"><input type="checkbox" data-check-all aria-label="Select all"></th>
            <?php if (!$filtered): ?><th class="col-drag"><span class="sr-only">Order</span></th><?php endif; ?>
            <th class="col-thumb"></th><th>Product</th><th>SKU</th><th>Category / Type</th><th>Brand</th><th>Storefronts</th>
            <th class="col-num">Retail</th><th class="col-num">Agent</th><th>Stock</th><th>Status</th><th class="col-actions"></th>
        </tr></thead>
        <tbody<?= $filtered ? '' : ' data-sortable data-sortable-save="action.php" data-sortable-table="products"' ?> data-csrf="<?= e(csrf_token()) ?>">
        <?php foreach ($rows as $r):
            $avail = ProductRepository::availability($r);
            $low = $r['stock_manage'] && (int)$r['stock_qty'] <= (int)$r['low_stock_threshold']; ?>
            <tr data-sortable-item data-id="<?= (int)$r['id'] ?>" class="<?= $r['status'] !== 'published' ? 'is-off' : '' ?>">
                <td class="col-check"><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" data-bulk-item aria-label="Select <?= e($r['name']) ?>"></td>
                <?php if (!$filtered): ?><td class="col-drag"><button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move <?= e($r['name']) ?>">⋮⋮</button></td><?php endif; ?>
                <td class="col-thumb"><?php if ($r['image']): ?><img src="<?= e(upload_url($r['image'])) ?>" alt="" loading="lazy"><?php endif; ?></td>
                <td><a class="adm-strong" href="edit.php?id=<?= (int)$r['id'] ?>"><?= e($r['name']) ?></a>
                    <?php if ($r['is_featured']): ?><span class="adm-star" title="Featured">★</span><?php endif; ?>
                    <?php if ($r['is_new']): ?><span class="adm-pill adm-pill--tiny">New</span><?php endif; ?>
                    <?php if ($r['is_bestseller']): ?><span class="adm-pill adm-pill--tiny">Best seller</span><?php endif; ?></td>
                <td class="small"><?= e($r['sku']) ?></td>
                <td class="small"><?= e(trim(($r['category_name'] ?? '') . ($r['subcategory_name'] ? ' › ' . $r['subcategory_name'] : ''))) ?: '–' ?><br><span class="muted"><?= e($r['type_name'] ?? '') ?></span></td>
                <td class="small"><?= e($r['brand_name'] ?? '–') ?></td>
                <td><?= p_store_badges($r['stores']) ?></td>
                <td class="col-num small"><?= e(money($r['retail_price'], $r['currency'])) ?: '–' ?><?php if ($r['sale_price'] !== null): ?><br><span class="muted">Sale <?= e(money($r['sale_price'], $r['currency'])) ?></span><?php endif; ?></td>
                <td class="col-num small"><?= e(money($r['agent_price'], $r['currency'])) ?: '–' ?></td>
                <td class="small"><span class="adm-stock adm-stock--<?= e($avail['key']) ?>"><?= e($avail['label']) ?></span>
                    <?php if ($r['stock_manage']): ?><br><span class="<?= $low ? 'adm-low' : 'muted' ?>"><?= (int)$r['stock_qty'] ?> in stock<?= $low ? ' – low' : '' ?></span><?php endif; ?></td>
                <td><span class="adm-pill adm-pill--<?= e($r['status']) ?>"><?= e(ProductRepository::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
                <td class="col-actions">
                    <a class="adm-btn adm-btn--sm" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                    <button class="adm-btn adm-btn--sm adm-btn--ghost" type="submit" name="single" value="duplicate:<?= (int)$r['id'] ?>" formnovalidate title="Duplicate">Copy</button>
                    <button class="adm-btn adm-btn--sm adm-btn--danger" type="submit" name="single" value="delete:<?= (int)$r['id'] ?>" data-confirm-click="Delete “<?= e($r['name']) ?>”? This cannot be undone.">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</form>

<?php if ($pages > 1): ?>
<nav class="adm-pager" aria-label="Pages">
    <?php for ($n = 1; $n <= $pages; $n++): ?>
        <?php if ($n === $page): ?><span class="is-current"><?= $n ?></span><?php else: ?><a href="<?= e($qs(['page' => $n])) ?>"><?= $n ?></a><?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php admin_footer(); ?>
