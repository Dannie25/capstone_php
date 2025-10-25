<?php
session_start();
header('Content-Type: application/json');

// Suppress any output from db.php
ob_start();
include '../db.php';
ob_end_clean();

// Check database connection
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['request_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$request_id = intval($_POST['request_id']);
$status = $_POST['status'];
// Optional fields from the subcontract price modal
$price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

// Validate status
$valid_statuses = ['pending', 'awaiting_confirmation', 'in_progress', 'to_deliver', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current request details
    $stmt = $conn->prepare("SELECT * FROM subcontract_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Subcontract request not found');
    }
    
    $request = $result->fetch_assoc();
    $old_status = $request['status'];
    $stmt->close();
    
    // Build dynamic update with optional price and notes
    $fields = ["status = ?", "updated_at = NOW()"];
    $types = "s"; // status (s)
    $params = [$status];
    
    if ($price !== null) {
        $fields[] = "price = ?";
        $types .= "d"; // price (d)
        $params[] = $price;
    }
    
    if ($notes !== null && $notes !== '') {
        $fields[] = "admin_notes = CONCAT(IFNULL(CONCAT(admin_notes, '\n\n'), ''), ?)";
        $types .= "s"; // notes (s)
        $timestamp_note = "[" . date('Y-m-d H:i:s') . "] " . $notes;
        $params[] = $timestamp_note;
    }
    
    $sql = "UPDATE subcontract_requests SET " . implode(", ", $fields) . " WHERE id = ?";
    $types .= "i"; // request_id (i)
    $params[] = $request_id;
    
    // Prepare and execute update query
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error . '. SQL: ' . $sql);
    }
    
    // bind_param needs references
    $bindParams = [];
    $bindParams[] = &$types;
    foreach ($params as $k => $v) {
        $bindParams[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    // If status is completed, update inventory if needed
    if ($status === 'completed') {
        // Add any inventory update logic here if needed
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Subcontract status updated successfully',
        'new_status' => $status,
        'old_status' => $old_status,
        'request_id' => $request_id,
        'affected_rows' => $affected_rows,
        'debug' => [
            'price_set' => $price !== null ? $price : 'not set',
            'notes_added' => $notes !== null && $notes !== '' ? 'yes' : 'no'
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log('Subcontract update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'request_id' => $request_id,
            'status' => $status,
            'error_line' => $e->getLine()
        ]
    ]);
} finally {
    $conn->close();
}
?>
