<?php
// Migrate existing product images to product_images table
include 'db.php';

echo "<h2>Migrating Product Images...</h2>";

// Get all products that have images
$result = $conn->query("SELECT id, image FROM products WHERE image IS NOT NULL AND image != ''");

if ($result->num_rows > 0) {
    $migrated = 0;
    $skipped = 0;
    
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['id'];
        $image = $row['image'];
        
        // Check if image already exists in product_images
        $check = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? AND image = ?");
        $check->bind_param("is", $product_id, $image);
        $check->execute();
        $check_result = $check->get_result();
        
        if ($check_result->num_rows == 0) {
            // Insert into product_images
            $insert = $conn->prepare("INSERT INTO product_images (product_id, image, sort_order) VALUES (?, ?, 0)");
            $insert->bind_param("is", $product_id, $image);
            
            if ($insert->execute()) {
                $migrated++;
                echo "✅ Migrated image for Product ID: $product_id<br>";
            } else {
                echo "❌ Failed to migrate Product ID: $product_id - " . $insert->error . "<br>";
            }
        } else {
            $skipped++;
        }
    }
    
    echo "<br><strong>Migration Complete!</strong><br>";
    echo "✅ Migrated: $migrated images<br>";
    echo "⏭️ Skipped (already exists): $skipped images<br>";
    echo "<br>You can now refresh your product detail pages!";
} else {
    echo "⚠️ No products with images found in the database.";
}

$conn->close();
?>
