<?php
header('Content-Type: application/json');
error_reporting(0);

include '../db.php';

if (!$conn) {
    echo json_encode(['stats' => ['total' => 0], 'pending_items' => []]);
    exit;
}

// Get counts
$orders_count = 0;
$subcon_count = 0;
$custom_count = 0;

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'pending'");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $orders_count = intval($row['c']);
}

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'pending'");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $subcon_count = intval($row['c']);
}

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM customization_requests WHERE status IN ('pending', 'submitted')");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $custom_count = intval($row['c']);
}

$total = $orders_count + $subcon_count + $custom_count;

// Get items
$items = array();

$r = mysqli_query($conn, "SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, total_amount, created_at FROM orders WHERE status = 'pending' ORDER BY created_at DESC LIMIT 50");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $row['type'] = 'order';
        $items[] = $row;
    }
}

$r = mysqli_query($conn, "SELECT id, customer_name, total_amount, created_at FROM subcontract_requests WHERE status = 'pending' ORDER BY created_at DESC LIMIT 50");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $row['type'] = 'subcontract';
        $items[] = $row;
    }
}

$r = mysqli_query($conn, "SELECT cr.id, u.name as customer_name, cr.estimated_price as total_amount, cr.created_at FROM customization_requests cr LEFT JOIN users u ON cr.user_id = u.id WHERE cr.status IN ('pending', 'submitted') ORDER BY cr.created_at DESC LIMIT 50");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $row['type'] = 'customization';
        $items[] = $row;
    }
}

echo json_encode(array(
    'pending_items' => $items,
    'stats' => array(
        'orders' => $orders_count,
        'subcontract' => $subcon_count,
        'customization' => $custom_count,
        'total' => $total
    )
));
