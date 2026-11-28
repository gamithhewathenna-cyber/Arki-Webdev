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

/**
 * The website logo (Header & Footer → Header) for the CMS.
 * $onDark: use the white version, or turn the normal logo white with CSS.
 */
function admin_logo(bool $onDark): string
{
    static $d = null;
    if ($d === null) {
        $row = Database::one("SELECT data FROM sections WHERE page = 'global' AND section_key = 'header'");
        $d = $row ? (json_decode((string)$row['data'], true) ?: []) : [];
    }
    $exists = static fn($p) => !empty($p) && is_file(ROOT_PATH . '/' . ltrim((string)$p, '/'));
    $alt = (string)(($d['logo_alt'] ?? '') ?: Settings::get('site_name', 'Website'));
    if ($onDark && $exists($d['logo_light'] ?? '')) {
        return '<img class="adm-logo-img" src="' . e(upload_url($d['logo_light'])) . '" alt="' . e($alt) . '">';
    }
    if ($exists($d['logo'] ?? '')) {
        return '<img class="adm-logo-img' . ($onDark ? ' adm-logo-img--invert' : '') . '" src="' . e(upload_url($d['logo'])) . '" alt="' . e($alt) . '">';
    }
    return '<span class="adm-logo-text">' . e(Settings::get('site_name', 'Website')) . '</span>';
}

/** Small line icons for the CMS menu. */
function admin_icon(string $name): string
{
    $p = [
        'home'     => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'page'     => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
        'layout'   => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M3 15h18"/>',
        'box'      => '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5z"/><path d="M3 7.5 12 12l9-4.5M12 12v9"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.5 3.3-5.5 6.5-5.5s5.9 2 6.5 5.5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c2 .7 3.2 2.4 3.5 5.2"/>',
        'badge'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2.2"/><path d="M5.8 16c.5-1.6 1.7-2.4 3.2-2.4s2.7.8 3.2 2.4M14.5 10h4M14.5 13.5h3"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>',
        'megaphone' => '<path d="M3 10v4a1 1 0 0 0 1 1h3l6 4V5L7 9H4a1 1 0 0 0-1 1z"/><path d="M17 8.5a5 5 0 0 1 0 7M19.5 6a8.5 8.5 0 0 1 0 12"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'logout'   => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3"/>',
        'chevron'  => '<path d="m9 6 6 6-6 6"/>',
    ][$name] ?? '';
    return '<svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

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
