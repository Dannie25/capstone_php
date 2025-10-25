<?php
// Don't start session here as it's already started in the calling script
include_once 'db.php';

// Function to add item to cart
function addToCart($product_id, $quantity = 1, $size = null, $color = null) {
    global $conn; // Make sure we can access the database connection
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $user_id = $_SESSION['user_id'];
    
    // Check if product exists and get max_per_order
    $stmt = $conn->prepare("SELECT id, name, price, max_per_order FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Product not found');
    }

    // Check if item with same product_id, size, and color already in cart
    $check_sql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? ";
    $params = array($user_id, $product_id);
    $types = "ii";
    
    if ($size !== null) {
        $check_sql .= "AND (size = ? OR size IS NULL) ";
        $params[] = $size;
        $types .= "s";
    } else {
        $check_sql .= "AND size IS NULL ";
    }
    
    if ($color !== null) {
        $check_sql .= "AND (color = ? OR color IS NULL) ";
        $params[] = $color;
        $types .= "s";
    } else {
        $check_sql .= "AND color IS NULL ";
    }
    
    $stmt = $conn->prepare($check_sql);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity if item exists
        $row = $result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("ii", $new_quantity, $row['id']);
    } else {
        // Add new item to cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, color) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("iiiss", $user_id, $product_id, $quantity, $size, $color);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update cart: ' . $stmt->error);
    }
    
    return ["status" => "success", "message" => "Item added to cart"];
}

// Function to get cart count
function getCartCount() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return 0;
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    if (!$stmt) {
        error_log('Database prepare failed: ' . $conn->error);
        return 0;
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        error_log('Database query failed: ' . $stmt->error);
        return 0;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (int)($row['count'] ?? 0);
}

// Function to get cart items
function getCartItems() {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    $user_id = $_SESSION['user_id'];
    $stmt = $GLOBALS['conn']->prepare(
        "SELECT c.*, p.name, p.price, p.image, (p.price * c.quantity) as total 
         FROM cart c 
         JOIN products p ON c.product_id = p.id 
         WHERE c.user_id = ?"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}
?>
