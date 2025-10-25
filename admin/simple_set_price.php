<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$request_id = intval($_POST['request_id']);
$price = floatval($_POST['price']);

if ($price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid price']);
    exit();
}

// Use prepared statement
$stmt = $conn->prepare("UPDATE subcontract_requests SET price = ?, status = 'awaiting_confirmation', updated_at = NOW() WHERE id = ?");
$stmt->bind_param("di", $price, $request_id);

if ($stmt->execute()) {
    // Insert notification for the user about subcontract price set
    try {
        $uStmt = $conn->prepare("SELECT user_id FROM subcontract_requests WHERE id = ?");
        if ($uStmt) {
            $uStmt->bind_param('i', $request_id);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRes && $uRow = $uRes->fetch_assoc()) {
                $targetUserId = $uRow['user_id'];
                $notifMsg = "Your subcontract request #" . str_pad($request_id, 6, '0', STR_PAD_LEFT) . " has been priced: ₱" . number_format($price, 2) . ". Please accept or reject the quote.";
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subcontract', ?)");
                if ($notifStmt) {
                    $notifStmt->bind_param('is', $targetUserId, $notifMsg);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            }
            $uStmt->close();
        }
    } catch (Exception $ex) {
        error_log('Notification insert error: ' . $ex->getMessage());
    }
    echo json_encode([
        'success' => true, 
        'message' => 'Price set and status updated to awaiting_confirmation',
        'affected_rows' => $stmt->affected_rows
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
