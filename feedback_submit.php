<?php
// feedback_submit.php - receives feedback from my_orders.php and stores it in order_feedback
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$feedback_text = isset($_POST['feedback_text']) ? trim($_POST['feedback_text']) : '';
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;

if ($order_id <= 0 || $feedback_text === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order or feedback']);
    exit();
}

// Optional: check if this user owns this order
$stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Order not found or not yours']);
    exit();
}

// Insert feedback
$stmt = $conn->prepare("INSERT INTO order_feedback (order_id, user_id, feedback_text, rating) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iisi", $order_id, $user_id, $feedback_text, $rating);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Feedback submitted']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
}
