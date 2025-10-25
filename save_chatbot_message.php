<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$sender = $_POST['sender'] ?? '';
$message = $_POST['message'] ?? '';
$session_id = session_id();

if (!$sender || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing sender or message']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO chatbot_conversations (user_id, sender, message, session_id) VALUES (?, ?, ?, ?)');
$stmt->bind_param('isss', $user_id, $sender, $message, $session_id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
