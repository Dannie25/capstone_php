<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate order ID
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate status
    $allowed_statuses = ['pending', 'shipped', 'completed'];
    if (!in_array($status, $allowed_statuses)) {
        $_SESSION['error'] = 'Invalid status';
        header("Location: orders.php");
        exit();
    }
    
    // Update the order status in the database
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        // Get user_id for this order
        $uid_stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
        $uid_stmt->bind_param("i", $order_id);
        $uid_stmt->execute();
        $uid_stmt->bind_result($user_id);
        $uid_stmt->fetch();
        $uid_stmt->close();
        if ($user_id) {
            // Insert notification
            $notif_msg = "Your order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " status is now: " . ucfirst($status);
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'order_status', ?)");
            $notif_stmt->bind_param("is", $user_id, $notif_msg);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        $_SESSION['success'] = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " has been marked as " . ucfirst($status);
    } else {
        $_SESSION['error'] = 'Failed to update order status: ' . $conn->error;
    }
    
    $stmt->close();
}

// Redirect back to the orders page
header("Location: orders.php");
exit();
?>
