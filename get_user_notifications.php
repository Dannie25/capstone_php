<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['notifications' => []]);
    exit;
}
$user_id = $_SESSION['user_id'];

// Get the latest 10 notifications (unread first, then recent)
$stmt = $conn->prepare("SELECT id, type, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT 10");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$notifs = [];
while ($row = $res->fetch_assoc()) {
    $notifs[] = $row;
}
echo json_encode(['notifications' => $notifs]);
