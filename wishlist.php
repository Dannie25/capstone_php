<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get wishlist items with product details
$sql = "SELECT w.id as wishlist_id, w.created_at, p.* 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id  
        WHERE w.user_id = ? 
        ORDER BY w.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - MTC Clothing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fafafa;
            margin: 0;
            padding-top: 0;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 15px 20px;
        }
        .page-header {
            padding: 12px 0;
            border-bottom: 2px solid #d9e6a7;
            margin-bottom: 20px;
        }
        .page-header h1 {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
        }
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 12px 0;
        }
        .wishlist-card {
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .wishlist-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .wishlist-card img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            background: #f8f8f8;
            padding: 12px;
        }
        .wishlist-card .card-body {
            padding: 12px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .wishlist-card h3 {
            font-size: 14px;
            margin: 0 0 8px 0;
            color: #333;
            height: 36px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .wishlist-card .price {
            font-size: 16px;
            font-weight: bold;
            color: #e44d26;
            margin: 8px 0;
        }
        .wishlist-card .buttons {
            display: flex;
            gap: 6px;
            margin-top: auto;
        }
        .wishlist-card .btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-view {
            background: #4CAF50;
            color: white;
        }
        .btn-view:hover {
            background: #45a049;
        }
        .btn-remove {
            background: #e74c3c;
            color: white;
            flex: 0 0 auto;
            padding: 8px 12px;
        }
        .btn-remove:hover {
            background: #c0392b;
        }
        .empty-wishlist {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        .empty-wishlist i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
        }
        .empty-wishlist h2 {
            color: #333;
            margin-bottom: 8px;
            font-size: 1.5rem;
        }
        .empty-wishlist p {
            margin-bottom: 20px;
            font-size: 14px;
        }
        .btn-shop {
            display: inline-block;
            padding: 10px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s ease;
            font-size: 14px;
        }
        .btn-shop:hover {
            background: #45a049;
        }
        .discount-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #e74c3c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-heart" style="color: #e74c3c;"></i> My Wishlist</h1>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="wishlist-grid">
                <?php while ($item = $result->fetch_assoc()): ?>
                    <div class="wishlist-card" data-product-id="<?php echo $item['id']; ?>">
                        <?php 
                        // Check if product has discount
                        if (!empty($item['discount_enabled']) && $item['discount_enabled'] != '0' && $item['discount_type'] && $item['discount_value'] > 0):
                            $discount_text = $item['discount_type'] === 'percent' ? $item['discount_value'] . '% OFF' : '₱' . number_format($item['discount_value'], 2) . ' OFF';
                        ?>
                            <div class="discount-badge"><?php echo $discount_text; ?></div>
                        <?php endif; ?>
                        
                        <a href="product_detail.php?id=<?php echo $item['id']; ?>" style="text-decoration: none;">
                            <img src="<?php 
                                $imgPath = file_exists('img/' . $item['image']) ? 'img/' . $item['image'] : $item['image'];
                                echo htmlspecialchars($imgPath); 
                            ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </a>
                        
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="price">
                                <?php
                                if (!empty($item['discount_enabled']) && $item['discount_enabled'] != '0' && $item['discount_type'] && $item['discount_value'] > 0) {
                                    $orig = $item['price'];
                                    if ($item['discount_type'] === 'percent') {
                                        $final = $orig * (1 - ($item['discount_value'] / 100));
                                    } else {
                                        $final = max($orig - $item['discount_value'], 0);
                                    }
                                    echo '<span style="color:#888;text-decoration:line-through;font-size:13px;">₱' . number_format($orig, 2) . '</span> ';
                                    echo '<span style="color:#e44d26;">₱' . number_format($final, 2) . '</span>';
                                } else {
                                    echo '₱' . number_format($item['price'], 2);
                                }
                                ?>
                            </div>
                            
                            <div class="buttons">
                                <button class="btn btn-view" onclick="window.location.href='product_detail.php?id=<?php echo $item['id']; ?>'">
                                    View Details
                                </button>
                                <button class="btn btn-remove" onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <i class="far fa-heart"></i>
                <h2>Your Wishlist is Empty</h2>
                <p>Start adding products you love to your wishlist!</p>
                <a href="women.php" class="btn-shop">Browse Women's Collection</a>
                <a href="men.php" class="btn-shop" style="margin-left: 8px;">Browse Men's Collection</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function removeFromWishlist(productId) {
        if (!confirm('Remove this item from your wishlist?')) {
            return;
        }
        
        fetch('add_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&action=remove'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the card from the page
                const card = document.querySelector(`.wishlist-card[data-product-id="${productId}"]`);
                if (card) {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        card.remove();
                        // Check if wishlist is now empty
                        const grid = document.querySelector('.wishlist-grid');
                        if (grid && grid.children.length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
                
                // Show success message
                alert(data.message);
            } else {
                alert(data.message || 'Failed to remove item');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    </script>
</body>
</html>
