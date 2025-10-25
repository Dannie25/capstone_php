<?php
include 'db.php';

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($result->num_rows === 0) {
    die("The 'orders' table does not exist in the database. Please run the setup script first.");
}

// Get table structure
$result = $conn->query("DESCRIBE orders");
if ($result) {
    echo "<h2>Orders Table Structure:</h2>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}

// Show sample data
$result = $conn->query("SELECT * FROM orders LIMIT 5");
if ($result) {
    echo "<h2>Sample Data (first 5 rows):</h2>";
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr>";
        // Print headers
        $fields = $result->fetch_fields();
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";
        
        // Print rows
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No records found in the orders table.";
    }
} else {
    echo "Error fetching data: " . $conn->error;
}

$conn->close();
?>
