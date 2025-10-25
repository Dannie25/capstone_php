<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if required fields are set
if (!isset($_POST['id']) || !isset($_POST['price'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$requestId = intval($_POST['id']);
$price = floatval($_POST['price']);
$notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';

// Start transaction
$conn->begin_transaction();

try {
    // Update the subcontract request with price and notes
    // Status changes to 'approved' meaning admin set price, waiting for customer confirmation
    $query = "UPDATE subcontract_requests 
              SET quoted_price = ?, admin_notes = ?, status = 'approved', price_set_at = NOW(), updated_at = NOW() 
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("dsi", $price, $notes, $requestId);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update subcontract request: ' . $stmt->error);
    }

    // Log the price update (optional)
    $adminUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'admin';
    $logDetails = "Price set to ₱" . number_format($price, 2) . 
                 (empty($notes) ? '' : ". Notes: " . $notes) . 
                 " by " . $adminUsername;
    
    // Note: Logging is optional - can be implemented if needed in the future
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Price updated successfully',
        'price' => $price,
        'status' => 'approved'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}

$stmt->close();
$conn->close();
?>
