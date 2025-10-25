-- Add price acceptance columns to subcontract_requests
ALTER TABLE `subcontract_requests` 
ADD COLUMN `price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `delivery_method`,
ADD COLUMN `admin_notes` TEXT NULL AFTER `price`,
ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `admin_notes`,
ADD COLUMN `final_delivery_mode` VARCHAR(50) NULL AFTER `payment_method`,
ADD COLUMN `accepted_at` DATETIME NULL AFTER `updated_at`,
ADD COLUMN `rejected_at` DATETIME NULL AFTER `accepted_at`,
ADD COLUMN `rejection_reason` TEXT NULL AFTER `rejected_at`;
