<?php
// Database connection
include 'db.php';

// Add cancel_reason column if it doesn't exist
$sql = "SHOW COLUMNS FROM `orders` LIKE 'cancel_reason'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // The column doesn't exist, so add it
    $alter_sql = "ALTER TABLE `orders` 
                 ADD COLUMN `cancel_reason` TEXT NULL DEFAULT NULL AFTER `status`,
                 ADD COLUMN `cancelled_at` DATETIME NULL DEFAULT NULL AFTER `cancel_reason`";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "Successfully added cancel_reason and cancelled_at columns to orders table.<br>";
    } else {
        echo "Error adding columns: " . $conn->error . "<br>";
    }
} else {
    echo "cancel_reason column already exists in orders table.<br>";
}

// Create order_history table if it doesn't exist
$table_check = $conn->query("SHOW TABLES LIKE 'order_history'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE `order_history` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `status` VARCHAR(50) NOT NULL,
        `notes` TEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        PRIMARY KEY (`id`),
        INDEX `idx_order_id` (`order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($create_table_sql) === TRUE) {
        echo "Successfully created order_history table.<br>";
    } else {
        echo "Error creating order_history table: " . $conn->error . "<br>";
    }
} else {
    echo "order_history table already exists.<br>";
}

echo "Database update complete. <a href='my_orders.php'>Go back to My Orders</a>";

// Close connection
$conn->close();
?>
