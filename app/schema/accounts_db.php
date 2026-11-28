<?php
/**
 * Customer / agent accounts, sign-in tokens, shopping carts and the email outbox.
 * Applied once by app/Migrations.php; the same SQL is in database-update-accounts.sql.
 * Every statement is safe to run again.
 *
 * Email (not sent yet): messages are written to `email_outbox` with status
 * 'pending'. When the website goes live, a mail sender only has to read that
 * table – password reset and email-verification tokens are already stored in
 * `user_tokens`, and `users.email_verified_at` is ready for verification.
 */
return <<<'SQL'
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_type` varchar(10) NOT NULL DEFAULT 'customer',
  `status` varchar(10) NOT NULL DEFAULT 'active',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `abn` varchar(20) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `state` varchar(40) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `newsletter` tinyint(1) NOT NULL DEFAULT 0,
  `admin_note` text DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_email` (`email`),
  KEY `idx_user_list` (`account_type`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `selector` char(24) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token_selector` (`selector`),
  KEY `idx_token_user` (`user_id`,`type`),
  CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `carts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` char(64) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_token` (`token_hash`),
  KEY `idx_cart_user` (`user_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `size_id` int(10) unsigned DEFAULT NULL,
  `colour_id` int(10) unsigned DEFAULT NULL,
  `material_id` int(10) unsigned DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_item_cart` (`cart_id`),
  CONSTRAINT `fk_item_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_outbox` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template` varchar(40) NOT NULL,
  `to_email` varchar(190) NOT NULL,
  `to_name` varchar(200) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `payload` text DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_outbox_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Header: Login button → login page, cart icon → cart page (only links that are still empty or "#").
UPDATE `sections`
SET `data` = JSON_SET(`data`,
        '$.login_url', IF(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.login_url')), '') IN ('', '#'), 'account/login/', JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.login_url'))),
        '$.cart_url',  IF(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.cart_url')), '') IN ('', '#'), 'cart/', JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.cart_url')))),
    `updated_at` = NOW()
WHERE `page` = 'global' AND `section_key` = 'header';
SQL;
