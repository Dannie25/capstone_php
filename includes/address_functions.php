<?php
function getCustomerAddresses($user_id) {
    global $conn;
    $addresses = [];
    $stmt = $conn->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC, id ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $addresses[] = $row;
    }
    return $addresses;
}

function saveCustomerAddress($user_id, $data, $is_default = false) {
    global $conn;
    
    // If this is set as default, unset any existing default
    if ($is_default) {
        $conn->query("UPDATE customer_addresses SET is_default = 0 WHERE user_id = $user_id");
    }
    
    $stmt = $conn->prepare("INSERT INTO customer_addresses 
        (user_id, first_name, last_name, email, phone, address, city, postal_code, 
        region_code, region_name, province_code, province_name, city_code, city_name, 
        barangay_code, barangay_name, is_default) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Convert boolean to integer for MySQL
    $default_value = $is_default ? 1 : 0;
    
    // Set default empty values if not provided
    $region_code = $data['region_code'] ?? '';
    $region_name = $data['region_name'] ?? '';
    $province_code = $data['province_code'] ?? '';
    $province_name = $data['province_name'] ?? '';
    $city_code = $data['city_code'] ?? '';
    $city_name = $data['city_name'] ?? $data['city'] ?? '';
    $barangay_code = $data['barangay_code'] ?? '';
    $barangay_name = $data['barangay_name'] ?? '';
    
    // Bind parameters
    $stmt->bind_param(
        "isssssssssssssssi", 
        $user_id, 
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['city'],
        $data['postal_code'],
        $region_code,
        $region_name,
        $province_code,
        $province_name,
        $city_code,
        $city_name,
        $barangay_code,
        $barangay_name,
        $default_value
    );
    
    return $stmt->execute();
}

function getDefaultAddress($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM customer_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
