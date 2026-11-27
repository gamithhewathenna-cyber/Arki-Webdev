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

/** Require POST + valid CSRF token. */
function admin_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    csrf_verify();
}
