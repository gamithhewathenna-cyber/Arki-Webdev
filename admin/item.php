<?php
declare(strict_types=1);
require __DIR__ . '/_init.php';

$section = ContentRepository::section((int)($_GET['section'] ?? 0));
$def = $section ? ContentRepository::sectionSchema($section['page'], $section['section_key']) : null;
if (!$section || !$def || !isset($def['items'])) {
    http_response_code(404);
    exit('Section not found.');
}
$itemDef = $def['items'];
$id   = (int)($_GET['id'] ?? 0);
$item = $id ? ContentRepository::item($id) : null;
if ($id && (!$item || (int)$item['section_id'] !== (int)$section['id'])) {
    http_response_code(404);
    exit('Item not found.');
}
$back = 'section.php?id=' . (int)$section['id'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    [$data, $errors, $orphans] = AdminForm::collect($itemDef['fields'], $item['data'] ?? []);
    $active = !empty($_POST['is_active']);
    $newId = ContentRepository::saveItem($item ? (int)$item['id'] : null, (int)$section['id'], $data, $active);
    foreach ($orphans as $o) {
        Uploader::deleteIfUnused($o);
    }
    foreach ($errors as $er) {
        flash('error', $er);
    }
    flash('success', $itemDef['label'] . ' saved.');
    redirect(isset($_POST['save_close']) ? $back : 'item.php?section=' . (int)$section['id'] . '&id=' . $newId);
}

$title = ($item ? 'Edit ' : 'Add ') . strtolower($itemDef['label']);
admin_header($title, $section['page']);
?>
<nav class="adm-crumbs">
    <a href="./?page=<?= e($section['page']) ?>"><?= e(ContentRepository::schema()[$section['page']]['label']) ?></a> /
    <a href="<?= e($back) ?>"><?= e($def['label']) ?></a> / <?= e($title) ?>
</nav>
<header class="adm-head"><h1><?= e(ucfirst($title)) ?></h1></header>

<form method="post" enctype="multipart/form-data" class="adm-card adm-form">
    <?= csrf_field() ?>
    <?= AdminForm::render($itemDef['fields'], $item['data'] ?? []) ?>
    <div class="field">
        <label class="check"><input type="checkbox" name="is_active" value="1" <?= ($item === null || $item['is_active']) ? 'checked' : '' ?>> Visible on the website</label>
    </div>
    <div class="adm-actions">
        <button class="adm-btn" type="submit">Save</button>
        <button class="adm-btn adm-btn--ghost" type="submit" name="save_close" value="1">Save &amp; close</button>
        <a class="adm-link" href="<?= e($back) ?>">Cancel</a>
    </div>
</form>
<?php admin_footer(); ?>
