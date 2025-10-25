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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = $_POST['address_id'] ?? null;
    
    if (!$address_id) {
        echo json_encode(['success' => false, 'message' => 'Address ID required']);
        exit();
    }
    
    try {
        // Verify address belongs to user
        $stmt = $conn->prepare("SELECT id FROM customer_addresses WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Address not found or access denied']);
            exit();
        }
        $stmt->close();
        
        // Set all addresses as non-default
        $conn->query("UPDATE customer_addresses SET is_default = 0 WHERE user_id = $user_id");
        
        // Set selected address as default
        $stmt = $conn->prepare("UPDATE customer_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Default address updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update default address']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
