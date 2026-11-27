<?php
/**
 * Inventory Management – stock for every product in one table.
 * Stock is shared by the Work, Learn and Store storefronts.
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $n = 0;
    foreach ((array)($_POST['inv'] ?? []) as $pid => $r) {
        $pid = (int)$pid;
        if ($pid <= 0 || !is_array($r)) continue;
        ProductRepository::update($pid, [
            'stock_manage'        => !empty($r['manage']) ? 1 : 0,
            'stock_qty'           => max(0, min(9999999, (int)($r['qty'] ?? 0))),
            'stock_status'        => array_key_exists($r['status'] ?? '', ProductRepository::STOCK) ? $r['status'] : 'in_stock',
            'low_stock_threshold' => max(0, (int)($r['low'] ?? 5)),
            'lead_time'           => mb_substr(trim((string)($r['lead'] ?? '')), 0, 150) ?: null,
        ]);
        $n++;
    }
    p_done([], 'Inventory saved for ' . $n . ' product' . ($n === 1 ? '' : 's') . '.', 'inventory.php' . (!empty($_GET['low']) ? '?low=1' : ''));
}

$lowOnly = !empty($_GET['low']);
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100);
[$rows] = ProductRepository::adminList(['q' => $q, 'stock' => $lowOnly ? 'low' : ''], 1, 500);
$lowCount = (int)Database::value('SELECT COUNT(*) FROM products WHERE stock_manage = 1 AND stock_qty <= low_stock_threshold');

admin_header('Inventory Management', 'p:inventory');
?>
<nav class="adm-crumbs"><a href="./">Products</a> / Inventory Management</nav>
<header class="adm-head">
    <div>
        <h1>Inventory Management</h1>
        <p class="muted">Stock is shared by Work, Learn and Store. With “Manage stock” on, a product shows Out of Stock automatically at 0.</p>
    </div>
    <?php if ($lowCount): ?><a class="adm-btn adm-btn--ghost" href="?low=1">⚠ <?= $lowCount ?> low on stock</a><?php endif; ?>
</header>

<form class="adm-card adm-filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or SKU…" aria-label="Search">
    <?= p_check('low', 'Low stock only', $lowOnly) ?>
    <button class="adm-btn adm-btn--sm" type="submit">Filter</button>
    <?php if ($q !== '' || $lowOnly): ?><a class="adm-link" href="inventory.php">Clear</a><?php endif; ?>
</form>

<form method="post" class="adm-form adm-card adm-card--flush">
    <?= csrf_field() ?>
    <?php if (!$rows): ?>
    <p class="adm-empty">No products found.</p>
    <?php else: ?>
    <div class="adm-table-wrap">
    <table class="adm-table adm-table--inventory">
        <thead><tr><th class="col-thumb"></th><th>Product</th><th>SKU</th><th>Manage stock</th><th>Quantity</th><th>Low alert at</th><th>Availability</th><th>Lead time</th><th>Shows as</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $low = $r['stock_manage'] && (int)$r['stock_qty'] <= (int)$r['low_stock_threshold'];
            $a = ProductRepository::availability($r);
            $n = 'inv[' . (int)$r['id'] . ']'; ?>
            <tr class="<?= $low ? 'is-low' : '' ?>">
                <td class="col-thumb"><?php if ($r['image']): ?><img src="<?= e(upload_url($r['image'])) ?>" alt="" loading="lazy"><?php endif; ?></td>
                <td><a class="adm-strong" href="edit.php?id=<?= (int)$r['id'] ?>&amp;tab=visibility"><?= e($r['name']) ?></a><?php if ($low): ?><br><span class="adm-low">Low stock</span><?php endif; ?></td>
                <td class="small"><?= e($r['sku']) ?></td>
                <td><input type="checkbox" name="<?= $n ?>[manage]" value="1"<?= $r['stock_manage'] ? ' checked' : '' ?> aria-label="Manage stock for <?= e($r['name']) ?>"></td>
                <td><input class="adm-num" type="number" min="0" name="<?= $n ?>[qty]" value="<?= (int)$r['stock_qty'] ?>" aria-label="Quantity"></td>
                <td><input class="adm-num" type="number" min="0" name="<?= $n ?>[low]" value="<?= (int)$r['low_stock_threshold'] ?>" aria-label="Low stock alert"></td>
                <td><?= p_select($n . '[status]', ProductRepository::STOCK, $r['stock_status'], '', 'inv_s_' . (int)$r['id']) ?></td>
                <td><input type="text" name="<?= $n ?>[lead]" value="<?= e((string)$r['lead_time']) ?>" maxlength="150" placeholder="e.g. 4–6 weeks" aria-label="Lead time"></td>
                <td><span class="adm-stock adm-stock--<?= e($a['key']) ?>"><?= e($a['label']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div class="adm-actions adm-actions--pad"><button class="adm-btn" type="submit">Save inventory</button></div>
    <?php endif; ?>
</form>
<?php admin_footer(); ?>
