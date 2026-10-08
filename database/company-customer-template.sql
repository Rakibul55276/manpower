
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
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_action_created_index` (`action`,`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(50) NOT NULL,
  `location` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `branches_company_id_code_unique` (`company_id`,`code`),
  KEY `branches_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `company_code` varchar(30) DEFAULT NULL,
  `location` varchar(150) NOT NULL DEFAULT 'Not specified',
  `registration_number` varchar(100) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscription_status` varchar(20) NOT NULL DEFAULT 'inactive',
  `subscription_started_at` date DEFAULT NULL,
  `subscription_expires_at` date DEFAULT NULL,
  `subscription_grace_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_name_unique` (`name`),
  UNIQUE KEY `companies_company_code_unique` (`company_code`),
  KEY `companies_subscription_status_subscription_expires_at_index` (`subscription_status`,`subscription_expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_user` (
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`company_id`,`user_id`),
  KEY `company_user_user_id_foreign` (`user_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `designations_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_library_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_library_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `title` varchar(180) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `storage_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_library_files_storage_path_unique` (`storage_path`),
  KEY `document_library_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `document_library_files_owner_id_category_index` (`owner_id`,`category`),
  KEY `document_library_files_expires_on_index` (`expires_on`),
  CONSTRAINT `document_library_files_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  CONSTRAINT `document_library_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_advance_repayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_advance_repayments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_advance_id` bigint(20) unsigned NOT NULL,
  `payroll_id` bigint(20) unsigned NOT NULL,
  `amount_cents` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `advance_payroll_unique` (`employee_advance_id`,`payroll_id`),
  KEY `employee_advance_repayments_payroll_id_foreign` (`payroll_id`),
  CONSTRAINT `employee_advance_repayments_employee_advance_id_foreign` FOREIGN KEY (`employee_advance_id`) REFERENCES `employee_advances` (`id`),
  CONSTRAINT `employee_advance_repayments_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `advance_date` date NOT NULL,
  `amount_cents` bigint(20) unsigned NOT NULL,
  `installment_cents` bigint(20) unsigned NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_advances_employee_id_foreign` (`employee_id`),
  KEY `employee_advances_branch_id_foreign` (`branch_id`),
  KEY `employee_advances_created_by_foreign` (`created_by`),
  KEY `employee_advances_company_id_branch_id_advance_date_index` (`company_id`,`branch_id`,`advance_date`),
  CONSTRAINT `employee_advances_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `employee_advances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `employee_advances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `employee_advances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `iqama_number` varchar(30) NOT NULL,
  `passport_number` varchar(30) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `personal_email` varchar(255) DEFAULT NULL,
  `professional_summary` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `designation_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `directorate` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `previous_experience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_experience`)),
  `blood_group` varchar(3) NOT NULL,
  `hourly_rate_cents` int(10) unsigned NOT NULL,
  `regular_hours_units` int(10) unsigned NOT NULL DEFAULT 800,
  `overtime_rate_cents` int(10) unsigned NOT NULL DEFAULT 0,
  `po_rate_cents` int(10) unsigned NOT NULL DEFAULT 0,
  `company_cost_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `salary_type` varchar(255) NOT NULL DEFAULT 'hourly',
  `employment_type` varchar(255) NOT NULL DEFAULT 'rental',
  `monthly_salary_cents` int(10) unsigned NOT NULL DEFAULT 0,
  `meal_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `transportation_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `housing_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `medical_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `retirement_insurance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `overtime_multiplier_units` int(10) unsigned NOT NULL DEFAULT 150,
  `joined_on` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_iqama_number_unique` (`iqama_number`),
  UNIQUE KEY `employees_passport_number_unique` (`passport_number`),
  KEY `employees_designation_id_foreign` (`designation_id`),
  KEY `employees_created_by_foreign` (`created_by`),
  KEY `employees_company_id_status_index` (`company_id`,`status`),
  KEY `employees_employment_type_index` (`employment_type`),
  KEY `employees_type_company_status_index` (`employment_type`,`company_id`,`status`),
  KEY `employees_name_index` (`name`),
  KEY `employees_branch_id_status_index` (`branch_id`,`status`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `employees_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `employees_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_ar` varchar(255) DEFAULT NULL,
  `customer_type` varchar(10) NOT NULL DEFAULT 'business',
  `vat_number` varchar(15) DEFAULT NULL,
  `commercial_registration` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_customers_customer_type_is_active_index` (`customer_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_events_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_events_user_id_foreign` (`user_id`),
  CONSTRAINT `invoice_events_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_ar` varchar(255) DEFAULT NULL,
  `unit_code` varchar(10) NOT NULL DEFAULT 'PCE',
  `unit_price_cents` bigint(20) unsigned NOT NULL,
  `tax_category` varchar(10) NOT NULL DEFAULT 'standard',
  `tax_rate_units` int(10) unsigned NOT NULL DEFAULT 1500,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_items_sku_unique` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `description_ar` varchar(255) DEFAULT NULL,
  `quantity_units` int(10) unsigned NOT NULL,
  `unit_code` varchar(10) NOT NULL,
  `unit_price_cents` bigint(20) unsigned NOT NULL,
  `line_subtotal_cents` bigint(20) unsigned NOT NULL,
  `discount_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `tax_category` varchar(10) NOT NULL,
  `tax_rate_units` int(10) unsigned NOT NULL,
  `tax_cents` bigint(20) unsigned NOT NULL,
  `line_total_cents` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_lines_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_lines_item_id_foreign` (`item_id`),
  CONSTRAINT `invoice_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_lines_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `invoice_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legal_name` varchar(255) NOT NULL,
  `legal_name_ar` varchar(255) DEFAULT NULL,
  `vat_number` varchar(15) NOT NULL,
  `commercial_registration` varchar(30) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(40) DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `invoice_prefix` varchar(20) NOT NULL DEFAULT 'DEMO',
  `next_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `demo_mode` tinyint(1) NOT NULL DEFAULT 1,
  `zatca_environment` varchar(255) NOT NULL DEFAULT 'disabled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `design_primary_color` varchar(7) NOT NULL DEFAULT '#167896',
  `design_text_color` varchar(7) NOT NULL DEFAULT '#17343a',
  `design_header_bg` varchar(7) NOT NULL DEFAULT '#e5f4f8',
  `invoice_title` varchar(255) NOT NULL DEFAULT 'TAX INVOICE',
  `invoice_title_ar` varchar(255) NOT NULL DEFAULT 'فاتورة ضريبية',
  `design_density` varchar(10) NOT NULL DEFAULT 'compact',
  `invoice_footer` text DEFAULT NULL,
  `show_bank_details` tinyint(1) NOT NULL DEFAULT 1,
  `show_signatures` tinyint(1) NOT NULL DEFAULT 1,
  `show_qr` tinyint(1) NOT NULL DEFAULT 1,
  `design_header_layout` varchar(20) NOT NULL DEFAULT 'split',
  `design_title_alignment` varchar(10) NOT NULL DEFAULT 'center',
  `design_logo_width` smallint(5) unsigned NOT NULL DEFAULT 58,
  `design_font_size` decimal(4,1) NOT NULL DEFAULT 7.3,
  `design_border_color` varchar(7) NOT NULL DEFAULT '#86999d',
  `show_company_cr` tinyint(1) NOT NULL DEFAULT 1,
  `show_seller_details` tinyint(1) NOT NULL DEFAULT 1,
  `show_customer_details` tinyint(1) NOT NULL DEFAULT 1,
  `show_references` tinyint(1) NOT NULL DEFAULT 1,
  `show_amount_words` tinyint(1) NOT NULL DEFAULT 1,
  `show_notes` tinyint(1) NOT NULL DEFAULT 1,
  `show_footer_uuid` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) DEFAULT NULL,
  `uuid` varchar(36) NOT NULL,
  `document_type` varchar(20) NOT NULL DEFAULT 'invoice',
  `invoice_type` varchar(10) NOT NULL DEFAULT 'standard',
  `customer_id` bigint(20) unsigned NOT NULL,
  `reference_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `issue_date` date NOT NULL,
  `supply_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `contract_po` varchar(255) DEFAULT NULL,
  `delivery_note` varchar(255) DEFAULT NULL,
  `invoice_period` varchar(255) DEFAULT NULL,
  `project_reference` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'SAR',
  `subtotal_cents` bigint(20) unsigned NOT NULL,
  `discount_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) unsigned NOT NULL,
  `total_cents` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `zatca_status` varchar(30) NOT NULL DEFAULT 'not_submitted',
  `notes` text DEFAULT NULL,
  `zatca_message` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint(20) unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_uuid_unique` (`uuid`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_customer_id_foreign` (`customer_id`),
  KEY `invoices_reference_invoice_id_foreign` (`reference_invoice_id`),
  KEY `invoices_created_by_foreign` (`created_by`),
  KEY `invoices_approved_by_foreign` (`approved_by`),
  KEY `invoices_paid_by_foreign` (`paid_by`),
  KEY `invoices_issue_date_status_index` (`issue_date`,`status`),
  KEY `invoices_invoice_type_zatca_status_index` (`invoice_type`,`zatca_status`),
  CONSTRAINT `invoices_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `invoice_customers` (`id`),
  CONSTRAINT `invoices_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`),
  CONSTRAINT `invoices_reference_invoice_id_foreign` FOREIGN KEY (`reference_invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payrolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payrolls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `month` varchar(7) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `salary_type` varchar(255) NOT NULL,
  `employment_type` varchar(255) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `designation_name` varchar(255) NOT NULL,
  `directorate` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `iqama_number` varchar(255) NOT NULL,
  `regular_units` int(10) unsigned NOT NULL,
  `overtime_units` int(10) unsigned NOT NULL,
  `regular_pay_cents` bigint(20) unsigned NOT NULL,
  `overtime_pay_cents` bigint(20) unsigned NOT NULL,
  `po_revenue_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `employee_cost_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `company_cost_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `margin_cents` bigint(20) NOT NULL DEFAULT 0,
  `allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meal_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `transportation_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `housing_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `medical_allowance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `deduction_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `advance_deduction_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `retirement_insurance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `net_pay_cents` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payrolls_employee_id_month_unique` (`employee_id`,`month`),
  KEY `payrolls_company_id_foreign` (`company_id`),
  KEY `payrolls_created_by_foreign` (`created_by`),
  KEY `payrolls_paid_by_foreign` (`paid_by`),
  KEY `payrolls_approved_by_foreign` (`approved_by`),
  KEY `payrolls_type_month_company_status_index` (`employment_type`,`month`,`company_id`,`status`),
  KEY `payrolls_employee_name_index` (`employee_name`),
  KEY `payrolls_branch_id_month_index` (`branch_id`,`month`),
  CONSTRAINT `payrolls_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payrolls_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `payrolls_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `payrolls_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payrolls_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `payrolls_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_type` varchar(20) NOT NULL DEFAULT 'retail',
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `vat_number` varchar(15) DEFAULT NULL,
  `commercial_registration` varchar(30) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `building_number` varchar(20) DEFAULT NULL,
  `street` varchar(150) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country_code` varchar(2) NOT NULL DEFAULT 'SA',
  `purchase_count` int(10) unsigned NOT NULL DEFAULT 0,
  `lifetime_value_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `last_purchase_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `approval_status` varchar(20) NOT NULL DEFAULT 'approved',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_shop_customers_phone_unique` (`phone`),
  KEY `safety_shop_customers_name_index` (`name`),
  KEY `safety_shop_customers_customer_type_index` (`customer_type`),
  KEY `safety_shop_customers_approved_by_foreign` (`approved_by`),
  KEY `safety_shop_customers_approval_status_index` (`approval_status`),
  CONSTRAINT `safety_shop_customers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_masters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `sku_prefix` varchar(10) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `vat_number` varchar(15) DEFAULT NULL,
  `commercial_registration` varchar(30) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_shop_masters_type_name_unique` (`type`,`name`),
  UNIQUE KEY `ss_master_company_type_prefix_unique` (`company_id`,`type`,`sku_prefix`),
  KEY `safety_shop_masters_company_id_type_is_active_index` (`company_id`,`type`,`is_active`),
  CONSTRAINT `safety_shop_masters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `request_key` char(36) NOT NULL,
  `type` varchar(20) NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `destination_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `balance_after` int(10) unsigned NOT NULL,
  `destination_balance_after` int(10) unsigned DEFAULT NULL,
  `movement_date` date NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `recipient` varchar(150) DEFAULT NULL,
  `notes` text NOT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `attachment_size` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_shop_movements_request_key_unique` (`request_key`),
  KEY `safety_shop_movements_location_id_foreign` (`location_id`),
  KEY `safety_shop_movements_destination_id_foreign` (`destination_id`),
  KEY `safety_shop_movements_supplier_id_foreign` (`supplier_id`),
  KEY `safety_shop_movements_created_by_foreign` (`created_by`),
  KEY `safety_shop_movements_movement_date_type_index` (`movement_date`,`type`),
  KEY `safety_shop_movements_product_id_location_id_index` (`product_id`,`location_id`),
  KEY `ss_movements_company_date_index` (`company_id`,`movement_date`),
  CONSTRAINT `safety_shop_movements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `safety_shop_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `safety_shop_movements_destination_id_foreign` FOREIGN KEY (`destination_id`) REFERENCES `safety_shop_masters` (`id`),
  CONSTRAINT `safety_shop_movements_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`),
  CONSTRAINT `safety_shop_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`),
  CONSTRAINT `safety_shop_movements_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `safety_shop_masters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'piece',
  `safety_standard` varchar(150) DEFAULT NULL,
  `reorder_level` int(10) unsigned NOT NULL DEFAULT 0,
  `cost_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `price_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ss_products_company_sku_unique` (`company_id`,`sku`),
  UNIQUE KEY `ss_products_company_barcode_unique` (`company_id`,`barcode`),
  KEY `safety_shop_products_category_id_foreign` (`category_id`),
  KEY `safety_shop_products_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `safety_shop_products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `safety_shop_masters` (`id`),
  CONSTRAINT `safety_shop_products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_receipt_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_receipt_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(180) NOT NULL DEFAULT 'Safety Shop',
  `tagline` varchar(180) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `default_location_id` bigint(20) unsigned DEFAULT NULL,
  `vat_number` varchar(30) DEFAULT NULL,
  `commercial_registration` varchar(30) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `footer_text` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `safety_shop_receipt_settings_default_location_id_foreign` (`default_location_id`),
  CONSTRAINT `safety_shop_receipt_settings_default_location_id_foreign` FOREIGN KEY (`default_location_id`) REFERENCES `safety_shop_masters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_return_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_return_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `sale_line_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `cost_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `refund_cents` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_shop_return_lines_return_id_sale_line_id_unique` (`return_id`,`sale_line_id`),
  KEY `safety_shop_return_lines_product_id_foreign` (`product_id`),
  KEY `safety_shop_return_lines_sale_line_id_index` (`sale_line_id`),
  CONSTRAINT `safety_shop_return_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`),
  CONSTRAINT `safety_shop_return_lines_return_id_foreign` FOREIGN KEY (`return_id`) REFERENCES `safety_shop_returns` (`id`),
  CONSTRAINT `safety_shop_return_lines_sale_line_id_foreign` FOREIGN KEY (`sale_line_id`) REFERENCES `safety_shop_sale_lines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_key` char(36) NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `refund_method` varchar(20) NOT NULL,
  `refund_cents` bigint(20) unsigned NOT NULL,
  `gross_refund_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount_refund_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `tax_refund_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `cost_reversal_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reason` varchar(500) NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_shop_returns_request_key_unique` (`request_key`),
  KEY `safety_shop_returns_location_id_foreign` (`location_id`),
  KEY `safety_shop_returns_created_by_foreign` (`created_by`),
  KEY `safety_shop_returns_sale_id_created_at_index` (`sale_id`,`created_at`),
  CONSTRAINT `safety_shop_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `safety_shop_returns_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`),
  CONSTRAINT `safety_shop_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `safety_shop_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_sale_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_sale_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(50) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `cost_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `price_cents` bigint(20) unsigned NOT NULL,
  `total_cents` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `safety_shop_sale_lines_sale_id_foreign` (`sale_id`),
  KEY `safety_shop_sale_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `safety_shop_sale_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`),
  CONSTRAINT `safety_shop_sale_lines_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `safety_shop_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_key` char(36) NOT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `customer_type` varchar(20) NOT NULL DEFAULT 'retail',
  `customer` varchar(150) NOT NULL,
  `customer_contact_person` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `customer_vat_number` varchar(15) DEFAULT NULL,
  `customer_commercial_registration` varchar(30) DEFAULT NULL,
  `customer_address` varchar(500) DEFAULT NULL,
  `subtotal_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `discount_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxable_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `tax_rate_units` int(10) unsigned NOT NULL DEFAULT 0,
  `tax_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `total_cents` bigint(20) unsigned NOT NULL,
  `paid_cents` bigint(20) unsigned NOT NULL,
  `cash_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `card_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `bank_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payment_method` varchar(20) NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_shop_sales_request_key_unique` (`request_key`),
  KEY `safety_shop_sales_location_id_foreign` (`location_id`),
  KEY `safety_shop_sales_created_by_foreign` (`created_by`),
  KEY `safety_shop_sales_customer_id_foreign` (`customer_id`),
  CONSTRAINT `safety_shop_sales_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `safety_shop_sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `safety_shop_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `safety_shop_sales_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_shop_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safety_shop_stocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_shop_stocks_product_id_location_id_unique` (`product_id`,`location_id`),
  KEY `safety_shop_stocks_location_id_foreign` (`location_id`),
  CONSTRAINT `safety_shop_stocks_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `safety_shop_masters` (`id`),
  CONSTRAINT `safety_shop_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `safety_shop_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timesheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timesheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `work_date` date NOT NULL,
  `regular_units` int(10) unsigned NOT NULL,
  `overtime_units` int(10) unsigned NOT NULL DEFAULT 0,
  `hourly_rate_cents` int(10) unsigned NOT NULL,
  `overtime_rate_cents` int(10) unsigned NOT NULL DEFAULT 0,
  `overtime_multiplier_units` int(10) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `payroll_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `timesheets_employee_id_work_date_unique` (`employee_id`,`work_date`),
  KEY `timesheets_created_by_foreign` (`created_by`),
  KEY `timesheets_reviewed_by_foreign` (`reviewed_by`),
  KEY `timesheets_payroll_id_foreign` (`payroll_id`),
  KEY `timesheets_work_date_status_index` (`work_date`,`status`),
  KEY `timesheets_company_id_foreign` (`company_id`),
  KEY `timesheets_branch_id_work_date_index` (`branch_id`,`work_date`),
  CONSTRAINT `timesheets_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `timesheets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `timesheets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `timesheets_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `timesheets_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`),
  CONSTRAINT `timesheets_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'manager',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_company_id_foreign` (`company_id`),
  KEY `users_branch_id_foreign` (`branch_id`),
  KEY `users_role_company_id_branch_id_index` (`role`,`company_id`,`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Laravel migration baseline; no users or business data are included.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2026_10_03_000001_create_manpower_tables',1),(5,'2026_10_04_000001_remove_demo_labels',1),(6,'2026_10_04_000002_add_username_to_users',1),(7,'2026_10_04_000003_add_cv_fields_to_employees',1),(8,'2026_10_04_000004_add_company_master_fields',1),(9,'2026_10_04_000005_add_payroll_approval_and_overtime_rate',1),(10,'2026_10_04_000006_add_regular_hours_to_employees',1),(11,'2026_10_04_000007_add_own_employee_salary_components',1),(12,'2026_10_04_000008_add_housing_allowance',1),(13,'2026_10_04_000009_convert_overtime_multiplier_to_fixed_rate',2),(14,'2026_10_04_000010_add_high_volume_query_indexes',2),(15,'2026_10_05_000001_create_invoicing_module_tables',3),(16,'2026_10_05_000002_separate_zatca_from_invoice_platform',4),(17,'2026_10_05_000003_add_professional_invoice_fields',5),(18,'2026_10_05_000004_add_invoice_design_settings',6),(19,'2026_10_05_000005_expand_invoice_design_controls',7),(20,'2026_10_05_000006_create_safety_shop_inventory_tables',8),(21,'2026_10_06_000001_add_customer_phone_to_safety_shop_sales',9),(22,'2026_10_06_000002_create_document_library_files_table',10),(23,'2026_10_06_000003_add_discount_and_split_payments_to_safety_shop_sales',11),(24,'2026_10_06_000004_create_safety_shop_receipt_settings_table',12),(25,'2026_10_06_000005_create_safety_shop_returns_tables',13),(26,'2026_10_06_000006_add_financial_snapshots_to_safety_shop',14),(27,'2026_10_06_000007_backfill_safety_shop_return_financials',15),(28,'2026_10_06_000008_create_safety_shop_customers',16),(29,'2026_10_07_000001_add_customer_types_and_harbour_edge_branding',17),(30,'2026_10_07_000002_add_customer_approval_status',18),(31,'2026_10_07_000003_add_default_shop_location_setting',19),(32,'2026_10_08_000001_create_company_branches_and_scope_users',20),(33,'2026_10_08_000002_scope_safety_shop_catalog_to_companies',20),(34,'2026_10_08_000003_add_sku_prefix_to_safety_shop_categories',21),(35,'2026_10_08_000004_scope_product_identifiers_to_company',22),(36,'2026_10_08_000005_add_supplier_company_fields',23),(37,'2026_10_08_000006_add_attachment_to_safety_shop_movements',24),(38,'2026_10_08_000007_scope_safety_shop_movements_to_companies',25),(39,'2026_10_08_000008_add_rental_billing_rates_and_monthly_margin',26),(40,'2026_10_08_000009_create_employee_advances',26),(41,'2026_10_08_000010_enable_saas_vouchers',27),(42,'2026_10_08_000011_add_logo_to_companies',28);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

