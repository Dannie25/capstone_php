-- Update customization_requests table to support full customization features
-- Run this SQL in your phpMyAdmin or MySQL client

-- Drop the old table if it exists (WARNING: This will delete existing data)
DROP TABLE IF EXISTS `customization_requests`;

-- Create the new enhanced customization_requests table
CREATE TABLE `customization_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_type` varchar(100) DEFAULT NULL,
  `garment_style` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `neckline_type` varchar(50) DEFAULT NULL,
  `sleeve_type` varchar(50) DEFAULT NULL,
  `fit_type` varchar(50) DEFAULT NULL,
  `chest_width` decimal(10,2) DEFAULT NULL,
  `waist_width` decimal(10,2) DEFAULT NULL,
  `shoulder_width` decimal(10,2) DEFAULT NULL,
  `sleeve_length` decimal(10,2) DEFAULT NULL,
  `garment_length` decimal(10,2) DEFAULT NULL,
  `hip_width` decimal(10,2) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `reference_image_path` varchar(255) DEFAULT NULL,
  `status` enum('submitted','pending','in_review','approved','rejected','completed') DEFAULT 'submitted',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `customization_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
