<?php
include '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit();
}

$productId = (int)$_GET['id'];

// Get main product image from products table
$mainImg = '';
$productRes = $conn->query("SELECT image FROM products WHERE id = $productId");
if ($productRes && ($row = $productRes->fetch_assoc())) {
    $mainImg = $row['image'];
}

// Get all color images for this product
$colorImgs = [];
$colorNames = [];
$colorRes = $conn->query("SELECT color, color_image FROM product_colors WHERE product_id = $productId AND color_image IS NOT NULL AND color_image != '' ORDER BY id");
while ($colorRes && ($row = $colorRes->fetch_assoc())) {
    $colorImgs[] = $row['color_image'];
    $colorNames[] = $row['color'];
}

// Combine: main image first, then color images
$allImgs = [];
$allImgLabels = [];
if (!empty($mainImg)) {
    $allImgs[] = $mainImg;
    $allImgLabels[] = 'Main';
}
foreach ($colorImgs as $idx => $cImg) {
    $allImgs[] = $cImg;
    $allImgLabels[] = $colorNames[$idx];
}

echo json_encode([
    'success' => true,
    'images' => $allImgs,
    'labels' => $allImgLabels
]);
