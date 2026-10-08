-- Manpower SaaS central control database (schema only)
-- Import this file into the CENTRAL database, never into a customer database.
-- Plain voucher codes must never be stored; store only their SHA-256 hashes.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `saas_company` (
  `company_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_code` varchar(30) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `status` enum('TRIAL','ACTIVE','INACTIVE','SUSPENDED') NOT NULL DEFAULT 'INACTIVE',
  `subscription_started_at` date DEFAULT NULL,
  `subscription_expires_at` date DEFAULT NULL,
  `grace_until` date DEFAULT NULL,
  `db_host` varchar(190) NOT NULL DEFAULT 'localhost',
  `db_port` smallint unsigned NOT NULL DEFAULT 3306,
  `db_name` varchar(190) NOT NULL,
  `db_user` varchar(190) NOT NULL,
  `db_pass_encrypted` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`company_id`),
  UNIQUE KEY `uq_saas_company_code` (`company_code`),
  UNIQUE KEY `uq_saas_company_database` (`db_host`,`db_name`),
  KEY `idx_saas_company_status_expiry` (`status`,`subscription_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saas_platform_admin` (
  `admin_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_saas_platform_admin_username` (`username`),
  UNIQUE KEY `uq_saas_platform_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saas_voucher` (
  `voucher_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_prefix` varchar(20) NOT NULL,
  `duration_days` int unsigned NOT NULL,
  `status` enum('UNUSED','REDEEMED','REVOKED') NOT NULL DEFAULT 'UNUSED',
  `assigned_company_id` bigint unsigned DEFAULT NULL,
  `redeemed_company_id` bigint unsigned DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `redeemed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`voucher_id`),
  UNIQUE KEY `uq_saas_voucher_code_hash` (`code_hash`),
  KEY `idx_saas_voucher_status_valid` (`status`,`valid_until`),
  CONSTRAINT `fk_saas_voucher_assigned_company` FOREIGN KEY (`assigned_company_id`) REFERENCES `saas_company` (`company_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_saas_voucher_redeemed_company` FOREIGN KEY (`redeemed_company_id`) REFERENCES `saas_company` (`company_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_saas_voucher_creator` FOREIGN KEY (`created_by`) REFERENCES `saas_platform_admin` (`admin_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_saas_voucher_duration` CHECK (`duration_days` BETWEEN 1 AND 3650)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saas_voucher_redemption` (
  `redemption_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `redeemed_by_username` varchar(100) NOT NULL,
  `previous_expiry` date DEFAULT NULL,
  `new_expiry` date NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`redemption_id`),
  UNIQUE KEY `uq_saas_redemption_voucher` (`voucher_id`),
  KEY `idx_saas_redemption_company_date` (`company_id`,`redeemed_at`),
  CONSTRAINT `fk_saas_redemption_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `saas_voucher` (`voucher_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_saas_redemption_company` FOREIGN KEY (`company_id`) REFERENCES `saas_company` (`company_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
