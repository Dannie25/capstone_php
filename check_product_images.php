<?php
include 'db.php';

echo "<h2>Checking Product Images for Product ID 27</h2>";

// Check if product exists
$result = $conn->query("SELECT id, name, image FROM products WHERE id = 27");
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo "<h3>Product Found:</h3>";
    echo "ID: " . $product['id'] . "<br>";
    echo "Name: " . htmlspecialchars($product['name']) . "<br>";
    echo "Main Image: " . htmlspecialchars($product['image']) . "<br>";
    
    // Check if main image file exists
    if (!empty($product['image'])) {
        $imagePath = $product['image'];
        if (file_exists($imagePath)) {
            echo "✅ Main image file EXISTS at: $imagePath<br>";
            echo "<img src='$imagePath' style='max-width:200px;border:2px solid green;'><br>";
        } else {
            echo "❌ Main image file NOT FOUND at: $imagePath<br>";
        }
    } else {
        echo "⚠️ No main image set for this product<br>";
    }
} else {
    echo "❌ Product ID 27 NOT FOUND<br>";
}

echo "<hr>";

// Check if product_images table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($tableCheck->num_rows > 0) {
    echo "<h3>✅ product_images table EXISTS</h3>";
    
    // Check for images in product_images table
    $imgResult = $conn->query("SELECT * FROM product_images WHERE product_id = 27 ORDER BY sort_order, id");
    if ($imgResult->num_rows > 0) {
        echo "<h4>Additional Images Found: " . $imgResult->num_rows . "</h4>";
        while ($img = $imgResult->fetch_assoc()) {
            echo "Image ID: " . $img['id'] . " - " . htmlspecialchars($img['image']) . "<br>";
            if (file_exists($img['image'])) {
                echo "✅ File EXISTS<br>";
                echo "<img src='" . htmlspecialchars($img['image']) . "' style='max-width:200px;border:2px solid green;margin:5px;'><br>";
            } else {
                echo "❌ File NOT FOUND at: " . $img['image'] . "<br>";
            }
        }
    } else {
        echo "⚠️ No additional images found in product_images table for product 27<br>";
    }
} else {
    echo "<h3>❌ product_images table DOES NOT EXIST</h3>";
    echo "<p>Run create_product_images_table.php to create it</p>";
}

echo "<hr>";

// Check color images
$colorResult = $conn->query("SELECT color, color_image FROM product_colors WHERE product_id = 27 AND color_image IS NOT NULL AND color_image != ''");
if ($colorResult && $colorResult->num_rows > 0) {
    echo "<h3>Color Images Found: " . $colorResult->num_rows . "</h3>";
    while ($color = $colorResult->fetch_assoc()) {
        echo "Color: " . htmlspecialchars($color['color']) . " - img/" . htmlspecialchars($color['color_image']) . "<br>";
        $colorImgPath = "img/" . $color['color_image'];
        if (file_exists($colorImgPath)) {
            echo "✅ File EXISTS<br>";
            echo "<img src='$colorImgPath' style='max-width:200px;border:2px solid green;margin:5px;'><br>";
        } else {
            echo "❌ File NOT FOUND at: $colorImgPath<br>";
        }
    }
} else {
    echo "<h3>⚠️ No color images found for product 27</h3>";
}

$conn->close();
?>
