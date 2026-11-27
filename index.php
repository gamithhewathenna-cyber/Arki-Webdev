<?php
/**
 * Homepage front controller.
 * Inner pages (next phase) follow the same pattern: load the page's
 * sections from ContentRepository and render them through layout/page.
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require APP_PATH . '/DevLock.php';

DevLock::enforce();

render('layout/page', [
    'pageTitle' => Settings::get('home_title', Settings::get('site_name', 'ARKI Environments')),
    'bodyClass' => 'page-home',
    'global'    => ContentRepository::page('global'),
    'sections'  => ContentRepository::page('home'),
]);
