<?php
/**
 * Make a product "new" for testing NEW badge
 */
include 'db.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Product NEW - Test Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%);
            padding: 40px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        h1 { color: #5b6b46; }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .product-item {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }
        .product-item:hover {
            border-color: #5b6b46;
            box-shadow: 0 4px 12px rgba(91, 107, 70, 0.2);
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
        }
        .btn:hover {
            background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
        }
        .new-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Make Product "NEW" - Test Tool</h1>
        
        <div class="info">
            <strong>Purpose:</strong> This tool updates a product's <code>created_at</code> timestamp to NOW,
            making it appear as a "new" product with the NEW badge.
        </div>

        <?php
        if ($product_id > 0) {
            // Update the product's created_at to NOW
            $update_sql = "UPDATE products SET created_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                // Get product details
                $product_sql = "SELECT name, created_at FROM products WHERE id = ?";
                $product_stmt = $conn->prepare($product_sql);
                $product_stmt->bind_param("i", $product_id);
                $product_stmt->execute();
                $product_result = $product_stmt->get_result();
                $product = $product_result->fetch_assoc();
                
                echo '<div class="success">';
                echo '<strong>✅ Success!</strong><br>';
                echo 'Product <strong>' . htmlspecialchars($product['name']) . '</strong> is now marked as NEW!<br>';
                echo 'Timestamp: ' . $product['created_at'];
                echo '</div>';
                
                echo '<div class="info">';
                echo '<strong>🎉 Go check it out:</strong><br>';
                echo '<a href="products.php" class="btn">View in All Products</a> ';
                echo '<a href="men.php" class="btn">View in Men\'s</a> ';
                echo '<a href="women.php" class="btn">View in Women\'s</a>';
                echo '</div>';
            }
        }
        ?>

        <h2>Select a Product to Make "NEW":</h2>
        
        <div class="product-list">
            <?php
            $sql = "SELECT id, name, category, created_at FROM products ORDER BY id DESC LIMIT 12";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $createdTime = strtotime($row['created_at']);
                    $currentTime = time();
                    $daysDiff = round(($currentTime - $createdTime) / (60 * 60 * 24), 1);
                    $isNew = ($daysDiff <= 3);
                    
                    echo '<div class="product-item">';
                    echo '<strong>' . htmlspecialchars($row['name']) . '</strong>';
                    if ($isNew) {
                        echo '<span class="new-badge">NEW</span>';
                    }
                    echo '<br>';
                    echo '<small style="color: #666;">' . $row['category'] . '</small><br>';
                    echo '<small style="color: #999;">Added: ' . $daysDiff . ' days ago</small><br>';
                    echo '<a href="?id=' . $row['id'] . '" class="btn">Make NEW</a>';
                    echo '</div>';
                }
            } else {
                echo '<p>No products found.</p>';
            }
            ?>
        </div>

        <div class="info" style="margin-top: 30px;">
            <strong>💡 How to Use:</strong>
            <ol style="margin: 10px 0 0 20px;">
                <li>Click "Make NEW" button on any product above</li>
                <li>Product's timestamp will be updated to NOW</li>
                <li>Visit products.php, men.php, or women.php</li>
                <li>You'll see the pulsing RED "NEW" badge on that product!</li>
            </ol>
        </div>

        <p style="margin-top: 30px; text-align: center;">
            <a href="products.php" style="color: #5b6b46; font-weight: 600; text-decoration: none;">
                ← Back to Products
            </a>
        </p>
    </div>
</body>
</html>
