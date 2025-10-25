<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'capstone_db');

if (!$conn) {
    die(json_encode(['error' => 'Connection failed', 'stats' => ['total' => 0]]));
}

// Initialize counts
$orders_count = 0;
$subcon_count = 0;
$custom_count = 0;

// Count pending orders
$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'pending'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $orders_count = (int)$row['c'];
}

// Count pending subcontracts
$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM subcontract_requests WHERE status = 'pending'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $subcon_count = (int)$row['c'];
}

// Count pending customizations
$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM customization_requests WHERE status IN ('pending', 'submitted')");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $custom_count = (int)$row['c'];
}

$total = $orders_count + $subcon_count + $custom_count;

// Get pending items
$items = [];

// Get orders
$result = mysqli_query($conn, "SELECT id, CONCAT(first_name, ' ', last_name) as customer_name, total_amount, created_at FROM orders WHERE status = 'pending' ORDER BY created_at DESC LIMIT 20");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => $row['id'],
            'customer_name' => $row['customer_name'],
            'total_amount' => $row['total_amount'],
            'created_at' => $row['created_at'],
            'type' => 'order'
        ];
    }
}

// Get subcontracts
$result = mysqli_query($conn, "SELECT id, customer_name, created_at FROM subcontract_requests WHERE status = 'pending' ORDER BY created_at DESC LIMIT 20");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => $row['id'],
            'customer_name' => $row['customer_name'],
            'total_amount' => null,
            'created_at' => $row['created_at'],
            'type' => 'subcontract'
        ];
    }
}

// Get customizations
$result = mysqli_query($conn, "SELECT cr.id, u.name as customer_name, cr.created_at FROM customization_requests cr LEFT JOIN users u ON cr.user_id = u.id WHERE cr.status IN ('pending', 'submitted') ORDER BY cr.created_at DESC LIMIT 20");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = [
            'id' => $row['id'],
            'customer_name' => $row['customer_name'],
            'total_amount' => null,
            'created_at' => $row['created_at'],
            'type' => 'customization'
        ];
    }
}

// Sort all items by created_at DESC (most recent first)
usort($items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Output JSON
echo json_encode([
    'pending_items' => $items,
    'stats' => [
        'orders' => $orders_count,
        'subcontract' => $subcon_count,
        'customization' => $custom_count,
        'total' => $total
    ]
]);

mysqli_close($conn);
?>
