
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
DROP TABLE IF EXISTS `amenities_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amenities_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cities_state_name` (`state_id`,`name`),
  CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_countries_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listing_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `listing_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_listing_types_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `localities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city_id` int(11) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_localities_city_name` (`city_id`,`name`),
  CONSTRAINT `localities_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mail_outbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_outbox` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipient` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` mediumtext NOT NULL,
  `transport` varchar(20) NOT NULL DEFAULT 'log',
  `status` varchar(20) NOT NULL DEFAULT 'logged',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mail_outbox_status_created` (`status`,`created_at`),
  KEY `idx_mail_outbox_recipient_created` (`recipient`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `draft_id` bigint(20) unsigned DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending','active','rejected','inactive','booked','sold','rented','occupied','deleted') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `rejected_reason` varchar(255) DEFAULT NULL,
  `owner_status_reason` varchar(500) DEFAULT NULL,
  `owner_status_updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `draft_id` (`draft_id`),
  KEY `idx_properties_status_created` (`status`,`created_at`),
  CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `properties_ibfk_2` FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_amenities` (
  `draft_id` bigint(20) unsigned NOT NULL,
  `amenity_id` int(11) NOT NULL,
  PRIMARY KEY (`draft_id`,`amenity_id`),
  KEY `amenity_id` (`amenity_id`),
  CONSTRAINT `property_amenities_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_basic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_basic` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` bigint(20) unsigned DEFAULT NULL,
  `property_type_id` int(11) DEFAULT NULL,
  `listing_type_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `posted_by` enum('owner','agent','builder') DEFAULT NULL,
  `purpose_note` varchar(150) DEFAULT NULL,
  `available_from` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_basic_draft_id` (`draft_id`),
  KEY `property_type_id` (`property_type_id`),
  KEY `listing_type_id` (`listing_type_id`),
  CONSTRAINT `property_basic_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_basic_ibfk_2` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`),
  CONSTRAINT `property_basic_ibfk_3` FOREIGN KEY (`listing_type_id`) REFERENCES `listing_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_drafts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `current_step` int(11) DEFAULT 1,
  `last_completed_step` int(11) DEFAULT NULL,
  `completion_percent` decimal(5,2) DEFAULT 0.00,
  `is_submitted` tinyint(1) DEFAULT 0,
  `submitted_at` datetime DEFAULT NULL,
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `property_drafts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_enquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `property_ref` varchar(120) NOT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `owner_user_id` bigint(20) unsigned DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `listing_type` varchar(80) DEFAULT NULL,
  `title` varchar(180) DEFAULT NULL,
  `price_text` varchar(80) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `locality` varchar(120) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `source` varchar(80) DEFAULT NULL,
  `enquiry_type` varchar(30) DEFAULT NULL,
  `preferred_contact` varchar(20) DEFAULT NULL,
  `consent_at` datetime DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','closed','cancelled') NOT NULL DEFAULT 'new',
  `metadata` longtext DEFAULT NULL,
  `notification_status` varchar(30) NOT NULL DEFAULT 'pending',
  `admin_notified_at` datetime DEFAULT NULL,
  `owner_notified_at` datetime DEFAULT NULL,
  `customer_notified_at` datetime DEFAULT NULL,
  `notification_error` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_property_enquiries_user_created` (`user_id`,`created_at`),
  KEY `idx_property_enquiries_status_created` (`status`,`created_at`),
  KEY `idx_property_enquiries_property_ref` (`property_ref`),
  KEY `idx_property_enquiries_property_id` (`property_id`),
  KEY `idx_property_enquiries_owner_created` (`owner_user_id`,`created_at`),
  CONSTRAINT `fk_property_enquiries_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_property_enquiries_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_property_enquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `property_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','converted','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `property_leads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `property_leads_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_location` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` bigint(20) unsigned DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `locality_id` int(11) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `map_address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_map_exact` tinyint(1) NOT NULL DEFAULT 1,
  `landmark` varchar(150) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_location_draft_id` (`draft_id`),
  KEY `idx_property_location_country` (`country_id`),
  KEY `idx_property_location_state` (`state_id`),
  KEY `idx_property_location_city` (`city_id`),
  KEY `idx_property_location_locality` (`locality_id`),
  CONSTRAINT `fk_property_location_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_property_location_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_property_location_locality` FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_property_location_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `property_location_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` enum('upload','youtube') NOT NULL DEFAULT 'upload',
  `file_url` text DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `thumbnail_url` text DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `video_provider` varchar(30) DEFAULT NULL,
  `type` enum('image','video','voice') DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `draft_id` (`draft_id`),
  CONSTRAINT `property_media_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_pricing` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` bigint(20) unsigned DEFAULT NULL,
  `expected_price` decimal(15,2) DEFAULT NULL,
  `price_per_area_unit` decimal(10,2) DEFAULT NULL,
  `price_per_sqft` decimal(10,2) DEFAULT NULL,
  `rent` decimal(15,2) DEFAULT NULL,
  `deposit` varchar(100) DEFAULT NULL,
  `security_deposit_type` varchar(20) DEFAULT NULL,
  `security_deposit_amount` decimal(15,2) DEFAULT NULL,
  `security_deposit_months` smallint(5) unsigned DEFAULT NULL,
  `booking_amount` decimal(15,2) DEFAULT NULL,
  `maintenance` decimal(10,2) DEFAULT NULL,
  `maintenance_period` varchar(20) DEFAULT NULL,
  `electricity_charges` varchar(100) DEFAULT NULL,
  `brokerage` varchar(100) DEFAULT NULL,
  `brokerage_type` varchar(20) DEFAULT NULL,
  `brokerage_value` decimal(15,2) DEFAULT NULL,
  `brokerage_negotiable` tinyint(1) NOT NULL DEFAULT 0,
  `lock_in_months` smallint(5) unsigned DEFAULT NULL,
  `annual_rent_increase_percent` decimal(5,2) DEFAULT NULL,
  `dg_ups_included` tinyint(1) NOT NULL DEFAULT 0,
  `electricity_water_excluded` tinyint(1) NOT NULL DEFAULT 0,
  `negotiable` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_pricing_draft_id` (`draft_id`),
  CONSTRAINT `property_pricing_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_profile` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` bigint(20) unsigned DEFAULT NULL,
  `builtup_area` decimal(10,2) DEFAULT NULL,
  `super_builtup_area` decimal(10,2) DEFAULT NULL,
  `carpet_area` decimal(10,2) DEFAULT NULL,
  `plot_area` decimal(10,2) DEFAULT NULL,
  `area_unit` varchar(20) DEFAULT 'sqft',
  `bedrooms` int(11) DEFAULT NULL,
  `bathrooms` int(11) DEFAULT NULL,
  `balconies` int(11) DEFAULT NULL,
  `parking_count` int(11) DEFAULT NULL,
  `servant_room` tinyint(1) NOT NULL DEFAULT 0,
  `pooja_room` tinyint(1) NOT NULL DEFAULT 0,
  `study_room` tinyint(1) NOT NULL DEFAULT 0,
  `floor_no` int(11) DEFAULT NULL,
  `total_floor` int(11) DEFAULT NULL,
  `furnishing` enum('unfurnished','semi','fully') DEFAULT NULL,
  `furnishing_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`furnishing_items`)),
  `profile_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`profile_details`)),
  `property_age` varchar(50) DEFAULT NULL,
  `facing` varchar(50) DEFAULT NULL,
  `ownership_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_profile_draft_id` (`draft_id`),
  CONSTRAINT `property_profile_ibfk_1` FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `category` enum('residential','commercial','land') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_property_types_category_name` (`category`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saved_properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saved_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `property_ref` varchar(120) NOT NULL,
  `listing_type` varchar(80) DEFAULT NULL,
  `title` varchar(180) DEFAULT NULL,
  `price_text` varchar(80) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `locality` varchar(120) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `image_url` varchar(600) DEFAULT NULL,
  `details_url` varchar(600) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_saved_property_user_ref` (`user_id`,`property_ref`),
  KEY `idx_saved_properties_user_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_saved_properties_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_states_country_name` (`country_id`,`name`),
  CONSTRAINT `states_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `activity_type` varchar(60) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` varchar(120) DEFAULT NULL,
  `search_query` varchar(255) DEFAULT NULL,
  `listing_type` varchar(80) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `page_url` varchar(600) DEFAULT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_activity_user_created` (`user_id`,`created_at`),
  KEY `idx_user_activity_session_created` (`session_id`,`created_at`),
  KEY `idx_user_activity_type_created` (`activity_type`,`created_at`),
  CONSTRAINT `fk_user_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_otps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(10) unsigned NOT NULL,
  `otp` varchar(6) NOT NULL,
  `type` enum('email','phone') DEFAULT 'email',
  `expires_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_user_otp_user` (`user_id`),
  CONSTRAINT `fk_user_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','tenant','owner','agent','builder','admin') DEFAULT 'customer',
  `status` enum('active','blocked','deleted') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  KEY `idx_users_role_status` (`role`,`status`),
  KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


