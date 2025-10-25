<?php
/**
 * Migration Script: Add Color-Size Inventory Table
 * Run this file once to create the product_color_size_inventory table
 */

include '../db.php';

echo "<h2>Running Color-Size Inventory Migration...</h2>";

// Create the inventory table
$sql1 = "CREATE TABLE IF NOT EXISTS `product_color_size_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `size` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_color_size` (`product_id`, `color`, `size`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_color` (`color`),
  KEY `idx_size` (`size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql1) === TRUE) {
    echo "<p style='color: green;'>✓ Table 'product_color_size_inventory' created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
}

// Add color_image column to product_colors if it doesn't exist
$sql2 = "ALTER TABLE `product_colors` 
ADD COLUMN IF NOT EXISTS `color_image` varchar(255) DEFAULT NULL AFTER `quantity`";

if ($conn->query($sql2) === TRUE) {
    echo "<p style='color: green;'>✓ Column 'color_image' added to product_colors table</p>";
} else {
    // Check if column already exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM product_colors LIKE 'color_image'");
    if ($checkColumn && $checkColumn->num_rows > 0) {
        echo "<p style='color: blue;'>ℹ Column 'color_image' already exists in product_colors table</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . $conn->error . "</p>";
    }
}

echo "<h3>Migration Complete!</h3>";
echo "<p><a href='../admin/product.php'>Go to Product Management</a></p>";

$conn->close();
?>
