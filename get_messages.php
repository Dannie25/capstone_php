<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

// Validate input
$inquiry_id = isset($_GET['inquiry_id']) ? (int)$_GET['inquiry_id'] : 0;
$last_message_id = 0;
if (isset($_GET['last_message_id'])) {
    $last_message_id = (int)$_GET['last_message_id'];
} elseif (isset($_GET['last_id'])) { // some clients may send last_id
    $last_message_id = (int)$_GET['last_id'];
}

if ($inquiry_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid inquiry_id']);
    exit;
}

// Build query
if ($last_message_id > 0) {
    $stmt = $conn->prepare("SELECT id, inquiry_id, sender_type, message, created_at FROM inquiry_messages WHERE inquiry_id = ? AND id > ? ORDER BY id ASC");
    $stmt->bind_param('ii', $inquiry_id, $last_message_id);
} else {
    // Return recent history if no last id provided
    $stmt = $conn->prepare("SELECT id, inquiry_id, sender_type, message, created_at FROM inquiry_messages WHERE inquiry_id = ? ORDER BY id ASC");
    $stmt->bind_param('i', $inquiry_id);
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Query failed']);
    exit;
}

$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    // Provide a field useful for admin UI as well
    $row['is_admin'] = ($row['sender_type'] === 'admin');
    $messages[] = $row;
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
]);

