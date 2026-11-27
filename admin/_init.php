<?php
/**
 * Shared bootstrap for every CMS page. Requires a logged-in administrator
 * unless the including script defines ADMIN_PUBLIC (login page only).
 */
declare(strict_types=1);

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === '_init.php') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';
require APP_PATH . '/Uploader.php';
require APP_PATH . '/AdminForm.php';

header('Cache-Control: no-store, private');
header('X-Frame-Options: DENY');

$ADMIN = defined('ADMIN_PUBLIC') ? Auth::user() : Auth::require();

function admin_header(string $title, string $active = ''): void
{
    render('admin/header', ['title' => $title, 'active' => $active, 'user' => Auth::user()]);
}

function admin_footer(): void
{
    render('admin/footer');
}

/**
 * Create database rows for any section that exists in the schema but not yet
 * in the database (lets developers add new sections without touching SQL).
 */
function admin_sync_sections(string $page): void
{
    ContentRepository::sync($page); // starting content comes from app/schema/seeds.php
}

/** Short tab name for a section: "Hero – Feature Cards" → "Hero". */
function admin_tab_label(array $def): string
{
    return (string)($def['tab'] ?? trim(explode('–', (string)$def['label'])[0]));
}

/**
 * Tab bar listing every section of a page in website order.
 * $current is 'overview' or the id of the section being edited.
 */
function admin_section_tabs(string $page, $current): void
{
    $schema = ContentRepository::schema()[$page] ?? null;
    if (!$schema) {
        return;
    }
    $rows = Database::all('SELECT id, section_key, is_active FROM sections WHERE page = ? ORDER BY sort_order, id', [$page]);
    echo '<nav class="adm-tabs" aria-label="' . e($schema['label']) . ' sections"><ul>';
    echo '<li><a class="adm-tab adm-tab--overview' . ($current === 'overview' ? ' is-active' : '') . '"'
        . ($current === 'overview' ? ' aria-current="page"' : '')
        . ' href="' . e(url('admin/?page=' . rawurlencode($page))) . '">Overview</a></li>';
    $n = 0;
    foreach ($rows as $r) {
        $def = $schema['sections'][$r['section_key']] ?? null;
        if (!$def) {
            continue;
        }
        $n++;
        $isCur = (string)$current === (string)$r['id'];
        echo '<li><a class="adm-tab' . ($isCur ? ' is-active' : '') . ($r['is_active'] ? '' : ' is-off') . '"'
            . ($isCur ? ' aria-current="page"' : '')
            . ' href="' . e(url('admin/section.php?id=' . (int)$r['id'])) . '"'
            . ($r['is_active'] ? '' : ' title="Hidden on the website"') . '>'
            . ($schema['sortable'] ? '<span class="adm-tab__num">' . $n . '</span>' : '')
            . e(admin_tab_label($def))
            . ($r['is_active'] ? '' : '<span class="adm-tab__off">Hidden</span>')
            . '</a></li>';
    }
    echo '</ul></nav>';
}

/** Require POST + valid CSRF token. */
function admin_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    csrf_verify();
}
