<?php
session_start();
include 'db.php';

// Require login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');

    // Get location data
    $region_code = $_POST['region_code'] ?? '';
    $region_name = $_POST['region_name'] ?? '';
    $province_code = $_POST['province_code'] ?? '';
    $province_name = $_POST['province_name'] ?? '';
    $city_code = $_POST['city_code'] ?? '';
    $city_name = $_POST['city_name'] ?? '';
    $barangay_code = $_POST['barangay_code'] ?? '';
    $barangay_name = $_POST['barangay_name'] ?? '';

    // Debug: Log the barangay data
    error_log("DEBUG: Barangay data from POST - Code: '$barangay_code', Name: '$barangay_name'");

    // Combine first and last name for backward compatibility
    $name = trim($first_name . ' ' . $last_name);

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($address)) {
        $error = "Please fill in all required fields (First Name, Last Name, Email, Address).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check if users table exists and update it
            $users_table_exists = false;
            try {
                $result = $conn->query("SHOW TABLES LIKE 'users'");
                $users_table_exists = $result->num_rows > 0;
            } catch (Exception $e) {
                // Table doesn't exist, will use customer_addresses only
            }

            if ($users_table_exists) {
                // Update users table (name, email only - no phone since it doesn't exist in users table)
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $email, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // Update session variables
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;

            // Save address to customer_addresses table
            $address_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city_name ?: $barangay_name, // Use city name as fallback
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

            // Debug: Log the address data being saved
            error_log("DEBUG: Address data being saved - Barangay Code: '$barangay_code', Barangay Name: '$barangay_name'");

            // Check if user has existing address
            include_once 'includes/address_functions.php';
            $existing_addresses = getCustomerAddresses($user_id);

            if (!empty($existing_addresses)) {
                // Update existing address as default
                $existing_id = $existing_addresses[0]['id'];
                $stmt = $conn->prepare("
                    UPDATE customer_addresses
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?,
                        region_code = ?, region_name = ?, province_code = ?, province_name = ?,
                        city_code = ?, city_name = ?, barangay_code = ?, barangay_name = ?,
                        is_default = 1
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "sssssssssssssssi",
                    $address_data['first_name'],
                    $address_data['last_name'],
                    $address_data['email'],
                    $address_data['phone'],
                    $address_data['address'],
                    $address_data['city'],
                    $address_data['postal_code'],
                    $address_data['region_code'],
                    $address_data['region_name'],
                    $address_data['province_code'],
                    $address_data['province_name'],
                    $address_data['city_code'],
                    $address_data['city_name'],
                    $address_data['barangay_code'],
                    $address_data['barangay_name'],
                    $existing_id
                );
                $stmt->execute();
                $stmt->close();

                // Set all other addresses as non-default
                $conn->query("UPDATE customer_addresses SET is_default = 0 WHERE user_id = $user_id AND id != $existing_id");
            } else {
                // Create new address as default
                $address_data['is_default'] = true;
                saveCustomerAddress($user_id, $address_data, true);
            }

            // Success message and redirect
            $_SESSION['success_message'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();

        } catch (Exception $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// If there's an error, redirect back with error
if (isset($error)) {
    $_SESSION['error_message'] = $error;
    header("Location: profile.php");
    exit();
}
?>
