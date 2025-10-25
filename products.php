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
  <title>Products - MTC Clothing</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%); color: #333; line-height: 1.6; }

    /* Navigation Buttons with Dropdown */
    .nav-container { max-width: 1200px; margin: 0 auto; padding: 30px 30px 20px; }
    .nav-buttons { display: flex; gap: 15px; flex-wrap: wrap; }
    
    .nav-btn-wrapper { position: relative; }
    
    .nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 28px;
      background: white;
      color: #5b6b46;
      border-radius: 30px;
      text-decoration: none;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 2px solid #e0e0e0;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
      cursor: pointer;
    }
    
    .nav-btn:hover {
      background: #f8f9fa;
      border-color: #5b6b46;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(91, 107, 70, 0.15);
    }
    
    .nav-btn.active {
      background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
      color: white;
      border-color: #5b6b46;
      box-shadow: 0 5px 18px rgba(91, 107, 70, 0.3);
    }
    
    .nav-btn i.fa-chevron-down {
      font-size: 12px;
      transition: transform 0.3s;
    }
    
    .nav-btn-wrapper:hover .nav-btn i.fa-chevron-down,
    .nav-btn-wrapper.show i.fa-chevron-down {
      transform: rotate(180deg);
    }
    
    /* Dropdown Menu */
    .dropdown-menu {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      padding: 8px;
      min-width: 200px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1000;
      border: 1px solid #e0e0e0;
    }
    
    .nav-btn-wrapper:hover .dropdown-menu,
    .nav-btn-wrapper.show .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .dropdown-item {
      display: block;
      padding: 10px 16px;
      color: #5b6b46;
      text-decoration: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s;
    }
    
    .dropdown-item:hover {
      background: linear-gradient(135deg, #f0f7e8 0%, #e8f0dc 100%);
      color: #4a5a38;
      transform: translateX(4px);
    }
    
    .dropdown-item.active {
      background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
      color: white;
      font-weight: 600;
    }
    
    .dropdown-divider {
      height: 1px;
      background: #e0e0e0;
      margin: 6px 0;
    }

    
    /* Page Header */
    .page-header {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px 30px 30px;
    }
    
    .page-header h1 {
      color: #5b6b46;
      font-size: 38px;
      font-weight: 700;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .page-header p {
      color: #666;
      font-size: 16px;
      line-height: 1.6;
    }
    
    /* Products Grid */
    .products-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 30px 40px;
    }
    
    .section-title {
      color: #5b6b46;
      font-size: 28px;
      font-weight: 700;
      margin: 30px 0 20px;
      padding-bottom: 12px;
      border-bottom: 3px solid #d9e6a7;
    }
    
    .products {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
      gap: 16px;
      padding: 0;
    }
    
    .product-card-inner {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(91, 107, 70, 0.08);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      height: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
    }
    
    .product-card-inner:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 32px rgba(91, 107, 70, 0.15) !important;
    }
    
    .product-image { transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
    .product-card .price { font-size: 14px; font-weight: 700; color: #e44d26; }
    
    .view-details-btn {
      margin-top: auto;
      width: 100%;
      padding: 8px;
      background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
      color: white;
      border: none;
      border-radius: 7px;
      cursor: pointer;
      font-size: 11px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 2px 6px rgba(91, 107, 70, 0.2);
    }
    
    .view-details-btn:hover {
      background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
      transform: translateY(-2px);
    }
    
    .wishlist-btn {
      position: absolute;
      top: 8px;
      right: 8px;
      background: white;
      border: none;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 10;
    }
    
    .wishlist-btn:hover {
      transform: scale(1.15);
      box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
      background: #fff5f5;
    }
    
    .wishlist-btn i { font-size: 12px; color: #e74c3c; transition: transform 0.2s; }
    .wishlist-btn.in-wishlist i { font-weight: 900; }
    
    .no-products {
      grid-column: 1 / -1;
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 16px rgba(91, 107, 70, 0.08);
    }
    
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
    
    @media (max-width: 768px) {
      .nav-buttons { flex-direction: column; }
      .products { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important; gap: 10px !important; }
      .new-badge {
        padding: 6px 14px;
        font-size: 11px;
      }
    }
    
    @media (max-width: 480px) {
      .products { grid-template-columns: 1fr !important; }
    }
  </style>
</head>
<body>

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
      <div class="notification-item">
        <strong>Reminders</strong>
        Don’t miss out! Complete your order before it’s gone.
      </div>
      <div class="notification-item">
        <strong>New arrival alert!</strong>
        Check out our latest addition—you might just find your new favorite.
      </div>
    </div>
  </div>
  </div>
  </header>

<?php
  // Get filter parameters
  $category = isset($_GET['category']) ? $_GET['category'] : 'all';
  $subcategory = isset($_GET['subcategory']) ? $_GET['subcategory'] : 'all';
  
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
  
  // Define subcategories for Men (always visible)
  $men_subcats = [
    'Shirts' => 'Shirts',
    'Pants' => 'Pants',
    'Shorts' => 'Shorts',
    'Jackets' => 'Jackets',
    'Hoodies' => 'Hoodies',
    'Polo' => 'Polo',
    'T-Shirts' => 'T-Shirts'
  ];
  
  // Define subcategories for Women (always visible)
  $women_subcats = [
    'Dresses' => 'Dresses',
    'Tops' => 'Tops',
    'Blouses' => 'Blouses',
    'Skirts' => 'Skirts',
    'Pants' => 'Pants',
    'Shorts' => 'Shorts',
    'Crop Tops' => 'Crop Tops'
  ];
?>

  <!-- Navigation Buttons -->
  <div class="nav-container">
    <div class="nav-buttons">
      <!-- All Products Button -->
      <div class="nav-btn-wrapper">
        <a href="?category=all" class="nav-btn <?php echo ($category === 'all') ? 'active' : ''; ?>">
          <i class="fas fa-th-large"></i>
          All Products
        </a>
      </div>
      
      <!-- Men Button with Dropdown -->
      <div class="nav-btn-wrapper">
        <a href="?category=Men" class="nav-btn <?php echo ($category === 'Men') ? 'active' : ''; ?>">
          <i class="fas fa-male"></i>
          Men
          <i class="fas fa-chevron-down"></i>
        </a>
        <div class="dropdown-menu">
          <a href="?category=Men&subcategory=all" class="dropdown-item <?php echo ($category === 'Men' && $subcategory === 'all') ? 'active' : ''; ?>">
            All Men's Items
          </a>
          <div class="dropdown-divider"></div>
          <?php foreach ($men_subcats as $key => $label): ?>
            <a href="?category=Men&subcategory=<?php echo urlencode($key); ?>" 
               class="dropdown-item <?php echo ($category === 'Men' && $subcategory === $key) ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($label); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- Women Button with Dropdown -->
      <div class="nav-btn-wrapper">
        <a href="?category=Women" class="nav-btn <?php echo ($category === 'Women') ? 'active' : ''; ?>">
          <i class="fas fa-female"></i>
          Women
          <i class="fas fa-chevron-down"></i>
        </a>
        <div class="dropdown-menu">
          <a href="?category=Women&subcategory=all" class="dropdown-item <?php echo ($category === 'Women' && $subcategory === 'all') ? 'active' : ''; ?>">
            All Women's Items
          </a>
          <div class="dropdown-divider"></div>
          <?php foreach ($women_subcats as $key => $label): ?>
            <a href="?category=Women&subcategory=<?php echo urlencode($key); ?>" 
               class="dropdown-item <?php echo ($category === 'Women' && $subcategory === $key) ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($label); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <h1>
      <i class="fas fa-shopping-bag"></i>
      <?php 
        if ($category === 'Men') {
          echo "Men's Collection";
          if ($subcategory !== 'all') {
            echo " - " . htmlspecialchars(ucfirst($subcategory));
          }
        } elseif ($category === 'Women') {
          echo "Women's Collection";
          if ($subcategory !== 'all') {
            echo " - " . htmlspecialchars(ucfirst($subcategory));
          }
        } else {
          echo "All Products";
        }
      ?>
    </h1>
    <p>
      <?php
        if ($category === 'Men') {
          echo "Discover our men's collection";
          if ($subcategory !== 'all') {
            echo " in " . htmlspecialchars(ucfirst($subcategory));
          }
        } elseif ($category === 'Women') {
          echo "Explore our women's collection";
          if ($subcategory !== 'all') {
            echo " in " . htmlspecialchars(ucfirst($subcategory));
          }
        } else {
          echo "Browse our entire product catalog across all categories";
        }
      ?>
    </p>
  </div>

  <!-- Products Container -->
  <div class="products-container">
    <div class="products">
      <?php
        // Build SQL query based on filters
        if ($category === 'all') {
          $sql = "SELECT * FROM products ORDER BY id DESC";
        } else {
          $sql = "SELECT * FROM products WHERE category = '" . $conn->real_escape_string($category) . "'";
          if ($subcategory !== 'all') {
            $sql .= " AND subcategory = '" . $conn->real_escape_string($subcategory) . "'";
          }
          $sql .= " ORDER BY id DESC";
        }
        
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
// Discount logic
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
          <h3 style="font-size: 24px; color: #5b6b46; margin-bottom: 12px; font-weight: 700;">No Products Found</h3>
          <p style="color: #666; font-size: 16px; line-height: 1.6;">
            <?php
              if ($category !== 'all' && $subcategory !== 'all') {
                echo "No products available in this subcategory.";
              } elseif ($category !== 'all') {
                echo "No products available in this category.";
              } else {
                echo "No products available at the moment.";
              }
            ?>
          </p>
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
        message.style.cssText = 'position: fixed; top: 80px; right: 20px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); z-index: 10000; animation: slideIn 0.3s ease;';
        message.textContent = data.message;
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
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
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
