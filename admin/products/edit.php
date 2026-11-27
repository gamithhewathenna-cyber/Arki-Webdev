<?php
/**
 * Add / edit a product. Each tab is its own form and saves only its own fields.
 */
declare(strict_types=1);
require __DIR__ . '/_lib.php';

$id  = (int)($_GET['id'] ?? 0);
$tab = isset(P_TABS[$_GET['tab'] ?? '']) ? (string)$_GET['tab'] : 'general';
$p   = $id ? ProductRepository::adminFind($id) : null;
if ($id && !$p) {
    http_response_code(404);
    exit('Product not found.');
}
if (!$p) {
    $tab = 'general';
}
$self = 'edit.php?id=' . $id . '&tab=' . $tab;

/* =====================================================================
 *  SAVE
 * ===================================================================== */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $errors = [];
    $pdo = Database::pdo();

    switch ($tab) {

        /* ---------------- General ---------------- */
        case 'general':
            $name = p_str('name', 200);
            $sku  = p_str('sku', 80);
            if ($name === '') $errors[] = 'Product name is required.';
            if ($sku === '') {
                $errors[] = 'Product SKU / code is required.';
            } elseif (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 _\-.\/]*$/', $sku)) {
                $errors[] = 'SKU may only contain letters, numbers, spaces and - _ . /';
            } elseif (ProductRepository::skuTaken($sku, $id)) {
                $errors[] = 'Another product already uses the SKU “' . $sku . '”.';
            }
            if ($errors && !$p) {
                // Can't create without a valid name + SKU.
                foreach ($errors as $er) flash('error', $er);
                $_SESSION['p_old'] = $_POST;
                redirect('edit.php');
            }
            if (!$p) {
                $id = ProductRepository::create($name, $sku);
                $p = ProductRepository::adminFind($id);
            }
            $slugIn = p_str('slug', 200);
            $cols = [
                'short_description' => p_str('short_description', 2000),
                'description'       => HtmlSanitizer::clean(p_str('description', 100000)),
                'suitable_for'      => p_str('suitable_for', 2000),
                'brand_id'          => p_term($_POST['brand_id'] ?? '', 'brand'),
                'type_id'           => p_term($_POST['type_id'] ?? '', 'type'),
                'env_rating_id'     => p_term($_POST['env_rating_id'] ?? '', 'env_rating'),
            ];
            if ($name !== '') $cols['name'] = $name;
            if ($sku !== '' && !$errors) $cols['sku'] = $sku;
            $cols['slug'] = Catalog::uniqueSlug('products', $slugIn !== '' ? $slugIn : ($name ?: $p['name']), $id);
            ProductRepository::update($id, $cols);

            // Storefronts (Main Store always includes every published product).
            Database::run('DELETE FROM product_storefronts WHERE product_id = ?', [$id]);
            $stores = ['store'];
            foreach ((array)($_POST['storefronts'] ?? []) as $sf) {
                if (in_array($sf, ['work', 'learn'], true)) {
                    Database::run('INSERT IGNORE INTO product_storefronts (product_id, storefront) VALUES (?, ?)', [$id, $sf]);
                    $stores[] = $sf;
                }
            }

            // Categories: one tree per storefront; only the storefronts the product is in are kept.
            Database::run("DELETE l FROM product_term_links l JOIN product_terms t ON t.id = l.term_id WHERE l.product_id = ? AND t.taxonomy LIKE 'cat\\_%'", [$id]);
            foreach (array_unique(array_map('intval', (array)($_POST['categories'] ?? []))) as $tid) {
                $t = Catalog::term($tid);
                if ($t && Catalog::isCategoryTax($t['taxonomy']) && in_array(Catalog::TAXONOMIES[$t['taxonomy']]['store'], $stores, true)) {
                    Database::run('INSERT IGNORE INTO product_term_links (product_id, term_id) VALUES (?, ?)', [$id, $tid]);
                }
            }
            unset($_SESSION['p_old']);
            p_done($errors, 'General information saved.', 'edit.php?id=' . $id . '&tab=general');

        /* ---------------- Pricing ---------------- */
        case 'pricing':
            $retail = p_money($_POST['retail_price'] ?? '', 'Retail price', $errors);
            $agent  = p_money($_POST['agent_price'] ?? '', 'Agent / dealer price', $errors);
            $sale   = p_money($_POST['sale_price'] ?? '', 'Sale price', $errors);
            if ($sale !== null && $retail !== null && (float)$sale >= (float)$retail) {
                $errors[] = 'The sale price should be lower than the retail price – it is saved, but no discount will show.';
            }
            $currency = array_key_exists($_POST['currency'] ?? '', ProductRepository::CURRENCIES) ? $_POST['currency'] : 'AUD';
            ProductRepository::update($id, [
                'retail_price' => $retail, 'agent_price' => $agent, 'sale_price' => $sale,
                'currency' => $currency, 'price_from' => !empty($_POST['price_from']) ? 1 : 0,
            ]);
            p_done($errors, 'Pricing saved.', $self);

        /* ---------------- Images & media ---------------- */
        case 'media':
            $cols = [];
            $old = [];
            // Single images
            foreach (['image' => ['img_main', 'main', 'Main product image'], 'video_thumb' => ['img_video_thumb', 'video', 'Video thumbnail'], 'collection_image' => ['img_collection', 'collection', 'Collection image']] as $col => [$input, $crop, $label]) {
                $new = p_image_upload($input, P_CROP[$crop], $errors, $label);
                if ($new !== null) {
                    $cols[$col] = $new;
                    if ($p[$col]) $old[] = $p[$col];
                } elseif (!empty($_POST['remove'][$col]) && $p[$col]) {
                    $cols[$col] = null;
                    $old[] = $p[$col];
                }
            }
            // Gallery: order, alt text, replace, delete
            $existing = array_column(ProductRepository::images($id, 'gallery'), 'path', 'id');
            $order = 0;
            foreach ((array)($_POST['gallery'] ?? []) as $gid => $g) {
                $gid = (int)$gid;
                if (!isset($existing[$gid])) continue;
                if (!empty($g['delete'])) {
                    Database::run('DELETE FROM product_images WHERE id = ? AND product_id = ?', [$gid, $id]);
                    $old[] = $existing[$gid];
                    continue;
                }
                $path = $existing[$gid];
                if ($new = p_image_upload('gallery_replace_' . $gid, P_CROP['gallery'], $errors, 'Gallery image')) {
                    $old[] = $path;
                    $path = $new;
                }
                Database::run('UPDATE product_images SET path = ?, alt = ?, sort_order = ? WHERE id = ? AND product_id = ?',
                    [$path, mb_substr(trim((string)($g['alt'] ?? '')), 0, 250), ++$order, $gid, $id]);
            }
            foreach ($_FILES as $field => $f) {
                if (str_starts_with((string)$field, 'gallery_new_') && ($path = p_image_upload((string)$field, P_CROP['gallery'], $errors, 'New gallery image'))) {
                    Database::run('INSERT INTO product_images (product_id, kind, path, alt, sort_order) VALUES (?, ?, ?, ?, ?)', [$id, 'gallery', $path, '', ++$order]);
                }
            }
            // Video: URL or uploaded file
            $vurl = p_str('video_url', 500);
            if ($vurl !== '' && !video_embed_url($vurl)) {
                $errors[] = 'Video URL: use a YouTube or Vimeo link (e.g. https://www.youtube.com/watch?v=…).';
                $vurl = (string)$p['video_url'];
            }
            $cols['video_url'] = $vurl !== '' ? $vurl : null;
            if (Uploader::has($_FILES['video_file'] ?? null)) {
                try {
                    $v = Uploader::file($_FILES['video_file'], 'video');
                    $cols['video_file'] = $v['path'];
                    if ($p['video_file']) $old[] = $p['video_file'];
                } catch (Throwable $ex) {
                    $errors[] = 'Video file: ' . $ex->getMessage();
                }
            } elseif (!empty($_POST['remove']['video_file']) && $p['video_file']) {
                $cols['video_file'] = null;
                $old[] = $p['video_file'];
            }
            ProductRepository::update($id, $cols);
            foreach ($old as $o) Uploader::deleteIfUnused($o);
            p_done($errors, 'Images & media saved.', $self);

        /* ---------------- Options & variations ---------------- */
        case 'options':
            $valid = [];
            foreach ((array)($_POST['opt'] ?? []) as $tid) {
                $t = Catalog::term((int)$tid);
                if ($t && !empty(Catalog::TAXONOMIES[$t['taxonomy']]['option'])) $valid[] = (int)$t['id'];
            }
            $pdo->beginTransaction();
            Database::run('DELETE l FROM product_term_links l JOIN product_terms t ON t.id = l.term_id WHERE l.product_id = ? AND t.taxonomy IN (\'size\', \'colour\', \'material\')', [$id]);
            foreach (array_unique($valid) as $tid) {
                Database::run('INSERT IGNORE INTO product_term_links (product_id, term_id) VALUES (?, ?)', [$id, $tid]);
            }
            $pdo->commit();

            $existing = [];
            foreach (ProductRepository::adminVariants($id) as $v) $existing[(int)$v['id']] = $v;
            $old = [];
            $order = 0;
            foreach ((array)($_POST['variants'] ?? []) as $key => $v) {
                $vid = (int)($v['id'] ?? 0);
                $row = $vid && isset($existing[$vid]) ? $existing[$vid] : null;
                if (!empty($v['delete'])) {
                    if ($row) {
                        Database::run('DELETE FROM product_variants WHERE id = ? AND product_id = ?', [$vid, $id]);
                        if ($row['image']) $old[] = $row['image'];
                    }
                    continue;
                }
                $size = p_term($v['size_id'] ?? '', 'size');
                $col  = p_term($v['colour_id'] ?? '', 'colour');
                $mat  = p_term($v['material_id'] ?? '', 'material');
                if (!$size && !$col && !$mat) {
                    $typed = trim(implode('', array_map(static fn($k) => is_string($v[$k] ?? null) ? $v[$k] : '', ['sku', 'retail_price', 'agent_price', 'stock_qty'])));
                    if ($row || $typed !== '') {
                        $errors[] = 'A variation needs at least a size, colour or material – one row was skipped.';
                    }
                    continue;
                }
                $label = 'Variation ' . ($order + 1);
                $vals = [
                    $size, $col, $mat,
                    mb_substr(trim((string)($v['sku'] ?? '')), 0, 80) ?: null,
                    p_money($v['retail_price'] ?? '', $label . ' retail price', $errors),
                    p_money($v['agent_price'] ?? '', $label . ' agent price', $errors),
                    ($v['stock_qty'] ?? '') === '' ? null : max(0, (int)$v['stock_qty']),
                    isset(ProductRepository::STOCK[$v['availability'] ?? '']) ? $v['availability'] : null,
                    ++$order,
                ];
                $image = $row['image'] ?? null;
                if ($new = p_image_upload('variant_img_' . $key, P_CROP['variant'], $errors, $label . ' image')) {
                    if ($image) $old[] = $image;
                    $image = $new;
                } elseif (!empty($v['remove_image']) && $image) {
                    $old[] = $image;
                    $image = null;
                }
                if ($row) {
                    Database::run('UPDATE product_variants SET size_id = ?, colour_id = ?, material_id = ?, sku = ?, retail_price = ?, agent_price = ?, stock_qty = ?, availability = ?, sort_order = ?, image = ? WHERE id = ? AND product_id = ?',
                        array_merge($vals, [$image, $vid, $id]));
                } else {
                    Database::run('INSERT INTO product_variants (size_id, colour_id, material_id, sku, retail_price, agent_price, stock_qty, availability, sort_order, image, product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        array_merge($vals, [$image, $id]));
                }
            }
            foreach ($old as $o) Uploader::deleteIfUnused($o);
            p_done($errors, 'Product options saved.', $self);

        /* ---------------- Specifications ---------------- */
        case 'specs':
            $pdo->beginTransaction();
            Database::run('DELETE FROM product_specs WHERE product_id = ?', [$id]);
            $order = 0;
            foreach ((array)($_POST['specs'] ?? []) as $s) {
                if (!empty($s['delete'])) continue;
                $n = mb_substr(trim((string)($s['name'] ?? '')), 0, 150);
                $v = mb_substr(trim(str_replace("\r\n", "\n", (string)($s['value'] ?? ''))), 0, 2000);
                if ($n === '' && $v === '') continue;
                if ($n === '') {
                    $errors[] = 'A specification row without a name was skipped.';
                    continue;
                }
                Database::run('INSERT INTO product_specs (product_id, name, value, sort_order) VALUES (?, ?, ?, ?)', [$id, $n, $v, ++$order]);
            }
            $pdo->commit();
            ProductRepository::update($id, ['suitable_for' => p_str('suitable_for', 2000)]);
            p_done($errors, 'Specifications saved.', $self);

        /* ---------------- Technical information ---------------- */
        case 'technical':
            ProductRepository::update($id, [
                'dimensions'        => p_str('dimensions', 2000),
                'weight'            => p_str('weight', 100),
                'technical_details' => p_str('technical_details', 5000),
                'assembly_info'     => p_str('assembly_info', 5000),
                'maintenance'       => p_str('maintenance', 5000),
            ]);
            $existing = array_column(ProductRepository::images($id, 'drawing'), 'path', 'id');
            $old = [];
            $order = 0;
            foreach ((array)($_POST['drawings'] ?? []) as $did => $d) {
                $did = (int)$did;
                if (!isset($existing[$did])) continue;
                if (!empty($d['delete'])) {
                    Database::run('DELETE FROM product_images WHERE id = ? AND product_id = ?', [$did, $id]);
                    $old[] = $existing[$did];
                    continue;
                }
                Database::run('UPDATE product_images SET alt = ?, sort_order = ? WHERE id = ? AND product_id = ?', [mb_substr(trim((string)($d['alt'] ?? '')), 0, 250), ++$order, $did, $id]);
            }
            foreach ($_FILES as $field => $f) {
                if (str_starts_with((string)$field, 'drawing_new_') && ($path = p_image_upload((string)$field, P_CROP['drawing'], $errors, 'Technical drawing'))) {
                    Database::run('INSERT INTO product_images (product_id, kind, path, alt, sort_order) VALUES (?, ?, ?, ?, ?)', [$id, 'drawing', $path, '', ++$order]);
                }
            }
            foreach ($old as $o) Uploader::deleteIfUnused($o);
            p_done($errors, 'Technical information saved.', $self);

        /* ---------------- Downloads ---------------- */
        case 'downloads':
            $existing = [];
            foreach (ProductRepository::downloads($id) as $d) $existing[(int)$d['id']] = $d;
            $old = [];
            $order = 0;
            foreach ((array)($_POST['downloads'] ?? []) as $key => $d) {
                $did = (int)($d['id'] ?? 0);
                $row = $did && isset($existing[$did]) ? $existing[$did] : null;
                if (!empty($d['delete'])) {
                    if ($row) {
                        Database::run('DELETE FROM product_downloads WHERE id = ? AND product_id = ?', [$did, $id]);
                        $old[] = $row['file_path'];
                    }
                    continue;
                }
                $title = mb_substr(trim((string)($d['title'] ?? '')), 0, 200);
                $type = array_key_exists($d['doc_type'] ?? '', ProductRepository::DOC_TYPES) ? $d['doc_type'] : 'other';
                $file = null;
                $failed = false;
                if (Uploader::has($_FILES['download_file_' . $key] ?? null)) {
                    try {
                        $file = Uploader::file($_FILES['download_file_' . $key], 'document');
                    } catch (Throwable $ex) {
                        $errors[] = ($title ?: 'Download') . ': ' . $ex->getMessage();
                        $failed = true;
                    }
                }
                if (!$row && !$file) {
                    if ($title !== '' && !$failed) $errors[] = '“' . $title . '” was not added – please choose a file.';
                    continue;
                }
                if ($title === '') $title = $file['name'] ?? $row['title'] ?? 'Document';
                if ($row) {
                    if ($file) $old[] = $row['file_path'];
                    Database::run('UPDATE product_downloads SET title = ?, doc_type = ?, sort_order = ?, file_path = ?, file_name = ?, file_size = ? WHERE id = ? AND product_id = ?',
                        [$title, $type, ++$order, $file['path'] ?? $row['file_path'], $file['name'] ?? $row['file_name'], $file['size'] ?? $row['file_size'], $did, $id]);
                } else {
                    Database::run('INSERT INTO product_downloads (product_id, title, doc_type, file_path, file_name, file_size, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$id, $title, $type, $file['path'], $file['name'], $file['size'], ++$order]);
                }
            }
            foreach ($old as $o) Uploader::deleteIfUnused($o);
            p_done($errors, 'Downloads saved.', $self);

        /* ---------------- Family ---------------- */
        case 'family':
            $fid = (int)($_POST['family_id'] ?? 0);
            $sections = ProductRepository::pageSections($p);
            $sections['family'] = !empty($_POST['show_family']) ? 1 : 0;
            ProductRepository::update($id, [
                'family_id'     => Catalog::family($fid) ? $fid : null,
                'family_order'  => max(0, (int)($_POST['family_order'] ?? 0)),
                'page_sections' => json_encode($sections),
            ]);
            p_done($errors, 'Product family saved.', $self);

        /* ---------------- Related ---------------- */
        case 'related':
            $mode = ($_POST['related_mode'] ?? '') === 'manual' ? 'manual' : 'auto';
            $sections = ProductRepository::pageSections($p);
            $sections['related'] = !empty($_POST['show_related']) ? 1 : 0;
            $pdo->beginTransaction();
            Database::run('DELETE FROM product_related WHERE product_id = ?', [$id]);
            $order = 0;
            foreach (array_unique(array_map('intval', (array)($_POST['related'] ?? []))) as $rid) {
                if ($rid > 0 && $rid !== $id && ProductRepository::adminFind($rid)) {
                    Database::run('INSERT IGNORE INTO product_related (product_id, related_id, sort_order) VALUES (?, ?, ?)', [$id, $rid, ++$order]);
                }
            }
            $pdo->commit();
            if ($mode === 'manual' && $order === 0) {
                $errors[] = 'Manual selection is on but no products are chosen – the section will be hidden.';
            }
            ProductRepository::update($id, ['related_mode' => $mode, 'page_sections' => json_encode($sections)]);
            p_done($errors, 'Related products saved.', $self);

        /* ---------------- Inventory & visibility ---------------- */
        case 'visibility':
            $status = array_key_exists($_POST['status'] ?? '', ProductRepository::STATUSES) ? $_POST['status'] : 'draft';
            $stock = array_key_exists($_POST['stock_status'] ?? '', ProductRepository::STOCK) ? $_POST['stock_status'] : 'in_stock';
            $sections = ProductRepository::pageSections($p);
            $sections['media'] = !empty($_POST['show_media']) ? 1 : 0;
            $sections['cta'] = !empty($_POST['show_cta']) ? 1 : 0;

            $cta = json_decode((string)$p['cta_override'], true) ?: [];
            $old = [];
            foreach (['tagline' => 200, 'heading' => 300, 'body' => 2000, 'primary_label' => 100, 'primary_url' => 500, 'secondary_label' => 100, 'secondary_url' => 500] as $k => $max) {
                $cta[$k] = p_str($k, $max, (array)($_POST['cta'] ?? []));
                if (str_ends_with($k, '_url') && $cta[$k] !== '' && safe_url($cta[$k]) === '#' && $cta[$k] !== '#') {
                    $errors[] = 'Call to action link: only web addresses, relative paths, mailto: and tel: are allowed.';
                    $cta[$k] = '';
                }
            }
            $cta['enabled'] = !empty($_POST['cta']['enabled']) ? 1 : 0;
            if ($new = p_image_upload('img_cta', P_CROP['cta'], $errors, 'Call to action image')) {
                if (!empty($cta['image'])) $old[] = $cta['image'];
                $cta['image'] = $new;
            } elseif (!empty($_POST['remove']['cta_image']) && !empty($cta['image'])) {
                $old[] = $cta['image'];
                $cta['image'] = '';
            }

            ProductRepository::update($id, [
                'status'              => $status,
                'is_featured'         => !empty($_POST['is_featured']) ? 1 : 0,
                'is_new'              => !empty($_POST['is_new']) ? 1 : 0,
                'is_bestseller'       => !empty($_POST['is_bestseller']) ? 1 : 0,
                'sort_order'          => max(0, (int)($_POST['sort_order'] ?? 0)),
                'stock_manage'        => !empty($_POST['stock_manage']) ? 1 : 0,
                'stock_qty'           => max(0, min(9999999, (int)($_POST['stock_qty'] ?? 0))),
                'stock_status'        => $stock,
                'low_stock_threshold' => max(0, (int)($_POST['low_stock_threshold'] ?? 5)),
                'lead_time'           => p_str('lead_time', 150) ?: null,
                'page_sections'       => json_encode($sections),
                'cta_override'        => json_encode($cta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            foreach ($old as $o) Uploader::deleteIfUnused($o);
            p_done($errors, 'Inventory & visibility saved.', $self);
    }
    redirect($self);
}

/* =====================================================================
 *  FORM
 * ===================================================================== */
$old = $_SESSION['p_old'] ?? null;
unset($_SESSION['p_old']);
$v = $p ?? [];
if ($old) {
    $v = array_merge($v, array_map(static fn($x) => is_string($x) ? $x : '', $old));
}
$val = static fn(string $k) => $v[$k] ?? '';
$title = $p ? $p['name'] : 'Add New Product';

admin_header($title, $p ? 'p:all' : 'p:new');
?>
<nav class="adm-crumbs"><a href="./">Products</a> / <?= $p ? e($p['name']) : 'New product' ?></nav>
<header class="adm-head">
    <div>
        <h1><?= e($title) ?></h1>
        <?php if ($p): ?>
        <p class="muted">SKU <?= e($p['sku']) ?> · <span class="adm-pill adm-pill--<?= e($p['status']) ?>"><?= e(ProductRepository::STATUSES[$p['status']] ?? $p['status']) ?></span>
            <?= $p['is_featured'] ? '<span class="adm-pill adm-pill--featured">★ Featured</span>' : '' ?></p>
        <?php else: ?>
        <p class="muted">Start with the general information. The other tabs open once the product is saved.</p>
        <?php endif; ?>
    </div>
    <?php if ($p): ?><a class="adm-btn adm-btn--ghost" href="<?= e(product_url($p['slug'])) ?>" target="_blank" rel="noopener">View on website ↗</a><?php endif; ?>
</header>
<?php p_tabs($p ? $id : null, $tab); ?>

<form method="post" enctype="multipart/form-data" class="adm-form" action="<?= e($p ? $self : 'edit.php') ?>">
<?= csrf_field() ?>
<?php switch ($tab):

case 'general':
    $stores = $p ? ProductRepository::storefronts($id) : (array)($old['storefronts'] ?? []);
    $chosenCats = $p ? ProductRepository::categoryIds($id) : array_map('intval', (array)($old['categories'] ?? [])); ?>
    <section class="adm-card">
        <h2>Product details</h2>
        <div class="adm-grid-2">
            <?= p_field('Product name', p_input('name', $val('name'), ['max' => 200, 'required' => true]), '', 'f_name', true) ?>
            <?= p_field('Product SKU / Product Code', p_input('sku', $val('sku'), ['max' => 80, 'required' => true, 'placeholder' => 'e.g. AE-A0136']), 'Must be unique.', 'f_sku', true) ?>
        </div>
        <?= p_field('Web address name', p_input('slug', $val('slug'), ['max' => 200, 'placeholder' => 'Created from the name']), 'Used in the product link: /product/?p=' . ($val('slug') ?: 'product-name') . '. Leave blank to create it from the name.', 'f_slug') ?>
        <div class="adm-grid-2">
            <?= p_field('Brand', p_select('brand_id', Catalog::options('brand'), $val('brand_id')), '', 'f_brand_id') ?>
            <?= p_field('Product type', p_select('type_id', Catalog::options('type'), $val('type_id')), '', 'f_type_id') ?>
            <?= p_field('Environmental rating', p_select('env_rating_id', Catalog::options('env_rating'), $val('env_rating_id')), '', 'f_env_rating_id') ?>
        </div>
        <p class="help">Lists are managed under Products → Lists: <a href="terms.php?tax=type">Product Types</a>, <a href="terms.php?tax=brand">Brands</a> and <a href="terms.php?tax=env_rating">Environmental Ratings</a>.</p>
    </section>

    <section class="adm-card">
        <h2>Storefronts &amp; categories</h2>
        <div class="adm-checks">
            <?= p_check('storefronts[]', 'Work', in_array('work', $stores, true), 'work', ' data-store-toggle="work"') ?>
            <?= p_check('storefronts[]', 'Learn', in_array('learn', $stores, true), 'learn', ' data-store-toggle="learn"') ?>
            <label class="check is-locked"><input type="checkbox" checked disabled> Main Store (always)</label>
        </div>
        <p class="help adm-card__intro">Every published product appears in the main Store. Tick Work and/or Learn to also show it there. Then choose its categories in each storefront – each storefront has its own category tree. One product – shared pricing and stock everywhere.</p>
        <div class="adm-cat-trees">
            <?php foreach (['work' => 'cat_work', 'learn' => 'cat_learn', 'store' => 'cat_store'] as $store => $tax):
                $tree = Catalog::terms($tax); ?>
            <fieldset class="adm-fieldset adm-cat-tree" data-store-cats="<?= $store ?>"<?= $store !== 'store' && !in_array($store, $stores, true) ? ' hidden' : '' ?>>
                <legend><?= e(Catalog::TAXONOMIES[$tax]['label']) ?> <a class="small" href="terms.php?tax=<?= $tax ?>">Edit list</a></legend>
                <?php if (!$tree): ?><p class="muted small">No categories yet.</p><?php endif; ?>
                <?php foreach ($tree as $c): ?>
                <label class="check<?= !empty($c['depth']) ? ' adm-cat-tree__child' : ' adm-cat-tree__parent' ?>">
                    <input type="checkbox" name="categories[]" value="<?= (int)$c['id'] ?>"<?= in_array((int)$c['id'], $chosenCats, true) ? ' checked' : '' ?>> <?= e($c['name']) ?>
                </label>
                <?php endforeach; ?>
            </fieldset>
            <?php endforeach; ?>
        </div>
        <p class="help">Tip: tick the subcategory (e.g. Seating › Task). The product then also shows under its main category.</p>
    </section>

    <section class="adm-card">
        <h2>Descriptions</h2>
        <?= p_field('Short description', p_textarea('short_description', $val('short_description'), 3, ['max' => 2000]), 'Shown under the product name.', 'f_short_description') ?>
        <?= p_field('Full description', p_textarea('description', $val('description'), 10, ['rich' => true]), 'Shown in the Description tab on the product page.', 'f_description') ?>
        <?= p_field('Suitable for', p_textarea('suitable_for', $val('suitable_for'), 2, ['max' => 2000]), 'Shown in the grey box beside the specification table, e.g. Living Rooms, Offices, Lounges.', 'f_suitable_for') ?>
    </section>
    <?php break;

case 'pricing': ?>
    <section class="adm-card">
        <h2>Pricing</h2>
        <div class="adm-grid-2">
            <?= p_field('Retail price (customer price)', p_input('retail_price', $val('retail_price'), ['step' => '0.01', 'placeholder' => 'e.g. 1950']), 'Shown on the website.', 'f_retail_price') ?>
            <?= p_field('Agent / dealer price', p_input('agent_price', $val('agent_price'), ['step' => '0.01']), 'Never shown on the website – visitors see “Login To View”. Ready for agent accounts in a later phase.', 'f_agent_price') ?>
            <?= p_field('Sale price (optional)', p_input('sale_price', $val('sale_price'), ['step' => '0.01']), 'When lower than the retail price, the retail price is shown crossed out.', 'f_sale_price') ?>
            <?= p_field('Currency', p_select('currency', ProductRepository::CURRENCIES, $val('currency') ?: 'AUD', ''), '', 'f_currency') ?>
        </div>
        <div class="field"><?= p_check('price_from', 'Show the price as “From $ …”', (bool)($v['price_from'] ?? 1)) ?></div>
    </section>
    <?php break;

case 'media':
    $gallery = ProductRepository::images($id, 'gallery'); ?>
    <section class="adm-card">
        <h2>Main product image</h2>
        <div class="field"><?= AdminForm::imageInput('img_main', 'img_main', P_CROP['main'], (string)$p['image'], 'remove[image]', 'Main product image') ?>
        <p class="help">Used on product cards and as the first image on the product page.</p><?= AdminForm::imageHelp(P_CROP['main']) ?></div>
    </section>

    <section class="adm-card">
        <h2>Gallery images <span class="muted">(<?= count($gallery) ?>)</span></h2>
        <p class="help adm-card__intro">Drag ⋮⋮ to reorder. Shown beside the main image on the product page.</p>
        <ul class="adm-gallery" data-sortable="grid">
            <?php foreach ($gallery as $g): ?>
            <li class="adm-gallery__item" data-sortable-item>
                <button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move image">⋮⋮</button>
                <input type="hidden" name="gallery[<?= (int)$g['id'] ?>][keep]" value="1">
                <?= AdminForm::imageInput('gallery_replace_' . (int)$g['id'], 'grep_' . (int)$g['id'], P_CROP['gallery'], $g['path'], null, 'Gallery image') ?>
                <input type="text" name="gallery[<?= (int)$g['id'] ?>][alt]" value="<?= e((string)$g['alt']) ?>" placeholder="Image description (alt text)" aria-label="Image description">
                <label class="check check--danger"><input type="checkbox" name="gallery[<?= (int)$g['id'] ?>][delete]" value="1"> Delete</label>
            </li>
            <?php endforeach; ?>
        </ul>
        <div data-repeater>
            <ul class="adm-gallery adm-gallery--new" data-repeater-list></ul>
            <template data-repeater-template>
                <li class="adm-gallery__item is-new" data-repeater-row>
                    <?= AdminForm::imageInput('gallery_new___KEY__', 'gnew___KEY__', P_CROP['gallery'], '', null, 'Gallery image') ?>
                    <button type="button" class="adm-link" data-repeater-remove>Remove</button>
                </li>
            </template>
            <button type="button" class="adm-btn adm-btn--sm adm-btn--ghost" data-repeater-add data-repeater-pick>+ Add gallery image</button>
        </div>
        <?= AdminForm::imageHelp(P_CROP['gallery']) ?>
    </section>

    <section class="adm-card">
        <h2>Product video</h2>
        <?= p_field('Video URL (YouTube or Vimeo)', p_input('video_url', $val('video_url'), ['max' => 500, 'placeholder' => 'https://www.youtube.com/watch?v=…']), '', 'f_video_url') ?>
        <div class="field">
            <label for="video_file">…or upload a video file</label>
            <?php if (!empty($p['video_file'])): ?>
            <p class="adm-file"><a href="<?= e(upload_url($p['video_file'])) ?>" target="_blank" rel="noopener"><?= e(basename($p['video_file'])) ?></a> <?= p_check('remove[video_file]', 'Remove', false) ?></p>
            <?php endif; ?>
            <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm">
            <p class="help">MP4 or WebM up to 100 MB (your hosting may allow less). An uploaded file is used before the URL.</p>
        </div>
        <div class="field"><label for="img_video_thumb">Video thumbnail</label>
            <?= AdminForm::imageInput('img_video_thumb', 'img_video_thumb', P_CROP['video'], (string)$p['video_thumb'], 'remove[video_thumb]', 'Video thumbnail') ?><?= AdminForm::imageHelp(P_CROP['video']) ?></div>
    </section>

    <section class="adm-card">
        <h2>Collection image</h2>
        <div class="field"><?= AdminForm::imageInput('img_collection', 'img_collection', P_CROP['collection'], (string)$p['collection_image'], 'remove[collection_image]', 'Collection image') ?>
        <p class="help">Shown beside the video, e.g. the whole Luna Collection.</p><?= AdminForm::imageHelp(P_CROP['collection']) ?></div>
    </section>
    <?php break;

case 'options':
    $linked = array_map('intval', array_column(Database::all('SELECT term_id FROM product_term_links WHERE product_id = ?', [$id]), 'term_id'));
    $variants = ProductRepository::adminVariants($id);
    $sizes = Catalog::options('size');
    $colours = Catalog::options('colour');
    $materials = Catalog::options('material');
    $variantRow = static function (string $key, array $r) use ($sizes, $colours, $materials): string {
        $n = 'variants[' . $key . ']';
        return '<li class="rep__row rep__row--variant" data-sortable-item' . (!empty($r['id']) ? ' data-existing' : '') . ' data-repeater-row>'
            . '<button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move">⋮⋮</button>'
            . '<input type="hidden" name="' . $n . '[id]" value="' . (int)($r['id'] ?? 0) . '"><input type="hidden" name="' . $n . '[delete]" value="0" data-delete-flag>'
            . '<div class="rep__fields rep__fields--variant">'
            . '<label>Size' . p_select($n . '[size_id]', $sizes, $r['size_id'] ?? '', 'Any', 'vs_' . $key) . '</label>'
            . '<label>Colour' . p_select($n . '[colour_id]', $colours, $r['colour_id'] ?? '', 'Any', 'vc_' . $key) . '</label>'
            . '<label>Material' . p_select($n . '[material_id]', $materials, $r['material_id'] ?? '', 'Any', 'vm_' . $key) . '</label>'
            . '<label>SKU' . p_input($n . '[sku]', $r['sku'] ?? '', ['id' => 'vsku_' . $key, 'max' => 80]) . '</label>'
            . '<label>Retail price' . p_input($n . '[retail_price]', $r['retail_price'] ?? '', ['id' => 'vrp_' . $key, 'step' => '0.01']) . '</label>'
            . '<label>Agent price' . p_input($n . '[agent_price]', $r['agent_price'] ?? '', ['id' => 'vap_' . $key, 'step' => '0.01']) . '</label>'
            . '<label>Stock qty' . p_input($n . '[stock_qty]', $r['stock_qty'] ?? '', ['id' => 'vq_' . $key, 'type' => 'number']) . '</label>'
            . '<label>Availability' . p_select($n . '[availability]', ProductRepository::STOCK, $r['availability'] ?? '', 'Same as product', 'vav_' . $key) . '</label>'
            . '<div class="rep__image">' . AdminForm::imageInput('variant_img_' . $key, 'vimg_' . $key, P_CROP['variant'], (string)($r['image'] ?? ''), $n . '[remove_image]', 'Variation image') . '</div>'
            . '</div>'
            . '<button type="button" class="adm-icon adm-icon--danger" data-repeater-remove title="Remove variation" aria-label="Remove variation">✕</button></li>';
    }; ?>
    <section class="adm-card">
        <h2>Options offered</h2>
        <p class="help adm-card__intro">Tick what customers can choose on the product page. Manage the lists under <a href="terms.php?tax=size">Sizes</a>, <a href="terms.php?tax=colour">Colours</a> and <a href="terms.php?tax=material">Materials</a>.</p>
        <div class="adm-grid-3">
            <?php foreach (['size' => $sizes, 'colour' => $colours, 'material' => $materials] as $tax => $opts): ?>
            <fieldset class="adm-fieldset">
                <legend><?= e(Catalog::TAXONOMIES[$tax]['label']) ?></legend>
                <?php if (!$opts): ?><p class="muted small">None yet – <a href="terms.php?tax=<?= $tax ?>">add some</a>.</p><?php endif; ?>
                <?php foreach ($opts as $tid => $tname): ?><?= p_check('opt[]', $tname, in_array((int)$tid, $linked, true), (string)$tid) ?><?php endforeach; ?>
            </fieldset>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="adm-card">
        <h2>Variations (optional)</h2>
        <p class="help adm-card__intro">Only needed when a size, colour or material has its own SKU, price, image or stock. Empty fields use the product's own values. “Any” matches every choice.</p>
        <div data-repeater>
            <ul class="rep" data-sortable data-repeater-list>
                <?php foreach ($variants as $r) echo $variantRow((string)$r['id'], $r); ?>
            </ul>
            <template data-repeater-template><?= $variantRow('__KEY__', []) ?></template>
            <button type="button" class="adm-btn adm-btn--sm adm-btn--ghost" data-repeater-add>+ Add variation</button>
        </div>
        <?= AdminForm::imageHelp(P_CROP['variant']) ?>
    </section>
    <?php break;

case 'specs':
    $specs = ProductRepository::specs($id);
    if (!$specs) {
        foreach (['Chair Type', 'Design Style', 'Upholstery', 'Frame', 'Leg Finish', 'Seat Type', 'Backrest', 'Armrests', 'Assembly', 'Application'] as $i => $n) {
            $specs[] = ['id' => 'x' . $i, 'name' => '', 'value' => '', 'placeholder' => $n];
        }
    }
    $specRow = static fn(string $key, array $s) => '<li class="rep__row" data-sortable-item data-repeater-row>'
        . '<button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move">⋮⋮</button>'
        . '<input type="hidden" name="specs[' . $key . '][delete]" value="0" data-delete-flag>'
        . '<div class="rep__fields rep__fields--spec">'
        . p_input('specs[' . $key . '][name]', $s['name'] ?? '', ['id' => 'sn_' . $key, 'max' => 150, 'placeholder' => $s['placeholder'] ?? 'Specification name'])
        . p_input('specs[' . $key . '][value]', $s['value'] ?? '', ['id' => 'sv_' . $key, 'max' => 2000, 'placeholder' => 'Details'])
        . '</div><button type="button" class="adm-icon adm-icon--danger" data-repeater-remove title="Remove row" aria-label="Remove row">✕</button></li>'; ?>
    <section class="adm-card">
        <h2>Specification table</h2>
        <p class="help adm-card__intro">Two columns on the product page: Specification and Details. Drag ⋮⋮ to reorder; empty rows are ignored.</p>
        <div class="rep__head rep__head--spec"><span>Specification</span><span>Details</span></div>
        <div data-repeater>
            <ul class="rep" data-sortable data-repeater-list>
                <?php foreach ($specs as $s) echo $specRow((string)$s['id'], $s); ?>
            </ul>
            <template data-repeater-template><?= $specRow('__KEY__', []) ?></template>
            <button type="button" class="adm-btn adm-btn--sm adm-btn--ghost" data-repeater-add>+ Add row</button>
        </div>
    </section>
    <section class="adm-card">
        <h2>Suitable for</h2>
        <?= p_field('Suitable for', p_textarea('suitable_for', $val('suitable_for'), 3, ['max' => 2000]), 'Grey box beside the table, e.g. Living Rooms, Offices, Lounges, Waiting Areas, Hospitality.', 'f_suitable_for') ?>
    </section>
    <?php break;

case 'technical':
    $drawings = ProductRepository::images($id, 'drawing'); ?>
    <section class="adm-card">
        <h2>Technical information</h2>
        <div class="adm-grid-2">
            <?= p_field('Product dimensions', p_textarea('dimensions', $val('dimensions'), 3, ['max' => 2000]), 'e.g. W 820 × D 860 × H 1020 mm', 'f_dimensions') ?>
            <?= p_field('Product weight', p_input('weight', $val('weight'), ['max' => 100, 'placeholder' => 'e.g. 18 kg']), '', 'f_weight') ?>
        </div>
        <?= p_field('Technical details', p_textarea('technical_details', $val('technical_details'), 4, ['max' => 5000]), '', 'f_technical_details') ?>
        <?= p_field('Assembly information', p_textarea('assembly_info', $val('assembly_info'), 3, ['max' => 5000]), '', 'f_assembly_info') ?>
        <?= p_field('Maintenance instructions', p_textarea('maintenance', $val('maintenance'), 3, ['max' => 5000]), '', 'f_maintenance') ?>
    </section>
    <section class="adm-card">
        <h2>Technical drawings</h2>
        <ul class="adm-gallery" data-sortable="grid">
            <?php foreach ($drawings as $d): ?>
            <li class="adm-gallery__item" data-sortable-item>
                <button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move drawing">⋮⋮</button>
                <img src="<?= e(upload_url($d['path'])) ?>" alt="" class="adm-gallery__img">
                <input type="text" name="drawings[<?= (int)$d['id'] ?>][alt]" value="<?= e((string)$d['alt']) ?>" placeholder="Description" aria-label="Drawing description">
                <label class="check check--danger"><input type="checkbox" name="drawings[<?= (int)$d['id'] ?>][delete]" value="1"> Delete</label>
            </li>
            <?php endforeach; ?>
        </ul>
        <div data-repeater>
            <ul class="adm-gallery adm-gallery--new" data-repeater-list></ul>
            <template data-repeater-template>
                <li class="adm-gallery__item is-new" data-repeater-row>
                    <?= AdminForm::imageInput('drawing_new___KEY__', 'dnew___KEY__', P_CROP['drawing'], '', null, 'Technical drawing') ?>
                    <button type="button" class="adm-link" data-repeater-remove>Remove</button>
                </li>
            </template>
            <button type="button" class="adm-btn adm-btn--sm adm-btn--ghost" data-repeater-add data-repeater-pick>+ Add drawing</button>
        </div>
        <?= AdminForm::imageHelp(P_CROP['drawing']) ?>
    </section>
    <?php break;

case 'downloads':
    $downloads = ProductRepository::downloads($id);
    $dlRow = static function (string $key, array $d): string {
        $n = 'downloads[' . $key . ']';
        $file = !empty($d['file_path']) ? '<a href="' . e(upload_url($d['file_path'])) . '" target="_blank" rel="noopener">' . e($d['file_name'] ?: basename($d['file_path'])) . '</a> · replace:' : 'File:';
        return '<li class="rep__row" data-sortable-item' . (!empty($d['id']) ? ' data-existing' : '') . ' data-repeater-row>'
            . '<button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move">⋮⋮</button>'
            . '<input type="hidden" name="' . $n . '[id]" value="' . (int)($d['id'] ?? 0) . '"><input type="hidden" name="' . $n . '[delete]" value="0" data-delete-flag>'
            . '<div class="rep__fields rep__fields--download">'
            . '<label>Document title' . p_input($n . '[title]', $d['title'] ?? '', ['id' => 'dt_' . $key, 'max' => 200, 'placeholder' => 'e.g. Luna Chair Brochure']) . '</label>'
            . '<label>Document type' . p_select($n . '[doc_type]', ProductRepository::DOC_TYPES, $d['doc_type'] ?? 'brochure', '', 'dty_' . $key) . '</label>'
            . '<label class="rep__file"><span>' . $file . '</span><input type="file" name="download_file_' . $key . '" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip"></label>'
            . '</div><button type="button" class="adm-icon adm-icon--danger" data-repeater-remove title="Remove document" aria-label="Remove document">✕</button></li>';
    }; ?>
    <section class="adm-card">
        <h2>Downloads</h2>
        <p class="help adm-card__intro">Brochures, specification sheets, assembly instructions and technical PDFs. PDF, Word, Excel or ZIP up to 25 MB. Drag ⋮⋮ to set the display order.</p>
        <div data-repeater>
            <ul class="rep" data-sortable data-repeater-list>
                <?php foreach ($downloads as $d) echo $dlRow((string)$d['id'], $d); ?>
            </ul>
            <template data-repeater-template><?= $dlRow('__KEY__', []) ?></template>
            <button type="button" class="adm-btn adm-btn--sm adm-btn--ghost" data-repeater-add>+ Add document</button>
        </div>
    </section>
    <?php break;

case 'family':
    $families = array_column(Catalog::families(), 'name', 'id');
    $sections = ProductRepository::pageSections($p);
    $members = $p['family_id'] ? Database::all('SELECT id, name, sku, status FROM products WHERE family_id = ? ORDER BY family_order, name', [$p['family_id']]) : []; ?>
    <section class="adm-card">
        <h2>Product family</h2>
        <div class="adm-grid-2">
            <?= p_field('Family / collection', p_select('family_id', $families, $val('family_id')), 'Create families under Products → Product Families.', 'f_family_id') ?>
            <?= p_field('Position in the family', p_input('family_order', $val('family_order'), ['type' => 'number']), 'Lower numbers show first.', 'f_family_order') ?>
        </div>
        <div class="field"><?= p_check('show_family', 'Show the “Family Of Products” section on this product page', (bool)$sections['family']) ?></div>
        <?php if ($members): ?>
        <p class="help">Products in this family: <?= implode(', ', array_map(static fn($m) => '<a href="edit.php?id=' . (int)$m['id'] . '&amp;tab=family">' . e($m['name']) . '</a>', $members)) ?>.
            Reorder them all at once under <a href="families.php?edit=<?= (int)$p['family_id'] ?>">Product Families</a>.</p>
        <?php endif; ?>
    </section>
    <?php break;

case 'related':
    $sections = ProductRepository::pageSections($p);
    $chosen = Database::all('SELECT p.id, p.name, p.sku FROM product_related r JOIN products p ON p.id = r.related_id WHERE r.product_id = ? ORDER BY r.sort_order', [$id]);
    $all = ProductRepository::adminOptions($id); ?>
    <section class="adm-card">
        <h2>Related products</h2>
        <div class="field adm-checks">
            <label class="check"><input type="radio" name="related_mode" value="auto"<?= $p['related_mode'] !== 'manual' ? ' checked' : '' ?>> Automatic – same category or product type</label>
            <label class="check"><input type="radio" name="related_mode" value="manual"<?= $p['related_mode'] === 'manual' ? ' checked' : '' ?>> Manual – the products chosen below</label>
        </div>
        <div class="field">
            <label for="related-add">Add a product</label>
            <select id="related-add" data-related-add>
                <option value="">Choose a product…</option>
                <?php foreach ($all as $rid => $rl): ?><option value="<?= (int)$rid ?>"><?= e($rl) ?></option><?php endforeach; ?>
            </select>
            <ul class="rep rep--compact" data-sortable data-related-list>
                <?php foreach ($chosen as $c): ?>
                <li class="rep__row" data-sortable-item data-repeater-row><button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move">⋮⋮</button>
                    <input type="hidden" name="related[]" value="<?= (int)$c['id'] ?>"><span class="rep__label"><?= e($c['name']) ?> <span class="muted">(<?= e($c['sku']) ?>)</span></span>
                    <button type="button" class="adm-icon adm-icon--danger" data-repeater-remove aria-label="Remove">✕</button></li>
                <?php endforeach; ?>
            </ul>
            <p class="help">The product itself is never shown in its own related products.</p>
        </div>
        <div class="field"><?= p_check('show_related', 'Show the “Related Products” section on this product page', (bool)$sections['related']) ?></div>
    </section>
    <?php break;

case 'visibility':
    $sections = ProductRepository::pageSections($p);
    $cta = json_decode((string)$p['cta_override'], true) ?: [];
    $avail = ProductRepository::availability($p); ?>
    <section class="adm-card">
        <h2>Status & visibility</h2>
        <div class="adm-grid-2">
            <?= p_field('Product status', p_select('status', ProductRepository::STATUSES, $p['status'], ''), 'Only Published products appear on the website. Hidden keeps the product but removes it everywhere.', 'f_status') ?>
            <?= p_field('Display order', p_input('sort_order', $p['sort_order'], ['type' => 'number']), 'Lower numbers show first. Easier: drag rows on the All Products list.', 'f_sort_order') ?>
        </div>
        <div class="adm-checks">
            <?= p_check('is_featured', '★ Featured product', (bool)$p['is_featured']) ?>
            <?= p_check('is_new', 'New arrival', (bool)$p['is_new']) ?>
            <?= p_check('is_bestseller', 'Best seller', (bool)$p['is_bestseller']) ?>
        </div>
    </section>

    <section class="adm-card">
        <h2>Inventory <span class="muted">– shared by Work, Learn and Store</span></h2>
        <div class="field"><?= p_check('stock_manage', 'Manage stock quantity (shows Out of Stock automatically at 0)', (bool)$p['stock_manage']) ?></div>
        <div class="adm-grid-2">
            <?= p_field('Stock quantity', p_input('stock_qty', $p['stock_qty'], ['type' => 'number']), '', 'f_stock_qty') ?>
            <?= p_field('Low stock alert at', p_input('low_stock_threshold', $p['low_stock_threshold'], ['type' => 'number']), 'Highlighted in Inventory Management at or below this quantity.', 'f_low_stock_threshold') ?>
            <?= p_field('Availability', p_select('stock_status', ProductRepository::STOCK, $p['stock_status'], ''), 'Currently shown as: ' . $avail['label'] . '.', 'f_stock_status') ?>
            <?= p_field('Estimated lead time', p_input('lead_time', $p['lead_time'], ['max' => 150, 'placeholder' => 'e.g. 4–6 weeks']), 'Shown for Made To Order / Out of Stock.', 'f_lead_time') ?>
        </div>
    </section>

    <section class="adm-card">
        <h2>Product page sections</h2>
        <div class="adm-checks">
            <?= p_check('show_media', 'Product video & collection image', (bool)$sections['media']) ?>
            <?= p_check('show_cta', 'Call-to-action banner', (bool)$sections['cta']) ?>
        </div>
        <p class="help">Family and related products are switched on/off in their own tabs. Sections without content are hidden automatically.</p>
    </section>

    <section class="adm-card">
        <h2>Call-to-action banner for this product (optional)</h2>
        <div class="field"><?= p_check('cta[enabled]', 'Use custom content for this product', !empty($cta['enabled'])) ?>
            <p class="help">Otherwise the default from Products → Storefront Settings is used. Empty fields below keep the default text.</p></div>
        <div class="adm-grid-2">
            <?= p_field('Subtitle', p_input('cta[tagline]', $cta['tagline'] ?? '', ['id' => 'cta_tagline', 'max' => 200]), '', 'cta_tagline') ?>
            <?= p_field('Heading', p_textarea('cta[heading]', $cta['heading'] ?? '', 2, ['id' => 'cta_heading', 'max' => 300]), '', 'cta_heading') ?>
        </div>
        <?= p_field('Description', p_textarea('cta[body]', $cta['body'] ?? '', 3, ['id' => 'cta_body', 'max' => 2000]), '', 'cta_body') ?>
        <div class="adm-grid-2">
            <?= p_field('Primary button text', p_input('cta[primary_label]', $cta['primary_label'] ?? '', ['id' => 'cta_pl', 'max' => 100]), '', 'cta_pl') ?>
            <?= p_field('Primary button link', p_input('cta[primary_url]', $cta['primary_url'] ?? '', ['id' => 'cta_pu', 'max' => 500]), '', 'cta_pu') ?>
            <?= p_field('Secondary button text', p_input('cta[secondary_label]', $cta['secondary_label'] ?? '', ['id' => 'cta_sl', 'max' => 100]), '', 'cta_sl') ?>
            <?= p_field('Secondary button link', p_input('cta[secondary_url]', $cta['secondary_url'] ?? '', ['id' => 'cta_su', 'max' => 500]), '', 'cta_su') ?>
        </div>
        <div class="field"><label for="img_cta">Background image</label>
            <?= AdminForm::imageInput('img_cta', 'img_cta', P_CROP['cta'], (string)($cta['image'] ?? ''), 'remove[cta_image]', 'Call to action image') ?><?= AdminForm::imageHelp(P_CROP['cta']) ?></div>
    </section>
    <?php break;
endswitch; ?>

<div class="adm-actions adm-actions--sticky">
    <button class="adm-btn" type="submit"><?= $p ? 'Save ' . e(strtolower(P_TABS[$tab])) : 'Create product' ?></button>
    <a class="adm-link" href="./">Back to all products</a>
</div>
</form>
<?php admin_footer(); ?>
