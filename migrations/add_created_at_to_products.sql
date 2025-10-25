-- Add created_at column to products table if it doesn't exist
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `discount_value`;

-- Update existing products to have a created_at timestamp (set to current time)
UPDATE `products` 
SET `created_at` = CURRENT_TIMESTAMP 
WHERE `created_at` IS NULL OR `created_at` = '0000-00-00 00:00:00';
