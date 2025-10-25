<?php
session_start();
include 'db.php';
header('Content-Type: application/json');
$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

$where = $user_id ? 'user_id = ?' : 'session_id = ?';
$id = $user_id ?: $session_id;

if ($user_id) {
    $stmt = $conn->prepare("SELECT sender, message, created_at FROM chatbot_conversations WHERE user_id = ? ORDER BY id ASC");
    $stmt->bind_param('i', $user_id);
} else {
    $stmt = $conn->prepare("SELECT sender, message, created_at FROM chatbot_conversations WHERE session_id = ? ORDER BY id ASC");
    $stmt->bind_param('s', $session_id);
}
$stmt->execute();
$res = $stmt->get_result();
$history = [];
$admin_last_reply = null;
while ($row = $res->fetch_assoc()) {
    $history[] = $row;
    if ($row['sender'] === 'bot') {
        $admin_last_reply = $row['created_at'];
    }
}
$stmt->close();
echo json_encode(['success'=>true, 'history'=>$history, 'admin_last_reply'=>$admin_last_reply]);
