<?php
include 'db.php';
header('Content-Type: text/plain');

echo "=== CHECKING PRODUCT IMAGES ===\n\n";

$result = $conn->query("SELECT id, name, image, category FROM products LIMIT 5");
while($row = $result->fetch_assoc()) {
    echo "Product ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Category: " . $row['category'] . "\n";
    echo "Image in DB: " . $row['image'] . "\n";
    
    // Check if file exists in different locations
    $checks = [
        'Direct path' => $row['image'],
        'img/ prefix' => 'img/' . $row['image'],
        'admin/ prefix' => 'admin/' . $row['image'],
        'admin/uploads/ prefix' => 'admin/uploads/' . $row['image'],
    ];
    
    foreach($checks as $label => $path) {
        if(file_exists($path)) {
            echo "  ✓ FOUND at: $path\n";
        }
    }
    
    echo "\n";
}
?>
