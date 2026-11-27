<?php
/**
 * POST-only product actions: duplicate, delete, bulk changes and
 * drag-and-drop ordering (products, list terms, families).
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';
admin_post();

$back = $_SERVER['HTTP_REFERER'] ?? url('admin/products/');
if (!str_contains($back, '/admin/') || parse_url($back, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? parse_url($back, PHP_URL_HOST))) {
    $back = url('admin/products/');
}

/* ---- drag-and-drop order (called by admin.js, answers JSON) ---- */
if (isset($_POST['reorder'])) {
    header('Content-Type: application/json');
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])))));
    $table = (string)$_POST['reorder'];
    if ($table === 'products') {
        // Keep the positions of products not on this page; reorder only the moved ones.
        $all = array_map('intval', array_column(Database::all('SELECT id FROM products ORDER BY sort_order, id DESC'), 'id'));
        $slots = [];
        foreach ($ids as $pid) {
            $pos = array_search($pid, $all, true);
            if ($pos !== false) $slots[] = $pos;
        }
        sort($slots);
        $moved = array_values(array_filter($ids, static fn($i) => in_array($i, $all, true)));
        foreach ($slots as $k => $pos) $all[$pos] = $moved[$k];
        $sql = 'UPDATE products SET sort_order = ? WHERE id = ?';
    } elseif ($table === 'terms' || $table === 'families') {
        $all = $ids;
        $sql = $table === 'terms' ? 'UPDATE product_terms SET sort_order = ? WHERE id = ?' : 'UPDATE product_families SET sort_order = ? WHERE id = ?';
    } elseif ($table === 'family_members') {
        $all = $ids;
        $sql = 'UPDATE products SET family_order = ? WHERE id = ?';
    } else {
        http_response_code(400);
        exit(json_encode(['ok' => false]));
    }
    $pdo = Database::pdo();
    $pdo->beginTransaction();
    foreach ($all as $i => $rid) {
        Database::run($sql, [$i + 1, $rid]);
    }
    $pdo->commit();
    exit(json_encode(['ok' => true]));
}

/* ---- single row: duplicate / delete ---- */
if (!empty($_POST['single']) && preg_match('/^(duplicate|delete):(\d+)$/', (string)$_POST['single'], $m)) {
    $pid = (int)$m[2];
    if ($m[1] === 'duplicate') {
        $new = ProductRepository::duplicate($pid);
        if ($new) {
            flash('success', 'Copy created as a draft – check the SKU and name.');
            redirect(url('admin/products/edit.php?id=' . $new));
        }
    } else {
        ProductRepository::delete($pid);
        flash('success', 'Product deleted.');
    }
    redirect($back);
}

/* ---- bulk actions ---- */
$ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])))));
$action = (string)($_POST['bulk'] ?? '');
if (!$ids || $action === '') {
    flash('error', 'Choose an action and tick at least one product.');
    redirect($back);
}
$in = implode(',', $ids);
$set = [
    'publish' => "status = 'published'", 'draft' => "status = 'draft'", 'hide' => "status = 'hidden'",
    'feature' => 'is_featured = 1', 'unfeature' => 'is_featured = 0',
    'new' => 'is_new = 1', 'unnew' => 'is_new = 0',
    'bestseller' => 'is_bestseller = 1', 'unbestseller' => 'is_bestseller = 0',
];
if (isset($set[$action])) {
    Database::run("UPDATE products SET {$set[$action]}, updated_at = NOW() WHERE id IN ($in)");
} elseif (in_array($action, ['work', 'learn'], true)) {
    foreach ($ids as $pid) Database::run('INSERT IGNORE INTO product_storefronts (product_id, storefront) VALUES (?, ?)', [$pid, $action]);
} elseif (in_array($action, ['unwork', 'unlearn'], true)) {
    Database::run("DELETE FROM product_storefronts WHERE storefront = ? AND product_id IN ($in)", [substr($action, 2)]);
} elseif ($action === 'delete') {
    foreach ($ids as $pid) ProductRepository::delete($pid);
} else {
    flash('error', 'Unknown action.');
    redirect($back);
}
flash('success', count($ids) . ' product' . (count($ids) === 1 ? '' : 's') . ' updated.');
redirect($back);
