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
    // Check if user already has 3 addresses
    include_once 'includes/address_functions.php';
    $existing_addresses = getCustomerAddresses($user_id);
    
    if (count($existing_addresses) >= 3) {
        echo json_encode(['success' => false, 'message' => 'Maximum of 3 addresses allowed']);
        exit();
    }
    
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
        // Prepare address data
        $address_data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city_name ?: $barangay_name,
            'postal_code' => $postal_code,
            'region_code' => $region_code,
            'region_name' => $region_name,
            'province_code' => $province_code,
            'province_name' => $province_name,
            'city_code' => $city_code,
            'city_name' => $city_name,
            'barangay_code' => $barangay_code,
            'barangay_name' => $barangay_name
        ];
        
        // If this is the first address, make it default
        $is_default = empty($existing_addresses);
        
        // Save address
        if (saveCustomerAddress($user_id, $address_data, $is_default)) {
            echo json_encode(['success' => true, 'message' => 'Address added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save address']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
