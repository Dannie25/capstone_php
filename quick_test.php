<?php
session_start();
require_once 'db.php';

echo "<h1>Quick Database Test</h1>";

// Test 1: Check connection
echo "<h3>1. Database Connection</h3>";
if ($conn) {
    echo "<p style='color: green;'>✓ Connected to database: capstone_db</p>";
} else {
    echo "<p style='color: red;'>✗ Connection failed</p>";
    die();
}

// Test 2: Check if table exists
echo "<h3>2. Check Table</h3>";
$result = $conn->query("SHOW TABLES LIKE 'customization_requests'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Table 'customization_requests' exists</p>";
} else {
    echo "<p style='color: red;'>✗ Table does NOT exist</p>";
    echo "<p><a href='test_customization_table.php'>Click here to create table</a></p>";
    die();
}

// Test 3: Check if user is logged in
echo "<h3>3. User Session</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✓ User logged in: ID = {$_SESSION['user_id']}</p>";
} else {
    echo "<p style='color: orange;'>⚠ Not logged in. Auto-login for testing...</p>";
    $result = $conn->query("SELECT id FROM users LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        echo "<p style='color: green;'>✓ Auto-logged in as user ID: {$row['id']}</p>";
    } else {
        echo "<p style='color: red;'>✗ No users found</p>";
        die();
    }
}

// Test 4: Try a simple insert
echo "<h3>4. Test Insert</h3>";
$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("INSERT INTO customization_requests (
        user_id, product_type, garment_style, description,
        neckline_type, sleeve_type, fit_type,
        chest_width, waist_width, shoulder_width, sleeve_length, garment_length, hip_width,
        special_instructions, reference_image_path, status
    ) VALUES (?, 'test_shirt', 'test_fit', 'Quick test submission', 'vneck', 'normal', 'regular', 36, 32, 16, 24, 28, 38, '{}', '', 'submitted')");
    
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        echo "<p style='color: green; font-size: 20px;'>✓ SUCCESS! Test record inserted with ID: $id</p>";
        echo "<p><strong>This means your database is working!</strong></p>";
        echo "<p>Now try the actual customization form: <a href='customization.php'>customization.php</a></p>";
        
        // Clean up test record
        $conn->query("DELETE FROM customization_requests WHERE id = $id");
        echo "<p style='color: #666; font-size: 12px;'>(Test record deleted)</p>";
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Insert failed: " . $e->getMessage() . "</p>";
    echo "<p>This is the problem! Check the error message above.</p>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p>If all tests passed, your database is ready!</p>";
echo "<p><a href='customization.php' style='padding: 10px 20px; background: #5b6b46; color: white; text-decoration: none; border-radius: 8px;'>Go to Customization Page</a></p>";

$conn->close();
?>
