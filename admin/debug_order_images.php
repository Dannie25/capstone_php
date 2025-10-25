<?php
session_start();
include '../db.php';

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 30;

echo "<h1>Debug Order Images - Order #$order_id</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;}</style>";

// Get order items
$items_sql = "SELECT oi.*, p.name as product_name, p.image as product_image, p.id as product_id
              FROM order_items oi
              LEFT JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// For each item, also check product_images table
foreach ($order_items as &$item) {
    $product_id = $item['product_id'];
    $img_sql = "SELECT image FROM product_images WHERE product_id = ? ORDER BY id LIMIT 1";
    $img_stmt = $conn->prepare($img_sql);
    $img_stmt->bind_param("i", $product_id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    if ($img_row = $img_result->fetch_assoc()) {
        $item['first_product_image'] = $img_row['image'];
    } else {
        $item['first_product_image'] = null;
    }
}
unset($item);

echo "<h2>Order Items:</h2>";
echo "<table>";
echo "<tr><th>Product Name</th><th>products.image</th><th>product_images (Image 1)</th><th>Checked Paths</th><th>Status</th></tr>";

foreach ($order_items as $item) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
    echo "<td>" . htmlspecialchars($item['product_image'] ?: '(empty)') . "</td>";
    echo "<td>" . htmlspecialchars($item['first_product_image'] ?: '(none)') . "</td>";
    echo "<td>";
    
    // Use first_product_image if available, fallback to product_image
    $imageToCheck = !empty($item['first_product_image']) ? $item['first_product_image'] : $item['product_image'];
    
    // Check different paths
    $cleanPath = ltrim($imageToCheck, '/\\');
    $paths = [
        '../img/' . basename($cleanPath),
        '../img/' . $cleanPath,
        '../' . $cleanPath,
        'img/' . basename($cleanPath),
    ];
    
    $found = false;
    foreach ($paths as $path) {
        $exists = file_exists($path);
        $color = $exists ? 'green' : 'red';
        $status = $exists ? '✓ EXISTS' : '✗ NOT FOUND';
        echo "<div style='color:$color;'>$path - $status</div>";
        if ($exists && !$found) {
            $found = true;
            echo "<div style='background:yellow;padding:5px;'><strong>USE THIS PATH!</strong></div>";
        }
    }
    
    echo "</td>";
    echo "<td>" . ($found ? "<span class='success'>✓ Found</span>" : "<span class='error'>✗ Not Found</span>") . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Check img folder contents:</h2>";
$imgDir = '../img/';
if (is_dir($imgDir)) {
    $files = scandir($imgDir);
    echo "<p class='success'>✓ img folder exists</p>";
    echo "<p>Files in img folder:</p><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p class='error'>✗ img folder NOT found at: " . realpath($imgDir) . "</p>";
}

echo "<hr>";
echo "<p><a href='view_order.php?id=$order_id'>Back to Order View</a></p>";
?>
