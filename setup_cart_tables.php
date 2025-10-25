<?php
include 'db.php';

// Create products table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Products table created successfully<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Create cart table
$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Cart table created successfully<br>";
} else {
    echo "Error creating cart table: " . $conn->error . "<br>";
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Add sample products if they don't exist
$products = [
    ['name' => 'Classic White Shirt', 'price' => 29.99, 'category' => 'men', 'image' => 'products/shirt1.jpg'],
    ['name' => 'Slim Fit Jeans', 'price' => 59.99, 'category' => 'men', 'image' => 'products/jeans1.jpg'],
    ['name' => 'Summer Dress', 'price' => 49.99, 'category' => 'women', 'image' => 'products/dress1.jpg'],
    ['name' => 'Casual T-Shirt', 'price' => 24.99, 'category' => 'women', 'image' => 'products/tshirt1.jpg']
];

foreach ($products as $product) {
    $name = $product['name'];
    $price = $product['price'];
    $category = $product['category'];
    $image = $product['image'];
    
    // Check if product already exists
    $check = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, category, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $name, $price, $category, $image);
        
        if ($stmt->execute()) {
            echo "Added product: $name<br>";
        } else {
            echo "Error adding product $name: " . $conn->error . "<br>";
        }
    } else {
        echo "Product already exists: $name<br>";
    }
}

echo "<br>Setup complete. <a href='home.php'>Go to Home</a>";
?>
