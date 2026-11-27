<?php
/**
 * Work page front controller (/work/).
 * Content: Admin → Work Page. Sections are defined in app/schema/pages.php
 * ('work'); starting content in app/schema/seeds.php.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require APP_PATH . '/DevLock.php';

DevLock::enforce();

ContentRepository::sync('work');
$sections = ContentRepository::page('work');

$title = trim(preg_replace('/\s+/', ' ', (string)($sections['banner']['data']['heading'] ?? 'Work')));

render('layout/page', [
    'pageTitle' => ($title !== '' ? $title . ' – ' : '') . Settings::get('site_name', 'ARKI Environments'),
    // The header is drawn over the banner image (white logo + menu) when the banner is shown.
    'bodyClass' => 'page-work' . (isset($sections['banner']) ? ' has-banner' : ''),
    'global'    => ContentRepository::page('global'),
    'sections'  => $sections,
]);
