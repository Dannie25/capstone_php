<?php
session_start();
include 'db.php';
include_once 'includes/image_helper.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get user's orders with items
    $orders_query = "SELECT o.*, 
                            COUNT(oi.id) as item_count,
                            GROUP_CONCAT(CONCAT(oi.product_name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_summary
                     FROM orders o 
                     LEFT JOIN order_items oi ON o.id = oi.order_id 
                     WHERE o.user_id = ? 
                     GROUP BY o.id 
                     ORDER BY o.created_at DESC";

    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get user's subcontract requests
    $subcontract_query = "SELECT * FROM subcontract_requests WHERE user_id = ? ORDER BY created_at DESC";
    $stmt_sub = $conn->prepare($subcontract_query);
    $stmt_sub->bind_param("i", $user_id);
    $stmt_sub->execute();
    $subcontracts = $stmt_sub->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get user's customization requests
    $customization_query = "SELECT *, quoted_price as price FROM customization_requests WHERE user_id = ? ORDER BY created_at DESC";
    $stmt_custom = $conn->prepare($customization_query);
    $stmt_custom->bind_param("i", $user_id);
    $stmt_custom->execute();
    $customizations = $stmt_custom->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get order items for each order
    $orders_with_items = [];
    foreach ($orders as $order) {
        $items_query = "SELECT oi.*, p.name as product_name, p.image as product_image, p.price as product_price 
                       FROM order_items oi 
                       LEFT JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $order['id']);
        $items_stmt->execute();
        $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Add display_image (Image 1 priority) to each item
        foreach ($items as &$item) {
            $item['display_image'] = getCatalogImage($conn, $item['product_id'], $item['product_image']);
        }
        unset($item); // Break reference
        
        $order['items'] = $items;
        
        // Check if feedback exists for this order
        $feedback_check = $conn->prepare("SELECT id FROM order_feedback WHERE order_id = ? AND user_id = ?");
        $feedback_check->bind_param("ii", $order['id'], $user_id);
        $feedback_check->execute();
        $feedback_result = $feedback_check->get_result();
        $order['has_feedback'] = $feedback_result->num_rows > 0;
        $feedback_check->close();
        
        $orders_with_items[] = $order;
    }

    echo json_encode([
        'success' => true,
        'orders' => $orders_with_items,
        'subcontracts' => $subcontracts,
        'customizations' => $customizations,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
