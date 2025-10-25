<?php
// Test script to check inventory matrix saving
include '../db.php';

echo "<h1>Inventory Matrix Save Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Check if table exists
echo "<h2>1. Checking if table exists...</h2>";
$result = $conn->query("SHOW TABLES LIKE 'product_color_size_inventory'");
if ($result->num_rows > 0) {
    echo "<p class='success'>✅ Table 'product_color_size_inventory' exists!</p>";
} else {
    echo "<p class='error'>❌ Table 'product_color_size_inventory' does NOT exist!</p>";
    echo "<p>Run this migration: <a href='../migrations/run_all_migrations.php'>Run All Migrations</a></p>";
    exit;
}

// Check table structure
echo "<h2>2. Table Structure:</h2>";
$result = $conn->query("DESCRIBE product_color_size_inventory");
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
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

// Check if there's any data
echo "<h2>3. Current Inventory Data:</h2>";
$result = $conn->query("SELECT * FROM product_color_size_inventory ORDER BY product_id, color, size");
if ($result->num_rows > 0) {
    echo "<p class='success'>Found {$result->num_rows} records</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Product ID</th><th>Color</th><th>Size</th><th>Quantity</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['product_id']}</td>";
        echo "<td>{$row['color']}</td>";
        echo "<td>{$row['size']}</td>";
        echo "<td><strong>{$row['quantity']}</strong></td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='info'>ℹ️ No inventory data yet. Add or edit a product to save inventory.</p>";
}

// Test insert
echo "<h2>4. Test Insert:</h2>";
echo "<p>Testing if we can insert data...</p>";

$test_product_id = 999999; // Use a test product ID
$test_color = "TestRed";
$test_size = "TestM";
$test_quantity = 100;

// Delete test data first
$conn->query("DELETE FROM product_color_size_inventory WHERE product_id = $test_product_id");

// Try to insert
$stmt = $conn->prepare("INSERT INTO product_color_size_inventory (product_id, color, size, quantity) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issi", $test_product_id, $test_color, $test_size, $test_quantity);

if ($stmt->execute()) {
    echo "<p class='success'>✅ Test insert successful!</p>";
    
    // Verify
    $result = $conn->query("SELECT * FROM product_color_size_inventory WHERE product_id = $test_product_id");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<p class='success'>✅ Data verified: {$row['color']} - {$row['size']} = {$row['quantity']}</p>";
    }
    
    // Clean up
    $conn->query("DELETE FROM product_color_size_inventory WHERE product_id = $test_product_id");
    echo "<p class='info'>Test data cleaned up.</p>";
} else {
    echo "<p class='error'>❌ Test insert failed: " . $conn->error . "</p>";
}

echo "<h2>5. Form Submission Check:</h2>";
echo "<p>When you submit the product form, check if:</p>";
echo "<ul>";
echo "<li>✓ Colors are checked</li>";
echo "<li>✓ Sizes are checked</li>";
echo "<li>✓ Inventory matrix appears</li>";
echo "<li>✓ Quantities are entered in the matrix cells</li>";
echo "<li>✓ Form name attributes are: <code>inventory[Color_Size]</code></li>";
echo "</ul>";

echo "<h2>6. Debug Form Data:</h2>";
echo "<p>To debug, add this to product.php after line with <code>\$_POST['inventory']</code>:</p>";
echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ccc;'>";
echo htmlspecialchars("echo '<pre>'; print_r(\$_POST['inventory']); echo '</pre>'; die();");
echo "</pre>";

echo "<hr>";
echo "<p><a href='product.php' style='padding:10px 20px;background:#5b6b46;color:white;text-decoration:none;border-radius:4px;'>Go to Product Management</a></p>";

$conn->close();
?>
