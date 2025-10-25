<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$result = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE admin_seen = 0");
$count = 0;
if ($result && $row = $result->fetch_assoc()) {
    $count = (int)$row['cnt'];
}

echo json_encode(['count' => $count]);

?>
