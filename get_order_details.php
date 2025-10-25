<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get order ID from request
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

include 'db.php';

// Get order details
$order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

$order = $order_result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.name as product_name, p.image as product_image, p.price as product_price 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = [];

// Define the base URL for images
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_path = '/capstone_php/';

while ($item = $items_result->fetch_assoc()) {
    // Initialize image path
    $image_path = null;
    
    // Check if product_image exists and is not empty
    if (!empty($item['product_image'])) {
        // Remove any leading slashes or backslashes
        $image_file = ltrim($item['product_image'], '/\\');
        
        // Check common image directories
        $possible_paths = [
            'admin/' . $image_file,
            'img/' . $image_file,
            'admin/img/' . $image_file,
            $image_file
        ];
        
        // Check each possible path
        foreach ($possible_paths as $path) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $base_path . $path)) {
                $image_path = $base_url . $base_path . $path;
                break;
            }
        }
    }
    
    // If no valid image found, use a placeholder
    if (empty($image_path)) {
        $image_path = $base_url . $base_path . 'img/no-image.jpg';
    }
    
    // Update the item with the full image URL
    $item['product_image'] = $image_path;
    $items[] = $item;
}

echo json_encode([
    'success' => true,
    'order' => [
        'id' => $order['id'],
        'created_at' => $order['created_at'],
        'status' => $order['status'],
        'payment_method' => $order['payment_method'],
        'delivery_mode' => $order['delivery_mode'],
        'address' => $order['address'],
        'city' => $order['city'],
        'postal_code' => $order['postal_code'],
        'subtotal' => $order['subtotal'],
        'shipping' => $order['shipping'],
        'tax' => $order['tax'],
        'total_amount' => $order['total_amount'],
        'cancel_reason' => $order['cancel_reason'] ?? null,
        'cancelled_at' => $order['cancelled_at'] ?? null
    ],
    'items' => $items
]);
?>
