<?php
/**
 * POST-only actions: reorder, show/hide, duplicate and delete.
 */
declare(strict_types=1);
require __DIR__ . '/_init.php';
admin_post();

$do  = (string)($_POST['do'] ?? '');
$id  = (int)($_POST['id'] ?? 0);
$dir = (int)($_POST['dir'] ?? 0) < 0 ? -1 : 1;
$back = $_SERVER['HTTP_REFERER'] ?? url('admin/');
// Only ever redirect back inside the admin.
if (!str_contains($back, '/admin/') || parse_url($back, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? parse_url($back, PHP_URL_HOST))) {
    $back = url('admin/');
}

switch ($do) {
    case 'move_section':
        if ($s = ContentRepository::section($id)) {
            ContentRepository::move('sections', $id, $dir, 'page', $s['page']);
        }
        break;

    case 'toggle_section':
        Database::run('UPDATE sections SET is_active = 1 - is_active, updated_at = NOW() WHERE id = ?', [$id]);
        flash('success', 'Section visibility updated.');
        break;

    case 'move_item':
        if ($it = ContentRepository::item($id)) {
            ContentRepository::move('section_items', $id, $dir, 'section_id', (int)$it['section_id']);
        }
        break;

    case 'toggle_item':
        Database::run('UPDATE section_items SET is_active = 1 - is_active, updated_at = NOW() WHERE id = ?', [$id]);
        flash('success', 'Visibility updated.');
        break;

    case 'duplicate_item':
        if ($it = ContentRepository::item($id)) {
            ContentRepository::saveItem(null, (int)$it['section_id'], $it['data'], false);
            flash('success', 'Copy created (hidden until you make it visible).');
        }
        break;

    case 'delete_item':
        if ($it = ContentRepository::item($id)) {
            Database::run('DELETE FROM section_items WHERE id = ?', [$id]);
            foreach ($it['data'] as $v) {
                if (is_string($v) && str_starts_with($v, 'uploads/')) {
                    Uploader::deleteIfUnused($v);
                }
            }
            flash('success', 'Deleted.');
        }
        break;

    default:
        http_response_code(400);
        exit('Unknown action');
}

redirect($back);
