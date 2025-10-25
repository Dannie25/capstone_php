<?php
include 'db.php';

// Create product_sizes table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `product_sizes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `size` varchar(20) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'product_sizes' created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create product_colors table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS `product_colors` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `color` varchar(50) NOT NULL,
    `color_code` varchar(7) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `product_colors_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'product_colors' created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Check if description column exists in products table
$check_desc = "SHOW COLUMNS FROM `products` LIKE 'description'";
$result = $conn->query($check_desc);

if ($result->num_rows == 0) {
    // Add description column if it doesn't exist
    $sql = "ALTER TABLE `products` ADD `description` TEXT DEFAULT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'description' added to 'products' table.<br>";
    } else {
        echo "Error adding description column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'description' already exists in 'products' table.<br>";
}

// Add material column to products table if it doesn't exist
$sql = "SHOW COLUMNS FROM `products` LIKE 'material'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `products` ADD `material` VARCHAR(100) DEFAULT NULL AFTER `description`";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'material' added to 'products' table.<br>";
    } else {
        echo "Error adding material column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'material' already exists in 'products' table.<br>";
}

// Add subcategory column to products table if it doesn't exist
$sql = "SHOW COLUMNS FROM `products` LIKE 'subcategory'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `products` ADD `subcategory` VARCHAR(50) DEFAULT NULL AFTER `category`";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'subcategory' added to 'products' table.<br>";
    } else {
        echo "Error adding subcategory column: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'subcategory' already exists in 'products' table.<br>";
}

echo "<br>Setup complete. <a href='admin/'>Go to Admin Panel</a> to manage products.";
?>
