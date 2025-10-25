<?php
// Run subcontract status enum migration
include 'db.php';

echo "<h2>Running Subcontract Status Enum Migration</h2>";

// Read the SQL file
$sql = file_get_contents(__DIR__ . '/migrations/update_subcontract_status_enum.sql');

if ($sql === false) {
    die("Error: Could not read migration file");
}

// Execute the migration
if ($conn->multi_query($sql)) {
    echo "<p style='color: green;'>✓ Migration executed successfully!</p>";
    
    // Clear any remaining results
    while ($conn->more_results()) {
        $conn->next_result();
    }
    
    // Verify the change
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'status'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<h3>Current Status Column Definition:</h3>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    
    echo "<p><strong>Status enum now includes:</strong></p>";
    echo "<ul>";
    echo "<li>pending - Initial submission</li>";
    echo "<li>awaiting_confirmation - Admin set price, waiting for customer acceptance</li>";
    echo "<li>in_progress - Customer accepted and paid, work in progress</li>";
    echo "<li>to_deliver - Ready for delivery</li>";
    echo "<li>completed - Delivered and completed</li>";
    echo "<li>cancelled - Cancelled by customer or admin</li>";
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>✗ Migration failed: " . $conn->error . "</p>";
}

$conn->close();
?>
