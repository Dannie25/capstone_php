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
$customization_status_filter = isset($_GET['customization_status']) ? $_GET['customization_status'] : '';

// Build query
$customization_query = "SELECT 
    cr.*, 
    u.name as customer_name,
    u.email as contact_email
FROM customization_requests cr
LEFT JOIN users u ON cr.user_id = u.id";

if (!empty($customization_status_filter) && in_array($customization_status_filter, ['pending', 'submitted', 'approved', 'verifying', 'in_progress', 'completed', 'cancelled'])) {
    if ($customization_status_filter === 'pending') {
        $customization_query .= " WHERE cr.status IN ('pending', 'submitted')";
    } else {
        $customization_query .= " WHERE cr.status = '" . $conn->real_escape_string($customization_status_filter) . "'";
    }
}

$customization_query .= " ORDER BY cr.created_at DESC LIMIT 10";

$result = $conn->query($customization_query);
$customizations = [];

if ($result) {
    $customizations = $result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$stats = [
    'pending' => 0,
    'approved' => 0,
    'verifying' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'total' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status IN ('pending', 'submitted')");
if ($result && $row = $result->fetch_assoc()) {
    $stats['pending'] = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'approved'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['approved'] = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'verifying'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['verifying'] = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'in_progress'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['in_progress'] = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'completed'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['completed'] = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests WHERE status = 'cancelled'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['cancelled'] = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM customization_requests");
if ($result && $row = $result->fetch_assoc()) {
    $stats['total'] = $row['count'];
}

header('Content-Type: application/json');
echo json_encode([
    'customizations' => $customizations,
    'stats' => $stats
]);
