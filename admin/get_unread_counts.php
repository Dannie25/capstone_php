<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

// Only allow if admin is logged in (soft check; adjust based on your auth)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$sql = "SELECT inquiry_id, COUNT(*) AS unread_count
        FROM inquiry_messages
        WHERE sender_type = 'customer' AND is_read = 0
        GROUP BY inquiry_id";

$result = $conn->query($sql);

$counts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $counts[] = [
            'inquiry_id' => (int)$row['inquiry_id'],
            'unread_count' => (int)$row['unread_count'],
        ];
    }
}

echo json_encode([
    'success' => true,
    'counts' => $counts,
]);

