<?php
// Migration to add delivery_mode column to orders table
include '../db.php';

try {
    // Check if column already exists
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_mode'");
    
    if ($result->num_rows == 0) {
        // Add delivery_mode column
        $sql = "ALTER TABLE orders ADD COLUMN delivery_mode VARCHAR(50) DEFAULT 'pickup' AFTER payment_method";
        
        if ($conn->query($sql) === TRUE) {
            echo "✓ Successfully added delivery_mode column to orders table<br>";
        } else {
            echo "✗ Error adding delivery_mode column: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ delivery_mode column already exists in orders table<br>";
    }
    
    // Show current table structure
    echo "<br><strong>Current orders table structure:</strong><br>";
    $result = $conn->query("DESCRIBE orders");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage();
}

$conn->close();
?>
