<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get status filter
$subcontract_status_filter = isset($_GET['subcontract_status']) ? $_GET['subcontract_status'] : '';

// Build query
$subcontract_query = "SELECT * FROM subcontract_requests";

if (!empty($subcontract_status_filter) && in_array($subcontract_status_filter, ['pending', 'awaiting_confirmation', 'in_progress', 'to_deliver', 'completed', 'cancelled'])) {
    $subcontract_query .= " WHERE status = '" . $conn->real_escape_string($subcontract_status_filter) . "'";
}

$subcontract_query .= " ORDER BY created_at DESC LIMIT 10";

$result = $conn->query($subcontract_query);
$subcontracts = [];

if ($result) {
    $subcontracts = $result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$stats = [
    'pending' => 0,
    'awaiting_confirmation' => 0,
    'in_progress' => 0,
    'to_deliver' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'total' => 0
];

// Get all counts in one query
$result = $conn->query("SELECT status, COUNT(*) as count FROM subcontract_requests GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
}

$result = $conn->query("SELECT COUNT(*) as count FROM subcontract_requests");
if ($result && $row = $result->fetch_assoc()) {
    $stats['total'] = $row['count'];
}

header('Content-Type: application/json');
echo json_encode([
    'subcontracts' => $subcontracts,
    'stats' => $stats
]);
