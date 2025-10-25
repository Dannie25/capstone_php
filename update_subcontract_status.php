<?php
session_start();
header('Content-Type: application/json');

// Suppress any output from db.php
ob_start();
include 'db.php';
ob_end_clean();

// Check database connection
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Handle GCash session storage
if (isset($_POST['action']) && $_POST['action'] === 'store_subcontract_gcash') {
    $_SESSION['pending_subcontract_gcash'] = [
        'request_id' => intval($_POST['request_id']),
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address']),
        'region' => trim($_POST['region']),
        'province' => trim($_POST['province']),
        'city' => trim($_POST['city']),
        'barangay' => trim($_POST['barangay']),
        'postal_code' => trim($_POST['postal_code']),
        'payment_method' => 'gcash',
        'delivery_mode' => trim($_POST['delivery_mode']),
        'price' => floatval($_POST['price'])
    ];
    echo json_encode(['success' => true, 'message' => 'Session stored']);
    exit();
}

// Debug: Log all POST data
error_log("Subcontract update POST data: " . json_encode($_POST));

// Check if required parameters are provided
if (!isset($_POST['request_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters. Got: ' . json_encode(array_keys($_POST))]);
    exit();
}

$request_id = intval($_POST['request_id']);
$status = $_POST['status'];
$rejection_reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
$payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : null;
$delivery_mode = isset($_POST['delivery_mode']) ? trim($_POST['delivery_mode']) : null;
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
$address = isset($_POST['address']) ? trim($_POST['address']) : null;
$user_id = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Get the current request to validate ownership and current status
    $stmt = $conn->prepare("SELECT * FROM subcontract_requests WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Subcontract request not found or access denied');
    }
    
    $request = $result->fetch_assoc();
    
    // Validate status transition
    $valid_transitions = [
        'in_progress' => ['accepted', 'rejected'],
        'awaiting_confirmation' => ['accepted', 'rejected'],
        'pending' => ['cancelled']
    ];
    
    // Log for debugging
    error_log("Subcontract status transition check: current=" . $request['status'] . ", new=" . $status);
    error_log("Valid transitions for " . $request['status'] . ": " . json_encode($valid_transitions[$request['status']] ?? []));
    
    if (!isset($valid_transitions[$request['status']]) || 
        !in_array($status, $valid_transitions[$request['status']])) {
        $error_msg = 'Invalid status transition from ' . $request['status'] . ' to ' . $status . '. ';
        $error_msg .= 'Valid transitions: ' . json_encode($valid_transitions[$request['status']] ?? 'none');
        throw new Exception($error_msg);
    }
    
    // Prepare the update query
    $update_sql = "UPDATE subcontract_requests SET ";
    
    $params = [];
    $types = "";
    
    // Add timestamp and reason based on status
    if ($status === 'accepted') {
        // Customer accepts the price
        // Check payment method to determine next action
        
        if ($payment_method === 'gcash') {
            // For GCash: Store session data and redirect to gcash.php
            // Don't update status yet, will be updated after payment
            // Get total amount (subtotal + shipping)
            $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : $request['price'];
            $shipping_fee = isset($_POST['shipping_fee']) ? floatval($_POST['shipping_fee']) : 0;
            $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : ($subtotal + $shipping_fee);
            
            $_SESSION['pending_subcontract_gcash'] = [
                'request_id' => $request_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone ?? '',
                'address' => $address,
                'region' => $_POST['region'] ?? '',
                'province' => $_POST['province'] ?? '',
                'city' => $_POST['city'] ?? '',
                'barangay' => $_POST['barangay'] ?? '',
                'postal_code' => $_POST['postal_code'] ?? '',
                'payment_method' => 'gcash',
                'delivery_mode' => $delivery_mode,
                'price' => $subtotal,
                'shipping_fee' => $shipping_fee,
                'total_amount' => $total_amount
            ];
            
            // Commit transaction and return redirect
            $conn->commit();
            echo json_encode([
                'success' => true,
                'redirect' => 'gcash.php?amount=' . $total_amount . '&type=subcontract&id=' . $request_id
            ]);
            exit();
        } elseif ($payment_method === 'cod') {
            // For COD: Update to in_progress immediately
            $update_sql .= "accepted_at = NOW(), rejected_at = NULL, rejection_reason = NULL, status = 'in_progress'";
        } else {
            // Default: just mark as accepted
            $update_sql .= "accepted_at = NOW(), rejected_at = NULL, rejection_reason = NULL";
        }
        
        // Add payment method and delivery mode if provided
        if ($payment_method) {
            $update_sql .= ", payment_method = ?";
            $params[] = $payment_method;
            $types .= "s";
        }
        if ($delivery_mode) {
            $update_sql .= ", final_delivery_mode = ?";
            $params[] = $delivery_mode;
            $types .= "s";
        }
        
        // Update billing info (overwrite existing customer info)
        if ($first_name && $last_name) {
            $full_name = $first_name . ' ' . $last_name;
            $update_sql .= ", customer_name = ?";
            $params[] = $full_name;
            $types .= "s";
        }
        if ($email) {
            $update_sql .= ", email = ?";
            $params[] = $email;
            $types .= "s";
        }
        if ($address) {
            $update_sql .= ", address = ?";
            $params[] = $address;
            $types .= "s";
        }
        // Note: phone is not in subcontract_requests table, so we skip it
        
        $update_sql .= ", ";
    } elseif ($status === 'rejected') {
        $update_sql .= "rejected_at = NOW(), accepted_at = NULL, rejection_reason = ?, ";
        $params[] = $rejection_reason;
        $types .= "s";
    } elseif ($status === 'cancelled') {
        $update_sql .= "status = 'cancelled', cancelled_at = NOW(), ";
    }
    
    $update_sql .= "updated_at = NOW() WHERE id = ?";
    $params[] = $request_id;
    $types .= "i";
    
    // Execute the update
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update subcontract request: ' . $stmt->error);
    }
    
    // Log the action (optional - only if table exists)
    try {
        $log_sql = "INSERT INTO activity_logs 
                   (user_id, action, reference_id, reference_type, details) 
                   VALUES (?, ?, ?, 'subcontract', ?)";
        
        $action = 'subcontract_' . $status;
        $log_details = "Subcontract request #$request_id status changed to: $status";
        if ($status === 'rejected' && $rejection_reason) {
            $log_details .= ". Reason: " . substr($rejection_reason, 0, 200);
        }
        
        $log_stmt = $conn->prepare($log_sql);
        if ($log_stmt) {
            $log_stmt->bind_param("isis", $user_id, $action, $request_id, $log_details);
            $log_stmt->execute();
            $log_stmt->close();
        }
    } catch (Exception $log_error) {
        // Ignore logging errors - not critical
        error_log('Activity log error: ' . $log_error->getMessage());
    }
    
    // Commit transaction
    $conn->commit();
    
    // If accepted, notify admin (you can implement this function)
    if ($status === 'accepted') {
        // notifyAdminAboutAcceptance($request_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Subcontract request updated successfully',
        'status' => $status
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log('Error updating subcontract status: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
