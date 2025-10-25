<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Get the last check timestamp from session or use a default
// If no last check, show notifications from last 24 hours
if (!isset($_SESSION['last_notification_check'])) {
    $lastCheck = date('Y-m-d H:i:s', strtotime('-24 hours'));
} else {
    $lastCheck = $_SESSION['last_notification_check'];
}

// Get new orders (pending status, created after last check)
$newOrders = [];
$query = "SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, total_amount, created_at 
          FROM orders 
          WHERE status = 'pending' AND created_at > ? 
          ORDER BY created_at DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lastCheck);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $newOrders[] = [
        'id' => $row['id'],
        'type' => 'new_order',
        'message' => 'New order from ' . $row['customer_name'],
        'amount' => number_format($row['total_amount'], 2),
        'time' => $row['created_at']
    ];
}

// Get cancelled orders (cancelled after last check)
$cancelledOrders = [];
$query = "SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, total_amount, 
          COALESCE(cancelled_at, updated_at) as cancel_time 
          FROM orders 
          WHERE status = 'cancelled' AND COALESCE(cancelled_at, updated_at) > ? 
          ORDER BY COALESCE(cancelled_at, updated_at) DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $lastCheck);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cancelledOrders[] = [
        'id' => $row['id'],
        'type' => 'cancelled_order',
        'message' => 'Order #' . $row['id'] . ' cancelled by ' . $row['customer_name'],
        'amount' => number_format($row['total_amount'], 2),
        'time' => $row['cancel_time']
    ];
}

// Combine and sort by time
$notifications = array_merge($newOrders, $cancelledOrders);
usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Don't update last check timestamp here - only update when user clears notifications
// This way, notifications persist until explicitly cleared

echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications,
    'last_check' => $lastCheck
]);
