<?php
require_once 'db.php';

// First, drop the existing table if it exists
$conn->query("DROP TABLE IF EXISTS customization_requests");

// Create the enhanced customization_requests table
$sql = "CREATE TABLE IF NOT EXISTS customization_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- Garment Details
    product_type ENUM('t-shirt', 'polo', 'blouse', 'dress', 'pants', 'skirt', 'hoodie', 'jacket') NOT NULL,
    garment_style VARCHAR(100) NOT NULL,  -- e.g., casual, formal, sporty
    
    -- Measurements (in cm)
    chest_width DECIMAL(5,1),
    waist_width DECIMAL(5,1),
    hip_width DECIMAL(5,1),
    shoulder_width DECIMAL(5,1),
    sleeve_length DECIMAL(5,1),
    garment_length DECIMAL(5,1),
    
    -- Design Elements
    neckline_type VARCHAR(50),  -- e.g., round, v-neck, polo
    sleeve_type VARCHAR(50),    -- e.g., short, long, three-quarter
    fit_type ENUM('slim', 'regular', 'loose'),
    fabric_type VARCHAR(100),
    
    -- Additional Customization
    color_preference_1 VARCHAR(50),
    color_preference_2 VARCHAR(50),
    pattern_type VARCHAR(50),   -- e.g., solid, striped, printed
    
    -- Design References
    description TEXT,
    reference_image_path VARCHAR(255),
    
    -- Special Instructions
    special_instructions TEXT,
    
    -- Status and Admin
    status ENUM('draft', 'submitted', 'in_review', 'approved', 'revision_requested', 'in_production', 'completed', 'cancelled') DEFAULT 'draft',
    admin_notes TEXT,
    price_quote DECIMAL(10,2),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'customization_requests' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
