<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = $_GET['id'] ?? null;

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Request ID required']);
    exit();
}

try {
    // Get customization request details (verify it belongs to the user)
    $stmt = $conn->prepare("SELECT * FROM customization_requests WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'customization' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
