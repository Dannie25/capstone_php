<?php
require_once 'db.php';

// Add subcategory column to products table
try {
    $sql = "ALTER TABLE products ADD COLUMN IF NOT EXISTS subcategory VARCHAR(50) AFTER category";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added 'subcategory' column to products table. <a href='women.php'>Go back to Women's Collection</a>";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
