-- Fix customization_requests status ENUM to include all needed statuses
-- Run this if the cancelled status is not working

ALTER TABLE `customization_requests`
MODIFY COLUMN `status` ENUM('pending','submitted','in_review','in_progress','approved','rejected','completed','cancelled') DEFAULT 'pending';
