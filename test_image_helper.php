<?php
/**
 * Test Image Helper Functions
 * Run this file to verify image helper is working correctly
 */

include 'db.php';
include_once 'includes/image_helper.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Image Helper - MTC Clothing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #5b6b46;
            border-bottom: 3px solid #5b6b46;
            padding-bottom: 10px;
        }
        h2 {
            color: #7a8f5e;
            margin-top: 30px;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .product-card {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .product-card img {
            max-width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .product-name {
            font-weight: bold;
            margin: 10px 0 5px;
            color: #333;
        }
        .product-path {
            font-size: 11px;
            color: #666;
            word-break: break-all;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <h1>🧪 Image Helper Test Page</h1>
    
    <div class="info-box">
        <strong>Purpose:</strong> This page tests if the image helper functions are working correctly across your product catalog.
    </div>

    <!-- Test 1: Function Availability -->
    <div class="test-section">
        <h2>Test 1: Helper Functions Available</h2>
        <?php
        $functions = [
            'getProductImagePath',
            'renderProductImage',
            'getAllProductImages',
            'getProductImages',
            'getProductColorImages',
            'getPlaceholderImage',
            'getBasePath'
        ];
        
        echo '<ul>';
        foreach ($functions as $func) {
            $exists = function_exists($func);
            $status = $exists ? 'success' : 'error';
            $text = $exists ? '✓ Available' : '✗ Missing';
            echo '<li><code>' . $func . '()</code> <span class="status ' . $status . '">' . $text . '</span></li>';
        }
        echo '</ul>';
        ?>
    </div>

    <!-- Test 2: Base Path Detection -->
    <div class="test-section">
        <h2>Test 2: Base Path Detection</h2>
        <?php
        $basePath = getBasePath();
        echo '<p><strong>Detected Base Path:</strong> <code>' . htmlspecialchars($basePath) . '</code></p>';
        echo '<p><strong>Document Root:</strong> <code>' . htmlspecialchars($_SERVER['DOCUMENT_ROOT']) . '</code></p>';
        echo '<p><strong>Script Name:</strong> <code>' . htmlspecialchars($_SERVER['SCRIPT_NAME']) . '</code></p>';
        ?>
    </div>

    <!-- Test 3: Placeholder Image -->
    <div class="test-section">
        <h2>Test 3: Placeholder Image</h2>
        <?php
        $placeholder = getPlaceholderImage();
        echo '<p><strong>Placeholder Path:</strong> <code>' . htmlspecialchars($placeholder) . '</code></p>';
        echo '<img src="' . htmlspecialchars($placeholder) . '" alt="Placeholder" style="max-width: 200px; border: 1px solid #ddd;">';
        ?>
    </div>

    <!-- Test 4: Sample Products -->
    <div class="test-section">
        <h2>Test 4: Sample Products (First 12)</h2>
        <p>Testing image display with actual products from database:</p>
        
        <div class="product-grid">
            <?php
            $sql = "SELECT id, name, image, category FROM products LIMIT 12";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $imagePath = getProductImagePath($row['image']);
                    echo '<div class="product-card">';
                    echo renderProductImage($row['image'], $row['name'], '', 'max-width: 100%; height: 150px; object-fit: contain;');
                    echo '<div class="product-name">' . htmlspecialchars($row['name']) . '</div>';
                    echo '<div class="product-path">ID: ' . $row['id'] . '<br>';
                    echo 'Category: ' . htmlspecialchars($row['category']) . '<br>';
                    echo 'DB Path: ' . htmlspecialchars($row['image']) . '<br>';
                    echo 'Resolved: ' . htmlspecialchars($imagePath) . '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p class="status error">No products found in database</p>';
            }
            ?>
        </div>
    </div>

    <!-- Test 5: Different Path Formats -->
    <div class="test-section">
        <h2>Test 5: Path Format Handling</h2>
        <p>Testing how different path formats are resolved:</p>
        
        <?php
        $testPaths = [
            'product.jpg',
            'img/product.jpg',
            'admin/product.jpg',
            'admin/img/product.jpg',
            '/img/product.jpg',
            'https://example.com/image.jpg',
            '',
            null
        ];
        
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr style="background: #f0f0f0; font-weight: bold;">';
        echo '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Input Path</th>';
        echo '<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Resolved Path</th>';
        echo '</tr>';
        
        foreach ($testPaths as $path) {
            $resolved = getProductImagePath($path, false);
            echo '<tr>';
            echo '<td style="padding: 10px; border: 1px solid #ddd;"><code>' . htmlspecialchars($path ?: '(empty)') . '</code></td>';
            echo '<td style="padding: 10px; border: 1px solid #ddd;"><code>' . htmlspecialchars($resolved) . '</code></td>';
            echo '</tr>';
        }
        
        echo '</table>';
        ?>
    </div>

    <!-- Test 6: Product with Multiple Images -->
    <div class="test-section">
        <h2>Test 6: Product with Multiple Images</h2>
        <?php
        $sql = "SELECT id, name, image FROM products WHERE id IN (
            SELECT DISTINCT product_id FROM product_images LIMIT 1
        ) LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $allImages = getAllProductImages($conn, $product['id'], $product['image']);
            
            echo '<p><strong>Product:</strong> ' . htmlspecialchars($product['name']) . ' (ID: ' . $product['id'] . ')</p>';
            echo '<p><strong>Total Images Found:</strong> ' . count($allImages) . '</p>';
            
            if (count($allImages) > 0) {
                echo '<div class="product-grid">';
                foreach ($allImages as $img) {
                    echo '<div class="product-card">';
                    echo '<img src="' . htmlspecialchars($img['path']) . '" alt="' . htmlspecialchars($img['label']) . '">';
                    echo '<div class="product-name">' . htmlspecialchars($img['label']) . '</div>';
                    echo '<div class="product-path">' . htmlspecialchars($img['path']) . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
        } else {
            echo '<p class="status error">No products with multiple images found</p>';
        }
        ?>
    </div>

    <!-- Summary -->
    <div class="test-section">
        <h2>✅ Test Summary</h2>
        <div class="info-box">
            <p><strong>If you see images displaying above:</strong></p>
            <ul>
                <li>✅ Image helper is working correctly</li>
                <li>✅ Path detection is functioning</li>
                <li>✅ Fallback system is active</li>
                <li>✅ Your product catalog will display images properly</li>
            </ul>
            
            <p><strong>If images are not showing:</strong></p>
            <ul>
                <li>Check if image files exist in <code>img/</code> or <code>admin/</code> directories</li>
                <li>Verify file permissions</li>
                <li>Check browser console for errors</li>
                <li>Ensure database has correct image paths</li>
            </ul>
        </div>
    </div>

    <div style="text-align: center; margin: 40px 0; padding: 20px; background: #5b6b46; color: white; border-radius: 8px;">
        <h3>🎉 Image Helper System is Active!</h3>
        <p>Your product images will now display consistently across all pages.</p>
        <p><a href="products.php" style="color: #d9e6a7; font-weight: bold;">View Products Page</a> | 
           <a href="men.php" style="color: #d9e6a7; font-weight: bold;">View Men's Collection</a> | 
           <a href="women.php" style="color: #d9e6a7; font-weight: bold;">View Women's Collection</a></p>
    </div>
</body>
</html>
