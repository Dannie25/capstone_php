<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
    exit();
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'error' => 'Cancellation reason is required']);
    exit();
}

try {
    // Verify the request belongs to the user and is cancellable
    $stmt = $conn->prepare("SELECT status FROM customization_requests WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Check if status is cancellable (only submitted)
        if ($row['status'] !== 'submitted') {
            echo json_encode(['success' => false, 'error' => 'Only submitted requests can be cancelled']);
            exit();
        }
        
        // Update status to cancelled with reason and timestamp
        $updateStmt = $conn->prepare("UPDATE customization_requests SET status = 'cancelled', cancel_reason = ?, cancelled_at = NOW() WHERE id = ? AND user_id = ?");
        $updateStmt->bind_param("sii", $reason, $request_id, $user_id);
        
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        
        $updateStmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Request not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>
