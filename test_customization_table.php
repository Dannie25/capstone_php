<?php
require_once 'db.php';

echo "<h2>Testing Customization Requests Table</h2>";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'customization_requests'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Table 'customization_requests' exists</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $result = $conn->query("DESCRIBE customization_requests");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count records
    $result = $conn->query("SELECT COUNT(*) as count FROM customization_requests");
    $row = $result->fetch_assoc();
    echo "<p>Total records: {$row['count']}</p>";
    
    // Show recent records
    $result = $conn->query("SELECT * FROM customization_requests ORDER BY created_at DESC LIMIT 5");
    if ($result->num_rows > 0) {
        echo "<h3>Recent Submissions:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Status</th><th>Created At</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No submissions yet.</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ Table 'customization_requests' does NOT exist</p>";
    echo "<p>Creating table...</p>";
    
    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS customization_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_type VARCHAR(100),
        garment_style VARCHAR(100),
        description TEXT,
        neckline_type VARCHAR(50),
        sleeve_type VARCHAR(50),
        fit_type VARCHAR(50),
        chest_width DECIMAL(5,1),
        waist_width DECIMAL(5,1),
        shoulder_width DECIMAL(5,1),
        sleeve_length DECIMAL(5,1),
        garment_length DECIMAL(5,1),
        hip_width DECIMAL(5,1),
        special_instructions TEXT,
        reference_image_path VARCHAR(255),
        status ENUM('draft', 'submitted', 'pending', 'in_review', 'approved', 'revision_requested', 'in_production', 'completed', 'cancelled') DEFAULT 'submitted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Table created successfully!</p>";
        echo "<p><a href='test_customization_table.php'>Refresh to see table structure</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
    }
}

$conn->close();
?>
