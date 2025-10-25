-- Update design_file column to TEXT to support multiple files (JSON array)
ALTER TABLE `subcontract_requests` 
MODIFY COLUMN `design_file` TEXT DEFAULT NULL;
