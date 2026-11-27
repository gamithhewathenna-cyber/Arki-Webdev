<?php
/**
 * Product catalogue – database structure + demo content.
 *
 * Applied automatically once by app/Migrations.php, and identical to the
 * copy-paste script database-update-products.sql. Every statement is safe
 * to run again (CREATE TABLE IF NOT EXISTS / INSERT IGNORE / guarded).
 *
 * Statements are separated by a line that ends with ";" – keep it that way.
 *
 * Future e-commerce: products / product_variants already carry SKU, currency,
 * agent price and stock, so carts, quotes, orders and agent accounts can
 * reference them by id without changing these tables.
 */
return <<<'SQL'
CREATE TABLE IF NOT EXISTS `product_terms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `taxonomy` varchar(20) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `hex` varchar(7) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_term_slug` (`taxonomy`,`slug`),
  KEY `idx_term_list` (`taxonomy`,`parent_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_families` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_family_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `sku` varchar(80) NOT NULL,
  `brand_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `subcategory_id` int(10) unsigned DEFAULT NULL,
  `type_id` int(10) unsigned DEFAULT NULL,
  `env_rating_id` int(10) unsigned DEFAULT NULL,
  `family_id` int(10) unsigned DEFAULT NULL,
  `family_order` int(11) NOT NULL DEFAULT 0,
  `short_description` text DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `suitable_for` text DEFAULT NULL,
  `retail_price` decimal(12,2) DEFAULT NULL,
  `agent_price` decimal(12,2) DEFAULT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'AUD',
  `price_from` tinyint(1) NOT NULL DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `video_thumb` varchar(255) DEFAULT NULL,
  `collection_image` varchar(255) DEFAULT NULL,
  `dimensions` text DEFAULT NULL,
  `weight` varchar(100) DEFAULT NULL,
  `technical_details` text DEFAULT NULL,
  `assembly_info` text DEFAULT NULL,
  `maintenance` text DEFAULT NULL,
  `stock_manage` tinyint(1) NOT NULL DEFAULT 0,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `stock_status` varchar(20) NOT NULL DEFAULT 'in_stock',
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `lead_time` varchar(150) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_new` tinyint(1) NOT NULL DEFAULT 0,
  `is_bestseller` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `related_mode` varchar(10) NOT NULL DEFAULT 'auto',
  `page_sections` text DEFAULT NULL,
  `cta_override` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_slug` (`slug`),
  UNIQUE KEY `uq_product_sku` (`sku`),
  KEY `idx_product_list` (`status`,`sort_order`),
  KEY `idx_product_category` (`category_id`),
  KEY `idx_product_subcategory` (`subcategory_id`),
  KEY `idx_product_type` (`type_id`),
  KEY `idx_product_brand` (`brand_id`),
  KEY `idx_product_family` (`family_id`,`family_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_storefronts` (
  `product_id` int(10) unsigned NOT NULL,
  `storefront` varchar(10) NOT NULL,
  PRIMARY KEY (`product_id`,`storefront`),
  KEY `idx_storefront` (`storefront`),
  CONSTRAINT `fk_store_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `kind` varchar(20) NOT NULL DEFAULT 'gallery',
  `path` varchar(255) NOT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_image_product` (`product_id`,`kind`,`sort_order`),
  CONSTRAINT `fk_image_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_specs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `value` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_spec_product` (`product_id`,`sort_order`),
  CONSTRAINT `fk_spec_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_downloads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `doc_type` varchar(40) NOT NULL DEFAULT 'brochure',
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(200) DEFAULT NULL,
  `file_size` int(10) unsigned NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_download_product` (`product_id`,`sort_order`),
  CONSTRAINT `fk_download_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_term_links` (
  `product_id` int(10) unsigned NOT NULL,
  `term_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`term_id`),
  KEY `idx_link_term` (`term_id`),
  CONSTRAINT `fk_link_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_link_term` FOREIGN KEY (`term_id`) REFERENCES `product_terms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `size_id` int(10) unsigned DEFAULT NULL,
  `colour_id` int(10) unsigned DEFAULT NULL,
  `material_id` int(10) unsigned DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `retail_price` decimal(12,2) DEFAULT NULL,
  `agent_price` decimal(12,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT NULL,
  `availability` varchar(20) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_variant_product` (`product_id`,`sort_order`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_related` (
  `product_id` int(10) unsigned NOT NULL,
  `related_id` int(10) unsigned NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`,`related_id`),
  CONSTRAINT `fk_related_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_related_other` FOREIGN KEY (`related_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Demo content (runs once: skipped when 'products_demo_seeded' is set)
-- ---------------------------------------------------------------------
SET @seed = (SELECT IF(COUNT(*) = 0, 1, 0) FROM `settings` WHERE `setting_key` = 'products_demo_seeded');

INSERT IGNORE INTO `product_terms` (`taxonomy`, `parent_id`, `name`, `slug`, `hex`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT d.* FROM (
          SELECT 'category' AS t, NULL AS p, 'Seating' AS n, 'seating' AS s, NULL AS h, 1 AS o, 1 AS a, NOW() AS c, NOW() AS u
    UNION ALL SELECT 'category', NULL, 'Lounge', 'lounge', NULL, 2, 1, NOW(), NOW()
    UNION ALL SELECT 'category', NULL, 'Privacy', 'privacy', NULL, 3, 1, NOW(), NOW()
    UNION ALL SELECT 'category', NULL, 'Storage & Accessories', 'storage-accessories', NULL, 4, 1, NOW(), NOW()
    UNION ALL SELECT 'category', NULL, 'Desks & Tables', 'desks-tables', NULL, 5, 1, NOW(), NOW()
    UNION ALL SELECT 'type', NULL, 'Sofa', 'sofa', NULL, 1, 1, NOW(), NOW()
    UNION ALL SELECT 'type', NULL, 'Chair', 'chair', NULL, 2, 1, NOW(), NOW()
    UNION ALL SELECT 'type', NULL, 'Stools', 'stools', NULL, 3, 1, NOW(), NOW()
    UNION ALL SELECT 'type', NULL, 'Desks', 'desks', NULL, 4, 1, NOW(), NOW()
    UNION ALL SELECT 'type', NULL, 'Tables', 'tables', NULL, 5, 1, NOW(), NOW()
    UNION ALL SELECT 'type', NULL, 'Meeting Tables', 'meeting-tables', NULL, 6, 1, NOW(), NOW()
    UNION ALL SELECT 'type', NULL, 'Outdoor Furniture', 'outdoor-furniture', NULL, 7, 1, NOW(), NOW()
    UNION ALL SELECT 'brand', NULL, 'ARKI Environments', 'arki', NULL, 1, 1, NOW(), NOW()
    UNION ALL SELECT 'brand', NULL, 'Steelcase', 'steelcase', NULL, 2, 1, NOW(), NOW()
    UNION ALL SELECT 'material', NULL, 'Fabric', 'fabric', NULL, 1, 1, NOW(), NOW()
    UNION ALL SELECT 'material', NULL, 'Leather', 'leather', NULL, 2, 1, NOW(), NOW()
    UNION ALL SELECT 'material', NULL, 'Timber', 'timber', NULL, 3, 1, NOW(), NOW()
    UNION ALL SELECT 'material', NULL, 'Metal', 'metal', NULL, 4, 1, NOW(), NOW()
    UNION ALL SELECT 'material', NULL, 'Marble', 'marble', NULL, 5, 1, NOW(), NOW()
    UNION ALL SELECT 'colour', NULL, 'Light Grey', 'light-grey', '#d6d6d6', 1, 1, NOW(), NOW()
    UNION ALL SELECT 'colour', NULL, 'Charcoal', 'charcoal', '#4a4a4a', 2, 1, NOW(), NOW()
    UNION ALL SELECT 'colour', NULL, 'White', 'white', '#ffffff', 3, 1, NOW(), NOW()
    UNION ALL SELECT 'colour', NULL, 'Natural Oak', 'natural-oak', '#c8a170', 4, 1, NOW(), NOW()
    UNION ALL SELECT 'colour', NULL, 'Walnut', 'walnut', '#6b3f2a', 5, 1, NOW(), NOW()
    UNION ALL SELECT 'size', NULL, 'M', 'm', NULL, 1, 1, NOW(), NOW()
    UNION ALL SELECT 'size', NULL, 'L', 'l', NULL, 2, 1, NOW(), NOW()
    UNION ALL SELECT 'size', NULL, 'XL', 'xl', NULL, 3, 1, NOW(), NOW()
    UNION ALL SELECT 'env_rating', NULL, 'GECA Certified', 'geca', NULL, 1, 1, NOW(), NOW()
    UNION ALL SELECT 'env_rating', NULL, 'GREENGUARD Gold', 'greenguard-gold', NULL, 2, 1, NOW(), NOW()
) d WHERE @seed = 1;

INSERT IGNORE INTO `product_terms` (`taxonomy`, `parent_id`, `name`, `slug`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT 'category', p.`id`, d.n, d.s, d.o, 1, NOW(), NOW()
FROM (
          SELECT 'seating' AS ps, 'Task Chairs' AS n, 'task-chairs' AS s, 1 AS o
    UNION ALL SELECT 'seating', 'Lounge Chairs', 'lounge-chairs', 2
    UNION ALL SELECT 'lounge', 'Sofas', 'sofas', 1
    UNION ALL SELECT 'desks-tables', 'Dining Tables', 'dining-tables', 1
    UNION ALL SELECT 'desks-tables', 'Side Tables', 'side-tables', 2
) d
JOIN `product_terms` p ON p.`taxonomy` = 'category' AND p.`slug` = d.ps AND p.`parent_id` IS NULL
WHERE @seed = 1;

INSERT IGNORE INTO `product_families` (`name`, `slug`, `description`, `image`, `sort_order`, `is_active`, `created_at`, `updated_at`)
SELECT 'Luna Collection', 'luna', 'Lorem ipsum dolor sit amet consectetur. Condimentum convallis nulla nec ultrices convallis non turpis.', 'uploads/demo/luna-collection.jpg', 1, 1, NOW(), NOW()
FROM DUAL WHERE @seed = 1;

INSERT IGNORE INTO `products`
  (`name`, `slug`, `sku`, `brand_id`, `category_id`, `subcategory_id`, `type_id`, `env_rating_id`, `family_id`, `family_order`,
   `short_description`, `description`, `suitable_for`, `retail_price`, `agent_price`, `currency`, `price_from`, `image`, `collection_image`,
   `dimensions`, `weight`, `technical_details`, `assembly_info`, `maintenance`,
   `stock_manage`, `stock_qty`, `stock_status`, `low_stock_threshold`, `lead_time`,
   `status`, `is_featured`, `is_new`, `is_bestseller`, `sort_order`, `related_mode`, `page_sections`, `created_at`, `updated_at`)
SELECT d.name, d.slug, d.sku,
       (SELECT `id` FROM `product_terms` WHERE `taxonomy` = 'brand' AND `slug` = d.brand),
       (SELECT `id` FROM `product_terms` WHERE `taxonomy` = 'category' AND `slug` = d.cat AND `parent_id` IS NULL),
       (SELECT `id` FROM `product_terms` WHERE `taxonomy` = 'category' AND `slug` = d.sub AND `parent_id` IS NOT NULL),
       (SELECT `id` FROM `product_terms` WHERE `taxonomy` = 'type' AND `slug` = d.ptype),
       (SELECT `id` FROM `product_terms` WHERE `taxonomy` = 'env_rating' AND `slug` = d.env),
       (SELECT `id` FROM `product_families` WHERE `slug` = d.fam), d.forder,
       'Lorem ipsum dolor sit amet consectetur. Condimentum convallis nulla nec ultrices convallis non turpis.',
       '<p>Lorem ipsum dolor sit amet consectetur. Condimentum convallis nulla nec ultrices convallis non turpis. Euismod lacus nunc in id justo ac sed. Sit habitant donec massa semper nibh maecenas.</p><p>Sit senectus ullamcorper massa turpis ac orci egestas. Augue tellus sed urna vehicula semper.</p>',
       'Living Rooms, Offices, Lounges, Waiting Areas, Hospitality',
       d.price, d.agent, 'AUD', 1, d.img, d.coll,
       d.dims, d.weight, 'Lorem ipsum dolor sit amet consectetur. Euismod lacus nunc in id justo ac sed.', 'Minimal assembly required. Legs attach with the supplied fixings.', 'Vacuum regularly and spot clean with a damp cloth.',
       d.sm, d.qty, d.stock, 5, d.lead,
       'published', d.feat, d.isnew, d.best, d.ord, 'auto', '{"family":1,"media":1,"related":1,"cta":1}', NOW() - INTERVAL d.ord DAY, NOW()
FROM (
          SELECT 'Luna Chair' AS name, 'luna-chair' AS slug, 'AE-A0136' AS sku, 'arki' AS brand, 'seating' AS cat, 'lounge-chairs' AS sub, 'chair' AS ptype, 'geca' AS env, 'luna' AS fam, 1 AS forder, 450.00 AS price, 360.00 AS agent, 'uploads/demo/store-product-1.jpg' AS img, 'uploads/demo/luna-collection.jpg' AS coll, 'W 820 × D 860 × H 1020 mm' AS dims, '18 kg' AS weight, 1 AS sm, 24 AS qty, 'in_stock' AS stock, '2–3 weeks' AS lead, 1 AS feat, 1 AS isnew, 1 AS best, 1 AS ord
    UNION ALL SELECT 'Zen Lounge', 'zen-lounge', 'AE-A0137', 'arki', 'lounge', NULL, 'chair', NULL, NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-2.jpg', NULL, 'W 560 × D 540 × H 820 mm', '6 kg', 1, 12, 'in_stock', NULL, 1, 0, 1, 2
    UNION ALL SELECT 'Zen Swivel Lounge', 'zen-swivel-lounge', 'AE-A0138', 'arki', 'lounge', NULL, 'chair', 'greenguard-gold', NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-3.jpg', NULL, 'W 780 × D 760 × H 700 mm', '22 kg', 1, 3, 'in_stock', NULL, 1, 1, 0, 3
    UNION ALL SELECT 'Zen Bouclé Armchair', 'zen-boucle-armchair', 'AE-A0139', 'arki', 'lounge', NULL, 'chair', NULL, NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-4.jpg', NULL, 'W 760 × D 800 × H 860 mm', '20 kg', 0, 0, 'made_to_order', '6–8 weeks', 1, 0, 0, 4
    UNION ALL SELECT 'Zen Tufted Chair', 'zen-tufted-chair', 'AE-A0141', 'arki', 'seating', 'lounge-chairs', 'chair', NULL, NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-6.jpg', NULL, 'W 620 × D 640 × H 800 mm', '12 kg', 1, 8, 'in_stock', NULL, 1, 0, 0, 5
    UNION ALL SELECT 'Axis Nesting Tables', 'axis-nesting-tables', 'AE-T0202', 'steelcase', 'desks-tables', 'side-tables', 'tables', NULL, NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-7.jpg', NULL, 'Ø 600 / Ø 500 × H 450 mm', '16 kg', 1, 0, 'in_stock', NULL, 1, 0, 0, 6
    UNION ALL SELECT 'Axis Dining Table', 'axis-dining-table', 'AE-T0201', 'steelcase', 'desks-tables', 'dining-tables', 'tables', 'geca', NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-5.jpg', NULL, 'W 1600 × D 900 × H 740 mm', '34 kg', 1, 5, 'in_stock', NULL, 1, 0, 1, 7
    UNION ALL SELECT 'Axis Bedside Cabinet', 'axis-bedside-cabinet', 'AE-S0301', 'steelcase', 'storage-accessories', NULL, 'tables', NULL, NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-9.jpg', NULL, 'W 500 × D 400 × H 560 mm', '14 kg', 0, 0, 'in_stock', NULL, 1, 1, 0, 8
    UNION ALL SELECT 'Axis Side Table', 'axis-side-table', 'AE-T0203', 'steelcase', 'desks-tables', 'side-tables', 'tables', NULL, NULL, 0, 1950.00, 1560.00, 'uploads/demo/store-product-8.jpg', NULL, 'Ø 500 × H 550 mm', '9 kg', 1, 2, 'in_stock', NULL, 1, 0, 0, 9
    UNION ALL SELECT 'Luna High Back Chair', 'luna-high-back-chair', 'AE-A0140', 'arki', 'seating', 'lounge-chairs', 'chair', 'geca', 'luna', 2, 750.00, 600.00, 'uploads/demo/luna-highback.jpg', 'uploads/demo/luna-collection.jpg', 'W 820 × D 880 × H 1180 mm', '21 kg', 0, 0, 'in_stock', NULL, 0, 0, 0, 10
    UNION ALL SELECT 'Luna 2-Seater Sofa', 'luna-2-seater-sofa', 'AE-L0137', 'arki', 'lounge', 'sofas', 'sofa', 'geca', 'luna', 3, 1199.00, 960.00, 'uploads/demo/luna-sofa-2.jpg', 'uploads/demo/luna-collection.jpg', 'W 1420 × D 860 × H 1020 mm', '34 kg', 0, 0, 'made_to_order', '4–6 weeks', 0, 0, 0, 11
    UNION ALL SELECT 'Luna 3-Seater Sofa', 'luna-3-seater-sofa', 'AE-L0138', 'arki', 'lounge', 'sofas', 'sofa', 'geca', 'luna', 4, 1599.00, 1280.00, 'uploads/demo/luna-sofa-3.jpg', 'uploads/demo/luna-collection.jpg', 'W 1980 × D 860 × H 1020 mm', '46 kg', 0, 0, 'made_to_order', '4–6 weeks', 0, 0, 0, 12
) d WHERE @seed = 1;

INSERT IGNORE INTO `product_storefronts` (`product_id`, `storefront`)
SELECT p.`id`, d.sf FROM (
          SELECT 'luna-chair' AS slug, 'work' AS sf
    UNION ALL SELECT 'luna-chair', 'learn'
    UNION ALL SELECT 'zen-lounge', 'work'
    UNION ALL SELECT 'zen-swivel-lounge', 'work'
    UNION ALL SELECT 'zen-swivel-lounge', 'learn'
    UNION ALL SELECT 'zen-tufted-chair', 'learn'
    UNION ALL SELECT 'axis-nesting-tables', 'work'
    UNION ALL SELECT 'axis-dining-table', 'work'
    UNION ALL SELECT 'axis-dining-table', 'learn'
    UNION ALL SELECT 'axis-side-table', 'learn'
    UNION ALL SELECT 'luna-high-back-chair', 'work'
) d JOIN `products` p ON p.`slug` = d.slug
WHERE @seed = 1;

INSERT IGNORE INTO `product_term_links` (`product_id`, `term_id`)
SELECT p.`id`, t.`id` FROM (
          SELECT 'luna-chair' AS slug, 'material' AS tx, 'fabric' AS ts
    UNION ALL SELECT 'luna-chair', 'material', 'timber'
    UNION ALL SELECT 'luna-chair', 'colour', 'light-grey'
    UNION ALL SELECT 'luna-chair', 'colour', 'charcoal'
    UNION ALL SELECT 'luna-chair', 'colour', 'white'
    UNION ALL SELECT 'luna-chair', 'size', 'm'
    UNION ALL SELECT 'luna-chair', 'size', 'l'
    UNION ALL SELECT 'luna-chair', 'size', 'xl'
    UNION ALL SELECT 'zen-lounge', 'material', 'timber'
    UNION ALL SELECT 'zen-lounge', 'colour', 'charcoal'
    UNION ALL SELECT 'zen-swivel-lounge', 'material', 'fabric'
    UNION ALL SELECT 'zen-swivel-lounge', 'colour', 'white'
    UNION ALL SELECT 'zen-boucle-armchair', 'material', 'fabric'
    UNION ALL SELECT 'zen-boucle-armchair', 'colour', 'light-grey'
    UNION ALL SELECT 'zen-tufted-chair', 'material', 'fabric'
    UNION ALL SELECT 'zen-tufted-chair', 'colour', 'white'
    UNION ALL SELECT 'axis-nesting-tables', 'material', 'marble'
    UNION ALL SELECT 'axis-nesting-tables', 'material', 'metal'
    UNION ALL SELECT 'axis-dining-table', 'material', 'timber'
    UNION ALL SELECT 'axis-dining-table', 'colour', 'natural-oak'
    UNION ALL SELECT 'axis-bedside-cabinet', 'material', 'timber'
    UNION ALL SELECT 'axis-bedside-cabinet', 'colour', 'walnut'
    UNION ALL SELECT 'axis-side-table', 'material', 'timber'
    UNION ALL SELECT 'axis-side-table', 'colour', 'walnut'
) d
JOIN `products` p ON p.`slug` = d.slug
JOIN `product_terms` t ON t.`taxonomy` = d.tx AND t.`slug` = d.ts
WHERE @seed = 1;

INSERT INTO `product_images` (`product_id`, `kind`, `path`, `alt`, `sort_order`)
SELECT p.`id`, 'gallery', d.path, d.alt, d.o FROM (
          SELECT 'uploads/demo/luna-1.jpg' AS path, 'Luna Chair – front view' AS alt, 1 AS o
    UNION ALL SELECT 'uploads/demo/luna-2.jpg', 'Luna Chair – back view', 2
    UNION ALL SELECT 'uploads/demo/luna-3.jpg', 'Luna Chair – seat detail', 3
    UNION ALL SELECT 'uploads/demo/luna-4.jpg', 'Luna Chair – leg detail', 4
) d JOIN `products` p ON p.`slug` = 'luna-chair'
WHERE @seed = 1;

INSERT INTO `product_specs` (`product_id`, `name`, `value`, `sort_order`)
SELECT p.`id`, d.n, d.v, d.o FROM (
          SELECT 'Chair Type' AS n, 'Lounge / Armchair' AS v, 1 AS o
    UNION ALL SELECT 'Design Style', 'Contemporary', 2
    UNION ALL SELECT 'Upholstery', 'Premium fabric upholstery', 3
    UNION ALL SELECT 'Frame', 'Solid timber frame', 4
    UNION ALL SELECT 'Leg Finish', 'Natural timber', 5
    UNION ALL SELECT 'Seat Type', 'Padded upholstered seat', 6
    UNION ALL SELECT 'Backrest', 'High supportive backrest', 7
    UNION ALL SELECT 'Armrests', 'Integrated curved armrests', 8
    UNION ALL SELECT 'Assembly', 'Minimal assembly required', 9
    UNION ALL SELECT 'Application', 'Residential & Commercial', 10
) d JOIN `products` p ON p.`slug` = 'luna-chair'
WHERE @seed = 1;

INSERT INTO `product_variants` (`product_id`, `size_id`, `colour_id`, `material_id`, `sku`, `retail_price`, `agent_price`, `image`, `stock_qty`, `availability`, `sort_order`)
SELECT p.`id`,
       (SELECT `id` FROM `product_terms` WHERE `taxonomy` = 'size' AND `slug` = d.sz),
       (SELECT `id` FROM `product_terms` WHERE `taxonomy` = 'colour' AND `slug` = d.co),
       NULL, d.sku, d.price, d.agent, NULL, NULL, d.av, d.o
FROM (
          SELECT 'xl' AS sz, NULL AS co, 'AE-A0136-XL' AS sku, 520.00 AS price, 416.00 AS agent, NULL AS av, 1 AS o
    UNION ALL SELECT NULL, 'charcoal', 'AE-A0136-CH', NULL, NULL, 'made_to_order', 2
) d JOIN `products` p ON p.`slug` = 'luna-chair'
WHERE @seed = 1;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('products_demo_seeded', '1')
ON DUPLICATE KEY UPDATE `setting_value` = '1';
SQL;
