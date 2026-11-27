<?php
/**
 * Product categories per storefront (Work / Learn / Store) + demo links.
 * Applied once by app/Migrations.php; the same SQL is in database-update-categories.sql.
 * Generated from the category list supplied by the client (Nov 2026). Edit categories in the CMS.
 */
return <<<'SQL'
-- Storefront category trees (Work / Learn / Store). Runs once: skipped when 'storefront_categories_seeded' is set.
SET @cs = (SELECT IF(COUNT(*) = 0, 1, 0) FROM `settings` WHERE `setting_key` = 'storefront_categories_seeded');

-- The old shared category list is replaced by one tree per storefront.
UPDATE `products` SET `category_id` = NULL, `subcategory_id` = NULL WHERE @cs = 1;
DELETE FROM `product_terms` WHERE `taxonomy` = 'category' AND @cs = 1;

INSERT IGNORE INTO `product_terms` (`taxonomy`, `parent_id`, `name`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT d.tx, NULL, d.n, d.s, d.o, 1, NOW(), NOW() FROM (
          SELECT 'cat_work' AS tx, 'Tables' AS n, 'tables' AS s, 1 AS o
    UNION ALL SELECT 'cat_work', 'Seating', 'seating', 2
    UNION ALL SELECT 'cat_work', 'Lounge', 'lounge', 3
    UNION ALL SELECT 'cat_work', 'Privacy', 'privacy', 4
    UNION ALL SELECT 'cat_work', 'Tools', 'tools', 5
    UNION ALL SELECT 'cat_learn', 'Tables', 'tables', 1
    UNION ALL SELECT 'cat_learn', 'Seating', 'seating', 2
    UNION ALL SELECT 'cat_learn', 'Lounge', 'lounge', 3
    UNION ALL SELECT 'cat_learn', 'Storage', 'storage', 4
    UNION ALL SELECT 'cat_learn', 'Teacher Tools', 'teacher-tools', 5
    UNION ALL SELECT 'cat_learn', 'Early Years', 'early-years', 6
    UNION ALL SELECT 'cat_store', 'Tables', 'tables', 1
    UNION ALL SELECT 'cat_store', 'Seating', 'seating', 2
    UNION ALL SELECT 'cat_store', 'Lounge', 'lounge', 3
    UNION ALL SELECT 'cat_store', 'Privacy', 'privacy', 4
    UNION ALL SELECT 'cat_store', 'Tools', 'tools', 5
) d WHERE @cs = 1;

INSERT IGNORE INTO `product_terms` (`taxonomy`, `parent_id`, `name`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT d.tx, p.`id`, d.n, d.s, d.o, 1, NOW(), NOW() FROM (
          SELECT 'cat_work' AS tx, 'tables' AS ps, 'Height Adjustable' AS n, 'tables-height-adjustable' AS s, 1 AS o
    UNION ALL SELECT 'cat_work', 'tables', 'Fixed', 'tables-fixed', 2
    UNION ALL SELECT 'cat_work', 'tables', 'Flexible', 'tables-flexible', 3
    UNION ALL SELECT 'cat_work', 'tables', 'Collaborative', 'tables-collaborative', 4
    UNION ALL SELECT 'cat_work', 'tables', 'Training', 'tables-training', 5
    UNION ALL SELECT 'cat_work', 'tables', 'Occasional', 'tables-occasional', 6
    UNION ALL SELECT 'cat_work', 'seating', 'Task', 'seating-task', 1
    UNION ALL SELECT 'cat_work', 'seating', 'Stools', 'seating-stools', 2
    UNION ALL SELECT 'cat_work', 'seating', 'Collaborative', 'seating-collaborative', 3
    UNION ALL SELECT 'cat_work', 'seating', 'Training', 'seating-training', 4
    UNION ALL SELECT 'cat_work', 'lounge', 'Armchairs', 'lounge-armchairs', 1
    UNION ALL SELECT 'cat_work', 'lounge', 'Modular', 'lounge-modular', 2
    UNION ALL SELECT 'cat_work', 'lounge', 'Ottomans', 'lounge-ottomans', 3
    UNION ALL SELECT 'cat_work', 'privacy', 'Booths', 'privacy-booths', 1
    UNION ALL SELECT 'cat_work', 'privacy', 'Screens', 'privacy-screens', 2
    UNION ALL SELECT 'cat_work', 'privacy', 'Acoustics', 'privacy-acoustics', 3
    UNION ALL SELECT 'cat_work', 'tools', 'Storage', 'tools-storage', 1
    UNION ALL SELECT 'cat_work', 'tools', 'Power', 'tools-power', 2
    UNION ALL SELECT 'cat_work', 'tools', 'Whiteboards', 'tools-whiteboards', 3
    UNION ALL SELECT 'cat_learn', 'tables', 'Student Classroom', 'tables-student-classroom', 1
    UNION ALL SELECT 'cat_learn', 'tables', 'STEAM', 'tables-steam', 2
    UNION ALL SELECT 'cat_learn', 'tables', 'Occasional', 'tables-occasional', 3
    UNION ALL SELECT 'cat_learn', 'tables', 'Collaborative', 'tables-collaborative', 4
    UNION ALL SELECT 'cat_learn', 'tables', 'Café & Outdoor', 'tables-cafe-outdoor', 5
    UNION ALL SELECT 'cat_learn', 'tables', 'Administration', 'tables-administration', 6
    UNION ALL SELECT 'cat_learn', 'seating', 'Student Chairs', 'seating-student-chairs', 1
    UNION ALL SELECT 'cat_learn', 'seating', 'Stools', 'seating-stools', 2
    UNION ALL SELECT 'cat_learn', 'seating', 'Meeting', 'seating-meeting', 3
    UNION ALL SELECT 'cat_learn', 'seating', 'Task / Admin', 'seating-task-admin', 4
    UNION ALL SELECT 'cat_learn', 'seating', 'Café & Outdoor', 'seating-cafe-outdoor', 5
    UNION ALL SELECT 'cat_learn', 'seating', 'Waiting / Occasional', 'seating-waiting-occasional', 6
    UNION ALL SELECT 'cat_learn', 'seating', 'Auditorium / Stacker', 'seating-auditorium-stacker', 7
    UNION ALL SELECT 'cat_learn', 'seating', 'Tiered Seating', 'seating-tiered-seating', 8
    UNION ALL SELECT 'cat_learn', 'lounge', 'Lounge', 'lounge-lounge', 1
    UNION ALL SELECT 'cat_learn', 'lounge', 'Armchairs', 'lounge-armchairs', 2
    UNION ALL SELECT 'cat_learn', 'lounge', 'Ottomans', 'lounge-ottomans', 3
    UNION ALL SELECT 'cat_learn', 'lounge', 'Modular', 'lounge-modular', 4
    UNION ALL SELECT 'cat_learn', 'lounge', 'Booths', 'lounge-booths', 5
    UNION ALL SELECT 'cat_learn', 'storage', 'Classroom', 'storage-classroom', 1
    UNION ALL SELECT 'cat_learn', 'storage', 'Library', 'storage-library', 2
    UNION ALL SELECT 'cat_learn', 'storage', 'Administration', 'storage-administration', 3
    UNION ALL SELECT 'cat_learn', 'teacher-tools', 'Screens / Space Division', 'teacher-tools-screens-space-division', 1
    UNION ALL SELECT 'cat_learn', 'teacher-tools', 'Lecterns', 'teacher-tools-lecterns', 2
    UNION ALL SELECT 'cat_learn', 'teacher-tools', 'Acoustics', 'teacher-tools-acoustics', 3
    UNION ALL SELECT 'cat_learn', 'teacher-tools', 'Whiteboards / Easels', 'teacher-tools-whiteboards-easels', 4
    UNION ALL SELECT 'cat_learn', 'teacher-tools', 'Student Charging', 'teacher-tools-student-charging', 5
    UNION ALL SELECT 'cat_learn', 'teacher-tools', 'Sensory Learning', 'teacher-tools-sensory-learning', 6
    UNION ALL SELECT 'cat_learn', 'early-years', 'Early Learning Furniture', 'early-years-early-learning-furniture', 1
    UNION ALL SELECT 'cat_store', 'tables', 'Height Adjustable', 'tables-height-adjustable', 1
    UNION ALL SELECT 'cat_store', 'tables', 'Fixed', 'tables-fixed', 2
    UNION ALL SELECT 'cat_store', 'tables', 'Flexible', 'tables-flexible', 3
    UNION ALL SELECT 'cat_store', 'tables', 'Collaborative', 'tables-collaborative', 4
    UNION ALL SELECT 'cat_store', 'tables', 'Training', 'tables-training', 5
    UNION ALL SELECT 'cat_store', 'tables', 'Occasional', 'tables-occasional', 6
    UNION ALL SELECT 'cat_store', 'seating', 'Task', 'seating-task', 1
    UNION ALL SELECT 'cat_store', 'seating', 'Stools', 'seating-stools', 2
    UNION ALL SELECT 'cat_store', 'seating', 'Collaborative', 'seating-collaborative', 3
    UNION ALL SELECT 'cat_store', 'seating', 'Training', 'seating-training', 4
    UNION ALL SELECT 'cat_store', 'lounge', 'Armchairs', 'lounge-armchairs', 1
    UNION ALL SELECT 'cat_store', 'lounge', 'Modular', 'lounge-modular', 2
    UNION ALL SELECT 'cat_store', 'lounge', 'Ottomans', 'lounge-ottomans', 3
    UNION ALL SELECT 'cat_store', 'privacy', 'Booths', 'privacy-booths', 1
    UNION ALL SELECT 'cat_store', 'privacy', 'Screens', 'privacy-screens', 2
    UNION ALL SELECT 'cat_store', 'privacy', 'Acoustics', 'privacy-acoustics', 3
    UNION ALL SELECT 'cat_store', 'tools', 'Storage', 'tools-storage', 1
    UNION ALL SELECT 'cat_store', 'tools', 'Power', 'tools-power', 2
    UNION ALL SELECT 'cat_store', 'tools', 'Whiteboards', 'tools-whiteboards', 3
) d
JOIN `product_terms` p ON p.`taxonomy` = d.tx AND p.`slug` = d.ps AND p.`parent_id` IS NULL
WHERE @cs = 1;

-- Demo products → their categories in each storefront (skipped for products that don't exist).
INSERT IGNORE INTO `product_term_links` (`product_id`, `term_id`)
SELECT p.`id`, t.`id` FROM (
          SELECT 'luna-chair' AS slug, 'cat_work' AS tx, 'lounge-armchairs' AS ts
    UNION ALL SELECT 'luna-chair', 'cat_learn', 'lounge-armchairs'
    UNION ALL SELECT 'luna-chair', 'cat_store', 'lounge-armchairs'
    UNION ALL SELECT 'zen-lounge', 'cat_work', 'seating-collaborative'
    UNION ALL SELECT 'zen-lounge', 'cat_store', 'seating-collaborative'
    UNION ALL SELECT 'zen-swivel-lounge', 'cat_work', 'lounge-armchairs'
    UNION ALL SELECT 'zen-swivel-lounge', 'cat_learn', 'lounge-lounge'
    UNION ALL SELECT 'zen-swivel-lounge', 'cat_store', 'lounge-armchairs'
    UNION ALL SELECT 'zen-boucle-armchair', 'cat_store', 'lounge-armchairs'
    UNION ALL SELECT 'zen-tufted-chair', 'cat_learn', 'seating-waiting-occasional'
    UNION ALL SELECT 'zen-tufted-chair', 'cat_store', 'lounge-armchairs'
    UNION ALL SELECT 'axis-nesting-tables', 'cat_work', 'tables-occasional'
    UNION ALL SELECT 'axis-nesting-tables', 'cat_store', 'tables-occasional'
    UNION ALL SELECT 'axis-dining-table', 'cat_work', 'tables-collaborative'
    UNION ALL SELECT 'axis-dining-table', 'cat_learn', 'tables-cafe-outdoor'
    UNION ALL SELECT 'axis-dining-table', 'cat_store', 'tables-collaborative'
    UNION ALL SELECT 'axis-bedside-cabinet', 'cat_store', 'tools-storage'
    UNION ALL SELECT 'axis-side-table', 'cat_learn', 'tables-occasional'
    UNION ALL SELECT 'axis-side-table', 'cat_store', 'tables-occasional'
    UNION ALL SELECT 'luna-high-back-chair', 'cat_work', 'lounge-armchairs'
    UNION ALL SELECT 'luna-high-back-chair', 'cat_store', 'lounge-armchairs'
    UNION ALL SELECT 'luna-2-seater-sofa', 'cat_store', 'lounge-modular'
    UNION ALL SELECT 'luna-3-seater-sofa', 'cat_store', 'lounge-modular'
) d
JOIN `products` p ON p.`slug` = d.slug
JOIN `product_terms` t ON t.`taxonomy` = d.tx AND t.`slug` = d.ts
WHERE @cs = 1;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('storefront_categories_seeded', '1')
ON DUPLICATE KEY UPDATE `setting_value` = '1';

-- Category cards on the Work / Learn / Store pages → their category in that storefront
-- (only links that are empty, "#" or an earlier category link).
UPDATE `section_items` i
JOIN `sections` s ON s.`id` = i.`section_id` AND s.`page` IN ('work', 'learn', 'store') AND s.`section_key` = 'categories'
JOIN `product_terms` t ON t.`taxonomy` = CONCAT('cat_', s.`page`) AND t.`parent_id` IS NULL AND t.`slug` =
    CASE LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.title'))))
        WHEN 'tables' THEN 'tables' WHEN 'seating' THEN 'seating' WHEN 'lounge' THEN 'lounge' WHEN 'privacy' THEN 'privacy'
        WHEN 'tools' THEN 'tools' WHEN 'storage' THEN 'storage' WHEN 'teacher tools' THEN 'teacher-tools' WHEN 'early years' THEN 'early-years' END
SET i.`data` = JSON_SET(i.`data`, '$.url', CONCAT(s.`page`, '/products/?cat[]=', t.`slug`)), i.`updated_at` = NOW()
WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.url')), '') IN ('', '#')
   OR JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.url')) LIKE '%/products/?cat[]=%';

-- Cards whose old category doesn't exist in that storefront → the storefront's full listing.
UPDATE `section_items` i
JOIN `sections` s ON s.`id` = i.`section_id` AND s.`page` IN ('work', 'learn', 'store') AND s.`section_key` = 'categories'
SET i.`data` = JSON_SET(i.`data`, '$.url', CONCAT(s.`page`, '/products/')), i.`updated_at` = NOW()
WHERE JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.url')) LIKE '%/products/?cat[]=%'
  AND NOT EXISTS (SELECT 1 FROM `product_terms` t WHERE t.`taxonomy` = CONCAT('cat_', s.`page`)
                  AND t.`slug` = SUBSTRING_INDEX(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.url')), '=', -1));
SQL;
