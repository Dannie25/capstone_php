<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Update the last check timestamp to current time
$_SESSION['last_notification_check'] = date('Y-m-d H:i:s');

echo json_encode(['success' => true]);
