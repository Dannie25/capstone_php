<?php
// Change to parent directory to include db.php
chdir(__DIR__ . '/..');
include 'db.php';

// Add color_image column to product_colors table
$sql = "ALTER TABLE `product_colors` ADD COLUMN IF NOT EXISTS `color_image` VARCHAR(255) DEFAULT NULL AFTER `quantity`";

if ($conn->query($sql) === TRUE) {
    echo "Column 'color_image' added to 'product_colors' table successfully.<br>";
} else {
    // Check if column already exists
    $check = $conn->query("SHOW COLUMNS FROM `product_colors` LIKE 'color_image'");
    if ($check->num_rows > 0) {
        echo "Column 'color_image' already exists in 'product_colors' table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
}

echo "<br>Migration complete. <a href='../admin/product.php'>Go to Product Management</a>";
$conn->close();
?>
