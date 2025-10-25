<?php
// Run database migration for enhanced customization_requests table
require_once 'db.php';

echo "<h2>Running Database Migration...</h2>";

// Read the SQL file
$sqlFile = __DIR__ . '/migrations/enhance_customization_request.sql';
if (!file_exists($sqlFile)) {
    die("Error: Migration file not found at $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Split by semicolon to get individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($statements as $statement) {
    // Skip comments and DESCRIBE statements
    if (empty($statement) || strpos($statement, '--') === 0 || stripos($statement, 'DESCRIBE') !== false) {
        continue;
    }
    
    echo "<p>Executing: " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
    
    if ($conn->query($statement)) {
        $successCount++;
        echo "<p style='color: green;'>✓ Success</p>";
    } else {
        // Check if error is because column already exists
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ Column already exists (skipped)</p>";
        } else {
            $errorCount++;
            $errors[] = $conn->error;
            echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($conn->error) . "</p>";
        }
    }
}

echo "<hr>";
echo "<h3>Migration Summary</h3>";
echo "<p>Successful statements: $successCount</p>";
echo "<p>Errors: $errorCount</p>";

if (!empty($errors)) {
    echo "<h4>Error Details:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Verify the table structure
echo "<hr>";
echo "<h3>Current Table Structure:</h3>";
$result = $conn->query("DESCRIBE customization_requests");
if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><strong>Migration complete!</strong> You can now go back to <a href='customization.php'>customization.php</a></p>";

$conn->close();
?>
