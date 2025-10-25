<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];
$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($requestId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
    exit();
}

if (empty($reason)) {
    echo json_encode(['success' => false, 'error' => 'Cancellation reason is required']);
    exit();
}

// Verify the request belongs to the user and is pending
$checkQuery = "SELECT id, status FROM subcontract_requests WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ii", $requestId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
    exit();
}

$request = $result->fetch_assoc();

if ($request['status'] !== 'pending') {
    echo json_encode(['success' => false, 'error' => 'Only pending requests can be cancelled']);
    exit();
}

// Update status to cancelled with reason and timestamp
$updateQuery = "UPDATE subcontract_requests SET status = 'cancelled', cancel_reason = ?, cancelled_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("si", $reason, $requestId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
