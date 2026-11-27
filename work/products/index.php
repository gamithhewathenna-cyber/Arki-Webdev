<?php
/**
 * Work product listing (/work/products/). Products: Admin → Products.
 * Banner and call to action: Admin → Products → Storefront Settings.
 */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Shop::listing('work');
