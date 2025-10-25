<?php
include 'db.php';

// Add size column if it doesn't exist
$sql = "ALTER TABLE cart 
        ADD COLUMN IF NOT EXISTS size VARCHAR(50) NULL AFTER quantity,
        ADD COLUMN IF NOT EXISTS color VARCHAR(50) NULL AFTER size";

if ($conn->multi_query($sql) === TRUE) {
    echo "Cart table updated successfully with size and color columns<br>";
} else {
    echo "Error updating cart table: " . $conn->error . "<br>";
}

// Add index for better performance
$sql = "ALTER TABLE cart 
        ADD INDEX idx_user_product (user_id, product_id, size, color)";

if ($conn->query($sql) === TRUE) {
    echo "Index added successfully<br>";
} else {
    echo "Error adding index: " . $conn->error . "<br>";
}

echo "<br>Update complete. <a href='home.php'>Go to Home</a>";

$conn->close();
?>
