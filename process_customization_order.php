<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$customization_id = isset($_POST['customization_id']) ? (int)$_POST['customization_id'] : 0;

if ($customization_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid customization request']);
    exit();
}

// Get customization details
// Allow processing when admin has set a quoted price and status is 'approved' or 'awaiting_confirmation'
$query = "SELECT *, quoted_price as price FROM customization_requests WHERE id = ? AND user_id = ? AND status IN ('approved', 'awaiting_confirmation')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $customization_id, $user_id);
$stmt->execute();
$customization = $stmt->get_result()->fetch_assoc();

if (!$customization) {
    echo json_encode(['success' => false, 'error' => 'Customization request not found or not ready for ordering']);
    exit();
}

if (empty($customization['price']) && (isset($customization['quoted_price']) ? floatval($customization['quoted_price']) : 0) <= 0) {
    echo json_encode(['success' => false, 'error' => 'Customization request found but price not set']);
    exit();
}

// Normalize price field
if (empty($customization['price']) && isset($customization['quoted_price'])) {
    $customization['price'] = floatval($customization['quoted_price']);
}

// Get form data
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$barangay = $_POST['barangay'] ?? '';
$municipality = $_POST['municipality'] ?? '';
$region = $_POST['region'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$delivery_mode = $_POST['delivery_mode'] ?? 'pickup';
$payment_method = $_POST['payment_method'] ?? 'cod';

// Validate required fields
if (empty($first_name) || empty($phone) || empty($address) || empty($city)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields']);
    exit();
}

// Calculate shipping fee
$shipping_fee = 0;
if ($delivery_mode === 'jnt' && !empty($region)) {
    $luzon = ["NCR", "Ilocos Region", "Cagayan Valley", "Central Luzon", "CALABARZON", "MIMAROPA Region", "CAR", "Bicol Region"];
    $visayas = ["Western Visayas", "Central Visayas", "Eastern Visayas"];
    $mindanao = ["Zamboanga Peninsula", "Northern Mindanao", "Davao Region", "SOCCSKSARGEN", "Caraga", "BARMM"];
    
    if (in_array($region, $luzon)) {
        $shipping_fee = 100;
    } elseif (in_array($region, $visayas)) {
        $shipping_fee = 130;
    } elseif (in_array($region, $mindanao)) {
        $shipping_fee = 150;
    }
} elseif ($delivery_mode === 'lalamove') {
    $shipping_fee = 0; // Tentative - no fee yet
}

$total_amount = $customization['price'] + $shipping_fee;

// Start transaction
$conn->begin_transaction();

try {
    // Add columns if they don't exist (MySQL doesn't support IF NOT EXISTS in ALTER TABLE ADD COLUMN)
    // Check and add payment_method column
    $result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'payment_method'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE customization_requests ADD payment_method VARCHAR(50) DEFAULT NULL");
    }
    
    // Check and add delivery_mode column
    $result = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'delivery_mode'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE customization_requests ADD delivery_mode VARCHAR(50) DEFAULT NULL");
    }
    
    // If GCash payment, store in session and redirect to gcash.php
    if ($payment_method === 'gcash') {
        // Initialize attempt counter if not exists
        if (!isset($_SESSION['gcash_payment_attempts'])) {
            $_SESSION['gcash_payment_attempts'] = 0;
        }
        
        // Only increment if there was a previous failed attempt
        if (isset($_SESSION['gcash_payment_start_time'])) {
            $_SESSION['gcash_payment_attempts']++;
        }
        
        // Reset timer for this new attempt
        unset($_SESSION['gcash_payment_start_time']);
        
        $_SESSION['pending_gcash_customization_order'] = [
            'user_id' => $user_id,
            'customization_id' => $customization_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'barangay' => $barangay,
            'region' => $region,
            'municipality' => $municipality,
            'postal_code' => $postal_code,
            'delivery_mode' => $delivery_mode,
            'payment_method' => $payment_method,
            'shipping_fee' => $shipping_fee,
            'total_amount' => $total_amount,
            'customization_price' => $customization['price']
        ];
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'redirect' => 'gcash.php',
            'message' => 'Redirecting to GCash payment'
        ]);
        exit();
    }
    
    // For COD, only update customization status to verifying
    // No order creation - customization orders stay in customization_requests table only
    
    // Update customization request with payment and delivery info
    $update_query = "UPDATE customization_requests 
                     SET status = 'verifying', 
                         payment_method = ?, 
                         delivery_mode = ?, 
                         updated_at = NOW() 
                     WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $payment_method, $delivery_mode, $customization_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update customization request");
    }
    
    // Commit transaction
    $conn->commit();

    // Return success response. Note: customization flow does not create a separate orders table entry
    echo json_encode([
        'success' => true,
        'customization_id' => $customization_id,
        'message' => 'Order placed successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process order: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
