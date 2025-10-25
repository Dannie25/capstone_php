-- Migration for GCash QR code table
CREATE TABLE IF NOT EXISTS gcash_qr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
