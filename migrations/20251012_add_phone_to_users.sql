-- Add phone column to users table
ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL 
AFTER email;

-- Update existing users with a default phone number if needed
-- UPDATE users SET phone = 'N/A' WHERE phone IS NULL;
