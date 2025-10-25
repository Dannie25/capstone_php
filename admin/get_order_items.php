<?php
// Ensure no output before headers
ob_start();
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order ID']);
    exit();
}

$order_id = (int)$_GET['order_id'];

// Include database connection
@include '../db.php';

// Check if connection was successful
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

try {
    // Get order items with product details
    $query = "SELECT 
                oi.*, 
                p.name as product_name,
                p.image as product_image,
                p.price as product_price
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $order_id);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Get result failed: ' . $stmt->error);
    }
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Get image path - try product_image first, then product_images table
        $imagePath = '';
        $imageSource = $row['product_image'];
        
        // If product_image is empty, try to get from product_images table
        if (empty($imageSource)) {
            $img_query = "SELECT image FROM product_images WHERE product_id = ? ORDER BY id LIMIT 1";
            $img_stmt = $conn->prepare($img_query);
            if ($img_stmt) {
                $img_stmt->bind_param("i", $row['product_id']);
                $img_stmt->execute();
                $img_result = $img_stmt->get_result();
                if ($img_row = $img_result->fetch_assoc()) {
                    $imageSource = $img_row['image'];
                }
                $img_stmt->close();
            }
        }
        
        // Clean up the image path
        if (!empty($imageSource)) {
            // Remove any leading slashes or dots
            $cleanPath = ltrim($imageSource, '/.\\');
            
            // If path doesn't have img/ prefix, add it
            if (!preg_match('/^img\//i', $cleanPath)) {
                $cleanPath = 'img/' . basename($cleanPath);
            }
            
            // Check if the file exists
            $possiblePaths = [
                '../' . $cleanPath,
                $cleanPath,
                '../img/' . basename($cleanPath),
            ];
            
            // Find the first valid path
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $imagePath = $path;
                    break;
                }
            }
        }
        
        $items[] = [
            'product_id' => (int)$row['product_id'],
            'product_name' => $row['product_name'] ?? 'Unknown Product',
            'price' => (float)$row['product_price'],
            'quantity' => (int)$row['quantity'],
            'size' => $row['size'] ?? '',
            'image' => $imagePath
        ];
    }
    
    // Clear any output that might have been generated
    ob_end_clean();
    
    // Output the JSON response
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_end_clean();
    
    // Log the error
    error_log('Error in get_order_items.php: ' . $e->getMessage());
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching order items',
        'debug' => (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) ? $e->getMessage() : null
    ]);
}

// Ensure no further output
if (ob_get_level() > 0) {
    ob_end_flush();
}

exit();
?>
