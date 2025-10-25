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
    // Update the customization request with price and notes
    // Status changes to 'approved' meaning admin set price, waiting for customer confirmation
    $query = "UPDATE customization_requests 
              SET quoted_price = ?, admin_notes = ?, status = 'approved', price_set_at = NOW(), updated_at = NOW() 
              WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("dsi", $price, $notes, $requestId);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update customization request: ' . $stmt->error);
    }

    // Log the price update (optional)
    $adminUsername = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'admin';
    $logDetails = "Price set to ₱" . number_format($price, 2) . 
                 (empty($notes) ? '' : ". Notes: " . $notes) . 
                 " by " . $adminUsername;
    
    // Note: Logging is optional - can be implemented if needed in the future
    
    // Commit transaction
    $conn->commit();
    // Insert notification for the user about price being set
    try {
        // Get user_id for this customization request
        $uStmt = $conn->prepare("SELECT user_id FROM customization_requests WHERE id = ?");
        if ($uStmt) {
            $uStmt->bind_param('i', $requestId);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRes && $uRow = $uRes->fetch_assoc()) {
                $targetUserId = $uRow['user_id'];
                $notifMsg = "Your customization request #" . str_pad($requestId, 6, '0', STR_PAD_LEFT) . " has been priced: ₱" . number_format($price, 2) . ". Please review and confirm.";
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'customization', ?)");
                if ($notifStmt) {
                    $notifStmt->bind_param('is', $targetUserId, $notifMsg);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            }
            $uStmt->close();
        }
    } catch (Exception $ex) {
        error_log('Notification insert error: ' . $ex->getMessage());
    }
    
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
