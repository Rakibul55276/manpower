-- Run once in phpMyAdmin after taking a database backup.
-- This is the no-Terminal equivalent of the Laravel migration.
ALTER TABLE `safety_shop_receipt_settings`
    ADD COLUMN `quotation_valid_days` SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER `default_location_id`,
    ADD COLUMN `quotation_terms` TEXT NULL AFTER `quotation_valid_days`;
