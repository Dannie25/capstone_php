<?php
session_start();
require_once 'db.php';

// Simulate a logged-in user for testing
if (!isset($_SESSION['user_id'])) {
    // Get first user from database for testing
    $result = $conn->query("SELECT id FROM users LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        echo "<p style='color: orange;'>⚠ Auto-logged in as user ID: {$row['id']} for testing</p>";
    } else {
        die("<p style='color: red;'>No users found in database. Please create a user first.</p>");
    }
}

echo "<h2>Test Customization Submission</h2>";
echo "<p>User ID: {$_SESSION['user_id']}</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Attempting to submit...</h3>";
    
    // Get data
    $userId = $_SESSION['user_id'];
    $neckType = $_POST['neck_type'] ?? 'vneck';
    $sleeveType = $_POST['sleeve_type'] ?? 'normalsleeve';
    $fitType = $_POST['fit_type'] ?? 'bodyfit';
    $shirtColor = $_POST['shirt_color'] ?? '#FFFFFF';
    
    $chestSize = !empty($_POST['chest_size']) ? (float)$_POST['chest_size'] : 36.0;
    $waistSize = !empty($_POST['waist_size']) ? (float)$_POST['waist_size'] : 32.0;
    $shoulderSize = !empty($_POST['shoulder_size']) ? (float)$_POST['shoulder_size'] : 16.0;
    $sleeveSize = !empty($_POST['sleeve_size']) ? (float)$_POST['sleeve_size'] : 24.0;
    $lengthSize = !empty($_POST['length_size']) ? (float)$_POST['length_size'] : 28.0;
    $hipSize = !empty($_POST['hip_size']) ? (float)$_POST['hip_size'] : 38.0;
    
    $productType = 'custom_shirt';
    $garmentStyle = $fitType;
    $description = "Test submission - $neckType, $sleeveType, $fitType";
    
    $extendedData = json_encode([
        'design_data' => [],
        'logo_path' => '',
        'neck_type' => $neckType,
        'sleeve_type' => $sleeveType,
        'fit_type' => $fitType,
        'shirt_color' => $shirtColor,
        'measurements' => [
            'chest' => $chestSize,
            'waist' => $waistSize,
            'shoulder' => $shoulderSize,
            'sleeve' => $sleeveSize,
            'length' => $lengthSize,
            'hip' => $hipSize
        ]
    ]);
    
    echo "<p><strong>Data to insert:</strong></p>";
    echo "<pre>";
    echo "User ID: $userId\n";
    echo "Product Type: $productType\n";
    echo "Garment Style: $garmentStyle\n";
    echo "Description: $description\n";
    echo "Neck Type: $neckType\n";
    echo "Sleeve Type: $sleeveType\n";
    echo "Fit Type: $fitType\n";
    echo "Shirt Color: $shirtColor\n";
    echo "Measurements: Chest=$chestSize, Waist=$waistSize, Shoulder=$shoulderSize, Sleeve=$sleeveSize, Length=$lengthSize, Hip=$hipSize\n";
    echo "</pre>";
    
    try {
        $stmt = $conn->prepare("INSERT INTO customization_requests (
            user_id, product_type, garment_style, description,
            neckline_type, sleeve_type, fit_type,
            chest_width, waist_width, shoulder_width, sleeve_length, garment_length, hip_width,
            special_instructions, reference_image_path, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $logoPath = '';
        $stmt->bind_param(
            "issssssddddddss",
            $userId, $productType, $garmentStyle, $description,
            $neckType, $sleeveType, $fitType,
            $chestSize, $waistSize, $shoulderSize, $sleeveSize, $lengthSize, $hipSize,
            $extendedData, $logoPath
        );
        
        if ($stmt->execute()) {
            $requestId = $conn->insert_id;
            echo "<p style='color: green; font-size: 20px;'>✓ SUCCESS! Request ID: $requestId</p>";
            echo "<p><a href='test_customization_table.php'>View in database</a></p>";
            echo "<p><a href='admin/orders.php'>View in admin panel</a></p>";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ ERROR: " . $e->getMessage() . "</p>";
    }
} else {
    // Show form
    ?>
    <form method="POST">
        <h3>Quick Test Form</h3>
        <p>Neck Type: <input type="text" name="neck_type" value="vneck"></p>
        <p>Sleeve Type: <input type="text" name="sleeve_type" value="normalsleeve"></p>
        <p>Fit Type: <input type="text" name="fit_type" value="bodyfit"></p>
        <p>Shirt Color: <input type="color" name="shirt_color" value="#FF0000"></p>
        <p>Chest: <input type="number" name="chest_size" value="36" step="0.5"></p>
        <p>Waist: <input type="number" name="waist_size" value="32" step="0.5"></p>
        <p>Shoulder: <input type="number" name="shoulder_size" value="16" step="0.5"></p>
        <p>Sleeve: <input type="number" name="sleeve_size" value="24" step="0.5"></p>
        <p>Length: <input type="number" name="length_size" value="28" step="0.5"></p>
        <p>Hip: <input type="number" name="hip_size" value="38" step="0.5"></p>
        <button type="submit" style="padding: 10px 20px; background: green; color: white; border: none; cursor: pointer;">Test Submit</button>
    </form>
    <?php
}

$conn->close();
?>
