<?php 
session_start();
include 'db.php'; 
include_once 'includes/image_helper.php';
?>
<!DOCTYPE html>
<html lang="en">  
  <?php include 'header.php'; ?>
<head>
  <meta charset="UTF-8">
  <title>Women - MTC Clothing</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Simple clean styles matching profile.php */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #fafafa; color: #333; line-height: 1.6; }
    
    /* Page Title Section */
    .page-title { background:#5b6b46; color:#e2e2e2; padding: 20px 0; }
    .page-title h1 { margin:0; font-size: 22px; color: #fff; }
    .page-title p { margin:4px 0 0; color:#fff; font-size:13px; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 30px; }


    .hero { display: grid; grid-template-columns: 1fr 1fr; min-height: 28vh; gap: 0; margin-bottom: 18px; }
    .hero-text { background: linear-gradient(135deg, #5b6b46 0%, #4a5a38 100%); color: #f5f5f5; display: flex; align-items: center; justify-content: center; padding: 20px 24px; position: relative; border-radius: 12px; }
    .hero-text h2 { font-size: 26px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 8px; line-height: 1.2; color: #ffffff; }
    .hero-text p { font-size: 14px; color: #e8e8e8; margin-bottom: 6px; }
    .hero-images { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; align-items: center; justify-items: center; padding: 12px; background: #ffffff; border-radius: 12px; }
    .hero-images img { width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .hero-images img:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 8px 24px rgba(0,0,0,0.15); }

    .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 16px; padding: 0; }
    .product-card-inner { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(91, 107, 70, 0.08); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; display: flex; flex-direction: column; position: relative; }
    .product-card-inner:hover { transform: translateY(-8px); box-shadow: 0 12px 32px rgba(91, 107, 70, 0.15) !important; }
    .product-image { transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .product-card .price { font-size: 14px; font-weight: 700; color: #e44d26; }
    .view-details-btn { margin-top: auto; width: 100%; padding: 8px; background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; border: none; border-radius: 7px; cursor: pointer; font-size: 11px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 2px 6px rgba(91, 107, 70, 0.2); }
    .view-details-btn:hover { background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%); transform: translateY(-2px); }

    .wishlist-btn { position: absolute; top: 8px; right: 8px; background: white; border: none; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.15); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); z-index: 10; }
    .wishlist-btn:hover { transform: scale(1.15); box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3); background: #fff5f5; }
    .wishlist-btn i { font-size: 12px; color: #e74c3c; transition: transform 0.2s; }
    .wishlist-btn.in-wishlist i { font-weight: 900; }

    /* NEW Badge */
    .new-badge {
      position: absolute;
      top: 8px;
      left: 8px;
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
      color: white;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.5px;
      box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
      z-index: 9;
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }

    @media (max-width: 968px) { .hero { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { 
      .products { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important; gap: 10px !important; }
      .new-badge { padding: 6px 14px; font-size: 11px; }
    }
    @media (max-width: 480px) { .products { grid-template-columns: 1fr !important; } }
    /* Page layout helpers */
    .container-main { max-width: 1200px; margin: 0 auto; padding: 40px 30px; }
    .section-header { margin-bottom: 40px; }
    .section-header h2 { margin: 0 0 12px 0; }
    .section-header p { margin: 0; }

    /* Filters row */
    .filters-row { margin-bottom: 40px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
    .filter-btn {
      display: inline-flex;
      align-items: center;
      padding: 11px 24px;
      background: white;
      color: #5b6b46;
      border-radius: 25px;
      text-decoration: none;
      font-size: 15px;
      font-weight: 600;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 2px solid #e0e0e0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .filter-btn:hover { background: #f8f9fa; border-color: #5b6b46; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(91, 107, 70, 0.15); }
    .filter-btn.active { background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; border-color: #5b6b46; box-shadow: 0 4px 16px rgba(91, 107, 70, 0.25); }
    .filter-btn.active:hover { background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%); transform: translateY(-2px); }

    /* Product card small helpers to avoid inline styles */
    .product-card { text-decoration: none; color: inherit; display: block; height: 100%; }
    .product-image-wrap { flex: 0 0 auto; padding: 10px 10px 5px; background: #fafafa; }
    .product-info { padding: 10px 12px 12px; flex-grow: 1; display: flex; flex-direction: column; }
    .product-price { font-size: 14px; font-weight: 700; color: #e44d26; margin: 0 0 8px 0; }
    .no-products { grid-column: 1 / -1; text-align: center; padding: 80px 20px; background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(91, 107, 70, 0.08); }
  </style>
</head>
<body>
  
    
  <!-- Using header.php's built-in notification panel and header structure -->

      <!-- NOTIFICATION DROPDOWN -->
      <div class="notification-panel" id="notifPanel">
          <div class="notification-header">
            Notification
            <a href="#">Mark all as Read</a>
          </div>
          <div class="notification-list">
            <div class="notification-item">
              <strong>Your order is on its way</strong>
              Your order is currently on its way and will be delivered within the expected timeframe.
            </div>
            <div class="notification-item">
              <strong>New Arrival Alert!</strong>
              A new item just arrived in our collection! Feel free to check it out.
            </div>
            <div class="notification-item">
              <strong>Back in Stock</strong>
              Your favorite item just got restocked.
            </div>
          </div>
      </div>

  <?php
  // Get the selected category from the URL
  $selected_subcat = isset($_GET['subcategory']) ? $_GET['subcategory'] : 'all';
  
  // Define available subcategories (should match admin options)
  $subcategories = [
    'all' => 'All Products',
    'Crop Tops' => 'Crop Tops',
    'Dresses' => 'Dresses',
    'Tops' => 'Tops',
    'Pants' => 'Pants',
    'Skirts' => 'Skirts'
  ];
  ?>
  
  <!-- Page Title Section -->
  <section class="page-title">
    <div class="container">
      <h1><i class="fas fa-venus" style="margin-right: 8px;"></i>Women's Collection</h1>
      <p>Explore our curated selection of elegant and trendy women's fashion</p>
    </div>
  </section>
  
  <div class="container" style="padding-top: 40px; padding-bottom: 40px;">
    
    <!-- Subcategory Filters -->
    <div style="margin-bottom: 40px; display: flex; flex-wrap: wrap; gap: 12px;">
      <?php foreach ($subcategories as $subcat => $name): ?>
        <a href="?subcategory=<?php echo urlencode($subcat); ?>"
           class="filter-btn <?php echo ($selected_subcat === $subcat) ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($name); ?>
        </a>
      <?php endforeach; ?>
      
      <style>
        .filter-btn {
          display: inline-flex;
          align-items: center;
          padding: 11px 24px;
          background: white;
          color: #5b6b46;
          border-radius: 25px;
          text-decoration: none;
          font-size: 15px;
          font-weight: 600;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          border: 2px solid #e0e0e0;
          box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .filter-btn:hover {
          background: #f8f9fa;
          border-color: #5b6b46;
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(91, 107, 70, 0.15);
        }
        
        .filter-btn.active {
          background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
          color: white;
          border-color: #5b6b46;
          box-shadow: 0 4px 16px rgba(91, 107, 70, 0.25);
        }
        
        .filter-btn.active:hover {
          background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
          transform: translateY(-2px);
        }
      </style>
    </div>
    <div class="products" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 16px; padding: 0;">
      <?php
      // Get user's wishlist items if logged in
      $wishlist_items = [];
      if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $wishlist_sql = "SELECT product_id FROM wishlist WHERE user_id = ?";
        $wishlist_stmt = $conn->prepare($wishlist_sql);
        $wishlist_stmt->bind_param("i", $user_id);
        $wishlist_stmt->execute();
        $wishlist_result = $wishlist_stmt->get_result();
        while ($w_row = $wishlist_result->fetch_assoc()) {
          $wishlist_items[] = $w_row['product_id'];
        }
        $wishlist_stmt->close();
      }
      
      // Build the SQL query based on selected subcategory
      // ORDER BY id DESC to show newest items first
      $sql = "SELECT * FROM products WHERE category='Women'";
      if ($selected_subcat !== 'all') {
        $sql .= " AND subcategory = '" . $conn->real_escape_string($selected_subcat) . "'";
      }
      $sql .= " ORDER BY id DESC";
      $result = $conn->query($sql);
      if ($result && $result->num_rows > 0):
        while($row = $result->fetch_assoc()):
          $in_wishlist = in_array($row['id'], $wishlist_items);
      ?>
        <div class="product-card" style="text-decoration: none; color: inherit; display: block; height: 100%;">
          <a href="product_detail.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
            <div class="product-card-inner" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(91, 107, 70, 0.08); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; display: flex; flex-direction: column; position: relative;">
              <!-- Wishlist Button -->
              <button class="wishlist-btn <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>" 
                      data-product-id="<?php echo $row['id']; ?>"
                      onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?php echo $row['id']; ?>, this);"
                      title="<?php echo $in_wishlist ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
              </button>
              
            <div style="flex: 0 0 auto; padding: 10px 10px 5px; background: #fafafa; position: relative;">
              <?php
                // Check if product is new (added within last 3 days)
                $isNew = false;
                if (isset($row['created_at'])) {
                  $createdTime = strtotime($row['created_at']);
                  $currentTime = time();
                  $daysDiff = ($currentTime - $createdTime) / (60 * 60 * 24);
                  $isNew = ($daysDiff <= 3);
                }
                
                // Show NEW badge if product is new
                if ($isNew):
              ?>
                <div class="new-badge">NEW</div>
              <?php endif; ?>
              
              <?php echo renderCatalogImage($conn, $row['id'], $row['image'], $row['name'], 'product-image', 'width: 100%; height: auto; max-height: 150px; object-fit: contain; transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); display: block; margin: 0 auto;'); ?>
            </div>
              <div style="padding: 10px 12px 12px; flex-grow: 1; display: flex; flex-direction: column;">
                <h3 style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #333; line-height: 1.3; height: 34px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                  <?php echo htmlspecialchars($row['name']); ?>
                </h3>
              <div class="price" style="font-size: 14px; font-weight: 700; color: #e44d26; margin: 0 0 8px 0;">
                <?php
// Discount logic (same as product_detail.php)
if (!empty($row['discount_enabled']) && $row['discount_enabled'] != '0' && $row['discount_type'] && $row['discount_value'] > 0) {
    $orig = $row['price'];
    if ($row['discount_type'] === 'percent') {
        $final = $orig * (1 - ($row['discount_value'] / 100));
        $desc = $row['discount_value'] . '% OFF';
    } else {
        $final = max($orig - $row['discount_value'], 0);
        $desc = '₱' . number_format($row['discount_value'], 2) . ' OFF';
    }
    echo '<span style="color:#888;text-decoration:line-through;font-size:11px;">₱' . number_format($orig, 2) . '</span> ';
    echo '<span style="color:#e44d26;">₱' . number_format($final, 2) . '</span> ';
    echo '<span style="color:#388e3c;font-size:10px;margin-left:2px;">(' . $desc . ')</span>';
} else {
    echo '₱' . number_format($row['price'], 2);
}
?>
              </div>
                <button type="button" class="view-details-btn" 
                        style="margin-top: auto; width: 100%; padding: 8px; background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; border: none; border-radius: 7px; cursor: pointer; font-size: 11px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 2px 6px rgba(91, 107, 70, 0.2);"
                        onclick="event.preventDefault(); window.location.href='product_detail.php?id=<?php echo $row['id']; ?>'">
                  <i class="fas fa-eye" style="margin-right: 4px;"></i>View Details
                </button>
              </div>
            </div>
          </a>
        </div>
      <?php
        endwhile;
      else:
      ?>
        <div class="no-products" style="grid-column: 1 / -1; text-align: center; padding: 80px 20px; background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(91, 107, 70, 0.08);">
          <i class="fas fa-shopping-bag" style="font-size: 64px; color: #d9e6a7; margin-bottom: 20px;"></i>
          <h3 style="font-size: 24px; color: #5b6b46; margin-bottom: 12px; font-weight: 700;">No Products Available</h3>
          <p style="color: #666; font-size: 16px; line-height: 1.6;">We're working on adding new items to our women's collection.<br>Please check back soon!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Header interactions (notification/menu/cart) are handled globally in header.php -->
  
  <script>
  function toggleWishlist(productId, button) {
    <?php if (!isset($_SESSION['user_id'])): ?>
      alert('Please login to add items to your wishlist');
      window.location.href = 'login.php';
      return;
    <?php endif; ?>
    
    const isInWishlist = button.classList.contains('in-wishlist');
    const action = isInWishlist ? 'remove' : 'add';
    
    // Optimistic UI update
    const icon = button.querySelector('i');
    const originalState = {
      classList: button.className,
      iconClass: icon.className
    };
    
    if (action === 'add') {
      button.classList.add('in-wishlist');
      icon.className = 'fas fa-heart';
      button.title = 'Remove from wishlist';
    } else {
      button.classList.remove('in-wishlist');
      icon.className = 'far fa-heart';
      button.title = 'Add to wishlist';
    }
    
    // Send request to server
    fetch('add_to_wishlist.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'product_id=' + productId + '&action=' + action
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success message briefly
        const message = document.createElement('div');
        message.style.cssText = 'position: fixed; top: 90px; right: 30px; background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(91, 107, 70, 0.3); z-index: 10000; animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-weight: 600; display: flex; align-items: center; gap: 10px;';
        message.innerHTML = '<i class="fas fa-check-circle" style="font-size: 20px;"></i>' + data.message;
        document.body.appendChild(message);
        
        setTimeout(() => {
          message.style.animation = 'slideOut 0.3s ease';
          setTimeout(() => message.remove(), 300);
        }, 2000);
      } else {
        // Revert UI on error
        button.className = originalState.classList;
        icon.className = originalState.iconClass;
        alert(data.message || 'Failed to update wishlist');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      // Revert UI on error
      button.className = originalState.classList;
      icon.className = originalState.iconClass;
      alert('An error occurred. Please try again.');
    });
  }
  
  // Add animation styles
  const wishlistStyle = document.createElement('style');
  wishlistStyle.textContent = `
    @keyframes slideIn {
      from { 
        transform: translateX(120%) scale(0.9);
        opacity: 0;
      }
      to { 
        transform: translateX(0) scale(1);
        opacity: 1;
      }
    }
    @keyframes slideOut {
      from { 
        transform: translateX(0) scale(1);
        opacity: 1;
      }
      to { 
        transform: translateX(120%) scale(0.9);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(wishlistStyle);
  </script>
</body>
</html> 