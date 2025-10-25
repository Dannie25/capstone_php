ALTER TABLE products 
  ADD COLUMN discount_enabled TINYINT(1) DEFAULT 0,
  ADD COLUMN discount_type ENUM('percent', 'fixed') DEFAULT NULL,
  ADD COLUMN discount_value DECIMAL(10,2) DEFAULT NULL;
