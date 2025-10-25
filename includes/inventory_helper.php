<?php
/**
 * Inventory Helper Functions
 * Handles color-size inventory matrix for customer-facing pages
 */

/**
 * Get inventory data for a product
 * Returns array with color_size keys and quantities
 */
function getProductInventory($conn, $product_id) {
    $inventory = [];
    
    $sql = "SELECT color, size, quantity FROM product_color_size_inventory WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $key = $row['color'] . '_' . $row['size'];
        $inventory[$key] = (int)$row['quantity'];
    }
    
    return $inventory;
}

/**
 * Get available quantity for specific color-size combination
 */
function getAvailableQuantity($conn, $product_id, $color, $size) {
    $sql = "SELECT quantity FROM product_color_size_inventory 
            WHERE product_id = ? AND color = ? AND size = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $product_id, $color, $size);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (int)$row['quantity'];
    }
    
    return 0;
}

/**
 * Check if product has inventory matrix data
 */
function hasInventoryMatrix($conn, $product_id) {
    $sql = "SELECT COUNT(*) as count FROM product_color_size_inventory WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

/**
 * Get inventory grouped by color (for backward compatibility)
 */
function getInventoryByColor($conn, $product_id) {
    $inventory = [];
    
    $sql = "SELECT color, SUM(quantity) as total_qty 
            FROM product_color_size_inventory 
            WHERE product_id = ? 
            GROUP BY color";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $inventory[$row['color']] = (int)$row['total_qty'];
    }
    
    return $inventory;
}

/**
 * Get inventory grouped by size
 */
function getInventoryBySize($conn, $product_id) {
    $inventory = [];
    
    $sql = "SELECT size, SUM(quantity) as total_qty 
            FROM product_color_size_inventory 
            WHERE product_id = ? 
            GROUP BY size";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $inventory[$row['size']] = (int)$row['total_qty'];
    }
    
    return $inventory;
}
?>
