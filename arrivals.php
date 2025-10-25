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
  <title>New Arrivals - MTC Clothing</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Simple clean styles matching profile.php */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #fafafa; color: #333; line-height: 1.6; min-height: 100vh; }
    
    /* Page Title Section */
    .page-title { background:#5b6b46; color:#e2e2e2; padding: 20px 0; }
    .page-title h1 { margin:0; font-size: 22px; color: #fff; }
    .page-title p { margin:4px 0 0; color:#fff; font-size:13px; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 30px; }

    /* Product card / grid styles */
    .products { display:grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap:16px; padding:0; }

    .product-card { text-decoration:none; color:inherit; display:block; height:100%; }
    .product-card-inner { background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(91,107,70,0.08); transition:all 0.4s cubic-bezier(0.4,0,0.2,1); height:100%; display:flex; flex-direction:column; position:relative; }

    .product-card-inner:hover { transform: translateY(-8px); box-shadow: 0 12px 32px rgba(91, 107, 70, 0.15) !important; }
    .product-card-inner:hover .product-image { transform: scale(1.05); }

    .product-image { width:100%; height:auto; max-height:150px; object-fit:contain; transition: transform 0.5s cubic-bezier(0.4,0,0.2,1); display:block; margin:0 auto; }

    .view-details-btn { margin-top:auto; width:100%; padding:8px; background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color:white; border:none; border-radius:7px; cursor:pointer; font-size:11px; font-weight:600; transition:all 0.3s ease; box-shadow:0 2px 6px rgba(91,107,70,0.2); }
    .view-details-btn:hover { background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%) !important; transform: translateY(-2px); box-shadow: 0 5px 16px rgba(91, 107, 70, 0.3) !important; }

    /* Wishlist (floating heart) */
    .wishlist-btn { position:absolute; top:8px; right:8px; background:white; border:none; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,0.15); transition:all 0.3s cubic-bezier(0.4,0,0.2,1); z-index:10; }
    .wishlist-btn:hover { transform:scale(1.15); box-shadow:0 5px 15px rgba(231,76,60,0.3); background:#fff5f5; }
    .wishlist-btn i { font-size:14px; color:#e74c3c; transition:transform 0.2s; }
    .wishlist-btn:hover i { transform:scale(1.1); }
    .wishlist-btn.in-wishlist i { font-weight:900; }
    
    /* NEW Badge */
    .new-badge { position:absolute; top:8px; left:8px; background:linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color:white; padding:4px 10px; border-radius:12px; font-size:10px; font-weight:700; letter-spacing:0.5px; box-shadow:0 2px 8px rgba(255,107,107,0.4); z-index:9; animation:pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { transform:scale(1); } 50% { transform:scale(1.05); } }

    /* Discount / price styling */
    .price { font-size:14px; font-weight:700; color:#e44d26; margin:0 0 8px 0; }

    /* No products placeholder */
    .no-products { grid-column: 1 / -1; text-align:center; padding:80px 20px; background:white; border-radius:16px; box-shadow:0 4px 16px rgba(91,107,70,0.08); }

    /* Responsive adjustments */
    @media (max-width: 968px) {
      .products { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap:14px; }
    }
    @media (max-width: 768px) {
      .products { grid-template-columns: repeat(auto-fill, minmax(145px, 1fr)) !important; gap:12px !important; }
    }
    @media (max-width: 480px) { .products { grid-template-columns: 1fr !important; } }
  </style>
</head>
<body>

  <!-- Page Title Section -->
  <section class="page-title">
    <div class="container">
      <h1><i class="fas fa-star" style="margin-right: 8px;"></i>New Arrivals</h1>
      <p>Check out our latest products and newest additions</p>
    </div>
  </section>

  <div class="container" style="padding-top: 40px; padding-bottom: 40px;">
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
        
        // Show the most recently added products. If you have a created_at column, prefer ORDER BY created_at DESC
        $sql = "SELECT * FROM products ORDER BY id DESC LIMIT 12";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0):
          while($row = $result->fetch_assoc()):
            $in_wishlist = in_array($row['id'], $wishlist_items);
      ?>
        <div class="product-card" style="text-decoration: none; color: inherit; display: block; height: 100%;">
          <a href="product_detail.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
            <div class="product-card-inner" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(91, 107, 70, 0.08); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; display: flex; flex-direction: column; position: relative;">
              <!-- NEW Badge -->
              <div class="new-badge">NEW</div>
              
              <!-- Wishlist Button -->
              <button class="wishlist-btn <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>" 
                      data-product-id="<?php echo $row['id']; ?>"
                      onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?php echo $row['id']; ?>, this);"
                      title="<?php echo $in_wishlist ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                <i class="<?php echo $in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
              </button>
              
            <div style="flex: 0 0 auto; padding: 10px 10px 5px; background: #fafafa;">
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
          <h3 style="font-size: 24px; color: #5b6b46; margin-bottom: 12px; font-weight: 700;">No New Arrivals</h3>
          <p style="color: #666; font-size: 16px; line-height: 1.6;">Check back soon for our latest products!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  function toggleWishlist(productId, button) {
    <?php if (!isset($_SESSION['user_id'])): ?>
      alert('Please login to add items to your wishlist');
      window.location.href = 'login.php';
      return;
    <?php endif; ?>
    
    const isInWishlist = button.classList.contains('in-wishlist');
    const action = isInWishlist ? 'remove' : 'add';
    
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
        const message = document.createElement('div');
        message.style.cssText = 'position: fixed; top: 90px; right: 30px; background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(91, 107, 70, 0.3); z-index: 10000; animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-weight: 600; display: flex; align-items: center; gap: 10px;';
        message.innerHTML = '<i class="fas fa-check-circle" style="font-size: 20px;"></i>' + data.message;
        document.body.appendChild(message);
        
        setTimeout(() => {
          message.style.animation = 'slideOut 0.3s ease';
          setTimeout(() => message.remove(), 300);
        }, 2000);
      } else {
        button.className = originalState.classList;
        icon.className = originalState.iconClass;
        alert(data.message || 'Failed to update wishlist');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      button.className = originalState.classList;
      icon.className = originalState.iconClass;
      alert('An error occurred. Please try again.');
    });
  }
  
  const wishlistStyle = document.createElement('style');
  wishlistStyle.textContent = `
    @keyframes slideIn {
      from { 
        transform: translateX(100%) scale(0.9); 
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
        transform: translateX(100%) scale(0.9); 
        opacity: 0; 
      }
    }
  `;
  document.head.appendChild(wishlistStyle);
  </script>
  
  <script>
    const notifBtn = document.getElementById("notifBtn");
    const notifPanel = document.getElementById("notifPanel");
    const menuBtn = document.getElementById("menuBtn");
    const menuPanel = document.getElementById("menuPanel");

    if (notifBtn && notifPanel) {
      notifBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        notifPanel.style.display = notifPanel.style.display === "block" ? "none" : "block";
        if (menuPanel) menuPanel.style.display = "none";
      });
    }

    if (menuBtn && menuPanel) {
      menuBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        menuPanel.style.display = menuPanel.style.display === "block" ? "none" : "block";
        if (notifPanel) notifPanel.style.display = "none";
      });
    }

    window.addEventListener("click", () => {
      if (notifPanel) notifPanel.style.display = "none";
      if (menuPanel) menuPanel.style.display = "none";
    });
  </script>
  
  <script>
    // Function to update cart count
    function updateCartCount() {
      fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
          const cartCount = document.getElementById('cartCount');
          if (data.count > 0) {
            if (cartCount) {
              cartCount.textContent = data.count;
              cartCount.style.display = 'flex';
            }
          } else {
            if (cartCount) cartCount.style.display = 'none';
          }
        });
    }

    // Function to handle Add to Cart
    function addToCart(productId, quantity = 1) {
      console.log('Adding to cart:', productId, quantity);
      fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          // Update cart count
          const cartCount = document.getElementById('cartCount');
          if (cartCount && data.cart_count !== undefined) {
            cartCount.textContent = data.cart_count;
            cartCount.style.display = 'flex';
          }
          // Show success message
          alert('Item added to cart!');
        } else {
          if (data.message === 'Please log in to add items to cart') {
            // Redirect to login if not logged in
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
          } else {
            alert(data.message || 'Failed to add item to cart');
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    }

    // Add click handler for cart icon
    const cartIcon = document.getElementById('cartIcon');
    if (cartIcon) {
      cartIcon.addEventListener('click', function() {
        window.location.href = 'cart.php';
      });
    }

    // Update cart count when page loads
    document.addEventListener('DOMContentLoaded', function() {
      updateCartCount();
    });

    // Additional toggle handlers (guarded)
    const notifBtn2 = document.getElementById('notifBtn');
    if (notifBtn2) {
      notifBtn2.addEventListener('click', function(e) {
        e.stopPropagation();
        const panel = document.getElementById('notifPanel');
        const menuPanel = document.getElementById('menuPanel');
        if (menuPanel && menuPanel.style.display === 'block') menuPanel.style.display = 'none';
        if (panel) panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
      });
    }

    const menuBtn2 = document.getElementById('menuBtn');
    if (menuBtn2) {
      menuBtn2.addEventListener('click', function(e) {
        e.stopPropagation();
        const panel = document.getElementById('menuPanel');
        const notifPanel = document.getElementById('notifPanel');
        if (notifPanel && notifPanel.style.display === 'block') notifPanel.style.display = 'none';
        if (panel) panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
      });
    }

    document.addEventListener('click', function() {
      const np = document.getElementById('notifPanel');
      const mp = document.getElementById('menuPanel');
      if (np) np.style.display = 'none';
      if (mp) mp.style.display = 'none';
    });

    document.querySelectorAll('.notification-panel, .menu-panel').forEach(panel => {
      panel.addEventListener('click', function(e) { e.stopPropagation(); });
    });
  </script>
</body>
</html>
