<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Require login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit();
}

$address_id = $_GET['id'] ?? null;
if (!$address_id) {
    echo json_encode(['success' => false, 'message' => 'Address ID required']);
    exit();
}

try {
    // Get address and verify it belongs to the user
    $stmt = $conn->prepare("SELECT * FROM customer_addresses WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $address_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'address' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Address not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
