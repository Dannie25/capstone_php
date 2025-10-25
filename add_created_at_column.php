<?php
/**
 * Add created_at column to products table
 * Run this file once to enable NEW badge feature
 */

include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add created_at Column - MTC Clothing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%);
            padding: 40px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        h1 {
            color: #5b6b46;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
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
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn:hover {
            background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
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
    <div class="container">
        <h1>🔧 Add created_at Column to Products</h1>
        
        <div class="info">
            <strong>Purpose:</strong> This will add a <code>created_at</code> column to the products table, 
            which is needed to track when products were added and show the NEW badge.
        </div>

        <?php
        // Check if column already exists
        $check_sql = "SHOW COLUMNS FROM products LIKE 'created_at'";
        $result = $conn->query($check_sql);

        if ($result->num_rows > 0) {
            echo '<div class="warning">';
            echo '<strong>⚠️ Column Already Exists</strong><br>';
            echo 'The <code>created_at</code> column already exists in the products table.';
            echo '</div>';
            
            // Show sample data
            $sample_sql = "SELECT id, name, created_at FROM products LIMIT 5";
            $sample_result = $conn->query($sample_sql);
            
            if ($sample_result && $sample_result->num_rows > 0) {
                echo '<div class="info">';
                echo '<strong>Sample Products with created_at:</strong><br><br>';
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<tr style="background: #f0f0f0; font-weight: bold;">';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">ID</th>';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Product Name</th>';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Created At</th>';
                echo '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Days Old</th>';
                echo '</tr>';
                
                while ($row = $sample_result->fetch_assoc()) {
                    $createdTime = strtotime($row['created_at']);
                    $currentTime = time();
                    $daysDiff = round(($currentTime - $createdTime) / (60 * 60 * 24), 1);
                    $isNew = ($daysDiff <= 3);
                    
                    echo '<tr>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd;">' . $row['id'] . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd;">' . $row['created_at'] . '</td>';
                    echo '<td style="padding: 8px; border: 1px solid #ddd;">';
                    echo $daysDiff . ' days';
                    if ($isNew) {
                        echo ' <span style="background: #ff6b6b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">NEW</span>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }
            
        } else {
            // Add the column
            echo '<div class="info">';
            echo '<strong>Adding column...</strong><br>';
            echo 'Executing SQL: <code>ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP</code>';
            echo '</div>';
            
            $sql = "ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER discount_value";
            
            if ($conn->query($sql) === TRUE) {
                echo '<div class="success">';
                echo '<strong>✅ Success!</strong><br>';
                echo 'Column <code>created_at</code> has been successfully added to the products table.';
                echo '</div>';
                
                // Update existing products
                $update_sql = "UPDATE products SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL";
                if ($conn->query($update_sql) === TRUE) {
                    echo '<div class="success">';
                    echo '<strong>✅ Updated Existing Products</strong><br>';
                    echo 'All existing products have been timestamped with the current date/time.';
                    echo '</div>';
                }
                
                echo '<div class="warning">';
                echo '<strong>📝 Note:</strong> All existing products now have today\'s timestamp. ';
                echo 'Only products added from now on will show the NEW badge for 3 days.';
                echo '</div>';
                
            } else {
                echo '<div class="error">';
                echo '<strong>❌ Error</strong><br>';
                echo 'Failed to add column: ' . $conn->error;
                echo '</div>';
            }
        }
        ?>

        <div class="info" style="margin-top: 30px;">
            <strong>How NEW Badge Works:</strong>
            <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                <li>Products added within the <strong>last 3 days</strong> will show the NEW badge</li>
                <li>The badge appears in the <strong>center of the product image</strong></li>
                <li>Badge has a <strong>red gradient with pulse animation</strong></li>
                <li>After 3 days, the badge <strong>automatically disappears</strong></li>
            </ul>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="products.php" class="btn">
                ← View Products Catalog
            </a>
            <a href="men.php" class="btn">
                View Men's Collection
            </a>
            <a href="women.php" class="btn">
                View Women's Collection
            </a>
        </div>

        <div class="info" style="margin-top: 30px;">
            <strong>💡 Testing the NEW Badge:</strong><br>
            To see the NEW badge in action:
            <ol style="margin: 10px 0 0 20px;">
                <li>Go to <strong>Admin Panel</strong></li>
                <li><strong>Add a new product</strong></li>
                <li>Go to <strong>products.php</strong>, <strong>men.php</strong>, or <strong>women.php</strong></li>
                <li>The new product should have a <strong>pulsing RED "NEW" badge</strong></li>
            </ol>
        </div>
    </div>
</body>
</html>
