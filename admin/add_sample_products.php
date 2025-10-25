<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "capstone_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Sample products data with image paths
$sample_products = [
    [
        'name' => 'Classic White T-Shirt',
        'description' => 'Comfortable 100% cotton white t-shirt',
        'price' => 599.00,
        'category' => 'T-Shirts',
        'image' => 'img/classic_white_tshirt.jpg',
        'stock' => 50
    ],
    [
        'name' => 'Slim Fit Jeans',
        'description' => 'Stylish slim fit jeans for men',
        'price' => 1299.00,
        'category' => 'Pants',
        'image' => 'img/slim_fit_jeans.jpg',
        'stock' => 30
    ],
    [
        'name' => 'Floral Summer Dress',
        'description' => 'Light and comfortable summer dress with floral pattern',
        'price' => 999.00,
        'category' => 'Dresses',
        'image' => 'img/floral_summer_dress.jpg',
        'stock' => 25
    ],
    [
        'name' => 'Sports Hoodie',
        'description' => 'Warm and comfortable hoodie for sports',
        'price' => 1499.00,
        'category' => 'Hoodies',
        'image' => 'img/sports_hoodie.jpg',
        'stock' => 40
    ],
    [
        'name' => 'Casual Sneakers',
        'description' => 'Comfortable casual sneakers for everyday wear',
        'price' => 1999.00,
        'category' => 'Shoes',
        'image' => 'img/casual_sneakers.jpg',
        'stock' => 20
    ]
];

// Check if products already exist
$check_query = "SELECT COUNT(*) as count FROM products";
$result = mysqli_query($conn, $check_query);
$row = mysqli_fetch_assoc($result);

if ($row['count'] > 0) {
    echo "Products already exist in the database. This script is for initial setup only.\n";
    exit;
}

// Insert sample products
$inserted = 0;
foreach ($sample_products as $product) {
    $query = "INSERT INTO products (name, description, price, category, image, stock, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssdssi", 
        $product['name'],
        $product['description'],
        $product['price'],
        $product['category'],
        $product['image'],
        $product['stock']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $inserted++;
        echo "Added: " . $product['name'] . "<br>";
    } else {
        echo "Error adding " . $product['name'] . ": " . mysqli_error($conn) . "<br>";
    }
}

echo "\nAdded $inserted sample products to the database.\n";

// Create sample images directory if it doesn't exist
$img_dir = __DIR__ . '/../img';
if (!file_exists($img_dir)) {
    mkdir($img_dir, 0777, true);
    echo "Created directory: $img_dir\n";
}

// Provide instructions for adding actual images
echo "\nIMPORTANT: Please add the following images to the '/img/' directory:\n";
foreach ($sample_products as $product) {
    echo "- " . basename($product['image']) . " (for: " . $product['name'] . ")\n";
}

mysqli_close($conn);
?>
