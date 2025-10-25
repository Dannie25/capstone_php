-- Add GCash reference number column to orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS gcash_reference_number VARCHAR(100) DEFAULT NULL AFTER payment_method;
