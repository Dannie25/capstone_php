<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'unauthorized', 'orders' => []]);
    exit;
}

// Get limit from query parameter, default to 10
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit < 1) $limit = 10;
if ($limit > 50) $limit = 50; // Max 50 orders

// Fetch pending orders sorted by created_at DESC (newest first)
$sql = "SELECT 
            id, 
            CONCAT(first_name, ' ', last_name) as customer_name, 
            email,
            total_amount, 
            status, 
            created_at,
            payment_method,
            delivery_mode
        FROM orders 
        WHERE status = 'pending' 
        ORDER BY created_at DESC 
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $limit);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(['orders' => $orders]);
?>
