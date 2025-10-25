<?php
require_once '../db.php';
header('Content-Type: application/json');

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT id, first_name, last_name, email, total_amount, status, created_at, payment_method, delivery_mode, gcash_receipt, gcash_reference_number, CONCAT(first_name, ' ', last_name) as customer_name FROM orders";
if ($status_filter) {
    $sql .= " WHERE status = ?";
}
$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if ($status_filter) {
    $stmt->bind_param('s', $status_filter);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = array();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get pending orders count
$pending_count = 0;
$pending_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
if ($pending_result && $pending_row = $pending_result->fetch_assoc()) {
    $pending_count = $pending_row['count'];
}

echo json_encode([
    'orders' => $orders,
    'stats' => [
        'pending' => $pending_count
    ]
]);
