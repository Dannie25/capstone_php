<?php
session_start();
include '../db.php';

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

// Validate status
$valid_statuses = ['submitted', 'pending', 'in_review', 'approved', 'rejected', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status. Must be one of: ' . implode(', ', $valid_statuses)]);
    exit();
}

// Update the customization request status
$sql = "UPDATE customization_requests SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $request_id);

if ($stmt->execute()) {
    // Try to insert a notification for the user about the customization status change
    try {
        // Fetch the request to get user_id
        $q = $conn->prepare("SELECT user_id FROM customization_requests WHERE id = ? LIMIT 1");
        if ($q) {
            $q->bind_param('i', $request_id);
            $q->execute();
            $res = $q->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $uid = intval($row['user_id']);
                switch ($status) {
                    case 'approved': $friendly = 'has been approved'; break;
                    case 'in_progress': $friendly = 'is now in progress'; break;
                    case 'completed': $friendly = 'has been completed'; break;
                    case 'cancelled': $friendly = 'has been cancelled and was not approved'; break;
                    default: $friendly = 'status changed to: ' . $status; break;
                }
                $notifMsg = "Your customization request #" . str_pad($request_id, 6, '0', STR_PAD_LEFT) . " " . $friendly . ".";
                $ins = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'customization', ?)");
                if ($ins) {
                    $ins->bind_param('is', $uid, $notifMsg);
                    $ins->execute();
                    $ins->close();
                }
            }
            $q->close();
        }
    } catch (Exception $e) {
        error_log('Customization status notification error: ' . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
