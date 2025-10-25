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
        // Check if address is default
        $stmt = $conn->prepare("SELECT is_default FROM customer_addresses WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $address_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Address not found or access denied']);
            exit();
        }
        
        $row = $result->fetch_assoc();
        if ($row['is_default']) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete default address. Set another address as default first.']);
            exit();
        }
        $stmt->close();
        
        // Delete address
        $stmt = $conn->prepare("DELETE FROM customer_addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete address']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
