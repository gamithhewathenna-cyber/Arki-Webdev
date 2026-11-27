<?php
/**
 * Product page (/product/?p=product-slug). Products: Admin → Products.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
Shop::product();
