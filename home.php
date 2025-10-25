<!-- filepath: C:\xampp\htdocs\capstone_php\home.php -->
<?php
// Start the session
session_start();

// Include database connection and CMS functions
include 'db.php';

// Add this function to fetch content from the database
function get_content($key, $default = '') {
    global $conn;
    $stmt = $conn->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->bind_result($value);
    if ($stmt->fetch()) {
        $stmt->close();
        return $value;
    }
    $stmt->close();
    return $default;
}

// Check if user is logged in
$user_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$user_name = $user_logged_in ? $_SESSION['user_name'] : '';


// Set default values if empty

?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MTC Clothing</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: Arial, sans-serif;
      background: #fafafa;
      color: #333;
      line-height: 1.6;
    }
    
    /* Page Title Section */
    .page-title { background:#5b6b46; color:#e2e2e2; padding: 20px 0; }
    .page-title h1 { margin:0; font-size: 22px; color: #fff; }
    .page-title p { margin:4px 0 0; color:#fff; font-size:13px; }
    .container { max-width: 1200px; margin: 0 auto; padding: 0 30px; }
    
      overflow: hidden;
      font-size: 12px;
    }
    
    .notification-header {
      padding: 8px 10px; /* reduced header padding */
      background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
      color: white;
      font-weight: 600;
      font-size: 13px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    /* Scrollable notification list */
    .notification-list {
      max-height: 200px; /* more compact height */
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      background: #fff;
    }

    /* Custom scrollbar (WebKit) */
  .notification-list::-webkit-scrollbar { width: 6px; }
  .notification-list::-webkit-scrollbar-track { background: transparent; }
  .notification-list::-webkit-scrollbar-thumb { background: linear-gradient(135deg,#d9e6a7 0%,#c8d99a 100%); border-radius: 6px; }

    /* Firefox scrollbar */
    .notification-list { scrollbar-width: thin; scrollbar-color: #d9e6a7 transparent; }
    
    .notification-header a {
      font-size: 12px;
      text-decoration: none;
      color: white;
      opacity: 0.9;
      transition: opacity 0.2s;
    }
    
    .notification-header a:hover {
      opacity: 1;
      text-decoration: underline;
    }
    
    .notification-item {
      padding: 6px 8px; /* even smaller padding */
      border-bottom: 1px solid #fafafa;
      transition: background 0.15s;
      font-size: 12px;
      line-height: 1.25;
    }
    
    .notification-item:hover {
      background: #f8f9fa;
    }
    
    .notification-item:last-child {
      border-bottom: none;
    }
    
    .notification-item strong {
      display: block;
      margin-bottom: 1px;
      color: #5b6b46;
      font-size: 12px;
      font-weight: 700;
    }
    
    .notification-item p {
      font-size: 12px;
      color: #666;
      line-height: 1.4;
    }
    .hero {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 65vh;
      gap: 0;
    }
    
    .hero-text {
      background: linear-gradient(135deg, #5b6b46 0%, #4a5a38 100%);
      color: #f5f5f5;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 30px 35px;
      position: relative;
    }
    
    .hero-text::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(circle at top right, rgba(217, 230, 167, 0.1) 0%, transparent 60%);
      pointer-events: none;
    }
    
    .hero-text > div {
      max-width: 600px;
      position: relative;
      z-index: 1;
    }
    
    .hero-text h2 {
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -0.5px;
      margin-bottom: 12px;
      line-height: 1.2;
      color: #ffffff;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .hero-text p {
      font-size: 14px;
      line-height: 1.5;
      color: #e8e8e8;
      margin-bottom: 6px;
    }
    
    .hero-images {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      align-items: center;
      justify-items: center;
      padding: 15px;
      background: #ffffff;
    }
    
    .hero-images img {
      width: 100%;
      height: auto;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .hero-images img:hover {
      transform: translateY(-5px) scale(1.02);
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    
    @media (max-width: 968px) {
      .hero {
        grid-template-columns: 1fr;
      }
      
      .hero-text h2 {
        font-size: 28px;
      }
      
      .hero-text {
        padding: 40px 30px;
      }
    }
    
    /* Hero Buttons */
    .hero-btn {
      display: inline-flex;
      align-items: center;
      padding: 8px 20px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.12);
      border: 2px solid transparent;
    }
    
    .hero-btn-primary {
      background: linear-gradient(135deg, #d9e6a7 0%, #c8d99a 100%);
      color: #5b6b46;
      border-color: #d9e6a7;
    }
    
    .hero-btn-primary:hover {
      background: #ffffff;
      color: #5b6b46;
      border-color: #d9e6a7;
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(217, 230, 167, 0.4);
    }
    
    .hero-btn-secondary {
      background: rgba(255, 255, 255, 0.15);
      color: #ffffff;
      border-color: rgba(255, 255, 255, 0.3);
      backdrop-filter: blur(10px);
    }
    
    .hero-btn-secondary:hover {
      background: #ffffff;
      color: #5b6b46;
      border-color: #ffffff;
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
    }
  </style>
</head>
<body>
  <!-- NAVIGATION -->
  
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

  <!-- HERO SECTION -->
  <section class="hero">
    <div class="hero-text">
      <div>
        <h2><?php echo get_content('hero_title', 'MASTERING TAILORING & FABRICS,<br>WHERE QUALITY MEETS CRAFT'); ?></h2>
        <p><?php echo get_content('hero_subtitle', 'At MTC Clothing, we specialize in high-quality tailoring and fabrics, combining skilled craftsmanship with attention to detail. Our focus is to deliver exceptional products and services that exceed customer expectations.'); ?></p>

        <!-- Sign In and Log In Buttons - Only show if user is not logged in -->
        <?php if (!$user_logged_in): ?>
        <div style="margin-top: 18px; display: flex; gap: 10px; flex-wrap: wrap;">
          <a href="signup.php" class="hero-btn hero-btn-primary">
            <i class="fas fa-user-plus" style="margin-right: 5px;"></i>Sign Up
          </a>
          <a href="login.php" class="hero-btn hero-btn-secondary">
            <i class="fas fa-sign-in-alt" style="margin-right: 5px;"></i>Log In
          </a>
        </div>
        <?php else: ?>
        <div style="margin-top: 18px;">
          <div style="background: rgba(217, 230, 167, 0.15); padding: 12px; border-radius: 8px; border-left: 3px solid #d9e6a7;">
            <p style="font-size: 16px; font-weight: 700; color: #ffffff; margin-bottom: 5px;">
              <i class="fas fa-user-circle" style="margin-right: 6px;"></i>Welcome back, <?php echo htmlspecialchars($user_name); ?>!
            </p>
            <p style="font-size: 13px; color: #e8e8e8; margin-top: 5px; line-height: 1.4;">
              Ready to explore our latest collection? <a href="products.php" style="color: #d9e6a7; text-decoration: underline;">Start shopping</a>
            </p>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="hero-images">
      <img src="hm2.png" alt="">
      <img src="hp1.png" alt="">
      <img src="hm3.png" alt="">
      <img src="hm4.png" alt="">
    </div>
  </section>

  <!-- FEATURED PHOTOS SECTION -->
  <section class="featured-photos" style="padding: 35px 20px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
    <div class="container" style="max-width: 1200px; margin: 0 auto;">
      <h2 style="text-align: center; margin-bottom: 8px; color: #5b6b46; font-size: 28px; font-weight: 700;"><?php echo get_content('featured_collections_title', 'Featured Collections'); ?></h2>
      <p style="text-align: center; margin-bottom: 25px; color: #666; max-width: 600px; margin-left: auto; margin-right: auto; font-size: 14px; line-height: 1.5;"><?php echo get_content('new_arrivals_subtitle', 'Discover the latest trends and elevate your style'); ?></p>

      <style>
        .photo-grid {
          display: grid;
          grid-template-columns: repeat(1, 1fr);
          gap: 15px;
          margin-bottom: 20px;
        }
        
        @media (min-width: 600px) {
          .photo-grid {
            grid-template-columns: repeat(2, 1fr);
          }
        }
        
        @media (min-width: 900px) {
          .photo-grid {
            grid-template-columns: repeat(3, 1fr);
          }
        }
        
        .photo-item {
          position: relative;
          border-radius: 10px;
          overflow: hidden;
          box-shadow: 0 4px 16px rgba(91, 107, 70, 0.1);
          transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
          height: 280px;
        }
        
        .photo-item:hover {
          transform: translateY(-8px);
          box-shadow: 0 16px 40px rgba(91, 107, 70, 0.2);
        }
        
        .photo-item img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .photo-item:hover img {
          transform: scale(1.08);
        }
        
        .photo-caption {
          position: absolute;
          bottom: 0;
          left: 0;
          right: 0;
          background: linear-gradient(to top, rgba(91, 107, 70, 0.95) 0%, rgba(91, 107, 70, 0.7) 50%, transparent 100%);
          color: white;
          padding: 18px 15px;
          text-align: center;
          transition: all 0.3s ease;
        }
        
        .photo-item:hover .photo-caption {
          background: linear-gradient(to top, rgba(91, 107, 70, 0.98) 0%, rgba(91, 107, 70, 0.8) 60%, transparent 100%);
          padding-bottom: 22px;
        }
        
        .photo-caption h3 {
          margin: 0 0 5px 0;
          font-size: 18px;
          font-weight: 700;
          letter-spacing: -0.5px;
        }
        
        .photo-caption p {
          margin: 0;
          font-size: 12px;
          opacity: 0.95;
          font-weight: 500;
        }
      </style>
      
      <div class="photo-grid">
        <!-- Men's Collection -->
        <a href="men.php" class="photo-item" style="display: block; position: relative; color: inherit; text-decoration: none;">
          <img src="fp3.jpg" alt="Men's Collection" style="object-position: center 20%;">
          <div class="photo-caption">
           <h3><?php echo get_content('mens_collection_title', "Men's Collection"); ?></h3>

            <p><?php echo get_content('mens_collection_subtitle', 'Trendy styles for men'); ?></p>

          </div>
        </a>
        
        <!-- New Arrivals -->
        <a href="arrivals.php" class="photo-item" style="display: block; position: relative; color: inherit; text-decoration: none;">
          <img src="admin/featured/fp3.jpg" alt="New Arrivals" style="object-position: center 30%;">
          <div class="photo-caption">
            <h3><?php echo get_content('new_arrivals_title', 'New Arrivals'); ?></h3>
          </div>
        </a>
        
        <!-- Women's Collection -->
        <a href="women.php" class="photo-item" style="display: block; position: relative; color: inherit; text-decoration: none;">
          <img src="fp3.jpg" alt="Women's Collection" style="object-position: center 50%;">
          <div class="photo-caption">
            <h3><?php echo get_content('womens_collection_title', "Women's Collection"); ?></h3>
            <p><?php echo get_content('womens_collection_subtitle', 'Elegant designs for women'); ?></p>
          </div>
        </a>
      </div>
    </div>
  </section>

  <div style="text-align: center; margin-top: 30px; margin-bottom: 45px;">
    <a href="products.php" class="view-all-btn">
      <span>View All Products</span>
      <i class="fas fa-arrow-right" style="margin-left: 6px; transition: transform 0.3s;"></i>
    </a>
  </div>
  
  <style>
    .view-all-btn {
      display: inline-flex;
      align-items: center;
      background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
      color: white;
      padding: 10px 28px;
      border-radius: 22px;
      text-decoration: none;
      font-weight: 700;
      font-size: 14px;
      transition: all 0.3s ease;
      box-shadow: 0 3px 12px rgba(91, 107, 70, 0.25);
      border: 2px solid transparent;
    }
    
    .view-all-btn:hover {
      background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(91, 107, 70, 0.35);
    }
    
    .view-all-btn:hover i {
      transform: translateX(5px);
    }
  </style>

  <script>
    const notifBtn = document.getElementById("notifBtn");
    const notifPanel = document.getElementById("notifPanel");
    const menuBtn = document.getElementById("menuBtn");
    const menuPanel = document.getElementById("menuPanel");

    notifBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      notifPanel.style.display = notifPanel.style.display === "block" ? "none" : "block";
      if (menuPanel) menuPanel.style.display = "none";
    });

    if (menuBtn && menuPanel) {
      menuBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        menuPanel.style.display = menuPanel.style.display === "block" ? "none" : "block";
        notifPanel.style.display = "none";
      });
    }

    window.addEventListener("click", () => {
      notifPanel.style.display = "none";
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
            cartCount.textContent = data.count;
            cartCount.style.display = 'flex';
          } else {
            cartCount.style.display = 'none';
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
          cartCount.textContent = data.cart_count;
          cartCount.style.display = 'flex';
          
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
    document.getElementById('cartIcon').addEventListener('click', function() {
      window.location.href = 'cart.php';
    });

    // Update cart count when page loads
    document.addEventListener('DOMContentLoaded', function() {
      updateCartCount();
    });

    // Toggle notification panel
    document.getElementById('notifBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        const panel = document.getElementById('notifPanel');
        const menuPanel = document.getElementById('menuPanel');
        
        // Close menu panel if open
        if (menuPanel.style.display === 'block') {
            menuPanel.style.display = 'none';
        }
        
        // Toggle notification panel
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
    });
    
    // Toggle menu panel
    document.getElementById('menuBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        const panel = document.getElementById('menuPanel');
        const notifPanel = document.getElementById('notifPanel');
        
        // Close notification panel if open
        if (notifPanel.style.display === 'block') {
            notifPanel.style.display = 'none';
        }
        
        // Toggle menu panel
        panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
    });
    
    // Close panels when clicking outside
    document.addEventListener('click', function() {
        document.getElementById('notifPanel').style.display = 'none';
        document.getElementById('menuPanel').style.display = 'none';
    });
    
    // Prevent panel from closing when clicking inside it
    document.querySelectorAll('.notification-panel, .menu-panel').forEach(panel => {
        panel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
  </script>
</body>
</html>