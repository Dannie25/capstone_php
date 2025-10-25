-- Migration: Add product_color_size_inventory table for color-size matrix
-- Date: 2025-01-16
-- Description: Creates a new table to store inventory quantities for each color-size combination

-- Create the inventory table
CREATE TABLE IF NOT EXISTS `product_color_size_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `size` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_color_size` (`product_id`, `color`, `size`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_color` (`color`),
  KEY `idx_size` (`size`),
  CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add color_image column to product_colors if it doesn't exist
ALTER TABLE `product_colors` 
ADD COLUMN IF NOT EXISTS `color_image` varchar(255) DEFAULT NULL AFTER `quantity`;
