<?php
session_start();
include 'db.php';

// Require login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
  $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'profile.php');
  header("Location: login.php?redirect={$redirect}");
  exit();
}

// Get user's default address if exists
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$user_phone = $_SESSION['user_phone'] ?? '';

// Get user's addresses
include_once 'includes/address_functions.php';
$default_address = $user_id ? getDefaultAddress($user_id) : [];
$all_addresses = $user_id ? getCustomerAddresses($user_id) : [];

// Optional: try to fetch more details if a users table exists (silent fail if not)
try {
  if ($conn && $user_id) {
    if ($stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1")) {
      $stmt->bind_param('i', $user_id);
      if ($stmt->execute()) {
        $stmt->bind_result($db_name, $db_email);
        if ($stmt->fetch()) {
          $user_name = $db_name ?: $user_name;
          $user_email = $db_email ?: $user_email;
        }
      }
      $stmt->close();
    }
  }
} catch (Throwable $e) { /* ignore if table or columns not present */ }
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Profile - MTC Clothing</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #fafafa; margin: 0; }
    .container { max-width: 1000px; margin: 0 auto; padding: 0 15px; }
    .page-title { background:#5b6b46; color:#e2e2e2; padding: 20px 0; }
    .page-title h1 { margin:0; font-size: 22px; }

    .grid { display:grid; grid-template-columns: 1fr; gap: 15px; padding: 15px 0; }
    @media (min-width: 900px) { .grid { grid-template-columns: 2fr 1fr; } }

    .card { background:#fff; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,.06); padding: 12px 15px; margin-bottom: 12px; }
    .card h2 { margin:0 0 10px; font-size:1.1rem; color:#333; border-bottom:2px solid #d9e6a7; padding-bottom:6px; }
    .muted { color:#666; }
    .row { display:flex; gap:10px; flex-wrap: wrap; }
    .row > * { flex:1; min-width: 180px; }
    label { display:block; margin: 8px 0 4px; font-weight:600; color:#444; font-size: 13px; }
    input[type="text"], input[type="email"], input[type="password"], textarea { 
        width:100%; 
        padding:8px 10px; 
        border:1px solid #ddd; 
        border-radius:6px; 
        font-size:13px;
        box-sizing: border-box;
    }
    textarea { min-height: 70px; resize:vertical; }
    .btn { 
        background:#5b6b46; 
        color:#fff; 
        border:none; 
        padding:8px 14px; 
        border-radius:6px; 
        cursor:pointer;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        font-size: 13px;
    }
    .btn:hover { background:#4a5938; }
    .btn.secondary { background:#d9e6a7; color:#222; border:2px solid #5b6b46; }
    .btn.secondary:hover { background:#c9d697; }

    /* Location Selector Styles */
    .form-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .form-group {
        flex: 1;
        min-width: 0;
        min-width: 180px;
    }
    .form-group label {
        display: block;
        margin-bottom: 4px;
        font-weight: 600;
        color: #444;
        font-size: 13px;
    }
    .form-group select {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 13px;
        background-color: #fff;
        box-sizing: border-box;
    }
    .form-group select:disabled {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }
    
    @media (max-width: 600px) {
        .row, .form-row {
            flex-direction: column;
        }
        .row > *, .form-group {
            min-width: 100%;
        }
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border: 1px solid #888;
        border-radius: 8px;
        width: 90%;
        max-width: 650px;
        max-height: 85vh;
        overflow-y: auto;
    }
    .modal-header {
        padding: 15px;
        background: #5b6b46;
        color: white;
        border-radius: 8px 8px 0 0;
    }
    .modal-header h2 {
        margin: 0;
        color: white;
        border: none;
        padding: 0;
        font-size: 1.1rem;
    }
    .modal-body {
        padding: 15px;
    }
    .close {
        color: white;
        float: right;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        line-height: 18px;
    }
    .close:hover,
    .close:focus {
        color: #ddd;
    }
    
    /* Address Item Hover Effect */
    .address-item {
        transition: all 0.2s ease;
    }
    .address-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
  </style>
</head>
<body>

  <section class="page-title">
    <div class="container">
      <h1>My Profile</h1>
      <p class="muted" style="margin:4px 0 0;color:#fff;font-size:13px;">View and update your account details.</p>
    </div>
  </section>

  <div class="container">
    <div class="grid">
      <!-- Left column: Profile Info and Forms -->
      <div>
        <div class="card">
          <h2>Account Overview</h2>
          <p style="font-size:13px;margin:6px 0;"><strong>First Name:</strong> <?php echo htmlspecialchars($default_address['first_name'] ?? $user_name); ?></p>
          <p style="font-size:13px;margin:6px 0;"><strong>Last Name:</strong> <?php echo htmlspecialchars($default_address['last_name'] ?? ''); ?></p>
          <p style="font-size:13px;margin:6px 0;"><strong>Email:</strong> <?php echo htmlspecialchars($user_email ?: ''); ?></p>
          <p style="font-size:13px;margin:6px 0;"><strong>Phone:</strong> <?php echo htmlspecialchars($user_phone ?: ($default_address['phone'] ?? '')); ?></p>
        </div>

        <div class="card">
          <h2>Edit Profile</h2>

          <?php if (isset($_SESSION['success_message'])): ?>
            <div style="background:#d4edda; color:#155724; padding:8px; border-radius:4px; margin-bottom:10px;font-size:13px;">
              <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['error_message'])): ?>
            <div style="background:#f8d7da; color:#721c24; padding:8px; border-radius:4px; margin-bottom:10px;font-size:13px;">
              <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
          <?php endif; ?>

          <form method="post" action="profile_update.php">
            <div class="row">
              <div>
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($default_address['first_name'] ?? $user_name); ?>" required>
              </div>
              <div>
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($default_address['last_name'] ?? ''); ?>" required>
              </div>
            </div>
            
            <div class="row">
              <div>
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email ?: ''); ?>" required>
              </div>
              <div>
                <label for="phone">Phone Number *</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_phone ?: ($default_address['phone'] ?? '')); ?>" required>
              </div>
            </div>

            <label for="address">Address *</label>
            <textarea id="address" name="address" required><?php echo htmlspecialchars($default_address['address'] ?? ''); ?></textarea>
            <div class="muted" style="font-size:11px; margin-top:4px; color:#6b7280;">House/Unit, Street, Subdivision (huwag isama ang Barangay/City dito — pipiliin sa ibaba)</div>

            <h2 class="section-title" style="font-size: 15px; font-weight: 600; color: #5b6b46; margin: 12px 0 12px; padding-bottom: 8px; border-bottom: 1px solid #dee2e6;">Location</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="region_select">Region *</label>
                    <select id="region_select" name="region" required>
                        <option value="">Select Region</option>
                        <?php if (!empty($default_address['region_code'])): ?>
                            <option value="<?php echo htmlspecialchars($default_address['region_code']); ?>" selected>
                                <?php echo htmlspecialchars($default_address['region_name']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="region_code" name="region_code" value="<?php echo htmlspecialchars($default_address['region_code'] ?? ''); ?>">
                    <input type="hidden" id="region_name" name="region_name" value="<?php echo htmlspecialchars($default_address['region_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="province_select">Province *</label>
                    <select id="province_select" name="province" <?php echo empty($default_address['region_code']) ? 'disabled' : ''; ?> required>
                        <option value="">Select Province</option>
                        <?php if (!empty($default_address['province_code'])): ?>
                            <option value="<?php echo htmlspecialchars($default_address['province_code']); ?>" selected>
                                <?php echo htmlspecialchars($default_address['province_name']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="province_code" name="province_code" value="<?php echo htmlspecialchars($default_address['province_code'] ?? ''); ?>">
                    <input type="hidden" id="province_name" name="province_name" value="<?php echo htmlspecialchars($default_address['province_name'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city_select">City/Municipality *</label>
                    <select id="city_select" name="city_select" <?php echo empty($default_address['province_code']) ? 'disabled' : ''; ?> required>
                        <option value="">Select City/Municipality</option>
                        <?php if (!empty($default_address['city_code'])): ?>
                            <option value="<?php echo htmlspecialchars($default_address['city_code']); ?>" selected>
                                <?php echo htmlspecialchars($default_address['city_name']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="city_code" name="city_code" value="<?php echo htmlspecialchars($default_address['city_code'] ?? ''); ?>">
                    <input type="hidden" id="city_name" name="city_name" value="<?php echo htmlspecialchars($default_address['city_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="barangay_select">Barangay *</label>
                    <select id="barangay_select" name="barangay" <?php echo empty($default_address['city_code']) ? 'disabled' : ''; ?> required>
                        <option value="">Select Barangay</option>
                        <?php if (!empty($default_address['barangay_code'])): ?>
                            <option value="<?php echo htmlspecialchars($default_address['barangay_code']); ?>" selected>
                                <?php echo htmlspecialchars($default_address['barangay_name']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="barangay_code" name="barangay_code" value="<?php echo htmlspecialchars($default_address['barangay_code'] ?? ''); ?>">
                    <input type="hidden" id="barangay_name" name="barangay_name" value="<?php echo htmlspecialchars($default_address['barangay_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="postal_code">Postal Code *</label>
                    <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($default_address['postal_code'] ?? ''); ?>" required>
                </div>
            </div>

            <div style="margin-top:10px; display:flex; gap:8px;">
              <button class="btn" type="submit">Save Changes</button>
              <a class="btn secondary" href="home.php">Cancel</a>
            </div>
          </form>
          <p class="muted" style="margin-top:8px; font-size:12px;">Note: This form posts to <code>profile_update.php</code>. If you want, I can implement that endpoint next.</p>
        </div>

              </div>

      <!-- Right column: Quick Actions -->
      <div>
        <div class="card">
          <h2>Quick Actions</h2>
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <a href="my_orders.php" class="btn" style="text-align: center;">
              <i class="fas fa-box"></i> View Orders
            </a>
            <a href="cart.php" class="btn" style="text-align: center;">
              <i class="fas fa-shopping-cart"></i> Go to Cart
            </a>
            <a href="helpsupport.php" class="btn" style="text-align: center;">
              <i class="fas fa-headset"></i> Help & Support
            </a>
          </div>
        </div>

        <div class="card" style="margin-top:12px;">
          <h2>Security Tips</h2>
          <ul style="margin:0; padding-left:16px; color:#555; font-size:13px;">
            <li>Use a strong, unique password.</li>
            <li>Never share your login details.</li>
            <li>Sign out on shared devices.</li>
          </ul>
        </div>

        <!-- Saved Addresses Section -->
        <div class="card" style="margin-top:12px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div>
              <h2 style="margin: 0;">My Addresses</h2>
              <p class="muted" style="font-size:11px; margin: 3px 0 0;">Manage your delivery addresses</p>
            </div>
            <?php if (count($all_addresses) < 3): ?>
              <button class="btn" onclick="showAddAddressModal()" style="padding: 6px 12px; font-size: 12px;" title="Add new address">
                <i class="fas fa-plus"></i> Add
              </button>
            <?php endif; ?>
          </div>
          
          <div id="addresses-list">
            <?php if (empty($all_addresses)): ?>
              <div style="text-align: center; padding: 20px 15px; background: #f9f9f9; border-radius: 8px; border: 2px dashed #ddd;">
                <i class="fas fa-map-marker-alt" style="font-size: 30px; color: #ccc; margin-bottom: 8px;"></i>
                <p class="muted" style="margin: 0; font-size: 13px;">No saved addresses yet</p>
                <p class="muted" style="margin: 4px 0 0; font-size: 11px;">Add your first delivery address</p>
              </div>
            <?php else: ?>
              <?php foreach ($all_addresses as $addr): ?>
                <div class="address-item" data-address-id="<?php echo $addr['id']; ?>" style="border: 2px solid <?php echo $addr['is_default'] ? '#5b6b46' : '#e0e0e0'; ?>; border-radius: 8px; padding: 10px; margin-bottom: 10px; position: relative; background: <?php echo $addr['is_default'] ? '#f8faf6' : '#fff'; ?>; transition: all 0.2s;">
                  <?php if ($addr['is_default']): ?>
                    <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 8px;">
                      <i class="fas fa-check-circle" style="color: #5b6b46; font-size: 12px;"></i>
                      <span style="background: #5b6b46; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; letter-spacing: 0.5px;">DEFAULT ADDRESS</span>
                    </div>
                  <?php endif; ?>
                  
                  <div style="margin-bottom: 6px;">
                    <i class="fas fa-user" style="color: #5b6b46; margin-right: 5px; font-size: 12px;"></i>
                    <strong style="font-size: 13px;"><?php echo htmlspecialchars($addr['first_name'] . ' ' . $addr['last_name']); ?></strong>
                  </div>
                  
                  <div style="margin-bottom: 4px;">
                    <i class="fas fa-phone" style="color: #666; margin-right: 5px; font-size: 11px; width: 12px;"></i>
                    <span style="color: #666; font-size: 12px;"><?php echo htmlspecialchars($addr['phone']); ?></span>
                  </div>
                  
                  <div style="margin-bottom: 8px;">
                    <i class="fas fa-map-marker-alt" style="color: #666; margin-right: 5px; font-size: 11px; width: 12px;"></i>
                    <span style="color: #666; font-size: 12px; line-height: 1.4;">
                      <?php echo htmlspecialchars($addr['address']); ?>, 
                      <?php echo htmlspecialchars($addr['barangay_name']); ?>, 
                      <?php echo htmlspecialchars($addr['city_name']); ?>, 
                      <?php echo htmlspecialchars($addr['province_name']); ?>, 
                      <?php echo htmlspecialchars($addr['region_name']); ?> 
                      <?php echo htmlspecialchars($addr['postal_code']); ?>
                    </span>
                  </div>
                  
                  <div style="margin-top: 8px; display: flex; gap: 5px; flex-wrap: wrap;">
                    <?php if (!$addr['is_default']): ?>
                      <button class="btn" style="padding: 5px 10px; font-size: 11px; background: #5b6b46;" onclick="setDefaultAddress(<?php echo $addr['id']; ?>)" title="Set as default address">
                        <i class="fas fa-check"></i> Set Default
                      </button>
                    <?php endif; ?>
                    <button class="btn secondary" style="padding: 5px 10px; font-size: 11px;" onclick="editAddress(<?php echo $addr['id']; ?>)" title="Edit address">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <?php if (!$addr['is_default']): ?>
                      <button class="btn" style="padding: 5px 10px; font-size: 11px; background: #dc3545; border: none;" onclick="deleteAddress(<?php echo $addr['id']; ?>)" title="Delete address">
                        <i class="fas fa-trash"></i> Delete
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <?php if (count($all_addresses) >= 3): ?>
            <div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
              <i class="fas fa-info-circle" style="color: #856404; margin-right: 5px;"></i>
              <span style="color: #856404; font-size: 11px;">You've reached the maximum of 3 saved addresses.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Address Modal -->
  <div id="addressModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <span class="close" onclick="closeAddressModal()">&times;</span>
        <h2 id="modalTitle">Add New Address</h2>
      </div>
      <div class="modal-body">
        <form id="addressForm">
          <input type="hidden" id="address_id" name="address_id">
          
          <div class="row">
            <div>
              <label for="modal_first_name">First Name *</label>
              <input type="text" id="modal_first_name" name="first_name" required>
            </div>
            <div>
              <label for="modal_last_name">Last Name *</label>
              <input type="text" id="modal_last_name" name="last_name" required>
            </div>
          </div>
          
          <div class="row">
            <div>
              <label for="modal_email">Email Address *</label>
              <input type="email" id="modal_email" name="email" required>
            </div>
            <div>
              <label for="modal_phone">Phone Number *</label>
              <input type="text" id="modal_phone" name="phone" required>
            </div>
          </div>

          <label for="modal_address">Address *</label>
          <textarea id="modal_address" name="address" required></textarea>
          <div class="muted" style="font-size:11px; margin-top:4px; color:#6b7280;">House/Unit, Street, Subdivision</div>

          <h2 class="section-title" style="font-size: 15px; font-weight: 600; color: #5b6b46; margin: 12px 0 12px; padding-bottom: 8px; border-bottom: 1px solid #dee2e6;">Location</h2>
          
          <div class="form-row">
            <div class="form-group">
              <label for="modal_region">Region *</label>
              <select id="modal_region" name="region" required>
                <option value="">Select Region</option>
              </select>
              <input type="hidden" id="modal_region_code" name="region_code">
              <input type="hidden" id="modal_region_name" name="region_name">
            </div>
            <div class="form-group">
              <label for="modal_province">Province *</label>
              <select id="modal_province" name="province" disabled required>
                <option value="">Select Province</option>
              </select>
              <input type="hidden" id="modal_province_code" name="province_code">
              <input type="hidden" id="modal_province_name" name="province_name">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modal_city">City/Municipality *</label>
              <select id="modal_city" name="city" disabled required>
                <option value="">Select City/Municipality</option>
              </select>
              <input type="hidden" id="modal_city_code" name="city_code">
              <input type="hidden" id="modal_city_name" name="city_name">
            </div>
            <div class="form-group">
              <label for="modal_barangay">Barangay *</label>
              <select id="modal_barangay" name="barangay" disabled required>
                <option value="">Select Barangay</option>
              </select>
              <input type="hidden" id="modal_barangay_code" name="barangay_code">
              <input type="hidden" id="modal_barangay_name" name="barangay_name">
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="modal_postal_code">Postal Code *</label>
              <input type="text" id="modal_postal_code" name="postal_code" required>
            </div>
          </div>

          <div style="margin-top:12px; display:flex; gap:8px; justify-content: flex-end;">
            <button type="button" class="btn secondary" onclick="closeAddressModal()">Cancel</button>
            <button type="submit" class="btn">Save Address</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // PSGC API endpoints
    const PSGC = {
        regions: 'https://psgc.gitlab.io/api/regions/',
        regionProvinces: (code) => `https://psgc.gitlab.io/api/regions/${code}/provinces/`,
        provinceCities: (code) => `https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`,
        cityBarangays: (code) => `https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`
    };

    // DOM Elements
    const regionSelect = document.getElementById('region_select');
    const provinceSelect = document.getElementById('province_select');
    const citySelect = document.getElementById('city_select');
    const barangaySelect = document.getElementById('barangay_select');
    const regionCodeInput = document.getElementById('region_code');
    const regionNameInput = document.getElementById('region_name');
    const provinceCodeInput = document.getElementById('province_code');
    const provinceNameInput = document.getElementById('province_name');
    const cityCodeInput = document.getElementById('city_code');
    const cityNameInput = document.getElementById('city_name');
    const barangayCodeInput = document.getElementById('barangay_code');
    const barangayNameInput = document.getElementById('barangay_name');
    
    // Function to update hidden inputs based on current selections
    function updateHiddenInputs() {
        // Update region hidden inputs
        const regionSelectedOption = regionSelect.options[regionSelect.selectedIndex];
        if (regionSelectedOption) {
            regionCodeInput.value = regionSelect.value;
            regionNameInput.value = regionSelectedOption.textContent;
        }

        // Update province hidden inputs
        const provinceSelectedOption = provinceSelect.options[provinceSelect.selectedIndex];
        if (provinceSelectedOption) {
            provinceCodeInput.value = provinceSelect.value;
            provinceNameInput.value = provinceSelectedOption.textContent;
        }

        // Update city hidden inputs
        const citySelectedOption = citySelect.options[citySelect.selectedIndex];
        if (citySelectedOption) {
            cityCodeInput.value = citySelect.value;
            cityNameInput.value = citySelectedOption.textContent;
        }

        // Update barangay hidden inputs
        const barangaySelectedOption = barangaySelect.options[barangaySelect.selectedIndex];
        if (barangaySelectedOption) {
            barangayCodeInput.value = barangaySelect.value;
            barangayNameInput.value = barangaySelectedOption.textContent;
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', async () => {
        // Load regions
        await loadRegions();

        // If we have saved values, load them
        const savedRegionCode = regionCodeInput.value;
        if (savedRegionCode) {
            await loadProvinces(savedRegionCode);
            regionSelect.value = savedRegionCode;

            const savedProvinceCode = provinceCodeInput.value;
            if (savedProvinceCode) {
                await loadCities(savedProvinceCode);
                provinceSelect.value = savedProvinceCode;

                const savedCityCode = cityCodeInput.value;
                if (savedCityCode) {
                    await loadBarangays(savedCityCode);
                    citySelect.value = savedCityCode;

                    const savedBarangayCode = barangayCodeInput.value;
                    if (savedBarangayCode) {
                        console.log('Loading saved barangay:', savedBarangayCode);
                        // Wait a bit for barangays to load, then set the value
                        setTimeout(() => {
                            console.log('Setting barangay select value to:', savedBarangayCode);
                            barangaySelect.value = savedBarangayCode;
                            console.log('Barangay select value after setting:', barangaySelect.value);

                            // Also update the hidden inputs
                            const selectedOption = barangaySelect.options[barangaySelect.selectedIndex];
                            if (selectedOption) {
                                barangayCodeInput.value = savedBarangayCode;
                                barangayNameInput.value = selectedOption.textContent;
                                console.log('Updated barangay hidden inputs - Code:', savedBarangayCode, 'Name:', selectedOption.textContent);
                            }
                        }, 500);
                    }
                }
            }
        }

        // Update hidden inputs after all data is loaded
        updateHiddenInputs();
    });

    // Event Listeners
    regionSelect.addEventListener('change', async (e) => {
        const regionCode = e.target.value;
        if (regionCode) {
            await loadProvinces(regionCode);
            // Clear downstream selections
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Update hidden inputs
            updateHiddenInputs();
        } else {
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            provinceSelect.disabled = true;
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Clear hidden inputs
            regionCodeInput.value = '';
            regionNameInput.value = '';
            provinceCodeInput.value = '';
            provinceNameInput.value = '';
            cityCodeInput.value = '';
            cityNameInput.value = '';
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
    });

    provinceSelect.addEventListener('change', async (e) => {
        const provinceCode = e.target.value;
        if (provinceCode) {
            await loadCities(provinceCode);
            // Clear barangay selection
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Update hidden inputs
            updateHiddenInputs();
        } else {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Clear hidden inputs
            provinceCodeInput.value = '';
            provinceNameInput.value = '';
            cityCodeInput.value = '';
            cityNameInput.value = '';
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
    });

    citySelect.addEventListener('change', async (e) => {
        const cityCode = e.target.value;
        if (cityCode) {
            await loadBarangays(cityCode);

            // Update hidden inputs
            updateHiddenInputs();
        } else {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Clear hidden inputs
            cityCodeInput.value = '';
            cityNameInput.value = '';
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
    });

    barangaySelect.addEventListener('change', (e) => {
        const barangayCode = e.target.value;
        if (barangayCode) {
            const selectedOption = barangaySelect.options[barangaySelect.selectedIndex];
            barangayCodeInput.value = barangayCode;
            barangayNameInput.value = selectedOption.textContent;
            console.log('Barangay changed - Code:', barangayCode, 'Name:', selectedOption.textContent);
        } else {
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
        // Update hidden inputs
        updateHiddenInputs();
    });

    // API Functions
    async function loadRegions() {
        try {
            const response = await fetch(PSGC.regions);
            const regions = await response.json();

            regions.forEach(region => {
                const option = document.createElement('option');
                option.value = region.code;
                option.textContent = region.name;
                regionSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading regions:', error);
            // Silent fail - don't show alert to avoid interruption
            regionSelect.innerHTML = '<option value="">Unable to load regions</option>';
        }
    }

    async function loadProvinces(regionCode) {
        try {
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            provinceSelect.disabled = true;

            const response = await fetch(PSGC.regionProvinces(regionCode));
            const provinces = await response.json();

            provinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province.code;
                option.textContent = province.name;
                provinceSelect.appendChild(option);
            });

            provinceSelect.disabled = false;
        } catch (error) {
            console.error('Error loading provinces:', error);
            alert('Failed to load provinces. Please try again.');
        }
    }

    async function loadCities(provinceCode) {
        try {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;

            const response = await fetch(PSGC.provinceCities(provinceCode));
            const cities = await response.json();

            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.code;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });

            citySelect.disabled = false;
        } catch (error) {
            console.error('Error loading cities:', error);
            alert('Failed to load cities. Please try again.');
        }
    }

    async function loadBarangays(cityCode) {
        try {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            const response = await fetch(PSGC.cityBarangays(cityCode));
            const barangays = await response.json();

            // If we have saved barangay data, add it first
            const savedBarangayCode = '<?php echo htmlspecialchars($default_address['barangay_code'] ?? ''); ?>';
            const savedBarangayName = '<?php echo htmlspecialchars($default_address['barangay_name'] ?? ''); ?>';

            if (savedBarangayCode && savedBarangayName) {
                // Check if this barangay is in the API response
                const foundInApi = barangays.find(b => b.code === savedBarangayCode);
                if (!foundInApi) {
                    // Add the saved barangay as the first option if not found in API
                    barangays.unshift({ code: savedBarangayCode, name: savedBarangayName });
                }
            }

            barangays.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.code;
                option.textContent = barangay.name;

                // Mark as selected if it matches saved data
                if (barangay.code === savedBarangayCode) {
                    option.selected = true;
                }

                barangaySelect.appendChild(option);
            });

            barangaySelect.disabled = false;
        } catch (error) {
            console.error('Error loading barangays:', error);
            alert('Failed to load barangays. Please try again.');
        }
    }

    // Event Listeners
    regionSelect.addEventListener('change', async (e) => {
        const regionCode = e.target.value;
        if (regionCode) {
            await loadProvinces(regionCode);
            // Clear downstream selections
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Update hidden inputs
            updateHiddenInputs();
        } else {
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            provinceSelect.disabled = true;
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Clear hidden inputs
            regionCodeInput.value = '';
            regionNameInput.value = '';
            provinceCodeInput.value = '';
            provinceNameInput.value = '';
            cityCodeInput.value = '';
            cityNameInput.value = '';
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
    });

    provinceSelect.addEventListener('change', async (e) => {
        const provinceCode = e.target.value;
        if (provinceCode) {
            await loadCities(provinceCode);
            // Clear barangay selection
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Update hidden inputs
            updateHiddenInputs();
        } else {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Clear hidden inputs
            provinceCodeInput.value = '';
            provinceNameInput.value = '';
            cityCodeInput.value = '';
            cityNameInput.value = '';
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
    });

    citySelect.addEventListener('change', async (e) => {
        const cityCode = e.target.value;
        if (cityCode) {
            await loadBarangays(cityCode);

            // Update hidden inputs
            updateHiddenInputs();
        } else {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;

            // Clear hidden inputs
            cityCodeInput.value = '';
            cityNameInput.value = '';
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
    });

    barangaySelect.addEventListener('change', (e) => {
        const barangayCode = e.target.value;
        if (barangayCode) {
            const selectedOption = barangaySelect.options[barangaySelect.selectedIndex];
            barangayCodeInput.value = barangayCode;
            barangayNameInput.value = selectedOption.textContent;
            console.log('Barangay changed - Code:', barangayCode, 'Name:', selectedOption.textContent);
        } else {
            barangayCodeInput.value = '';
            barangayNameInput.value = '';
        }
        // Update hidden inputs
        updateHiddenInputs();
    });

    // ============ ADDRESS MANAGEMENT FUNCTIONS ============
    
    // Modal DOM Elements
    const modal = document.getElementById('addressModal');
    const modalTitle = document.getElementById('modalTitle');
    const addressForm = document.getElementById('addressForm');
    const modalRegion = document.getElementById('modal_region');
    const modalProvince = document.getElementById('modal_province');
    const modalCity = document.getElementById('modal_city');
    const modalBarangay = document.getElementById('modal_barangay');

    // Show Add Address Modal
    function showAddAddressModal() {
        modalTitle.textContent = 'Add New Address';
        addressForm.reset();
        document.getElementById('address_id').value = '';
        
        // Load regions for modal
        loadModalRegions();
        
        modal.style.display = 'block';
    }

    // Show Edit Address Modal
    async function editAddress(addressId) {
        modalTitle.textContent = 'Edit Address';
        
        // Fetch address data
        try {
            const response = await fetch(`address_get.php?id=${addressId}`);
            const data = await response.json();
            
            if (data.success) {
                const addr = data.address;
                
                // Fill form fields
                document.getElementById('address_id').value = addr.id;
                document.getElementById('modal_first_name').value = addr.first_name;
                document.getElementById('modal_last_name').value = addr.last_name;
                document.getElementById('modal_email').value = addr.email;
                document.getElementById('modal_phone').value = addr.phone;
                document.getElementById('modal_address').value = addr.address;
                document.getElementById('modal_postal_code').value = addr.postal_code;
                
                // Load regions and set location
                await loadModalRegions();
                
                if (addr.region_code) {
                    modalRegion.value = addr.region_code;
                    await loadModalProvinces(addr.region_code);
                    
                    if (addr.province_code) {
                        modalProvince.value = addr.province_code;
                        await loadModalCities(addr.province_code);
                        
                        if (addr.city_code) {
                            modalCity.value = addr.city_code;
                            await loadModalBarangays(addr.city_code);
                            
                            if (addr.barangay_code) {
                                setTimeout(() => {
                                    modalBarangay.value = addr.barangay_code;
                                    updateModalHiddenInputs();
                                }, 300);
                            }
                        }
                    }
                }
                
                modal.style.display = 'block';
            } else {
                alert('Error loading address: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error loading address data');
        }
    }

    // Close Modal
    function closeAddressModal() {
        modal.style.display = 'none';
        addressForm.reset();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            closeAddressModal();
        }
    }

    // Handle Address Form Submission
    addressForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(addressForm);
        const addressId = document.getElementById('address_id').value;
        
        // Update hidden inputs before submitting
        updateModalHiddenInputs();
        
        // Re-create FormData with updated values
        const finalFormData = new FormData(addressForm);
        
        try {
            const url = addressId ? 'address_update.php' : 'address_add.php';
            const response = await fetch(url, {
                method: 'POST',
                body: finalFormData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                closeAddressModal();
                location.reload(); // Reload to show updated addresses
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving address');
        }
    });

    // Set Default Address
    async function setDefaultAddress(addressId) {
        if (!confirm('Set this as your default address?')) return;
        
        try {
            const response = await fetch('address_set_default.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'address_id=' + addressId
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error setting default address');
        }
    }

    // Delete Address
    async function deleteAddress(addressId) {
        if (!confirm('Are you sure you want to delete this address?')) return;
        
        try {
            const response = await fetch('address_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'address_id=' + addressId
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error deleting address');
        }
    }

    // ============ MODAL LOCATION FUNCTIONS ============
    
    async function loadModalRegions() {
        try {
            const response = await fetch(PSGC.regions);
            const regions = await response.json();
            
            modalRegion.innerHTML = '<option value="">Select Region</option>';
            regions.forEach(region => {
                const option = document.createElement('option');
                option.value = region.code;
                option.textContent = region.name;
                modalRegion.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading regions:', error);
        }
    }

    async function loadModalProvinces(regionCode) {
        try {
            modalProvince.innerHTML = '<option value="">Select Province</option>';
            modalProvince.disabled = true;
            
            const response = await fetch(PSGC.regionProvinces(regionCode));
            const provinces = await response.json();
            
            provinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province.code;
                option.textContent = province.name;
                modalProvince.appendChild(option);
            });
            
            modalProvince.disabled = false;
        } catch (error) {
            console.error('Error loading provinces:', error);
        }
    }

    async function loadModalCities(provinceCode) {
        try {
            modalCity.innerHTML = '<option value="">Select City/Municipality</option>';
            modalCity.disabled = true;
            
            const response = await fetch(PSGC.provinceCities(provinceCode));
            const cities = await response.json();
            
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.code;
                option.textContent = city.name;
                modalCity.appendChild(option);
            });
            
            modalCity.disabled = false;
        } catch (error) {
            console.error('Error loading cities:', error);
        }
    }

    async function loadModalBarangays(cityCode) {
        try {
            modalBarangay.innerHTML = '<option value="">Select Barangay</option>';
            modalBarangay.disabled = true;
            
            const response = await fetch(PSGC.cityBarangays(cityCode));
            const barangays = await response.json();
            
            barangays.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.code;
                option.textContent = barangay.name;
                modalBarangay.appendChild(option);
            });
            
            modalBarangay.disabled = false;
        } catch (error) {
            console.error('Error loading barangays:', error);
        }
    }

    function updateModalHiddenInputs() {
        // Region
        const regionOption = modalRegion.options[modalRegion.selectedIndex];
        if (regionOption && modalRegion.value) {
            document.getElementById('modal_region_code').value = modalRegion.value;
            document.getElementById('modal_region_name').value = regionOption.textContent;
        }
        
        // Province
        const provinceOption = modalProvince.options[modalProvince.selectedIndex];
        if (provinceOption && modalProvince.value) {
            document.getElementById('modal_province_code').value = modalProvince.value;
            document.getElementById('modal_province_name').value = provinceOption.textContent;
        }
        
        // City
        const cityOption = modalCity.options[modalCity.selectedIndex];
        if (cityOption && modalCity.value) {
            document.getElementById('modal_city_code').value = modalCity.value;
            document.getElementById('modal_city_name').value = cityOption.textContent;
        }
        
        // Barangay
        const barangayOption = modalBarangay.options[modalBarangay.selectedIndex];
        if (barangayOption && modalBarangay.value) {
            document.getElementById('modal_barangay_code').value = modalBarangay.value;
            document.getElementById('modal_barangay_name').value = barangayOption.textContent;
        }
    }

    // Modal location change handlers
    modalRegion.addEventListener('change', async (e) => {
        const regionCode = e.target.value;
        if (regionCode) {
            await loadModalProvinces(regionCode);
            modalCity.innerHTML = '<option value="">Select City/Municipality</option>';
            modalCity.disabled = true;
            modalBarangay.innerHTML = '<option value="">Select Barangay</option>';
            modalBarangay.disabled = true;
            updateModalHiddenInputs();
        } else {
            modalProvince.innerHTML = '<option value="">Select Province</option>';
            modalProvince.disabled = true;
            modalCity.innerHTML = '<option value="">Select City/Municipality</option>';
            modalCity.disabled = true;
            modalBarangay.innerHTML = '<option value="">Select Barangay</option>';
            modalBarangay.disabled = true;
        }
    });

    modalProvince.addEventListener('change', async (e) => {
        const provinceCode = e.target.value;
        if (provinceCode) {
            await loadModalCities(provinceCode);
            modalBarangay.innerHTML = '<option value="">Select Barangay</option>';
            modalBarangay.disabled = true;
            updateModalHiddenInputs();
        } else {
            modalCity.innerHTML = '<option value="">Select City/Municipality</option>';
            modalCity.disabled = true;
            modalBarangay.innerHTML = '<option value="">Select Barangay</option>';
            modalBarangay.disabled = true;
        }
    });

    modalCity.addEventListener('change', async (e) => {
        const cityCode = e.target.value;
        if (cityCode) {
            await loadModalBarangays(cityCode);
            updateModalHiddenInputs();
        } else {
            modalBarangay.innerHTML = '<option value="">Select Barangay</option>';
            modalBarangay.disabled = true;
        }
    });

    modalBarangay.addEventListener('change', () => {
        updateModalHiddenInputs();
    });
  </script>

</body>
</html>

