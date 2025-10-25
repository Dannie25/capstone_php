<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit();
}

$product_id = intval($_GET['id']);

// Get product details
try {
    include '../db.php';
    
    // Check if database connection is successful
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Prepare and execute the query
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit();
    }
    
    $product = $result->fetch_assoc();
    
    // Get product sizes
    $sizes = [];
    $size_result = $conn->query("SELECT size FROM product_sizes WHERE product_id = $product_id");
    if ($size_result && $size_result->num_rows > 0) {
        while ($row = $size_result->fetch_assoc()) {
            $sizes[] = $row['size'];
        }
    }
    
    // Get product colors with quantities and images
    $colors = [];
    $color_result = $conn->query("SELECT color, quantity, color_image FROM product_colors WHERE product_id = $product_id");
    if ($color_result && $color_result->num_rows > 0) {
        while ($row = $color_result->fetch_assoc()) {
            $colors[] = [
                'color' => $row['color'],
                'quantity' => (int)$row['quantity'],
                'color_image' => $row['color_image']
            ];
        }
    }
    
    // Get all product images
    $product_images = [];
    $images_result = $conn->query("SELECT image FROM product_images WHERE product_id = $product_id ORDER BY id");
    if ($images_result && $images_result->num_rows > 0) {
        while ($row = $images_result->fetch_assoc()) {
            $product_images[] = $row['image'];
        }
    }
    
    // Get color-size inventory data
    $inventory = [];
    $inventory_result = $conn->query("SELECT color, size, quantity FROM product_color_size_inventory WHERE product_id = $product_id");
    if ($inventory_result && $inventory_result->num_rows > 0) {
        while ($row = $inventory_result->fetch_assoc()) {
            $key = $row['color'] . '_' . $row['size'];
            $inventory[$key] = (int)$row['quantity'];
        }
    }
    
    // Combine all data
    $response = [
        'id' => $product['id'],
        'name' => $product['name'],
        'category' => $product['category'],
        'subcategory' => $product['subcategory'],
        'price' => $product['price'],
        'description' => $product['description'],
        'material' => $product['material'],
        'image' => $product['image'],
        'product_images' => $product_images,
        'sizes' => $sizes,
        'colors' => $colors,
        'inventory' => $inventory,
        'discount_enabled' => $product['discount_enabled'],
        'discount_type' => $product['discount_type'],
        'discount_value' => $product['discount_value'],
    ];
    
    // Return JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
