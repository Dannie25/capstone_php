<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get and validate input
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
$other_reason = isset($_POST['other_reason']) ? trim($_POST['other_reason']) : '';

// If reason is 'Others', use the custom reason
if ($cancel_reason === 'Others') {
    $cancel_reason = !empty($other_reason) ? 'Other: ' . $other_reason : 'No reason provided';
}

// Validate input
if ($order_id <= 0) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
    exit();
}

if (empty($cancel_reason)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide a cancellation reason']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // 1. Verify the order exists and belongs to the user
    $stmt = $conn->prepare("SELECT id, status, user_id FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Order not found or you do not have permission to cancel this order');
    }
    
    $order = $result->fetch_assoc();
    
    // 2. Check if the order is already cancelled
    if ($order['status'] === 'cancelled') {
        throw new Exception('This order has already been cancelled.');
    }
    
    // 3. Check if the order can be cancelled (only pending or processing orders can be cancelled)
    if (!in_array($order['status'], ['pending', 'processing'])) {
        throw new Exception('This order cannot be cancelled because it is already ' . $order['status']);
    }
    
    // 4. First, try with cancel_reason column
    $update_success = false;
    $update_stmt = null;
    
    // Try with cancel_reason column first
    try {
        $update_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', cancel_reason = ? WHERE id = ?");
        $update_stmt->bind_param("si", $cancel_reason, $order_id);
        $update_success = $update_stmt->execute();
    } catch (Exception $e) {
        // If that fails, try without the cancel_reason column
        error_log('Failed to update with cancel_reason, trying without: ' . $e->getMessage());
        $update_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $update_stmt->bind_param("i", $order_id);
        $update_success = $update_stmt->execute();
    }
    
    if (!$update_success) {
        throw new Exception('Failed to update order status: ' . $conn->error);
    }
    
    // 5. Try to add to order history (if table exists)
    try {
        $history_check = $conn->query("SHOW TABLES LIKE 'order_history'");
        if ($history_check->num_rows > 0) {
            $history_sql = "INSERT INTO order_history (order_id, status, notes, created_at) VALUES (?, 'cancelled', ?, NOW())";
            $history_notes = "Order cancelled by customer. Reason: " . $cancel_reason;
            $history_stmt = $conn->prepare($history_sql);
            $history_stmt->bind_param("is", $order_id, $history_notes);
            $history_stmt->execute();
        }
    } catch (Exception $e) {
        // Log the error but don't fail the operation
        error_log('Could not update order history: ' . $e->getMessage());
    }
    
    // 6. Restore product quantities (if needed)
    try {
        $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $items_stmt = $conn->prepare($items_sql);
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        while ($item = $items_result->fetch_assoc()) {
            $update_qty = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $update_qty->bind_param("ii", $item['quantity'], $item['product_id']);
            $update_qty->execute();
            $update_qty->close();
        }
    } catch (Exception $e) {
        // Log the error but don't fail the operation
        error_log('Could not update product quantities: ' . $e->getMessage());
    }
    
    // Commit transaction
    $conn->commit();
    
    // Send success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Your order has been cancelled successfully.'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Log the error
    error_log('Order cancellation failed: ' . $e->getMessage());
    
    // Send error response
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Close database connection
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>
