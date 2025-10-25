<?php
/**
 * Fix customization_requests status ENUM
 * This ensures that 'cancelled' status is available in the ENUM
 */

require_once '../db.php';

echo "Checking customization_requests status ENUM...\n<br>";

// Get current ENUM values
$result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'status'");
if ($result && $row = $result->fetch_assoc()) {
    echo "Current status ENUM: " . $row['Type'] . "\n<br><br>";
}

// Update the ENUM to include all statuses
$sql = "ALTER TABLE `customization_requests`
        MODIFY COLUMN `status` ENUM('pending','submitted','in_review','in_progress','approved','rejected','completed','cancelled') DEFAULT 'pending'";

if ($conn->query($sql) === TRUE) {
    echo "✓ Successfully updated status ENUM!\n<br><br>";
    
    // Verify the change
    $result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'status'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "New status ENUM: " . $row['Type'] . "\n<br>";
    }
    
    echo "\n<br><strong>Status ENUM now includes 'cancelled'!</strong>\n<br>";
} else {
    echo "✗ Error updating status ENUM: " . $conn->error . "\n<br>";
}

$conn->close();
?>
