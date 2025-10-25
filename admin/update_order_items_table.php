<?php
include '../db.php';

// Add size and color columns to order_items table if they don't exist
$alter_sql = "ALTER TABLE `order_items` 
              ADD COLUMN IF NOT EXISTS `size` VARCHAR(50) NULL AFTER `quantity`,
              ADD COLUMN IF NOT EXISTS `color` VARCHAR(50) NULL AFTER `size`";

if ($conn->query($alter_sql) === TRUE) {
    echo "Successfully updated order_items table with size and color columns.";
} else {
    echo "Error updating order_items table: " . $conn->error;
}

$conn->close();
?>
