/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `progress` int unsigned NOT NULL DEFAULT '0',
  `target` int unsigned NOT NULL DEFAULT '1',
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievements_user_id_type_unique` (`user_id`,`type`),
  KEY `achievements_type_index` (`type`),
  CONSTRAINT `achievements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `attribute_changes` json DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_broadcasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sent_by` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_ar` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_segment` enum('all_users','active_users','inactive_users','specific_city','specific_users') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_filters` json DEFAULT NULL,
  `target_user_ids` json DEFAULT NULL,
  `recipients_count` int unsigned NOT NULL DEFAULT '0',
  `status` enum('pending','sending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_broadcasts_sent_by_foreign` (`sent_by`),
  CONSTRAINT `admin_broadcasts_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_configs_key_unique` (`key`),
  KEY `app_configs_is_public_index` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_environments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_environments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `platform_id` bigint unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `app_environments_platform_id_index` (`platform_id`),
  KEY `app_environments_platform_id_is_active_index` (`platform_id`,`is_active`),
  CONSTRAINT `app_environments_platform_id_foreign` FOREIGN KEY (`platform_id`) REFERENCES `app_platforms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_platforms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `platform_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` json NOT NULL,
  `latest_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_required_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direct_apk_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direct_apk_enabled` tinyint NOT NULL DEFAULT '0',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `order_column` int unsigned NOT NULL DEFAULT '0',
  `last_updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_platforms_platform_key_unique` (`platform_key`),
  KEY `app_platforms_is_active_index` (`is_active`),
  KEY `app_platforms_last_updated_by_foreign` (`last_updated_by`),
  CONSTRAINT `app_platforms_last_updated_by_foreign` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `platform` enum('ios','android') COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `build_number` int NOT NULL,
  `is_force_update` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `release_notes` text COLLATE utf8mb4_unicode_ci,
  `release_notes_ar` text COLLATE utf8mb4_unicode_ci,
  `released_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_versions_platform_version_unique` (`platform`,`version`),
  KEY `app_versions_platform_is_active_index` (`platform`,`is_active`),
  KEY `app_versions_platform_index` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `changes` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` text COLLATE utf8mb4_unicode_ci,
  `subtitle_ar` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url_dark` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_value` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` enum('home_top','home_middle','home_bottom','venue_list','event_list','profile') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home_top',
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `clicks_count` int unsigned NOT NULL DEFAULT '0',
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banners_position_is_active_index` (`position`,`is_active`),
  KEY `banners_starts_at_ends_at_index` (`starts_at`,`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blog_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excerpt_ar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_ar` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `tags` json DEFAULT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `likes_count` int unsigned NOT NULL DEFAULT '0',
  `reading_time_minutes` int unsigned NOT NULL DEFAULT '5',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_articles_slug_unique` (`slug`),
  KEY `blog_articles_is_published_published_at_index` (`is_published`,`published_at`),
  KEY `blog_articles_category_index` (`category`),
  KEY `blog_articles_is_featured_index` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_instances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_start_time` time NOT NULL,
  `scheduled_end_time` time NOT NULL,
  `status` enum('scheduled','created','skipped','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `payment_status` enum('pending','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `skipped_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `payment_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `last_payment_attempt_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_instances_booking_id_foreign` (`booking_id`),
  KEY `booking_instances_scheduled_date_index` (`scheduled_date`),
  KEY `booking_instances_subscription_id_scheduled_date_index` (`subscription_id`,`scheduled_date`),
  KEY `booking_instances_status_scheduled_date_index` (`status`,`scheduled_date`),
  CONSTRAINT `booking_instances_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_instances_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('invited','accepted','declined','confirmed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invited',
  `payment_share` int unsigned NOT NULL,
  `payment_status` enum('pending','paid','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `invited_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_participants_booking_id_user_id_unique` (`booking_id`,`user_id`),
  KEY `booking_participants_user_id_index` (`user_id`),
  KEY `booking_participants_booking_id_payment_status_index` (`booking_id`,`payment_status`),
  CONSTRAINT `booking_participants_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_splits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_splits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','overdue','waived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_id` bigint unsigned DEFAULT NULL,
  `payment_due_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_payment_splits_booking_id_user_id_unique` (`booking_id`,`user_id`),
  KEY `booking_payment_splits_user_id_foreign` (`user_id`),
  KEY `booking_payment_splits_payment_id_foreign` (`payment_id`),
  KEY `booking_payment_splits_booking_id_status_index` (`booking_id`,`status`),
  CONSTRAINT `booking_payment_splits_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_payment_splits_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_payment_splits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rescheduled_from_booking_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `captain_id` bigint unsigned DEFAULT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `venue_id` bigint unsigned NOT NULL,
  `sport_category_id` bigint unsigned DEFAULT NULL,
  `booking_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_code` text COLLATE utf8mb4_unicode_ci,
  `source` enum('mobile','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mobile',
  `manual_type` enum('external','blocked') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manual_note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending_payment','confirmed','scheduled','checked_in','cancelled','completed','no_show','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `refund_status` enum('none','requested','partially_refunded','fully_refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `duration_minutes` smallint unsigned NOT NULL,
  `venue_price` int unsigned NOT NULL DEFAULT '0',
  `commission_amount` int unsigned NOT NULL DEFAULT '0',
  `commission_type` enum('fixed','percentage') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_price` int unsigned NOT NULL DEFAULT '0',
  `club_payout_amount` int unsigned NOT NULL DEFAULT '0',
  `cancellation_commission` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYP',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `is_recurring` tinyint NOT NULL DEFAULT '0',
  `is_group_booking` tinyint(1) NOT NULL DEFAULT '0',
  `group_size` int unsigned DEFAULT NULL,
  `payment_split_type` enum('full','equal','custom','individual') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_split_data` json DEFAULT NULL,
  `recurrence_pattern` json DEFAULT NULL,
  `recurrence_parent_id` bigint unsigned DEFAULT NULL,
  `reminder_2h_sent_at` timestamp NULL DEFAULT NULL,
  `reminder_1h_sent_at` timestamp NULL DEFAULT NULL,
  `deposit_amount` int unsigned NOT NULL DEFAULT '0',
  `deposit_status` enum('none','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `remaining_amount` int unsigned NOT NULL DEFAULT '0',
  `remaining_status` enum('none','due_on_arrival','confirmed','waived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `remaining_confirmed_at` timestamp NULL DEFAULT NULL,
  `remaining_confirmed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reschedule_history` json DEFAULT NULL,
  `reschedule_count` int NOT NULL DEFAULT '0',
  `applied_promotion_id` bigint unsigned DEFAULT NULL,
  `discount_amount` int unsigned NOT NULL DEFAULT '0',
  `is_split_payment` tinyint(1) NOT NULL DEFAULT '0',
  `split_method` enum('equal','custom') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_booking_code_unique` (`booking_code`),
  KEY `bookings_user_id_index` (`user_id`),
  KEY `bookings_venue_id_index` (`venue_id`),
  KEY `bookings_status_index` (`status`),
  KEY `bookings_booking_date_index` (`booking_date`),
  KEY `bookings_starts_at_index` (`starts_at`),
  KEY `bookings_venue_id_booking_date_status_index` (`venue_id`,`booking_date`,`status`),
  KEY `bookings_venue_id_booking_date_start_time_end_time_status_index` (`venue_id`,`booking_date`,`start_time`,`end_time`,`status`),
  KEY `bookings_status_booking_date_reminder_2h_sent_at_index` (`status`,`booking_date`,`reminder_2h_sent_at`),
  KEY `bookings_recurrence_parent_id_index` (`recurrence_parent_id`),
  KEY `bookings_source_index` (`source`),
  KEY `bookings_remaining_status_index` (`remaining_status`),
  KEY `bookings_sport_category_id_foreign` (`sport_category_id`),
  KEY `bookings_cancelled_by_foreign` (`cancelled_by`),
  KEY `bookings_remaining_confirmed_by_foreign` (`remaining_confirmed_by`),
  KEY `bookings_subscription_id_index` (`subscription_id`),
  KEY `bookings_captain_id_index` (`captain_id`),
  KEY `bookings_team_id_index` (`team_id`),
  KEY `bookings_is_group_booking_index` (`is_group_booking`),
  KEY `bookings_rescheduled_from_booking_id_index` (`rescheduled_from_booking_id`),
  KEY `bookings_applied_promotion_id_foreign` (`applied_promotion_id`),
  CONSTRAINT `bookings_applied_promotion_id_foreign` FOREIGN KEY (`applied_promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_captain_id_foreign` FOREIGN KEY (`captain_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_recurrence_parent_id_foreign` FOREIGN KEY (`recurrence_parent_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_remaining_confirmed_by_foreign` FOREIGN KEY (`remaining_confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_rescheduled_from_booking_id_foreign` FOREIGN KEY (`rescheduled_from_booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_sport_category_id_foreign` FOREIGN KEY (`sport_category_id`) REFERENCES `sport_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bookings_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned DEFAULT NULL,
  `state_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '0',
  `is_popular` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `venues_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_state_id_index` (`state_id`),
  KEY `cities_is_active_index` (`is_active`),
  KEY `cities_state_id_is_active_index` (`state_id`,`is_active`),
  KEY `cities_country_id_foreign` (`country_id`),
  KEY `cities_is_popular_index` (`is_popular`),
  CONSTRAINT `cities_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_followers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_followers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `club_id` bigint unsigned NOT NULL,
  `notify_updates` tinyint(1) NOT NULL DEFAULT '1',
  `notify_events` tinyint(1) NOT NULL DEFAULT '1',
  `notify_promotions` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `club_followers_user_id_club_id_unique` (`user_id`,`club_id`),
  KEY `club_followers_club_id_index` (`club_id`),
  CONSTRAINT `club_followers_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `club_followers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `content_ar` text COLLATE utf8mb4_unicode_ci,
  `type` enum('announcement','promotion','event','news','venue_update','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'announcement',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `related_event_id` bigint unsigned DEFAULT NULL,
  `related_promotion_id` bigint unsigned DEFAULT NULL,
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `likes_count` int unsigned NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `club_updates_related_event_id_foreign` (`related_event_id`),
  KEY `club_updates_related_promotion_id_foreign` (`related_promotion_id`),
  KEY `club_updates_club_id_is_published_index` (`club_id`,`is_published`),
  KEY `club_updates_type_is_published_index` (`type`,`is_published`),
  KEY `club_updates_published_at_index` (`published_at`),
  CONSTRAINT `club_updates_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `club_updates_related_event_id_foreign` FOREIGN KEY (`related_event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `club_updates_related_promotion_id_foreign` FOREIGN KEY (`related_promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `club_user_club_id_user_id_unique` (`club_id`,`user_id`),
  KEY `club_user_user_id_index` (`user_id`),
  CONSTRAINT `club_user_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `club_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clubs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned NOT NULL,
  `name` json NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` json DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `amenities` json DEFAULT NULL,
  `status` enum('pending_approval','active','inactive','suspended','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_approval',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspended_by` bigint unsigned DEFAULT NULL,
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `unsuspended_at` timestamp NULL DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `is_featured` tinyint NOT NULL DEFAULT '0',
  `avg_rating` decimal(3,2) DEFAULT NULL,
  `reviews_count` int unsigned NOT NULL DEFAULT '0',
  `followers_count` int unsigned NOT NULL DEFAULT '0',
  `price_from` int unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clubs_slug_unique` (`slug`),
  KEY `clubs_city_id_index` (`city_id`),
  KEY `clubs_owner_id_index` (`owner_id`),
  KEY `clubs_status_index` (`status`),
  KEY `clubs_is_featured_index` (`is_featured`),
  KEY `clubs_avg_rating_index` (`avg_rating`),
  KEY `clubs_price_from_index` (`price_from`),
  KEY `clubs_city_id_status_is_featured_index` (`city_id`,`status`,`is_featured`),
  KEY `clubs_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `clubs_approved_by_foreign` (`approved_by`),
  KEY `clubs_rejected_by_foreign` (`rejected_by`),
  KEY `clubs_suspended_by_foreign` (`suspended_by`),
  CONSTRAINT `clubs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clubs_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clubs_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clubs_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clubs_suspended_by_foreign` FOREIGN KEY (`suspended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `commission_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commission_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scope` enum('global','club','venue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `club_id` bigint unsigned DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `commission_type` enum('fixed','percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `commission_value` int unsigned NOT NULL DEFAULT '0',
  `cancellation_fee` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `effective_from` date NOT NULL DEFAULT (curdate()),
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commission_configs_scope_index` (`scope`),
  KEY `commission_configs_is_active_index` (`is_active`),
  KEY `commission_configs_effective_from_index` (`effective_from`),
  KEY `commission_configs_club_id_index` (`club_id`),
  KEY `commission_configs_venue_id_index` (`venue_id`),
  KEY `commission_configs_scope_venue_id_is_active_index` (`scope`,`venue_id`,`is_active`),
  KEY `commission_configs_created_by_foreign` (`created_by`),
  CONSTRAINT `commission_configs_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commission_configs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `commission_configs_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` json NOT NULL,
  `description` json DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_published` tinyint NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `competitions_is_published_index` (`is_published`),
  KEY `competitions_is_published_start_date_index` (`is_published`,`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `content_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `content_pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` json NOT NULL,
  `body` json NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_pages_slug_unique` (`slug`),
  KEY `content_pages_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `iso2` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso3` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capital` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_symbol` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag_emoji` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_iso2_unique` (`iso2`),
  UNIQUE KEY `countries_iso3_unique` (`iso3`),
  KEY `countries_is_active_index` (`is_active`),
  KEY `countries_is_visible_index` (`is_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alternative_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('police','ambulance','fire','civil_defense','support','platform_emergency') COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `coverage_areas` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emergency_contacts_is_active_type_index` (`is_active`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emergency_location_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_location_shares` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `share_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_phones` json DEFAULT NULL,
  `message_to_recipients` text COLLATE utf8mb4_unicode_ci,
  `last_latitude` decimal(10,7) DEFAULT NULL,
  `last_longitude` decimal(10,7) DEFAULT NULL,
  `last_updated_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `stopped_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emergency_location_shares_share_token_unique` (`share_token`),
  KEY `emergency_location_shares_user_id_foreign` (`user_id`),
  KEY `emergency_location_shares_booking_id_foreign` (`booking_id`),
  KEY `emergency_location_shares_share_token_index` (`share_token`),
  KEY `emergency_location_shares_is_active_expires_at_index` (`is_active`,`expires_at`),
  CONSTRAINT `emergency_location_shares_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emergency_location_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emergency_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `type` enum('medical','safety','security','fire','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `location_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('reported','acknowledged','in_progress','resolved','false_alarm') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reported',
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emergency_reports_booking_id_foreign` (`booking_id`),
  KEY `emergency_reports_venue_id_foreign` (`venue_id`),
  KEY `emergency_reports_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `emergency_reports_status_index` (`status`),
  KEY `emergency_reports_type_severity_index` (`type`,`severity`),
  KEY `emergency_reports_user_id_index` (`user_id`),
  CONSTRAINT `emergency_reports_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emergency_reports_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emergency_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emergency_reports_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_registrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending_payment','confirmed','cancelled','attended','no_show','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `payment_id` bigint unsigned DEFAULT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `refund_request_id` bigint unsigned DEFAULT NULL,
  `participant_info` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_user_registration` (`event_id`,`user_id`),
  UNIQUE KEY `event_registrations_registration_number_unique` (`registration_number`),
  KEY `event_registrations_team_id_foreign` (`team_id`),
  KEY `event_registrations_payment_id_foreign` (`payment_id`),
  KEY `event_registrations_refund_request_id_foreign` (`refund_request_id`),
  KEY `event_registrations_event_id_status_index` (`event_id`,`status`),
  KEY `event_registrations_user_id_index` (`user_id`),
  CONSTRAINT `event_registrations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_registrations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_registrations_refund_request_id_foreign` FOREIGN KEY (`refund_request_id`) REFERENCES `refund_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_registrations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `registration_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `rank` int unsigned NOT NULL,
  `stats` json DEFAULT NULL,
  `prize_amount` decimal(12,2) DEFAULT NULL,
  `prize_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prize_paid` tinyint(1) NOT NULL DEFAULT '0',
  `prize_paid_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_results_event_id_registration_id_unique` (`event_id`,`registration_id`),
  KEY `event_results_registration_id_foreign` (`registration_id`),
  KEY `event_results_user_id_foreign` (`user_id`),
  KEY `event_results_team_id_foreign` (`team_id`),
  KEY `event_results_event_id_rank_index` (`event_id`,`rank`),
  CONSTRAINT `event_results_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_results_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_results_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_results_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_id` bigint unsigned NOT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `description_ar` text COLLATE utf8mb4_unicode_ci,
  `cover_image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gallery_urls` json DEFAULT NULL,
  `type` enum('tournament','training','social','exhibition','workshop','camp') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sport_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NOT NULL,
  `registration_opens_at` timestamp NULL DEFAULT NULL,
  `registration_closes_at` timestamp NOT NULL,
  `max_participants` int unsigned NOT NULL,
  `min_participants` int unsigned NOT NULL DEFAULT '1',
  `current_participants` int unsigned NOT NULL DEFAULT '0',
  `registration_fee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `participant_type` enum('individual','team') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `team_size` int unsigned DEFAULT NULL,
  `prize_structure` json DEFAULT NULL,
  `rules` text COLLATE utf8mb4_unicode_ci,
  `rules_ar` text COLLATE utf8mb4_unicode_ci,
  `requirements` text COLLATE utf8mb4_unicode_ci,
  `requirements_ar` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','open','closed','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_venue_id_foreign` (`venue_id`),
  KEY `events_created_by_foreign` (`created_by`),
  KEY `events_status_starts_at_index` (`status`,`starts_at`),
  KEY `events_club_id_index` (`club_id`),
  KEY `events_is_featured_index` (`is_featured`),
  KEY `events_type_sport_type_index` (`type`,`sport_type`),
  CONSTRAINT `events_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `events_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `targeting` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flags_key_unique` (`key`),
  KEY `feature_flags_is_enabled_index` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `featured_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `featured_content` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contentable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contentable_id` bigint unsigned NOT NULL,
  `section` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `featured_content_contentable_type_contentable_id_index` (`contentable_type`,`contentable_id`),
  KEY `featured_content_section_is_active_index` (`section`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `football_leagues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `football_leagues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID from football-data.org',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PL, PD, CL, etc.',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emblem_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('LEAGUE','CUP','CHAMPIONSHIP') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LEAGUE',
  `current_season_start` date DEFAULT NULL,
  `current_season_end` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `api_sports_id` int DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `football_leagues_external_id_unique` (`external_id`),
  UNIQUE KEY `football_leagues_code_unique` (`code`),
  KEY `football_leagues_is_active_index` (`is_active`),
  KEY `football_leagues_is_featured_index` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `football_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `football_teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tla` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crest_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `league_id` bigint unsigned DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `founded_year` int DEFAULT NULL,
  `is_popular` tinyint(1) NOT NULL DEFAULT '0',
  `popularity_rank` int DEFAULT NULL,
  `api_sports_id` int DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `football_teams_external_id_unique` (`external_id`),
  KEY `football_teams_is_popular_index` (`is_popular`),
  KEY `football_teams_league_id_index` (`league_id`),
  CONSTRAINT `football_teams_league_id_foreign` FOREIGN KEY (`league_id`) REFERENCES `football_leagues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `live_match_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_match_states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fixture_external_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `minute` int DEFAULT NULL,
  `home_team_id` bigint unsigned DEFAULT NULL,
  `away_team_id` bigint unsigned DEFAULT NULL,
  `league_id` bigint unsigned DEFAULT NULL,
  `home_score` int NOT NULL DEFAULT '0',
  `away_score` int NOT NULL DEFAULT '0',
  `last_event_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_event_at` timestamp NULL DEFAULT NULL,
  `last_polled_at` timestamp NOT NULL,
  `raw_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `live_match_states_fixture_external_id_unique` (`fixture_external_id`),
  KEY `live_match_states_home_team_id_foreign` (`home_team_id`),
  KEY `live_match_states_away_team_id_foreign` (`away_team_id`),
  KEY `live_match_states_status_index` (`status`),
  KEY `live_match_states_league_id_index` (`league_id`),
  KEY `live_match_states_last_polled_at_index` (`last_polled_at`),
  CONSTRAINT `live_match_states_away_team_id_foreign` FOREIGN KEY (`away_team_id`) REFERENCES `football_teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `live_match_states_home_team_id_foreign` FOREIGN KEY (`home_team_id`) REFERENCES `football_teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `live_match_states_league_id_foreign` FOREIGN KEY (`league_id`) REFERENCES `football_leagues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_windows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_windows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `message_ar` text COLLATE utf8mb4_unicode_ci,
  `allowed_ips` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_windows_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `match_reminders_sent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `match_reminders_sent` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `match_external_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reminder_type` enum('1h_before','15min_before','started','finished','daily_summary','goal_scored') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_match_reminder` (`user_id`,`match_external_id`,`reminder_type`),
  KEY `match_reminders_sent_match_external_id_index` (`match_external_id`),
  KEY `match_reminders_sent_sent_at_index` (`sent_at`),
  CONSTRAINT `match_reminders_sent_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `notification_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `push_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `sms_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `email_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_settings_user_id_notification_type_unique` (`user_id`,`notification_type`),
  KEY `notification_settings_notification_type_index` (`notification_type`),
  CONSTRAINT `notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_notifiable_type_notifiable_id_read_at_index` (`notifiable_type`,`notifiable_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otp_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_challenges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('sms','whatsapp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sms',
  `expires_at` timestamp NOT NULL,
  `consumed_at` timestamp NULL DEFAULT NULL,
  `attempts_count` tinyint unsigned NOT NULL DEFAULT '0',
  `resend_count` tinyint unsigned NOT NULL DEFAULT '0',
  `delivery_status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `otp_challenges_uuid_unique` (`uuid`),
  KEY `otp_challenges_phone_number_index` (`phone_number`),
  KEY `otp_challenges_expires_at_index` (`expires_at`),
  KEY `otp_challenges_phone_number_consumed_at_expires_at_index` (`phone_number`,`consumed_at`,`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` json NOT NULL,
  `provider_key` enum('syriatel_cash','mtn_cash','fatora','sama_pay','cash') COLLATE utf8mb4_unicode_ci NOT NULL,
  `flow_type` enum('otp','webview','internal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `order_column` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_methods_provider_key_unique` (`provider_key`),
  KEY `payment_methods_is_active_index` (`is_active`),
  KEY `payment_methods_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `wallet_id` bigint unsigned DEFAULT NULL,
  `amount` int unsigned NOT NULL,
  `bonus_amount` bigint unsigned NOT NULL DEFAULT '0',
  `total_credited` bigint unsigned DEFAULT NULL,
  `payment_type` enum('booking','wallet_topup') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'booking',
  `refund_amount` int unsigned DEFAULT NULL,
  `refund_reason` text COLLATE utf8mb4_unicode_ci,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refunded_by` bigint unsigned DEFAULT NULL,
  `retry_count` int unsigned NOT NULL DEFAULT '0',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYP',
  `provider` enum('syriatel_cash','mtn_cash','fatora','sama_pay','wallet','cash') COLLATE utf8mb4_unicode_ci NOT NULL,
  `flow_type` enum('otp','webview','internal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','completed','failed','refunded','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `provider_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_meta` json DEFAULT NULL,
  `provider_payload` json DEFAULT NULL,
  `initiated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_booking_id_index` (`booking_id`),
  KEY `payments_user_id_index` (`user_id`),
  KEY `payments_status_index` (`status`),
  KEY `payments_provider_index` (`provider`),
  KEY `payments_provider_transaction_id_index` (`provider_transaction_id`),
  KEY `payments_status_created_at_index` (`status`,`created_at`),
  KEY `payments_refunded_by_foreign` (`refunded_by`),
  KEY `payments_wallet_id_foreign` (`wallet_id`),
  KEY `payments_payment_type_idx` (`payment_type`),
  KEY `payments_user_type_status_idx` (`user_id`,`payment_type`,`status`),
  CONSTRAINT `payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `payments_refunded_by_foreign` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `payments_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `phone_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phone_change_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `current_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_expires_at` timestamp NOT NULL,
  `status` enum('pending','verified','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `phone_change_requests_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `phone_change_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `anonymous_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `properties` json DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `player_events_user_id_event_name_occurred_at_index` (`user_id`,`event_name`,`occurred_at`),
  KEY `player_events_event_name_occurred_at_index` (`event_name`,`occurred_at`),
  KEY `player_events_session_id_index` (`session_id`),
  KEY `player_events_anonymous_id_index` (`anonymous_id`),
  CONSTRAINT `player_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `player_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `total_bookings` int unsigned NOT NULL DEFAULT '0',
  `completed_bookings` int unsigned NOT NULL DEFAULT '0',
  `cancelled_bookings` int unsigned NOT NULL DEFAULT '0',
  `total_hours_played` int unsigned NOT NULL DEFAULT '0',
  `total_spent` bigint unsigned NOT NULL DEFAULT '0',
  `favorite_sport_id` bigint unsigned DEFAULT NULL,
  `favorite_venue_id` bigint unsigned DEFAULT NULL,
  `average_rating_given` decimal(3,2) DEFAULT NULL,
  `bookings_this_month` int unsigned NOT NULL DEFAULT '0',
  `streak_days` int unsigned NOT NULL DEFAULT '0',
  `last_booking_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_stats_user_id_unique` (`user_id`),
  KEY `player_stats_favorite_sport_id_foreign` (`favorite_sport_id`),
  KEY `player_stats_favorite_venue_id_foreign` (`favorite_venue_id`),
  KEY `player_stats_total_bookings_index` (`total_bookings`),
  KEY `player_stats_total_spent_index` (`total_spent`),
  KEY `player_stats_total_hours_played_index` (`total_hours_played`),
  CONSTRAINT `player_stats_favorite_sport_id_foreign` FOREIGN KEY (`favorite_sport_id`) REFERENCES `venue_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_stats_favorite_venue_id_foreign` FOREIGN KEY (`favorite_venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL,
  CONSTRAINT `player_stats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promo_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `discount_amount` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promo_usages_user_id_foreign` (`user_id`),
  KEY `promo_usages_promotion_id_user_id_index` (`promotion_id`,`user_id`),
  KEY `promo_usages_booking_id_index` (`booking_id`),
  CONSTRAINT `promo_usages_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promo_usages_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promo_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_venue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_venue` (
  `promotion_id` bigint unsigned NOT NULL,
  `venue_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`promotion_id`,`venue_id`),
  KEY `promotion_venue_venue_id_foreign` (`venue_id`),
  CONSTRAINT `promotion_venue_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_venue_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_venue_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_venue_category` (
  `promotion_id` bigint unsigned NOT NULL,
  `venue_category_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`promotion_id`,`venue_category_id`),
  KEY `promotion_venue_category_venue_category_id_foreign` (`venue_category_id`),
  CONSTRAINT `promotion_venue_category_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotion_venue_category_venue_category_id_foreign` FOREIGN KEY (`venue_category_id`) REFERENCES `venue_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_id` bigint unsigned DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_qr_promotion` tinyint(1) NOT NULL DEFAULT '0',
  `qr_redemption_limit` int unsigned NOT NULL DEFAULT '1',
  `qr_redemption_count` int unsigned NOT NULL DEFAULT '0',
  `name` json NOT NULL,
  `description` json DEFAULT NULL,
  `type` enum('percentage','fixed_amount','free_hours') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` int unsigned NOT NULL,
  `min_amount` int unsigned DEFAULT NULL,
  `max_discount` int unsigned DEFAULT NULL,
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_to` timestamp NULL DEFAULT NULL,
  `max_uses` int unsigned DEFAULT NULL,
  `max_uses_per_user` int unsigned DEFAULT NULL,
  `current_uses` int unsigned NOT NULL DEFAULT '0',
  `applies_to` enum('all','venues','categories') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `status` enum('draft','active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `first_booking_only` tinyint(1) NOT NULL DEFAULT '0',
  `allowed_days` json DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotions_code_unique` (`code`),
  UNIQUE KEY `promotions_slug_unique` (`slug`),
  UNIQUE KEY `promotions_qr_code_unique` (`qr_code`),
  KEY `promotions_venue_id_foreign` (`venue_id`),
  KEY `promotions_created_by_foreign` (`created_by`),
  KEY `promotions_status_index` (`status`),
  KEY `promotions_is_featured_index` (`is_featured`),
  KEY `promotions_valid_from_valid_to_index` (`valid_from`,`valid_to`),
  KEY `promotions_club_id_status_index` (`club_id`,`status`),
  CONSTRAINT `promotions_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promotions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promotions_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qr_promotion_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_promotion_redemptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `redeemed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_qr_redemption` (`promotion_id`,`user_id`),
  KEY `qr_promotion_redemptions_user_id_foreign` (`user_id`),
  KEY `qr_promotion_redemptions_booking_id_foreign` (`booking_id`),
  CONSTRAINT `qr_promotion_redemptions_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qr_promotion_redemptions_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qr_promotion_redemptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referrals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `referrer_id` bigint unsigned NOT NULL,
  `referee_id` bigint unsigned DEFAULT NULL,
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referee_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','completed','rewarded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reward_amount` int unsigned NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referrals_referral_code_unique` (`referral_code`),
  KEY `referrals_referee_id_foreign` (`referee_id`),
  KEY `referrals_referrer_id_index` (`referrer_id`),
  CONSTRAINT `referrals_referee_id_foreign` FOREIGN KEY (`referee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referrals_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `refund_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refund_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `requested_amount` decimal(12,2) NOT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending_review','approved','rejected','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_review',
  `refund_method` enum('wallet','original_method') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wallet',
  `policy_applied` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_approved` tinyint(1) NOT NULL DEFAULT '0',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refund_requests_user_id_foreign` (`user_id`),
  KEY `refund_requests_reviewed_by_foreign` (`reviewed_by`),
  KEY `refund_requests_status_index` (`status`),
  KEY `refund_requests_booking_id_status_index` (`booking_id`,`status`),
  CONSTRAINT `refund_requests_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refund_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refund_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_helpful_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_helpful_votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_helpful_votes_review_id_user_id_unique` (`review_id`,`user_id`),
  KEY `review_helpful_votes_user_id_index` (`user_id`),
  CONSTRAINT `review_helpful_votes_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_helpful_votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reason` enum('spam','offensive_language','false_review','personal_attack','inappropriate_content','fake_review','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','review_hidden','review_kept','review_deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_review_report` (`review_id`,`user_id`),
  KEY `review_reports_user_id_foreign` (`user_id`),
  KEY `review_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `review_reports_status_index` (`status`),
  CONSTRAINT `review_reports_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `review_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint unsigned NOT NULL,
  `club_id` bigint unsigned NOT NULL,
  `responded_by` bigint unsigned NOT NULL,
  `response` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_responses_review_id_unique` (`review_id`),
  KEY `review_responses_club_id_foreign` (`club_id`),
  KEY `review_responses_responded_by_foreign` (`responded_by`),
  CONSTRAINT `review_responses_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_responses_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_responses_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `club_id` bigint unsigned NOT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `booking_id` bigint unsigned NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `pros` json DEFAULT NULL,
  `cons` json DEFAULT NULL,
  `helpful_count` int unsigned NOT NULL DEFAULT '0',
  `is_anonymous` tinyint NOT NULL DEFAULT '0',
  `venue_hint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint NOT NULL DEFAULT '1',
  `published_at` timestamp NULL DEFAULT NULL,
  `hidden_at` timestamp NULL DEFAULT NULL,
  `hidden_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reports_count` int NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `photos` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_user_id_club_id_unique` (`user_id`,`club_id`),
  KEY `reviews_club_id_index` (`club_id`),
  KEY `reviews_is_published_index` (`is_published`),
  KEY `reviews_club_id_is_published_created_at_index` (`club_id`,`is_published`,`created_at`),
  KEY `reviews_booking_id_index` (`booking_id`),
  KEY `reviews_rating_index` (`rating`),
  KEY `reviews_hidden_by_foreign` (`hidden_by`),
  KEY `reviews_venue_id_index` (`venue_id`),
  CONSTRAINT `reviews_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `reviews_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `reviews_hidden_by_foreign` FOREIGN KEY (`hidden_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `reviews_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saved_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_searches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filters` json NOT NULL,
  `notify_new_venues` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_searches_user_id_index` (`user_id`),
  CONSTRAINT `saved_searches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saved_venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_venues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `venue_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_venues_user_id_venue_id_unique` (`user_id`,`venue_id`),
  KEY `saved_venues_venue_id_index` (`venue_id`),
  CONSTRAINT `saved_venues_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_venues_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_group_name_unique` (`group`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settlement_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settlement_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `settlement_id` bigint unsigned NOT NULL,
  `booking_id` bigint unsigned NOT NULL,
  `venue_price` int unsigned NOT NULL,
  `commission_amount` int unsigned NOT NULL,
  `club_payout_amount` int unsigned NOT NULL,
  `cancellation_comm` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlement_items_settlement_id_booking_id_unique` (`settlement_id`,`booking_id`),
  KEY `settlement_items_settlement_id_index` (`settlement_id`),
  KEY `settlement_items_booking_id_index` (`booking_id`),
  CONSTRAINT `settlement_items_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `settlement_items_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `club_id` bigint unsigned NOT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `total_bookings` int unsigned NOT NULL DEFAULT '0',
  `total_venue_price` int unsigned NOT NULL DEFAULT '0',
  `total_commission` int unsigned NOT NULL DEFAULT '0',
  `total_cancellation_fees` int unsigned NOT NULL DEFAULT '0',
  `net_payable` int unsigned NOT NULL DEFAULT '0',
  `paid_amount` int unsigned NOT NULL DEFAULT '0',
  `status` enum('draft','pending','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `payment_method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_notes` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `notes_updated_at` timestamp NULL DEFAULT NULL,
  `notes_updated_by` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `settled_by` bigint unsigned DEFAULT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settlements_club_id_index` (`club_id`),
  KEY `settlements_status_index` (`status`),
  KEY `settlements_club_id_period_from_period_to_index` (`club_id`,`period_from`,`period_to`),
  KEY `settlements_settled_at_index` (`settled_at`),
  KEY `settlements_settled_by_foreign` (`settled_by`),
  KEY `settlements_created_by_foreign` (`created_by`),
  KEY `settlements_notes_updated_by_foreign` (`notes_updated_by`),
  CONSTRAINT `settlements_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `settlements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `settlements_notes_updated_by_foreign` FOREIGN KEY (`notes_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `settlements_settled_by_foreign` FOREIGN KEY (`settled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `slot_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slot_reservations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` smallint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `reserved_until` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slot_reservations_venue_id_booking_date_start_time_unique` (`venue_id`,`booking_date`,`start_time`),
  KEY `slot_reservations_venue_id_booking_date_reserved_until_index` (`venue_id`,`booking_date`,`reserved_until`),
  KEY `slot_reservations_reserved_until_index` (`reserved_until`),
  KEY `slot_reservations_user_id_index` (`user_id`),
  KEY `slot_reservations_category_id_foreign` (`category_id`),
  CONSTRAINT `slot_reservations_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `venue_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `slot_reservations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `slot_reservations_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_identities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_uid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `social_identities_provider_provider_uid_unique` (`provider`,`provider_uid`),
  KEY `social_identities_user_id_index` (`user_id`),
  CONSTRAINT `social_identities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sport_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sport_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` json NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_column` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sport_categories_slug_unique` (`slug`),
  KEY `sport_categories_is_active_index` (`is_active`),
  KEY `sport_categories_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `states_country_id_index` (`country_id`),
  KEY `states_is_active_index` (`is_active`),
  KEY `states_country_id_is_active_index` (`country_id`,`is_active`),
  KEY `states_country_id_is_visible_index` (`country_id`,`is_visible`),
  CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `venue_id` bigint unsigned NOT NULL,
  `frequency` enum('weekly','biweekly','monthly','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `interval` int unsigned DEFAULT NULL,
  `day_of_week` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_of_month` tinyint unsigned DEFAULT NULL,
  `start_time` time NOT NULL,
  `duration_hours` int unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','active','paused','cancelled','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method_id` bigint unsigned DEFAULT NULL,
  `auto_pay` tinyint(1) NOT NULL DEFAULT '1',
  `price_per_booking` int unsigned NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `next_booking_date` date DEFAULT NULL,
  `next_charge_date` date DEFAULT NULL,
  `total_bookings_created` int unsigned NOT NULL DEFAULT '0',
  `pause_count` int unsigned NOT NULL DEFAULT '0',
  `paused_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_payment_method_id_foreign` (`payment_method_id`),
  KEY `subscriptions_user_id_index` (`user_id`),
  KEY `subscriptions_venue_id_index` (`venue_id`),
  KEY `subscriptions_status_index` (`status`),
  KEY `subscriptions_next_booking_date_index` (`next_booking_date`),
  KEY `subscriptions_status_next_booking_date_index` (`status`,`next_booking_date`),
  CONSTRAINT `subscriptions_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `ticket_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('booking_issue','payment_issue','technical','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL,
  `admin_response` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `assigned_agent_id` bigint unsigned DEFAULT NULL,
  `status_history` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_tickets_ticket_number_unique` (`ticket_number`),
  KEY `support_tickets_user_id_index` (`user_id`),
  KEY `support_tickets_status_index` (`status`),
  KEY `support_tickets_assigned_agent_id_foreign` (`assigned_agent_id`),
  CONSTRAINT `support_tickets_assigned_agent_id_foreign` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('captain','admin','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `status` enum('invited','active','left','removed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'invited',
  `invited_by` bigint unsigned DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT NULL,
  `left_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_members_team_id_user_id_unique` (`team_id`,`user_id`),
  KEY `team_members_invited_by_foreign` (`invited_by`),
  KEY `team_members_user_id_index` (`user_id`),
  KEY `team_members_team_id_status_index` (`team_id`,`status`),
  CONSTRAINT `team_members_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `team_members_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('casual','regular','competitive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'casual',
  `sport_category_id` bigint unsigned NOT NULL,
  `captain_id` bigint unsigned NOT NULL,
  `max_members` int unsigned NOT NULL DEFAULT '20',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '0',
  `regular_schedule` json DEFAULT NULL,
  `avatar_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_bookings` int unsigned NOT NULL DEFAULT '0',
  `total_members` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teams_sport_category_id_index` (`sport_category_id`),
  KEY `teams_type_index` (`type`),
  KEY `teams_captain_id_name_index` (`captain_id`,`name`),
  CONSTRAINT `teams_captain_id_foreign` FOREIGN KEY (`captain_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teams_sport_category_id_foreign` FOREIGN KEY (`sport_category_id`) REFERENCES `venue_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `sender_type` enum('user','agent','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` bigint unsigned DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL,
  `is_internal_note` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_messages_sender_id_foreign` (`sender_id`),
  KEY `ticket_messages_ticket_id_index` (`ticket_id`),
  KEY `ticket_messages_ticket_id_created_at_index` (`ticket_id`,`created_at`),
  CONSTRAINT `ticket_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_ar` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tips_is_active_category_index` (`is_active`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `device_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fcm_token` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_version` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_devices_device_id_unique` (`device_id`),
  KEY `user_devices_user_id_foreign` (`user_id`),
  KEY `user_devices_device_id_index` (`device_id`),
  CONSTRAINT `user_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_favorite_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_favorite_teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `team_id` bigint unsigned NOT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `notify_matches` tinyint(1) NOT NULL DEFAULT '1',
  `notify_goals` tinyint(1) NOT NULL DEFAULT '0',
  `notify_results` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_favorite_teams_user_id_team_id_unique` (`user_id`,`team_id`),
  KEY `user_favorite_teams_team_id_foreign` (`team_id`),
  KEY `user_favorite_teams_user_id_display_order_index` (`user_id`,`display_order`),
  CONSTRAINT `user_favorite_teams_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `football_teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_favorite_teams_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_followed_leagues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_followed_leagues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `league_id` bigint unsigned NOT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `notify_matches` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_followed_leagues_user_id_league_id_unique` (`user_id`,`league_id`),
  KEY `user_followed_leagues_league_id_foreign` (`league_id`),
  CONSTRAINT `user_followed_leagues_league_id_foreign` FOREIGN KEY (`league_id`) REFERENCES `football_leagues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_followed_leagues_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_match_notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_match_notification_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `match_reminders_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `reminder_minutes_before` int NOT NULL DEFAULT '60',
  `second_reminder_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `second_reminder_minutes_before` int NOT NULL DEFAULT '15',
  `goal_notifications_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `result_notifications_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `daily_summary_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `daily_summary_time` time NOT NULL DEFAULT '09:00:00',
  `quiet_hours_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_match_notification_settings_user_id_unique` (`user_id`),
  CONSTRAINT `user_match_notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `interests` json DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `country_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '+963',
  `firebase_uid` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firebase_provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_state_id` bigint unsigned DEFAULT NULL,
  `default_city_id` bigint unsigned DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fcm_token` text COLLATE utf8mb4_unicode_ci,
  `fcm_platform` enum('mobile','web') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_status` enum('active','blocked','suspended','pending_profile_completion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_profile_completion',
  `block_reason` text COLLATE utf8mb4_unicode_ci,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `blocked_by` bigint unsigned DEFAULT NULL,
  `unblocked_at` timestamp NULL DEFAULT NULL,
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `notifications_push_enabled` tinyint NOT NULL DEFAULT '1',
  `notifications_sms_enabled` tinyint NOT NULL DEFAULT '1',
  `notifications_reminders_enabled` tinyint NOT NULL DEFAULT '1',
  `preferred_language` enum('ar','en') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ar',
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Damascus',
  `google2fa_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google2fa_enabled_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `privacy_settings` json DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `managed_club_id` bigint unsigned DEFAULT NULL,
  `staff_club_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_number_unique` (`phone_number`),
  UNIQUE KEY `users_firebase_uid_unique` (`firebase_uid`),
  UNIQUE KEY `users_referral_code_unique` (`referral_code`),
  KEY `users_account_status_index` (`account_status`),
  KEY `users_default_city_id_index` (`default_city_id`),
  KEY `users_account_status_default_city_id_index` (`account_status`,`default_city_id`),
  KEY `users_default_state_id_foreign` (`default_state_id`),
  KEY `users_blocked_by_foreign` (`blocked_by`),
  KEY `users_managed_club_id_foreign` (`managed_club_id`),
  KEY `users_staff_club_id_foreign` (`staff_club_id`),
  CONSTRAINT `users_blocked_by_foreign` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_default_city_id_foreign` FOREIGN KEY (`default_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_default_state_id_foreign` FOREIGN KEY (`default_state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_managed_club_id_foreign` FOREIGN KEY (`managed_club_id`) REFERENCES `clubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_staff_club_id_foreign` FOREIGN KEY (`staff_club_id`) REFERENCES `clubs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_blocked_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_blocked_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `blocked_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `reason` enum('maintenance','private_event','staff_unavailable','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venue_blocked_slots_created_by_foreign` (`created_by`),
  KEY `venue_blocked_slots_venue_id_blocked_date_index` (`venue_id`,`blocked_date`),
  CONSTRAINT `venue_blocked_slots_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `venue_blocked_slots_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` json NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('sports','hall','court','outdoor','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sports',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `order_column` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `venue_categories_slug_unique` (`slug`),
  KEY `venue_categories_is_active_index` (`is_active`),
  KEY `venue_categories_type_index` (`type`),
  KEY `venue_categories_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_flash_deals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_flash_deals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `original_price` int unsigned NOT NULL,
  `discounted_price` int unsigned NOT NULL,
  `max_bookings` tinyint unsigned DEFAULT NULL,
  `bookings_count` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `status` enum('active','expired','fully_booked','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venue_flash_deals_venue_id_status_expires_at_index` (`venue_id`,`status`,`expires_at`),
  KEY `venue_flash_deals_status_start_datetime_index` (`status`,`start_datetime`),
  KEY `venue_flash_deals_created_by_foreign` (`created_by`),
  CONSTRAINT `venue_flash_deals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `venue_flash_deals_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_flash_deal_dates` CHECK ((`end_datetime` > `start_datetime`)),
  CONSTRAINT `chk_flash_deal_price` CHECK ((`discounted_price` < `original_price`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_pricing_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_pricing_tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `name` json NOT NULL,
  `day_type` enum('all_days','weekday','weekend','friday','specific_day') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all_days',
  `specific_day` enum('saturday','sunday','monday','tuesday','wednesday','thursday','friday') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` smallint unsigned NOT NULL,
  `price` int unsigned NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1',
  `order_column` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venue_pricing_tiers_venue_id_index` (`venue_id`),
  KEY `venue_pricing_tiers_is_active_index` (`is_active`),
  KEY `vpt_venue_day_time_idx` (`venue_id`,`is_active`,`day_type`,`start_time`,`end_time`),
  KEY `vpt_venue_duration_idx` (`venue_id`,`is_active`,`duration_minutes`),
  CONSTRAINT `venue_pricing_tiers_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reason` enum('inappropriate_content','false_information','safety_concern','pricing_dispute','fake_venue','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_urls` json DEFAULT NULL,
  `status` enum('pending','under_review','action_taken','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_venue_report` (`venue_id`,`user_id`),
  KEY `venue_reports_user_id_foreign` (`user_id`),
  KEY `venue_reports_reviewed_by_foreign` (`reviewed_by`),
  KEY `venue_reports_status_index` (`status`),
  KEY `venue_reports_venue_id_index` (`venue_id`),
  CONSTRAINT `venue_reports_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `venue_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venue_reports_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_sport_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_sport_category` (
  `venue_id` bigint unsigned NOT NULL,
  `sport_category_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`venue_id`,`sport_category_id`),
  KEY `venue_sport_category_sport_category_id_index` (`sport_category_id`),
  CONSTRAINT `venue_sport_category_sport_category_id_foreign` FOREIGN KEY (`sport_category_id`) REFERENCES `sport_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venue_sport_category_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `venue_id` bigint unsigned NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `venue_views_user_id_viewed_at_index` (`user_id`,`viewed_at`),
  KEY `venue_views_venue_id_viewed_at_index` (`venue_id`,`viewed_at`),
  CONSTRAINT `venue_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `venue_views_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venue_waitlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_waitlist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration_minutes` smallint unsigned NOT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `venue_waitlist_venue_id_user_id_booking_date_start_time_unique` (`venue_id`,`user_id`,`booking_date`,`start_time`),
  KEY `venue_waitlist_user_id_foreign` (`user_id`),
  KEY `vw_venue_date_time_notify_idx` (`venue_id`,`booking_date`,`start_time`,`notified_at`),
  KEY `venue_waitlist_expires_at_index` (`expires_at`),
  CONSTRAINT `venue_waitlist_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venue_waitlist_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `club_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `name` json NOT NULL,
  `description` json DEFAULT NULL,
  `size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int unsigned DEFAULT NULL,
  `amenities` json DEFAULT NULL,
  `opening_hours` json DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `avg_rating` decimal(3,2) DEFAULT NULL,
  `reviews_count` int unsigned NOT NULL DEFAULT '0',
  `reports_count` int NOT NULL DEFAULT '0',
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  `price_from` int unsigned DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `view_count` int unsigned NOT NULL DEFAULT '0',
  `order_column` int unsigned NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `venues_slug_unique` (`slug`),
  KEY `venues_club_id_index` (`club_id`),
  KEY `venues_category_id_index` (`category_id`),
  KEY `venues_status_index` (`status`),
  KEY `venues_club_id_status_order_column_index` (`club_id`,`status`,`order_column`),
  KEY `venues_is_featured_index` (`is_featured`),
  KEY `venues_view_count_index` (`view_count`),
  CONSTRAINT `venues_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `venue_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `venues_club_id_foreign` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `videos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_ar` text COLLATE utf8mb4_unicode_ci,
  `youtube_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_seconds` int unsigned NOT NULL DEFAULT '0',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tutorial',
  `tags` json DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `videos_is_active_category_index` (`is_active`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint unsigned NOT NULL,
  `type` enum('credit','debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `credit_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'topup, promotional, referral, refund, transfer_in, transfer_out, booking, withdrawal, bonus',
  `amount` int unsigned NOT NULL,
  `balance_after` int NOT NULL,
  `reason` enum('booking_refund','booking_payment','admin_adjustment','cancellation_deduction') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `expires_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallet_transactions_wallet_id_index` (`wallet_id`),
  KEY `wallet_transactions_wallet_id_created_at_index` (`wallet_id`,`created_at`),
  KEY `wallet_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `wallet_transactions_created_by_foreign` (`created_by`),
  KEY `wt_credit_type_idx` (`credit_type`),
  KEY `wt_status_idx` (`status`),
  KEY `wt_expires_at_idx` (`expires_at`),
  CONSTRAINT `wallet_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wallet_transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `balance` int NOT NULL DEFAULT '0',
  `locked` bigint unsigned NOT NULL DEFAULT '0',
  `total_earned` bigint unsigned NOT NULL DEFAULT '0',
  `total_spent` bigint unsigned NOT NULL DEFAULT '0',
  `total_topup` bigint unsigned NOT NULL DEFAULT '0',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYP',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallets_user_id_unique` (`user_id`),
  CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_wallet_balance_non_negative` CHECK ((`balance` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `withdrawal_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawal_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `request_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` bigint unsigned NOT NULL,
  `fee` bigint unsigned NOT NULL,
  `total_amount` bigint unsigned NOT NULL,
  `withdrawal_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'syriatel/mtn/bank',
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','processing','completed','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `processed_by` bigint unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `withdrawal_requests_request_code_unique` (`request_code`),
  KEY `withdrawal_requests_processed_by_foreign` (`processed_by`),
  KEY `withdrawal_requests_user_id_index` (`user_id`),
  KEY `withdrawal_requests_status_index` (`status`),
  CONSTRAINT `withdrawal_requests_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `withdrawal_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2022_12_14_083707_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2024_01_01_000001_create_countries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_01_01_000002_create_states_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_01_01_000003_create_cities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_01_01_000004_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_01_01_000005_create_social_identities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_01_01_000006_create_otp_challenges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_01_01_000007_create_venue_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_01_01_000008_create_sport_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_01_01_000009_create_clubs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_01_01_000010_create_club_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2024_01_01_000011_create_venues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2024_01_01_000011_zz_add_slug_to_venues_if_missing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2024_01_01_000012_create_venue_pricing_tiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2024_01_01_000013_create_commission_configs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2024_01_01_000014_create_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2024_01_01_000015_create_slot_reservations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2024_01_01_000016_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2024_01_01_000017_create_payment_methods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2024_01_01_000018_create_wallets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2024_01_01_000019_create_wallet_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2024_01_01_000020_create_reviews_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2024_01_01_000021_create_saved_venues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2024_01_01_000022_create_settlements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2024_01_01_000023_create_settlement_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2024_01_01_000024_create_app_platforms_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2024_01_01_000025_create_app_environments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2024_01_01_000026_create_content_pages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2024_01_01_000027_create_competitions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2024_01_01_000028_create_venue_flash_deals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2024_01_01_000029_create_venue_waitlist_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2024_01_01_000030_create_player_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2024_01_01_000031_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2024_01_01_000032_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2024_01_01_000033_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_04_15_084457_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_04_15_084457_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_04_15_084458_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_04_21_021231_add_capacity_to_venues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_04_21_021232_create_venue_sport_category_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_04_21_140345_add_status_tracking_and_commission_to_clubs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_04_21_142918_add_block_tracking_and_address_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_04_21_155220_add_refund_and_retry_to_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_04_21_162118_add_receipt_and_notes_to_settlements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_04_22_173914_add_featured_and_views_to_venues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_04_22_173915_create_venue_views_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_04_22_205353_add_qr_and_checkin_to_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_04_22_230726_add_pending_payment_to_bookings_status_enum',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_04_23_103603_add_cash_to_payment_providers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_04_23_120001_create_promotions_and_review_helpful_votes_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_04_23_140001_create_notifications_and_settings_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_04_23_150001_create_player_stats_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_04_23_150002_create_achievements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_04_23_150003_create_saved_searches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_04_23_150004_create_support_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_04_23_150005_create_referrals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_04_23_150006_add_bio_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_04_23_160001_create_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_04_23_160002_create_booking_instances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_04_23_160003_add_subscription_to_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_04_23_220626_add_remember_token_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_04_24_170001_create_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_04_24_170002_create_team_members_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_04_24_170003_create_booking_participants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_04_24_170004_add_group_fields_to_bookings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_04_24_180001_extend_wallets_for_credits_phase10',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_04_24_180002_extend_wallet_transactions_for_credits_phase10',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_04_24_180003_create_withdrawal_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_04_25_000001_extend_payments_for_wallet_topup',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_04_25_100001_create_football_leagues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_04_25_100002_create_football_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_04_25_100003_create_user_favorite_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_04_25_100004_create_user_followed_leagues_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_04_25_100005_create_user_match_notification_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_04_25_100006_create_match_reminders_sent_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_04_25_100007_create_live_match_states_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_04_25_200001_extend_countries_for_phase14',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_04_25_200002_extend_states_for_phase14',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_04_25_200003_extend_cities_for_phase14',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_04_25_200004_create_app_versions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_04_25_200005_create_feature_flags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_04_25_200006_create_maintenance_windows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_04_25_200007_create_app_configs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_04_25_300001_add_reschedule_support_to_bookings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_04_25_300002_create_refund_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_04_25_300003_create_ticket_messages_and_extend_tickets',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_04_25_300004_create_venue_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_04_25_300005_create_review_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_04_25_300006_backfill_ticket_numbers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_04_25_400001_add_followers_count_to_clubs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_04_25_400002_create_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_04_25_400003_create_club_followers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_04_25_400004_create_club_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_04_25_400005_create_event_registrations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_04_25_400006_create_event_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_04_25_500001_create_banners_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_04_25_500002_create_blog_articles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_04_25_500003_create_tips_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_04_25_500004_create_videos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_04_25_500005_create_featured_content_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_04_25_500006_create_emergency_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_04_25_500007_create_emergency_location_shares_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_04_25_500008_create_emergency_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_04_25_600000_create_user_devices_table_if_missing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_04_25_600001_create_phone_change_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_04_25_600002_add_qr_to_promotions_and_create_redemptions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_04_25_600003_add_photos_to_reviews',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_04_25_600004_create_booking_payment_splits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_04_25_700001_create_audit_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_04_25_700002_create_venue_blocked_slots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_04_25_700003_create_review_responses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_04_25_700004_create_admin_broadcasts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_04_25_700005_add_club_management_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_04_29_083828_add_date_of_birth_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_04_29_100000_make_user_devices_fcm_token_nullable',1);
