-- Direct SQL Fix for Subcontract Status
-- Run this in phpMyAdmin SQL tab

-- First, check current statuses
SELECT id, status, created_at FROM subcontract_requests ORDER BY id;

-- Update NULL/empty statuses to 'submitted'
UPDATE subcontract_requests SET status = 'submitted' WHERE id = 2;
UPDATE subcontract_requests SET status = 'submitted' WHERE id = 3;

-- Update any other NULL statuses
UPDATE subcontract_requests SET status = 'submitted' WHERE status IS NULL;
UPDATE subcontract_requests SET status = 'submitted' WHERE status = '';

-- Set default value for status column
ALTER TABLE subcontract_requests MODIFY COLUMN status VARCHAR(50) DEFAULT 'submitted';

-- Verify the fix
SELECT id, status, created_at FROM subcontract_requests ORDER BY id;

-- Show counts
SELECT status, COUNT(*) as count FROM subcontract_requests GROUP BY status;
