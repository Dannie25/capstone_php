-- Add price and notes columns to customization_requests table
-- Run this if the PHP migration script doesn't work

-- Add price column
ALTER TABLE customization_requests 
ADD COLUMN price DECIMAL(10,2) DEFAULT NULL AFTER reference_image_path;

-- Add notes column
ALTER TABLE customization_requests 
ADD COLUMN notes TEXT DEFAULT NULL AFTER price;

-- Verify the changes
DESCRIBE customization_requests;
