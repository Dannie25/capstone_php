<?php
// admin/cancel_order.php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $cancel_reason = isset($_POST['cancel_reason']) ? trim($_POST['cancel_reason']) : '';
    $cancelled_at = date('Y-m-d H:i:s');

    if ($order_id > 0 && $cancel_reason !== '') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', cancel_reason = ?, cancelled_at = ? WHERE id = ?");
        $stmt->bind_param('ssi', $cancel_reason, $cancelled_at, $order_id);
        if ($stmt->execute()) {
            // Notify the user
            $uid_stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
            $uid_stmt->bind_param('i', $order_id);
            $uid_stmt->execute();
            $uid_stmt->bind_result($user_id);
            $uid_stmt->fetch();
            $uid_stmt->close();
            if ($user_id) {
                $notif_msg = "Your order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " has been cancelled. Reason: " . $cancel_reason;
                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'order_status', ?)");
                $notif_stmt->bind_param('is', $user_id, $notif_msg);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
            $_SESSION['success_message'] = 'Order cancelled successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to cancel order.';
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = 'Order ID and reason are required.';
    }
    
    // Redirect back to orders page (filtered to cancelled)
    header('Location: orders.php?status=cancelled');
    exit();
} else {
    header('Location: orders.php');
    exit();
}
