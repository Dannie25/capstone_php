<?php include 'db.php'; ?>
<?php
// CMS helper: fetch content by key from site_content (same pattern as home.php)
if (!function_exists('get_content')) {
  function get_content($key, $default = '') {
    global $conn;
    if (!$conn) return $default;
    $stmt = $conn->prepare("SELECT content_value FROM site_content WHERE content_key = ?");
    if (!$stmt) return $default;
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
}
// Dedicated About CMS helper using about_content table
if (!function_exists('get_about_content')) {
  function get_about_content($key, $default = '') {
    global $conn;
    if (!$conn) return $default;
    $stmt = $conn->prepare("SELECT content_value FROM about_content WHERE content_key = ?");
    if (!$stmt) return $default;
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
}
// Dedicated About MAP helper using about_map table
if (!function_exists('get_about_map')) {
  function get_about_map($key, $default = '') {
    global $conn;
    if (!$conn) return $default;
    $stmt = $conn->prepare("SELECT content_value FROM about_map WHERE content_key = ?");
    if (!$stmt) return $default;
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
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<head>
  <meta charset="UTF-8">
  <title>About Us - MTC Clothing</title>
  <style>
    body { font-family: Arial, sans-serif; background: #fafafa; margin: 0; }
    header { background: #d9e6a7; padding: 15px 50px; display: flex; justify-content: space-between; align-items: center; }
    .logo { font-size: 22px; font-weight: bold; }
    nav a { margin: 0 15px; text-decoration: none; color: #222; font-weight: bold; }
    nav a.active { text-decoration: underline; }
    .nav-right { display: flex; align-items: center; gap: 15px; position: relative; }
    .btn-style { background-color: #fff; border: 1px solid #555; padding: 5px 12px; border-radius: 20px; cursor: pointer; }
    .icon { cursor: pointer; font-size: 18px; position: relative; }
    .badge { position: absolute; top: -5px; right: -10px; background: red; color: white; border-radius: 50%; font-size: 12px; padding: 2px 6px; }

    .hero-banner { background: #5b6b46; color: #e2e2e2; padding: 40px 20px; }
    .hero-inner { max-width: 1000px; margin: 0 auto; display: grid; gap: 15px; grid-template-columns: 1.1fr 0.9fr; align-items: center; }
    .hero-title { font-size: 24px; margin: 0 0 8px; letter-spacing: .5px; }
    .hero-sub { font-size: 14px; line-height: 1.5; margin: 0; }
    .hero-card { background: #ffffff15; border: 1px solid #ffffff30; padding: 12px; border-radius: 8px; font-size: 14px; }
    .hero-cta { display: flex; gap: 10px; margin-top: 12px; }
    .hero-cta a { background: #d9e6a7; color: #222; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: bold; border: 2px solid #5b6b46; font-size: 14px; }

    .container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
    .section { padding: 20px 0; }
    .section h2 { color: #333; font-size: 1.3rem; border-bottom: 2px solid #d9e6a7; padding-bottom: 8px; margin-bottom: 12px; }
    .two-col { display: grid; gap: 15px; grid-template-columns: 1fr; }
    @media (min-width: 900px) { .two-col { grid-template-columns: 1fr 1fr; } }
    .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 12px 15px; font-size: 14px; }
    .card p { margin: 8px 0; line-height: 1.5; }
    .list { margin: 0; padding-left: 16px; font-size: 14px; }
    .list li { margin: 4px 0; }
    .stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
    @media (min-width: 700px) { .stat-grid { grid-template-columns: repeat(4, 1fr); } }
    .stat { background:#fff; border-radius: 8px; text-align:center; padding:12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .stat .num { font-size: 20px; font-weight: 800; color:#5b6b46; }
    .stat .label { color:#555; margin-top:4px; font-size: 12px; }
    .testimonials { display: grid; grid-template-columns: 1fr; gap: 12px; }
    @media (min-width: 900px) { .testimonials { grid-template-columns: repeat(3, 1fr); } }
    blockquote { margin: 0; padding: 10px 12px; background:#fff; border-left: 4px solid #d9e6a7; border-radius: 6px; font-style: italic; color:#444; font-size: 13px; }
    .cta-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .cta-row a { display:inline-block; background:#5b6b46; color:#fff; padding:8px 16px; border-radius: 20px; text-decoration:none; font-weight:bold; border: 2px solid #5b6b46; font-size: 14px; }
    .cta-row a.secondary { background:#fff; color:#222; border-color:#5b6b46; }
    .map { width:100%; height: 220px; background:#eaeaea; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#777; }
  </style>
  <!-- Leaflet CSS for the map -->
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
  />
</head>
<body>

  <!-- Hero banner -->
  <section class="hero-banner">
    <div class="hero-inner">
      <div>
        <h1 class="hero-title"><?php echo htmlspecialchars(get_about_content('about_title', 'About MTC Clothing')); ?></h1>
        <p class="hero-sub"><?php echo htmlspecialchars(get_about_content('about_tagline', 'Mastering tailoring and fabrics — where quality meets craft. We create garments that fit beautifully, feel great, and last longer.')); ?></p>
        <div class="hero-cta">
          <a href="arrivals.php"><?php echo htmlspecialchars(get_about_content('about_cta_shop', 'Shop New Arrivals')); ?></a>
          <a href="products.php" class="secondary" style="background:#fff; color:#222;">
            <?php echo htmlspecialchars(get_about_content('about_cta_products', 'Browse All Products')); ?>
          </a>
        </div>
      </div>
      <div class="hero-card">
        <strong><?php echo htmlspecialchars(get_about_content('about_why_title', 'Why customers choose us')); ?></strong>
        <ul class="list">
          <li><?php echo htmlspecialchars(get_about_content('about_why_1', 'Tailor-grade craftsmanship with modern fits')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_why_2', 'Curated, durable, and comfortable fabrics')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_why_3', 'Small-batch production and careful QC')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_why_4', 'Friendly support and a fit-first mindset')); ?></li>
        </ul>
      </div>
    </div>
  </section>

  <div class="container">
    <!-- Our Story -->
    <section class="section two-col">
      <div class="card">
        <h2><?php echo htmlspecialchars(get_about_content('about_story_title', 'Our Story')); ?></h2>
        <p><?php echo htmlspecialchars(get_about_content('about_story_p1', 'MTC Clothing began with a simple belief: clothes should be crafted to fit you, not the other way around. What started as a small tailoring workshop has grown into a community of makers and customers who care about quality, comfort, and timeless style.')); ?></p>
        <p><?php echo htmlspecialchars(get_about_content('about_story_p2', 'From fabric selection to the final stitch, we obsess over details—so your garments look great, feel better, and last longer.')); ?></p>
      </div>
      <div class="card">
        <h2><?php echo htmlspecialchars(get_about_content('about_mission_title', 'Mission & Values')); ?></h2>
        <ul class="list">
          <li><?php echo htmlspecialchars(get_about_content('about_values_1', 'Quality first — built to last and to love')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_values_2', 'Craftsmanship — precise tailoring and clean finishes')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_values_3', 'Comfort & Fit — modern silhouettes that move with you')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_values_4', 'Customer-first — friendly support and easy help')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_values_5', 'Responsible production — small batches, less waste')); ?></li>
        </ul>
      </div>
    </section>

    <!-- Craft & Difference -->
    <section class="section two-col">
      <div class="card">
        <h2><?php echo htmlspecialchars(get_about_content('about_craft_title', 'Materials & Craft')); ?></h2>
        <p><?php echo htmlspecialchars(get_about_content('about_craft_p1', 'We work with breathable, durable fabrics and finish every piece with careful construction—reinforced stress points, clean seams, and a thorough quality check before your order leaves our workshop.')); ?></p>
      </div>
      <div class="card">
        <h2><?php echo htmlspecialchars(get_about_content('about_diff_title', 'What Sets Us Apart')); ?></h2>
        <ul class="list">
          <li><?php echo htmlspecialchars(get_about_content('about_diff_1', 'Tailor-grade construction with everyday wearability')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_diff_2', 'Limited runs for better quality control')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_diff_3', 'Local craftsmanship you can trust')); ?></li>
          <li><?php echo htmlspecialchars(get_about_content('about_diff_4', 'Fit-first support and easy alterations')); ?></li>
        </ul>
      </div>
    </section>

    <!-- Numbers & Social Proof -->
    <section class="section">
      <h2><?php echo htmlspecialchars(get_about_content('about_numbers_title', 'By the Numbers')); ?></h2>
      <div class="stat-grid">
        <div class="stat"><div class="num"><?php echo htmlspecialchars(get_about_content('about_stat_1_num', '5+ yrs')); ?></div><div class="label"><?php echo htmlspecialchars(get_about_content('about_stat_1_label', 'Tailoring Experience')); ?></div></div>
        <div class="stat"><div class="num"><?php echo htmlspecialchars(get_about_content('about_stat_2_num', '2,000+')); ?></div><div class="label"><?php echo htmlspecialchars(get_about_content('about_stat_2_label', 'Happy Customers')); ?></div></div>
        <div class="stat"><div class="num"><?php echo htmlspecialchars(get_about_content('about_stat_3_num', '98%')); ?></div><div class="label"><?php echo htmlspecialchars(get_about_content('about_stat_3_label', '5-star Reviews')); ?></div></div>
        <div class="stat"><div class="num"><?php echo htmlspecialchars(get_about_content('about_stat_4_num', '100%')); ?></div><div class="label"><?php echo htmlspecialchars(get_about_content('about_stat_4_label', 'Quality Checked')); ?></div></div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="section">
      <h2><?php echo htmlspecialchars(get_about_content('about_testimonials_title', 'What Customers Say')); ?></h2>
      <div class="testimonials">
        <blockquote><?php echo htmlspecialchars(get_about_content('about_testimonial_1', '“Great fit and quality. You can feel the craftsmanship in every seam.”')); ?></blockquote>
        <blockquote><?php echo htmlspecialchars(get_about_content('about_testimonial_2', '“Fast, friendly service — the hoodie I got is my new favorite.”')); ?></blockquote>
        <blockquote><?php echo htmlspecialchars(get_about_content('about_testimonial_3', '“Love the fabrics. Comfortable and durable — worth every peso.”')); ?></blockquote>
      </div>
    </section>

    <!-- Visit / Contact -->
    <section class="section two-col">
      <div class="card">
        <h2><?php echo htmlspecialchars(get_about_content('about_visit_title', 'Visit Our Workshop')); ?></h2>
        <p><?php echo htmlspecialchars(get_about_content('about_visit_p1', 'We\'re a small team of pattern-makers, tailors, and fabric enthusiasts based in your local community. We love what we do — and we think you’ll feel it in every piece.')); ?></p>
        <div id="map" class="map"></div>
      </div>
      <div class="card">
        <h2><?php echo htmlspecialchars(get_about_content('about_contact_title', 'Get in Touch')); ?></h2>
        <p><?php echo htmlspecialchars(get_about_content('about_contact_p1', 'Have a question, custom request, or need help with sizing? We’re here to help.')); ?></p>
        <div class="cta-row">
          <a href="arrivals.php"><?php echo htmlspecialchars(get_about_content('about_cta_shop', 'Shop New Arrivals')); ?></a>
          <a href="products.php" class="secondary"><?php echo htmlspecialchars(get_about_content('about_cta_products', 'View All Products')); ?></a>
        </div>
      </div>
    </section>
  </div>

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

    // Additional toggle handlers (guarded) and prevent inside-click close
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
  <!-- Leaflet JS and map initialization -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script>
    (function(){
      // Read coordinates from CMS with sensible defaults
      const lat = parseFloat('<?php echo htmlspecialchars(get_about_map('about_map_lat', '14.3306101')); ?>');
      const lng = parseFloat('<?php echo htmlspecialchars(get_about_map('about_map_lng', '120.9364813')); ?>');
      const zoom = parseInt('<?php echo htmlspecialchars(get_about_map('about_map_zoom', '15')); ?>', 10) || 15;
      const popupText = '<?php echo htmlspecialchars(get_about_map('about_map_popup', 'MTC Clothing Workshop')); ?>';

      const mapEl = document.getElementById('map');
      if (!mapEl) return;

      // Initialize Leaflet map
      const map = L.map('map').setView([lat, lng], zoom);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      const marker = L.marker([lat, lng]).addTo(map);
      if (popupText) {
        marker.bindPopup(popupText).openPopup();
      }
    })();
  </script>
</body>
</html>
