<?php
/**
 * Fix Customization Table - Add Missing Columns
 * This script safely adds missing columns to the customization_requests table
 * without deleting existing data.
 */

require_once 'db.php';

echo "<h2>Fixing Customization Requests Table</h2>";
echo "<pre>";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'customization_requests'");
if ($tableCheck->num_rows == 0) {
    echo "❌ Table 'customization_requests' does not exist!\n";
    echo "Creating table from scratch...\n\n";
    
    $createTable = "CREATE TABLE `customization_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `product_type` varchar(100) DEFAULT NULL,
        `garment_style` varchar(100) DEFAULT NULL,
        `garment_purpose` varchar(100) DEFAULT NULL,
        `occasion` varchar(100) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `neckline_type` varchar(50) DEFAULT NULL,
        `sleeve_type` varchar(50) DEFAULT NULL,
        `fit_type` varchar(50) DEFAULT NULL,
        `fabric_type` varchar(100) DEFAULT NULL,
        `fabric_weight` varchar(50) DEFAULT NULL,
        `color_preference_1` varchar(50) DEFAULT NULL,
        `color_preference_2` varchar(50) DEFAULT NULL,
        `pattern_type` varchar(100) DEFAULT NULL,
        `chest_width` decimal(10,2) DEFAULT NULL,
        `waist_width` decimal(10,2) DEFAULT NULL,
        `shoulder_width` decimal(10,2) DEFAULT NULL,
        `sleeve_length` decimal(10,2) DEFAULT NULL,
        `garment_length` decimal(10,2) DEFAULT NULL,
        `hip_width` decimal(10,2) DEFAULT NULL,
        `neck_circumference` decimal(10,2) DEFAULT NULL,
        `arm_circumference` decimal(10,2) DEFAULT NULL,
        `wrist_circumference` decimal(10,2) DEFAULT NULL,
        `inseam_length` decimal(10,2) DEFAULT NULL,
        `budget_min` decimal(10,2) DEFAULT NULL,
        `budget_max` decimal(10,2) DEFAULT NULL,
        `quantity` int(11) DEFAULT 1,
        `deadline` date DEFAULT NULL,
        `special_instructions` text DEFAULT NULL,
        `reference_image_path` varchar(255) DEFAULT NULL,
        `reference_image_2` varchar(255) DEFAULT NULL,
        `reference_image_3` varchar(255) DEFAULT NULL,
        `status` enum('submitted','pending','in_review','approved','rejected','completed') DEFAULT 'submitted',
        `admin_notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `customization_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($createTable)) {
        echo "✅ Table created successfully!\n";
    } else {
        echo "❌ Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "✅ Table 'customization_requests' exists\n\n";
    
    // Get current columns
    $columnsResult = $conn->query("SHOW COLUMNS FROM customization_requests");
    $existingColumns = [];
    while ($col = $columnsResult->fetch_assoc()) {
        $existingColumns[] = $col['Field'];
    }
    
    echo "Current columns: " . implode(", ", $existingColumns) . "\n\n";
    
    // Define all required columns with their definitions
    $requiredColumns = [
        'garment_style' => "VARCHAR(100) DEFAULT NULL",
        'garment_purpose' => "VARCHAR(100) DEFAULT NULL",
        'occasion' => "VARCHAR(100) DEFAULT NULL",
        'neckline_type' => "VARCHAR(50) DEFAULT NULL",
        'sleeve_type' => "VARCHAR(50) DEFAULT NULL",
        'fit_type' => "VARCHAR(50) DEFAULT NULL",
        'fabric_type' => "VARCHAR(100) DEFAULT NULL",
        'fabric_weight' => "VARCHAR(50) DEFAULT NULL",
        'color_preference_1' => "VARCHAR(50) DEFAULT NULL",
        'color_preference_2' => "VARCHAR(50) DEFAULT NULL",
        'pattern_type' => "VARCHAR(100) DEFAULT NULL",
        'chest_width' => "DECIMAL(10,2) DEFAULT NULL",
        'waist_width' => "DECIMAL(10,2) DEFAULT NULL",
        'shoulder_width' => "DECIMAL(10,2) DEFAULT NULL",
        'sleeve_length' => "DECIMAL(10,2) DEFAULT NULL",
        'garment_length' => "DECIMAL(10,2) DEFAULT NULL",
        'hip_width' => "DECIMAL(10,2) DEFAULT NULL",
        'neck_circumference' => "DECIMAL(10,2) DEFAULT NULL",
        'arm_circumference' => "DECIMAL(10,2) DEFAULT NULL",
        'wrist_circumference' => "DECIMAL(10,2) DEFAULT NULL",
        'inseam_length' => "DECIMAL(10,2) DEFAULT NULL",
        'budget_min' => "DECIMAL(10,2) DEFAULT NULL",
        'budget_max' => "DECIMAL(10,2) DEFAULT NULL",
        'quantity' => "INT(11) DEFAULT 1",
        'deadline' => "DATE DEFAULT NULL",
        'special_instructions' => "TEXT DEFAULT NULL",
        'reference_image_path' => "VARCHAR(255) DEFAULT NULL",
        'reference_image_2' => "VARCHAR(255) DEFAULT NULL",
        'reference_image_3' => "VARCHAR(255) DEFAULT NULL"
    ];
    
    // Add missing columns
    $addedCount = 0;
    $skippedCount = 0;
    
    foreach ($requiredColumns as $columnName => $columnDef) {
        if (!in_array($columnName, $existingColumns)) {
            $sql = "ALTER TABLE customization_requests ADD COLUMN `$columnName` $columnDef";
            if ($conn->query($sql)) {
                echo "✅ Added column: $columnName\n";
                $addedCount++;
            } else {
                echo "❌ Error adding column $columnName: " . $conn->error . "\n";
            }
        } else {
            $skippedCount++;
        }
    }
    
    echo "\n";
    echo "Summary:\n";
    echo "- Columns added: $addedCount\n";
    echo "- Columns already exist: $skippedCount\n";
    
    // Update status enum if needed
    $statusCheck = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'status'");
    if ($statusCheck && $statusCheck->num_rows > 0) {
        $statusCol = $statusCheck->fetch_assoc();
        if (strpos($statusCol['Type'], 'submitted') === false) {
            echo "\nUpdating status enum to include 'submitted'...\n";
            $updateStatus = "ALTER TABLE customization_requests 
                            MODIFY COLUMN `status` ENUM('submitted','pending','in_review','approved','rejected','completed') 
                            DEFAULT 'submitted'";
            if ($conn->query($updateStatus)) {
                echo "✅ Status enum updated\n";
            } else {
                echo "❌ Error updating status: " . $conn->error . "\n";
            }
        }
    }
}

echo "\n";
echo "=================================\n";
echo "✅ Database update completed!\n";
echo "=================================\n";
echo "</pre>";

echo "<p><a href='customization.php' style='display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Go to Customization Page</a></p>";
?>
