-- Migration: Add delivery_mode column to orders table
-- Date: 2025-10-11

-- Add delivery_mode column if it doesn't exist
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS delivery_mode VARCHAR(50) DEFAULT 'pickup' AFTER payment_method;

-- Show the updated structure
DESCRIBE orders;
