-- Add cancellation_reason column to customization_requests table
ALTER TABLE customization_requests 
ADD COLUMN cancellation_reason TEXT NULL DEFAULT NULL AFTER status;

-- Update the update_customization_status.php procedure to handle cancellation reason
-- This is a note for the developer to update the PHP code accordingly
