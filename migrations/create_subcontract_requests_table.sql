-- Create subcontract_requests table
CREATE TABLE IF NOT EXISTS `subcontract_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `what_for` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `design_file` text DEFAULT NULL,
  `date_needed` date NOT NULL,
  `time_needed` time NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(100) NOT NULL,
  `delivery_method` enum('Pick-up','Lalamove') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
