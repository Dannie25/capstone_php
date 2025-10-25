<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];
$notif_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($notif_id < 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification id']);
    exit;
}
$stmt = $conn->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $notif_id, $user_id);
$stmt->execute();
$stmt->close();
echo json_encode(['success' => true]);
