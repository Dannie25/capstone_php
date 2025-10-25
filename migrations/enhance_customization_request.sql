-- Enhance customization_requests table to fully support the capstone objectives
-- Objective: Allow customers to submit specific garment preferences, measurements, and design references

-- Add additional measurement fields
ALTER TABLE `customization_requests`
ADD COLUMN `neck_circumference` DECIMAL(10,2) DEFAULT NULL COMMENT 'Neck measurement in inches' AFTER `hip_width`,
ADD COLUMN `inseam_length` DECIMAL(10,2) DEFAULT NULL COMMENT 'Inseam for pants/trousers' AFTER `neck_circumference`,
ADD COLUMN `arm_circumference` DECIMAL(10,2) DEFAULT NULL COMMENT 'Bicep/arm circumference' AFTER `inseam_length`,
ADD COLUMN `wrist_circumference` DECIMAL(10,2) DEFAULT NULL COMMENT 'Wrist measurement' AFTER `arm_circumference`,
ADD COLUMN `thigh_circumference` DECIMAL(10,2) DEFAULT NULL COMMENT 'Thigh measurement' AFTER `wrist_circumference`,
ADD COLUMN `ankle_circumference` DECIMAL(10,2) DEFAULT NULL COMMENT 'Ankle measurement' AFTER `thigh_circumference`;

-- Add fabric and material preferences
ALTER TABLE `customization_requests`
ADD COLUMN `fabric_type` VARCHAR(100) DEFAULT NULL COMMENT 'Preferred fabric (cotton, polyester, silk, etc.)' AFTER `fit_type`,
ADD COLUMN `fabric_weight` VARCHAR(50) DEFAULT NULL COMMENT 'Light, medium, heavy' AFTER `fabric_type`,
ADD COLUMN `fabric_texture` VARCHAR(100) DEFAULT NULL COMMENT 'Smooth, textured, etc.' AFTER `fabric_weight`;

-- Add color and pattern preferences
ADD COLUMN `color_preference_1` VARCHAR(50) DEFAULT NULL COMMENT 'Primary color' AFTER `fabric_texture`,
ADD COLUMN `color_preference_2` VARCHAR(50) DEFAULT NULL COMMENT 'Secondary color' AFTER `color_preference_1`,
ADD COLUMN `pattern_type` VARCHAR(100) DEFAULT NULL COMMENT 'Solid, stripes, checks, etc.' AFTER `color_preference_2`;

-- Add budget and timeline
ALTER TABLE `customization_requests`
ADD COLUMN `budget_min` DECIMAL(10,2) DEFAULT NULL COMMENT 'Minimum budget' AFTER `pattern_type`,
ADD COLUMN `budget_max` DECIMAL(10,2) DEFAULT NULL COMMENT 'Maximum budget' AFTER `budget_min`,
ADD COLUMN `quantity` INT DEFAULT 1 COMMENT 'Number of pieces' AFTER `budget_max`,
ADD COLUMN `deadline` DATE DEFAULT NULL COMMENT 'Preferred completion date' AFTER `quantity`,
ADD COLUMN `urgency` ENUM('low','medium','high','urgent') DEFAULT 'medium' AFTER `deadline`;

-- Add additional design reference fields
ALTER TABLE `customization_requests`
ADD COLUMN `reference_image_2` VARCHAR(255) DEFAULT NULL COMMENT 'Additional reference image' AFTER `reference_image_path`,
ADD COLUMN `reference_image_3` VARCHAR(255) DEFAULT NULL COMMENT 'Additional reference image' AFTER `reference_image_2`,
ADD COLUMN `design_inspiration` TEXT DEFAULT NULL COMMENT 'Description of design inspiration' AFTER `reference_image_3`;

-- Add garment purpose and occasion
ALTER TABLE `customization_requests`
ADD COLUMN `garment_purpose` VARCHAR(100) DEFAULT NULL COMMENT 'Casual, formal, sports, etc.' AFTER `garment_style`,
ADD COLUMN `occasion` VARCHAR(200) DEFAULT NULL COMMENT 'Wedding, office, party, etc.' AFTER `garment_purpose`;

-- Update status enum to include more statuses
ALTER TABLE `customization_requests`
MODIFY COLUMN `status` ENUM('pending','submitted','in_review','approved','in_progress','completed','cancelled','rejected') DEFAULT 'submitted';

-- Add customer confirmation fields
ALTER TABLE `customization_requests`
ADD COLUMN `customer_confirmed` TINYINT(1) DEFAULT 0 COMMENT 'Customer confirmed the price' AFTER `notes`,
ADD COLUMN `confirmed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When customer confirmed' AFTER `customer_confirmed`;

-- Show updated structure
DESCRIBE customization_requests;
