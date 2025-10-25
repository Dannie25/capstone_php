<?php
session_start();
include 'db.php';
include_once 'includes/image_helper.php';

// Force show badge for testing
$forceShowBadge = true;
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<head>
    <meta charset="UTF-8">
    <title>Test NEW Badge</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
        }
        
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }
        
        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 16px;
            margin-top: 30px;
        }
        
        .product-card-inner {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(91, 107, 70, 0.08);
            transition: all 0.4s;
        }
        
        .product-card-inner:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(91, 107, 70, 0.15);
        }
        
        /* NEW Badge with Animation */
        .new-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            z-index: 5;
            animation: pulse 2s infinite;
            border: 2px solid white;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.05);
                box-shadow: 0 6px 20px rgba(255, 107, 107, 0.6);
            }
        }
        
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 style="color: #5b6b46; margin-bottom: 10px;">
            <i class="fas fa-flask"></i> NEW Badge Animation Test
        </h1>
        
        <div class="info-box">
            <strong>⚠️ Test Mode:</strong> All products below will show the NEW badge with pulse animation.
            Watch the red badge - dapat umiikot/tumitibok (pulse effect).
        </div>
        
        <h2 style="color: #5b6b46;">Sample Products with NEW Badge:</h2>
        
        <div class="products">
            <?php
            // Get first 6 products for testing
            $sql = "SELECT * FROM products LIMIT 6";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
                <div class="product-card">
                    <div class="product-card-inner">
                        <div style="flex: 0 0 auto; padding: 10px 10px 5px; background: #fafafa; position: relative;">
                            <!-- FORCE SHOW NEW BADGE FOR TESTING -->
                            <div class="new-badge">NEW</div>
                            
                            <?php echo renderCatalogImage($conn, $row['id'], $row['image'], $row['name'], 'product-image', 'width: 100%; height: auto; max-height: 150px; object-fit: contain; display: block; margin: 0 auto;'); ?>
                        </div>
                        <div style="padding: 10px 12px 12px;">
                            <h3 style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #333;">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </h3>
                            <div style="font-size: 14px; font-weight: 700; color: #e44d26;">
                                ₱<?php echo number_format($row['price'], 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <p>No products found. Please add products in admin panel.</p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 40px; padding: 20px; background: #e7f3ff; border-radius: 8px;">
            <h3 style="color: #2196F3; margin-top: 0;">✓ Animation Checklist:</h3>
            <ul style="line-height: 1.8;">
                <li>✅ NEW badge should be visible in the center of each image</li>
                <li>✅ Badge should have RED gradient background</li>
                <li>✅ Badge should PULSE (grow and shrink) continuously</li>
                <li>✅ Badge should have WHITE border</li>
                <li>✅ Badge should have GLOWING shadow</li>
            </ul>
            
            <p style="margin-top: 20px;">
                <strong>Kung hindi pa rin kita ang animation:</strong><br>
                1. Try hard refresh: <code>Ctrl + Shift + R</code><br>
                2. Check browser console (F12) for errors<br>
                3. Try different browser (Chrome, Firefox, Edge)
            </p>
        </div>
        
        <p style="margin-top: 30px;">
            <a href="products.php" style="color: #5b6b46; font-weight: 600; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </p>
    </div>
</body>
</html>
