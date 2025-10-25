<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, total_amount, status, created_at FROM orders WHERE admin_seen = 0 ORDER BY created_at DESC LIMIT ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $limit);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(['orders' => $orders]);

?>
