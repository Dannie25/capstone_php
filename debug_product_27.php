<?php
// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include 'db.php';

$product_id = 27;

echo "<h2>Debug Product ID: $product_id</h2>";

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>❌ Product not found!</p>";
    exit();
}

$product = $result->fetch_assoc();

echo "<h3>Product Info:</h3>";
echo "<pre>";
print_r($product);
echo "</pre>";

echo "<hr>";

// Simulate the image gathering logic
$imgs = [];
$imgLabels = [];

echo "<h3>Step 1: Main Product Image</h3>";
if (!empty($product['image'])) {
    // Add img/ prefix if not already present
    $mainImage = $product['image'];
    if (strpos($mainImage, 'img/') !== 0 && strpos($mainImage, '/') !== 0) {
        $mainImage = 'img/' . $mainImage;
    }
    $imgs[] = $mainImage;
    $imgLabels[] = 'Main';
    echo "Original path: " . htmlspecialchars($product['image']) . "<br>";
    echo "✅ Main image added: " . htmlspecialchars($mainImage) . "<br>";
    echo "File exists: " . (file_exists($mainImage) ? '<strong style="color:green;">YES ✓</strong>' : '<strong style="color:red;">NO ✗</strong>') . "<br>";
} else {
    echo "⚠️ No main image set<br>";
}

echo "<hr>";

echo "<h3>Step 2: Additional Product Images (product_images table)</h3>";
$tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
if ($tableCheck->num_rows > 0) {
    echo "✅ product_images table exists<br>";
    
    $productImgRes = $conn->query("SELECT image FROM product_images WHERE product_id = " . (int)$product['id'] . " ORDER BY sort_order, id");
    if ($productImgRes) {
        $imgCounter = 1;
        echo "Found " . $productImgRes->num_rows . " rows<br>";
        while ($row = $productImgRes->fetch_assoc()) {
            if (!empty($row['image'])) {
                // Add img/ prefix if not already present
                $imagePath = $row['image'];
                if (strpos($imagePath, 'img/') !== 0 && strpos($imagePath, '/') !== 0) {
                    $imagePath = 'img/' . $imagePath;
                }
                $imgs[] = $imagePath;
                $imgLabels[] = 'Image ' . $imgCounter;
                echo "Original path: " . htmlspecialchars($row['image']) . "<br>";
                echo "✅ Added image $imgCounter: " . htmlspecialchars($imagePath) . "<br>";
                echo "File exists: " . (file_exists($imagePath) ? '<strong style="color:green;">YES ✓</strong>' : '<strong style="color:red;">NO ✗</strong>') . "<br><br>";
                $imgCounter++;
            }
        }
    }
} else {
    echo "❌ product_images table does NOT exist<br>";
}

echo "<hr>";

echo "<h3>Step 3: Color Images</h3>";
$colorImgRes = $conn->query("SELECT color, color_image FROM product_colors WHERE product_id = " . (int)$product['id'] . " AND color_image IS NOT NULL AND color_image != '' ORDER BY id");
if ($colorImgRes && $colorImgRes->num_rows > 0) {
    echo "Found " . $colorImgRes->num_rows . " color images<br>";
    while ($row = $colorImgRes->fetch_assoc()) {
        $fullPath = 'img/' . $row['color_image'];
        $imgs[] = $fullPath;
        $imgLabels[] = $row['color'];
        echo "✅ Added color image for " . htmlspecialchars($row['color']) . ": " . htmlspecialchars($fullPath) . "<br>";
        echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "<br>";
    }
} else {
    echo "⚠️ No color images found<br>";
}

echo "<hr>";

echo "<h3>Final Result:</h3>";
echo "<p><strong>Total images: " . count($imgs) . "</strong></p>";

if (count($imgs) > 0) {
    echo "<h4>Images Array:</h4>";
    echo "<pre>";
    print_r($imgs);
    echo "</pre>";
    
    echo "<h4>Labels Array:</h4>";
    echo "<pre>";
    print_r($imgLabels);
    echo "</pre>";
    
    echo "<h4>Visual Preview:</h4>";
    foreach ($imgs as $i => $img) {
        echo "<div style='margin:10px;padding:10px;border:2px solid #ccc;'>";
        echo "<strong>" . htmlspecialchars($imgLabels[$i]) . "</strong><br>";
        echo "Path: " . htmlspecialchars($img) . "<br>";
        if (file_exists($img)) {
            echo "<img src='" . htmlspecialchars($img) . "' style='max-width:300px;border:2px solid green;'><br>";
        } else {
            echo "<span style='color:red;'>❌ File not found!</span><br>";
        }
        echo "</div>";
    }
} else {
    echo "<p style='color:red;font-weight:bold;'>❌ NO IMAGES FOUND!</p>";
}

$conn->close();
?>
