<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if required fields are set
if (!isset($_POST['request_id']) || !isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$requestId = intval($_POST['request_id']);
$action = $_POST['action']; // 'accept' or 'decline'
$userId = intval($_SESSION['user_id']);

// Validate action
if (!in_array($action, ['accept', 'decline'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Verify that the request belongs to the user and is in 'awaiting_confirmation' status
    $checkQuery = "SELECT id, price, status FROM subcontract_requests 
                   WHERE id = ? AND user_id = ? AND status = 'awaiting_confirmation'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $requestId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Request not found or already processed');
    }
    
    $request = $result->fetch_assoc();
    $checkStmt->close();
    
    // Update status based on action
    // Note: 'accept' is now handled by checkout process (process_subcontract_order.php)
    // This file only handles 'decline' action
    if ($action === 'accept') {
        throw new Exception('Accept action is now handled by checkout process');
    } else {
        $newStatus = 'cancelled';
        $message = 'You have declined the price quote. The request has been cancelled.';
    }
    
    $updateQuery = "UPDATE subcontract_requests 
                    SET status = ?, updated_at = NOW() 
                    WHERE id = ? AND user_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sii", $newStatus, $requestId, $userId);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update request status');
    }
    
    $updateStmt->close();
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'status' => $newStatus
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
