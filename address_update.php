<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Require login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = $_POST['address_id'] ?? null;
    
    if (!$address_id) {
        echo json_encode(['success' => false, 'message' => 'Address ID required']);
        exit();
    }
    
    // Verify address belongs to user
    $stmt = $conn->prepare("SELECT id FROM customer_addresses WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Address not found or access denied']);
        exit();
    }
    $stmt->close();
    
    // Get form data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $region_code = $_POST['region_code'] ?? '';
    $region_name = $_POST['region_name'] ?? '';
    $province_code = $_POST['province_code'] ?? '';
    $province_name = $_POST['province_name'] ?? '';
    $city_code = $_POST['city_code'] ?? '';
    $city_name = $_POST['city_name'] ?? '';
    $barangay_code = $_POST['barangay_code'] ?? '';
    $barangay_name = $_POST['barangay_name'] ?? '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }
    
    try {
        // Update address
        $stmt = $conn->prepare("
            UPDATE customer_addresses 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
                city = ?, postal_code = ?, region_code = ?, region_name = ?, 
                province_code = ?, province_name = ?, city_code = ?, city_name = ?, 
                barangay_code = ?, barangay_name = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $city = $city_name ?: $barangay_name;
        
        $stmt->bind_param(
            "sssssssssssssssii",
            $first_name,
            $last_name,
            $email,
            $phone,
            $address,
            $city,
            $postal_code,
            $region_code,
            $region_name,
            $province_code,
            $province_name,
            $city_code,
            $city_name,
            $barangay_code,
            $barangay_name,
            $address_id,
            $user_id
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Address updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update address']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
