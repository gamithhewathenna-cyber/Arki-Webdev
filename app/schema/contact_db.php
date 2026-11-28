<?php
/**
 * Contact page: enquiries table + "Contact Us" menu/footer links.
 * Applied once by app/Migrations.php; the same SQL is in database-update-contact.sql.
 * Every statement is safe to run again.
 */
return <<<'SQL'
CREATE TABLE IF NOT EXISTS `enquiries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `topic` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `newsletter` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `emailed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_enquiry_list` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Contact Us" in the header menu and the footer → the new page (only links that are still empty or "#").
UPDATE `section_items` i
JOIN `sections` s ON s.`id` = i.`section_id` AND s.`page` = 'global' AND s.`section_key` IN ('navigation', 'footer_links')
SET i.`data` = JSON_SET(i.`data`, '$.url', 'contact/'), i.`updated_at` = NOW()
WHERE LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.label')))) IN ('contact us', 'contact')
  AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(i.`data`, '$.url')), '') IN ('', '#');
SQL;
