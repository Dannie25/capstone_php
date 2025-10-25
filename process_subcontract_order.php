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
$subcontract_id = isset($_POST['subcontract_id']) ? (int)$_POST['subcontract_id'] : 0;

if ($subcontract_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid subcontract request']);
    exit();
}

// Get subcontract details
$query = "SELECT * FROM subcontract_requests WHERE id = ? AND user_id = ? AND status = 'awaiting_confirmation'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $subcontract_id, $user_id);
$stmt->execute();
$subcontract = $stmt->get_result()->fetch_assoc();

if (!$subcontract || empty($subcontract['price'])) {
    echo json_encode(['success' => false, 'error' => 'Subcontract request not found or price not set']);
    exit();
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

// DEBUG: Log ALL POST data
error_log("SUBCONTRACT DEBUG - ALL POST: " . print_r($_POST, true));
error_log("SUBCONTRACT DEBUG - Delivery Mode: " . $delivery_mode);
error_log("SUBCONTRACT DEBUG - Region: " . $region);

error_log("SUBCONTRACT DEBUG - Checking JNT condition: delivery_mode=" . $delivery_mode . ", region=" . $region . ", empty=" . (empty($region) ? 'YES' : 'NO'));

if ($delivery_mode === 'jnt' && !empty($region)) {
    error_log("SUBCONTRACT DEBUG - Inside JNT block");
    $luzon = ["NCR", "Ilocos Region", "Cagayan Valley", "Central Luzon", "CALABARZON", "MIMAROPA Region", "CAR", "Bicol Region"];
    $visayas = ["Western Visayas", "Central Visayas", "Eastern Visayas"];
    $mindanao = ["Zamboanga Peninsula", "Northern Mindanao", "Davao Region", "SOCCSKSARGEN", "Caraga", "BARMM"];
    
    error_log("SUBCONTRACT DEBUG - Region value: '" . $region . "' (length: " . strlen($region) . ")");
    error_log("SUBCONTRACT DEBUG - in_array check: " . (in_array($region, $luzon) ? 'TRUE' : 'FALSE'));
    
    if (in_array($region, $luzon)) {
        $shipping_fee = 100;
        error_log("SUBCONTRACT DEBUG - Luzon region, shipping: 100");
    } elseif (in_array($region, $visayas)) {
        $shipping_fee = 130;
        error_log("SUBCONTRACT DEBUG - Visayas region, shipping: 130");
    } elseif (in_array($region, $mindanao)) {
        $shipping_fee = 150;
        error_log("SUBCONTRACT DEBUG - Mindanao region, shipping: 150");
    } else {
        error_log("SUBCONTRACT DEBUG - Region not matched: '" . $region . "'");
    }
} elseif ($delivery_mode === 'lalamove') {
    $shipping_fee = 0; // Tentative - no fee yet
    error_log("SUBCONTRACT DEBUG - Lalamove, shipping: 0");
} elseif ($delivery_mode === 'pickup') {
    $shipping_fee = 0; // Pick up has no shipping fee
    error_log("SUBCONTRACT DEBUG - Pickup, shipping: 0");
} else {
    error_log("SUBCONTRACT DEBUG - NOT entering any delivery mode block. delivery_mode='" . $delivery_mode . "'");
}

error_log("SUBCONTRACT DEBUG - Final shipping_fee: " . $shipping_fee);
error_log("SUBCONTRACT DEBUG - Subcontract price: " . $subcontract['price']);

$total_amount = $subcontract['price'] + $shipping_fee;

error_log("SUBCONTRACT DEBUG - Total amount: " . $total_amount);

// Build delivery address
$delivery_address = trim($address);
if (!empty($barangay)) $delivery_address .= ", " . $barangay;
if (!empty($municipality)) $delivery_address .= ", " . $municipality;
if (!empty($city)) $delivery_address .= ", " . $city;
if (!empty($region)) $delivery_address .= ", " . $region;
if (!empty($postal_code)) $delivery_address .= " " . $postal_code;

// Start transaction
$conn->begin_transaction();

try {
    // Add columns if they don't exist
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'payment_method'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD payment_method VARCHAR(50) DEFAULT NULL");
    }
    
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'delivery_mode'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD delivery_mode VARCHAR(50) DEFAULT NULL");
    }
    
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'delivery_address'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD delivery_address TEXT DEFAULT NULL");
    }
    
    $result = $conn->query("SHOW COLUMNS FROM subcontract_requests LIKE 'email'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE subcontract_requests ADD email VARCHAR(255) DEFAULT NULL");
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
        
        $_SESSION['pending_subcontract_gcash'] = [
            'request_id' => $subcontract_id,
            'user_id' => $user_id,
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
            'price' => $subcontract['price'],
            'delivery_address' => $delivery_address
        ];
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'redirect' => 'gcash.php',
            'message' => 'Redirecting to GCash payment'
        ]);
        exit();
    }
    
    // For COD, update subcontract request with payment and delivery info
    $update_query = "UPDATE subcontract_requests 
                     SET status = 'in_progress', 
                         payment_method = ?, 
                         delivery_mode = ?, 
                         delivery_address = ?,
                         email = ?,
                         accepted_at = NOW(),
                         updated_at = NOW() 
                     WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $payment_method, $delivery_mode, $delivery_address, $email, $subcontract_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update subcontract request");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Insert notification for user: order confirmed / in progress
    try {
        $notifMsg = "Your subcontract request #" . str_pad($subcontract_id, 6, '0', STR_PAD_LEFT) . " has been confirmed and is now in progress.";
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subcontract', ?)");
        if ($notifStmt) {
            $notifStmt->bind_param('is', $user_id, $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();
        }
    } catch (Exception $nex) {
        error_log('Notification insert error: ' . $nex->getMessage());
    }

    // Return success response
    echo json_encode([
        'success' => true,
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
