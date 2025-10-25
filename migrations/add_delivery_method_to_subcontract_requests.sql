-- Add delivery_method column to subcontract_requests table
ALTER TABLE `subcontract_requests` 
ADD COLUMN `delivery_method` ENUM('Pick-up', 'Lalamove') DEFAULT NULL AFTER `email`;
