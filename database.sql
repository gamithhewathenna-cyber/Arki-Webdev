-- =====================================================================
--  ARKI Environments – database (homepage content + CMS)
--
--  HOW TO IMPORT (cPanel → phpMyAdmin):
--    1. Select your database in the left-hand list (e.g. cpuser_arki).
--    2. Click "Import", choose this file, click "Import" / "Go".
--    Do NOT create tables first – this file creates everything.
--    Re-importing replaces all tables (and any CMS edits) with this content.
--
--  Default logins (CHANGE BOTH after the first login):
--    CMS  /admin/        username: admin     password: ArkiAdmin@2026
--    Website preview password (site lock is ON):  arki-preview
--
--  Compatible with MySQL 5.7+ and MariaDB 10.3+. Character set utf8mb4.
-- =====================================================================


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `display_name` varchar(120) NOT NULL DEFAULT '',
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES
(1,'admin','admin','$2y$10$OEaWjm/AMW/RggckzeRU2.km.YqsibivTUGUTmmwgfc5wJfiQtZ.2',1,NULL,'2026-11-27 08:00:00');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(20) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attempt_lookup` (`scope`,`ip`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;

--
-- Table structure for table `section_items`
--

DROP TABLE IF EXISTS `section_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `section_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `data` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_section_order` (`section_id`,`sort_order`),
  CONSTRAINT `fk_item_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `section_items`
--

/*!40000 ALTER TABLE `section_items` DISABLE KEYS */;
INSERT INTO `section_items` VALUES
(1,1,'{\"title\":\"Work\",\"subtitle\":\"Commercial office furniture\",\"image\":\"uploads/demo/hero-work.jpg\",\"image_alt\":\"Open-plan office with workstations\",\"url\":\"#\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(2,1,'{\"title\":\"Learn\",\"subtitle\":\"Education furniture\",\"image\":\"uploads/demo/hero-learn.jpg\",\"image_alt\":\"Classroom with desks and chairs\",\"url\":\"#\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(3,1,'{\"title\":\"Store\",\"subtitle\":\"Hone residential home office\",\"image\":\"uploads/demo/hero-store.jpg\",\"image_alt\":\"Home office with desk and shelving\",\"url\":\"#\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(4,3,'{\"image\":\"uploads/demo/gal-1.jpg\",\"alt\":\"Lounge area with sofa and ottomans\",\"url\":\"\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(5,3,'{\"image\":\"uploads/demo/gal-2.jpg\",\"alt\":\"Collaborative breakout space\",\"url\":\"\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(6,3,'{\"image\":\"uploads/demo/gal-3.jpg\",\"alt\":\"Team working at shared desks\",\"url\":\"\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(7,3,'{\"image\":\"uploads/demo/gal-4.jpg\",\"alt\":\"Sofa and coffee table\",\"url\":\"\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(8,3,'{\"image\":\"uploads/demo/gal-5.jpg\",\"alt\":\"Lounge chair in a quiet corner\",\"url\":\"\"}',1,5,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(9,4,'{\"title\":\"Workplace\\nFurniture\",\"text\":\"Quality furniture solutions for individual work, meetings, collaboration and shared environments.\",\"link_label\":\"Explore More\",\"url\":\"#\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(10,4,'{\"title\":\"Workplace\\nSolutions\",\"text\":\"Flexible solutions designed around how your people work, connect and collaborate.\",\"link_label\":\"Explore More\",\"url\":\"#\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(11,4,'{\"title\":\"Project\\nSupport\",\"text\":\"From product selection through to delivery and installation, we help make workplace projects easier.\",\"link_label\":\"Explore More\",\"url\":\"#\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(12,4,'{\"title\":\"Commercial\\nEnvironments\",\"text\":\"Solutions for offices, corporate spaces, education, hospitality and other professional environments.\",\"link_label\":\"Explore More\",\"url\":\"#\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(13,5,'{\"image\":\"uploads/demo/proj-1.jpg\",\"image_alt\":\"Lounge with leather sofa\",\"title\":\"XYZ Headquarters\",\"meta\":\"Melbourne, VIC · Workplace\",\"link_label\":\"View Products\",\"url\":\"#\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(14,5,'{\"image\":\"uploads/demo/proj-2.jpg\",\"image_alt\":\"Minimal reception with stair\",\"title\":\"XYZ Headquarters\",\"meta\":\"Melbourne, VIC · Workplace\",\"link_label\":\"View Products\",\"url\":\"#\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(15,5,'{\"image\":\"uploads/demo/proj-3.jpg\",\"image_alt\":\"Double-height lounge\",\"title\":\"XYZ Headquarters\",\"meta\":\"Melbourne, VIC · Workplace\",\"link_label\":\"View Products\",\"url\":\"#\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(16,5,'{\"image\":\"uploads/demo/proj-1.jpg\",\"image_alt\":\"Lounge with leather sofa\",\"title\":\"XYZ Headquarters\",\"meta\":\"Melbourne, VIC · Workplace\",\"link_label\":\"View Products\",\"url\":\"#\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(17,6,'{\"name\":\"Stem Society\",\"logo\":\"uploads/demo/brand-1.png\",\"url\":\"\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(18,6,'{\"name\":\"Luxmuse Studio\",\"logo\":\"uploads/demo/brand-2.png\",\"url\":\"\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(19,6,'{\"name\":\"Dwelling Place\",\"logo\":\"uploads/demo/brand-3.png\",\"url\":\"\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(20,6,'{\"name\":\"Fifty Six\",\"logo\":\"uploads/demo/brand-4.png\",\"url\":\"\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(21,6,'{\"name\":\"Cresswell\",\"logo\":\"uploads/demo/brand-5.png\",\"url\":\"\"}',1,5,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(22,6,'{\"name\":\"Refined Beauty\",\"logo\":\"uploads/demo/brand-6.png\",\"url\":\"\"}',1,6,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(23,6,'{\"name\":\"Stem Society\",\"logo\":\"uploads/demo/brand-1.png\",\"url\":\"\"}',1,7,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(24,6,'{\"name\":\"Luxmuse Studio\",\"logo\":\"uploads/demo/brand-2.png\",\"url\":\"\"}',1,8,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(25,7,'{\"image\":\"uploads/demo/insp-1.jpg\",\"alt\":\"Sideboard with lamp and artwork\",\"url\":\"#\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(26,7,'{\"image\":\"uploads/demo/insp-2.jpg\",\"alt\":\"Upholstered lounge chair\",\"url\":\"#\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(27,8,'{\"name\":\"Lorem Ipsum Dolor\",\"role\":\"Lorem Ipsum Dolor\",\"photo\":\"uploads/demo/av-1.jpg\",\"quote\":\"Lorem ipsum dolor sit amet consectetur. Vel lacus sit sit accumsan adipiscing felis pretium. Et et eu tincidunt tincidunt ut iaculis rhoncus ggaud kkidi oqu in.\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(28,8,'{\"name\":\"Lorem Ipsum Dolor\",\"role\":\"Lorem Ipsum Dolor\",\"photo\":\"uploads/demo/av-2.jpg\",\"quote\":\"Lorem ipsum dolor sit amet consectetur. Vel lacus sit sit accumsan adipiscing felis pretium. Et et eu tincidunt tincidunt ut iaculis rhoncus ggaud kkidi oqu in.\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(29,8,'{\"name\":\"Lorem Ipsum Dolor\",\"role\":\"Lorem Ipsum Dolor\",\"photo\":\"uploads/demo/av-3.jpg\",\"quote\":\"Lorem ipsum dolor sit amet consectetur. Vel lacus sit sit accumsan adipiscing felis pretium. Et et eu tincidunt tincidunt ut iaculis rhoncus ggaud kkidi oqu in.\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(30,8,'{\"name\":\"Lorem Ipsum Dolor\",\"role\":\"Lorem Ipsum Dolor\",\"photo\":\"uploads/demo/av-4.jpg\",\"quote\":\"Lorem ipsum dolor sit amet consectetur. Vel lacus sit sit accumsan adipiscing felis pretium. Et et eu tincidunt tincidunt ut iaculis rhoncus ggaud kkidi oqu in.\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(31,11,'{\"label\":\"Products\",\"url\":\"#\",\"submenu\":\"Seating | #\\nLounge | #\\nStorage & Accessories | #\\nDesks & Tables | #\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(32,11,'{\"label\":\"Projects\",\"url\":\"#\",\"submenu\":\"\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(33,11,'{\"label\":\"Research\",\"url\":\"#\",\"submenu\":\"\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(34,11,'{\"label\":\"About Us\",\"url\":\"#\",\"submenu\":\"\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(35,11,'{\"label\":\"Contact Us\",\"url\":\"#\",\"submenu\":\"\"}',1,5,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(36,13,'{\"column\":\"links\",\"label\":\"Home\",\"url\":\"/\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(37,13,'{\"column\":\"links\",\"label\":\"Project\",\"url\":\"#\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(38,13,'{\"column\":\"links\",\"label\":\"Research\",\"url\":\"#\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(39,13,'{\"column\":\"links\",\"label\":\"Solutions\",\"url\":\"#\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(40,13,'{\"column\":\"links\",\"label\":\"About Us\",\"url\":\"#\"}',1,5,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(41,13,'{\"column\":\"links\",\"label\":\"Contact Us\",\"url\":\"#\"}',1,6,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(42,13,'{\"column\":\"products\",\"label\":\"Seating\",\"url\":\"#\"}',1,7,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(43,13,'{\"column\":\"products\",\"label\":\"Lounge\",\"url\":\"#\"}',1,8,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(44,13,'{\"column\":\"products\",\"label\":\"Privacy\",\"url\":\"#\"}',1,9,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(45,13,'{\"column\":\"products\",\"label\":\"Storage & Accessories\",\"url\":\"#\"}',1,10,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(46,13,'{\"column\":\"products\",\"label\":\"Desks & Tables\",\"url\":\"#\"}',1,11,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(47,14,'{\"icon\":\"location\",\"label\":\"Location\",\"url\":\"#\"}',1,1,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(48,14,'{\"icon\":\"phone\",\"label\":\"Phone\",\"url\":\"#\"}',1,2,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(49,14,'{\"icon\":\"email\",\"label\":\"Email\",\"url\":\"#\"}',1,3,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(50,14,'{\"icon\":\"facebook\",\"label\":\"Facebook\",\"url\":\"#\"}',1,4,'2026-11-27 08:00:00','2026-11-27 08:00:00'),
(51,14,'{\"icon\":\"instagram\",\"label\":\"Instagram\",\"url\":\"#\"}',1,5,'2026-11-27 08:00:00','2026-11-27 08:00:00');
/*!40000 ALTER TABLE `section_items` ENABLE KEYS */;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page` varchar(60) NOT NULL,
  `section_key` varchar(60) NOT NULL,
  `data` longtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_page_section` (`page`,`section_key`),
  KEY `idx_page_order` (`page`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES
(1,'home','hero','[]',1,1,'2026-11-27 08:00:00'),
(2,'home','intro','{\"tagline\":\"Thoughtful Spaces. Quality Products. Better Ways Of Working.\",\"heading\":\"Creating Better Workplace Environments\",\"body\":\"ARKI Environments delivers workplace furniture and environment solutions for businesses looking to create functional, comfortable and inspiring spaces.\\n\\nWe work with organisations, designers and project teams to provide carefully selected furniture solutions that balance performance, aesthetics and the needs of the people who use them.\\n\\nFrom a single product to a complete workplace solution, our approach is centred around understanding your space and helping you make the right choices.\",\"link_label\":\"About ARKI Environments\",\"link_url\":\"#\"}',1,2,'2026-11-27 08:00:00'),
(3,'home','gallery','[]',1,3,'2026-11-27 08:00:00'),
(4,'home','solutions','{\"tagline\":\"Supporting Every Part Of Your Workplace\",\"heading\":\"Our Solutions\",\"body\":\"Lorem ipsum dolor sit amet consectetur. Lobortis sed consequat purus ut elementum. Et dapibus sed duis vulputate elementum fringilla pretium mattis sit. Orci consectetur velit dis diam purus nisl magna sem massa.\"}',1,4,'2026-11-27 08:00:00'),
(5,'home','projects','{\"tagline\":\"See ARKI In The Workplace\",\"heading\":\"Our Project\",\"body\":\"Explore environments where furniture, functionality and thoughtful design come together.\\nFrom individual spaces to complete workplace transformations, discover how ARKI solutions are being used in real-world environments\",\"button_label\":\"Explore Our Projects\",\"button_url\":\"#\"}',1,5,'2026-11-27 08:00:00'),
(6,'home','brands','{\"heading\":\"Quality Brands.\\nConsidered Solutions.\",\"body\":\"We partner with leading furniture brands and manufacturers to bring quality, innovative and design-led workplace products to our clients. Our collection is carefully selected to provide solutions for different spaces, working styles and project requirements.\"}',1,6,'2026-11-27 08:00:00'),
(7,'home','inspiration','{\"tagline\":\"Inspiration\",\"heading\":\"Ideas For Better\\nWorkplaces\",\"body\":\"Discover workplace ideas, product insights, design inspiration and practical guidance for creating spaces where people can work better.\",\"button_label\":\"Explore Inspiration\",\"button_url\":\"#\"}',1,7,'2026-11-27 08:00:00'),
(8,'home','testimonials','{\"heading\":\"What Our\\nCustomers Say\"}',1,8,'2026-11-27 08:00:00'),
(9,'home','cta','{\"image\":\"uploads/demo/cta-bg.jpg\",\"image_alt\":\"Bright living space with sofa and round coffee tables\",\"tagline\":\"More Than A Furniture Store\",\"heading\":\"Let\'s Create A Better\\nWorkplace\",\"body\":\"Planning a new workplace, upgrading your existing space or simply looking for the right furniture?\\n\\nTalk to the ARKI Environments team about your requirements and discover solutions designed around your space, people and business.\",\"primary_label\":\"Start A Conversation\",\"primary_url\":\"#\",\"secondary_label\":\"Explore Products\",\"secondary_url\":\"#\"}',1,9,'2026-11-27 08:00:00'),
(10,'global','header','{\"logo\":\"uploads/demo/logo-header.png\",\"logo_alt\":\"Steelcase | ARKI Environments\",\"login_label\":\"Login\",\"login_url\":\"#\",\"search_url\":\"#\",\"cart_url\":\"#\"}',1,1,'2026-11-27 08:00:00'),
(11,'global','navigation','[]',1,2,'2026-11-27 08:00:00'),
(12,'global','footer','{\"brand_logo\":\"uploads/demo/logo-arki.png\",\"brand_text\":\"Lorem ipsum dolor sit amet consectetur. Condimentum convallis nulla nec ultrices convallis non turpis. Euismod lacus nunc in id justo ac sed. Sit habitant donec massa semper nibh maecenas.\\n\\nSit senectus ullamcorper massa turpis ac orci egestas. Augue tellus sed urna vehicula semper.\",\"dealer_logo\":\"uploads/demo/logo-steelcase.png\",\"dealer_title\":\"Steelcase Authorised Dealer\",\"dealer_text\":\"Lorem ipsum dolor sit amet consectetur. Condimentum convallis nulla nec ultrices convallis non turpis. Euismod lacus nunc in id justo ac sed. Sit habitant donec massa semper nibh maecenas.\",\"links_title\":\"Link\",\"products_title\":\"Product\",\"payments_title\":\"Safe & Secure Payments\",\"payments_text\":\"Secure payments through trusted payment providers.\",\"payments_image\":\"uploads/demo/payments.png\",\"copyright\":\"© {year} Arki Environments. All rights reserved.\",\"terms_label\":\"Terms and Conditions\",\"terms_url\":\"#\",\"privacy_label\":\"Privacy Policy\",\"privacy_url\":\"#\"}',1,3,'2026-11-27 08:00:00'),
(13,'global','footer_links','[]',1,4,'2026-11-27 08:00:00'),
(14,'global','footer_contacts','[]',1,5,'2026-11-27 08:00:00');
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('dev_lock_enabled','1'),
('dev_lock_password','$2y$10$Tnr2AIPPRIuLpTaO1tjUtejcuHDK2.k83BH5h5QVHx3eLZ8xhbLYS'),
('home_title','ARKI Environments – Workplace Furniture & Environments'),
('site_name','ARKI Environments');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
