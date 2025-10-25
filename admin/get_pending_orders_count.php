<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'unauthorized', 'count' => 0]);
    exit;
}

// Get count of pending orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$result = $conn->query($sql);

$count = 0;
if ($result && $row = $result->fetch_assoc()) {
    $count = (int)$row['count'];
}

echo json_encode(['count' => $count]);
?>
