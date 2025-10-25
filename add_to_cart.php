<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

// Start session after headers
session_start();

// Function to send JSON response and exit
function sendJsonResponse($data) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    echo json_encode($data);
    exit();
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['status' => 'error', 'message' => 'Please log in to add items to cart']);
    }

    // Check if product_id is provided and valid
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        sendJsonResponse(['status' => 'error', 'message' => 'Invalid product ID']);
    }

    // Include required files
    require_once 'db.php';
    require_once 'cart_functions.php';
    require_once 'includes/inventory_helper.php';

    // Validate database connection
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }

    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
    $size = isset($_POST['size']) ? $_POST['size'] : null;
    $color = isset($_POST['color']) ? $_POST['color'] : null;

    // Check inventory before adding to cart
    $available_qty = 0;
    
    // If both color and size are provided, check color-size inventory matrix
    if ($color && $size) {
        $available_qty = getAvailableQuantity($conn, $product_id, $color, $size);
        
        if ($quantity > $available_qty) {
            sendJsonResponse([
                'success' => false, 
                'message' => "Requested quantity exceeds available stock. Only {$available_qty} available for {$color} - {$size}."
            ]);
        }
    }
    // Fallback: Check color-only inventory (for old products)
    else if ($color) {
        $stmt = $conn->prepare("SELECT quantity FROM product_colors WHERE product_id = ? AND color = ?");
        $stmt->bind_param("is", $product_id, $color);
        $stmt->execute();
        $stmt->bind_result($available_qty);
        $stmt->fetch();
        $stmt->close();
        
        if ($quantity > $available_qty) {
            sendJsonResponse([
                'success' => false, 
                'message' => "Requested quantity exceeds available stock. Only {$available_qty} available for {$color}."
            ]);
        }
    }
    // Add to cart with size and color
    $result = addToCart($product_id, $quantity, $size, $color);

    // Get updated cart count
    $cart_count = getCartCount();
    $result['cart_count'] = $cart_count;

    // Send success response
    sendJsonResponse($result);

} catch (Exception $e) {
    // Log the error
    error_log('Add to cart error: ' . $e->getMessage());
    
    // Send error response
    sendJsonResponse([
        'status' => 'error', 
        'message' => 'An error occurred: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
