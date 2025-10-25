-- Add cancel_reason and cancelled_at columns to the orders table
ALTER TABLE orders ADD COLUMN cancel_reason TEXT NULL;
ALTER TABLE orders ADD COLUMN cancelled_at DATETIME NULL;
