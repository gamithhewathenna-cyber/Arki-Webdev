<?php
/**
 * Learn page (/learn/). Content: Admin → Learn Page.
 * Layout: app/schema/pages.php · starting content: app/schema/seeds.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
render_inner_page('learn');
