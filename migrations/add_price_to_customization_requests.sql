-- Add price and notes columns to customization_requests table
ALTER TABLE `customization_requests`
ADD COLUMN `price` DECIMAL(10,2) DEFAULT NULL AFTER `reference_image_path`,
ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `price`,
MODIFY COLUMN `status` ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending';
