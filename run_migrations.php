<?php
// Run database migrations
include 'db.php';

echo "<h2>Running Database Migrations</h2>";

// Migration 1: Add delivery_method column
$sql1 = "ALTER TABLE `subcontract_requests` 
         ADD COLUMN `delivery_method` ENUM('Pick-up', 'Lalamove') DEFAULT NULL AFTER `email`";

echo "<p>1. Adding delivery_method column...</p>";
if ($conn->query($sql1) === TRUE) {
    echo "<p style='color: green;'>✓ Successfully added delivery_method column</p>";
} else {
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "<p style='color: orange;'>⚠ Column delivery_method already exists (skipped)</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
}

// Migration 2: Update design_file column to TEXT
$sql2 = "ALTER TABLE `subcontract_requests` 
         MODIFY COLUMN `design_file` TEXT DEFAULT NULL";

echo "<p>2. Updating design_file column to TEXT...</p>";
if ($conn->query($sql2) === TRUE) {
    echo "<p style='color: green;'>✓ Successfully updated design_file column</p>";
} else {
    echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h3>Migration Complete!</h3>";
echo "<p><a href='subcon.php'>Go to Subcontract Form</a></p>";

$conn->close();
?>
