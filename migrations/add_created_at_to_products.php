<?php
/**
 * Migration: Add created_at column to products table
 * This allows us to track when products were added and show "NEW" badges
 */

require_once __DIR__ . '/../db.php';

echo "<h2>Adding created_at column to products table...</h2>";

// Check if column already exists
$check_sql = "SHOW COLUMNS FROM products LIKE 'created_at'";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    echo "<p style='color: orange;'>✓ Column 'created_at' already exists in products table.</p>";
} else {
    // Add created_at column with default CURRENT_TIMESTAMP
    $sql = "ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER discount_value";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Successfully added 'created_at' column to products table.</p>";
        
        // Update existing products to have current timestamp
        $update_sql = "UPDATE products SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL";
        if ($conn->query($update_sql) === TRUE) {
            echo "<p style='color: green;'>✓ Updated existing products with current timestamp.</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error adding column: " . $conn->error . "</p>";
    }
}

echo "<br><a href='../products.php'>← Back to Products</a>";
?>
