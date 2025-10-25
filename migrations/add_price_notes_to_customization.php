<?php
/**
 * Add price and notes columns to customization_requests table
 */

require_once '../db.php';

echo "<h2>Adding price and notes columns to customization_requests table...</h2>\n<br>";

// Check if columns already exist
$result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'price'");
if ($result && $result->num_rows > 0) {
    echo "✓ Column 'price' already exists\n<br>";
} else {
    echo "Adding 'price' column...\n<br>";
    $sql = "ALTER TABLE customization_requests ADD COLUMN price DECIMAL(10,2) DEFAULT NULL AFTER reference_image_path";
    if ($conn->query($sql) === TRUE) {
        echo "✓ Successfully added 'price' column\n<br>";
    } else {
        echo "✗ Error adding 'price' column: " . $conn->error . "\n<br>";
    }
}

$result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'notes'");
if ($result && $result->num_rows > 0) {
    echo "✓ Column 'notes' already exists\n<br>";
} else {
    echo "Adding 'notes' column...\n<br>";
    $sql = "ALTER TABLE customization_requests ADD COLUMN notes TEXT DEFAULT NULL AFTER price";
    if ($conn->query($sql) === TRUE) {
        echo "✓ Successfully added 'notes' column\n<br>";
    } else {
        echo "✗ Error adding 'notes' column: " . $conn->error . "\n<br>";
    }
}

echo "\n<br><h3>Migration complete!</h3>\n<br>";
echo "<p>You can now use the price and notes features for customization requests.</p>";

// Show updated table structure
echo "\n<br><h3>Updated Table Structure:</h3>\n<br>";
$result = $conn->query("DESCRIBE customization_requests");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
