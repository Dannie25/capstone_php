<?php 
session_start();
include 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $what_for = $conn->real_escape_string($_POST['what_for']);
    $quantity = intval($_POST['quantity']);
    
    // Validate quantity (1-100)
    if ($quantity < 1 || $quantity > 100) {
        $error_message = "Quantity must be between 1 and 100.";
    }
    
    $date_needed = $conn->real_escape_string($_POST['date_needed']);
    $time_needed = $conn->real_escape_string($_POST['time_needed']);
    $customer_name = $conn->real_escape_string($_POST['customer_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $email = $conn->real_escape_string($_POST['email']);
    $delivery_method = $conn->real_escape_string($_POST['delivery_method']);
    $note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';
    
    // Only proceed if no validation errors
    if (!isset($error_message)) {
    
    // Handle multiple file uploads (max 5)
    $design_files = [];
    if (isset($_FILES['design']) && !empty($_FILES['design']['name'][0])) {
        $upload_dir = 'uploads/subcontract_designs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_count = count($_FILES['design']['name']);
        $max_files = min($file_count, 5); // Limit to 5 files
        
        for ($i = 0; $i < $max_files; $i++) {
            if ($_FILES['design']['error'][$i] === UPLOAD_ERR_OK) {
                $file_extension = pathinfo($_FILES['design']['name'][$i], PATHINFO_EXTENSION);
                $file_name = uniqid() . '_' . $i . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['design']['tmp_name'][$i], $file_path)) {
                    $design_files[] = $file_path;
                }
            }
        }
    }
    
    // Convert array to JSON string for storage
    $design_file = !empty($design_files) ? json_encode($design_files) : null;
    
    // Insert into database
    $sql = "INSERT INTO subcontract_requests (user_id, what_for, quantity, design_file, date_needed, time_needed, customer_name, address, email, delivery_method, note, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isissssssss", $user_id, $what_for, $quantity, $design_file, $date_needed, $time_needed, $customer_name, $address, $email, $delivery_method, $note);
    
    if ($stmt->execute()) {
        $request_id = $stmt->insert_id;
        $_SESSION['subcon_success'] = true;
        $_SESSION['subcon_delivery_method'] = $delivery_method;
    // Create a user notification for subcontract request submission
    if ($user_id) {
      $notif_msg = "Your subcontract request #" . $request_id . " has been submitted and is pending approval.";
      $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'subcontract', ?)");
      if ($notifStmt) {
        $notifStmt->bind_param("is", $user_id, $notif_msg);
        $notifStmt->execute();
        $notifStmt->close();
      }
    }
        header("Location: my_orders.php");
        exit();
    } else {
        $error_message = "Error submitting request. Please try again.";
    }
    
    $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <?php include 'header.php'; ?>
<head>
  <title>SUB-CONTRACT - MTC Clothing</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Global site theme from home.php (page-specific rules only) */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%); color: #333; line-height: 1.6; min-height: 100vh; }

    /* NOTE: header styles are provided by header.php to keep header consistent across pages.
       This file only contains page-specific layout and form styles below. */

    /* Hero-ready styles (page can add hero section if desired) */
    .hero { display: grid; grid-template-columns: 1fr 1fr; min-height: 20vh; gap: 0; margin-bottom: 18px; }
    .hero-text { background: linear-gradient(135deg, #5b6b46 0%, #4a5a38 100%); color: #f5f5f5; display: flex; align-items: center; justify-content: center; padding: 20px 24px; position: relative; border-radius: 12px; }
    .hero-text h2 { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 8px; line-height: 1.2; color: #ffffff; }
    .hero-text p { font-size: 14px; color: #e8e8e8; margin-bottom: 6px; }
    .hero-images { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; align-items: center; justify-items: center; padding: 12px; background: #ffffff; border-radius: 12px; }

    /* Keep subcontract form/container styles but tune to match theme */
    .container { background: white; padding: 25px; max-width: 1000px; margin: 20px auto; border-radius: 12px; box-shadow: 0 8px 32px rgba(91, 107, 70, 0.12); }

    h2 { color: #5b6b46; font-size: 26px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.5px; display: flex; align-items: center; gap: 8px; }
    h2::before { content: "\f15c"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: 22px; }
    h3 { color: #5b6b46; font-size: 17px; font-weight: 600; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid #d9e6a7; }

    form { background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%); border-radius: 12px; padding: 20px; display: flex; gap: 20px; box-shadow: 0 4px 16px rgba(91, 107, 70, 0.08); border: 1px solid #e8e8e8; }
    .form-section { flex: 1; }
    label { display: block; margin-bottom: 6px; font-weight: 600; color: #5b6b46; font-size: 13px; }

    input[type="text"], input[type="number"], input[type="email"], input[type="date"], input[type="time"], select, textarea { width: 100%; padding: 8px 12px; margin-bottom: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 13px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: white; }
    input:focus, select:focus, textarea:focus { border-color: #5b6b46; outline: none; box-shadow: 0 0 0 3px rgba(91, 107, 70, 0.12); transform: translateY(-1px); }
    textarea { min-height: 80px; resize: vertical; line-height: 1.6; }

    .upload-box { width: 100%; max-width: 180px; height: 140px; border: 2px dashed #d0d0d0; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 5px 0 12px; background: white; font-size: 30px; color: #999; cursor: pointer; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .upload-box:hover { border-color: #5b6b46; background: #fafff8; transform: translateY(-4px); box-shadow: 0 8px 24px rgba(91, 107, 70, 0.15); }
    .upload-box i { font-size: 32px; margin-bottom: 6px; transition: all 0.3s; }
    .upload-box span { font-size: 12px; font-weight: 600; text-align: center; padding: 0 10px; color: #666; }

    .row { display: flex; gap: 10px; margin-bottom: 0; }
    .row > * { flex: 1; }

    .submit-btn { background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; border: none; width: 50px; height: 50px; border-radius: 50%; font-size: 18px; cursor: pointer; margin: 15px auto 0; display: flex; align-items: center; justify-content: center; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 6px 20px rgba(91, 107, 70, 0.3); position: relative; }
    .submit-btn::before { content: ''; position: absolute; inset: -4px; border-radius: 50%; background: linear-gradient(135deg, #d9e6a7 0%, #c8d99a 100%); z-index: -1; opacity: 0; transition: opacity 0.3s; }
    .submit-btn:hover { background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%); transform: translateY(-4px) scale(1.05); box-shadow: 0 10px 30px rgba(91, 107, 70, 0.4); }
    .submit-btn:hover::before { opacity: 1; }
    .submit-btn:active { transform: translateY(-2px) scale(1.02); }

    small { display: block; margin-top: -8px; margin-bottom: 10px; color: #888; font-size: 11px; font-style: italic; }
    small i { margin-right: 4px; color: #5b6b46; }

    @media (max-width: 768px) {
      form { flex-direction: column; padding: 25px; }
      .container { padding: 25px; margin: 20px; border-radius: 16px; }
      h2 { font-size: 28px; }
      h3 { font-size: 20px; }
      .row { flex-direction: column; gap: 0; }
      .upload-box { max-width: 100%; height: 160px; }
      .submit-btn { width: 56px; height: 56px; font-size: 20px; }
    }
    /* Compact notification dropdown */
    .notification-panel { display: none; position: absolute; top: 50px; right: 10px; width: 240px; background: #ffffff; border-radius: 8px; box-shadow: 0 6px 20px rgba(0,0,0,0.12); z-index: 1000; overflow: hidden; font-size: 12px; }
    .notification-header { padding: 8px 10px; background: #d9e6a7; font-weight: 700; display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
    .notification-header a { font-size: 11px; text-decoration: none; color: #333; }
    .notification-list { max-height: 200px; overflow-y: auto; }
    .notification-list::-webkit-scrollbar { width: 6px; }
    .notification-list::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #7a8f5e, #5b6b46); border-radius: 10px; }
    .notification-item { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 12px; }
    .notification-item:last-child { border-bottom: none; }
  </style>
</head>
<body>
  <div class="container">
    <!-- NOTIFICATION DROPDOWN -->
    <div class="notification-panel" id="notifPanel">
      <div class="notification-header">
        Notification
        <a href="#">Mark all as Read</a>
      </div>
      <div class="notification-list">
        <div class="notification-item">
          <strong>Your subcontract request was received</strong>
          We'll review your request and get back to you with a quote.
        </div>
        <div class="notification-item">
          <strong>Reminder:</strong>
          Please ensure your design files are high-resolution images for best results.
        </div>
      </div>
    </div>
    <h2>SUB-CONTRACT</h2>
    <p style="color: #666; font-size: 13px; line-height: 1.5; margin-bottom: 15px; max-width: 800px;">Submit your custom order request and let us bring your designs to life with quality craftsmanship</p>
    <form method="post" enctype="multipart/form-data">
      <div class="form-section">
        <h3>Contract Details</h3>
        <label>What for:</label>
        <input type="text" name="what_for" placeholder="e.g. School Jersey" required>
        <label>Quantity:</label>
        <input type="number" name="quantity" min="1" max="100" required>
        <small style="color: #666; display: block; margin-top: -8px; margin-bottom: 10px; font-style: italic; font-size: 11px;">
          <i class="fas fa-info-circle"></i> Maximum quantity: 100
        </small>
        <label>Upload Design (Max 5 images):</label>
        <label class="upload-box" id="uploadBox">
          <input type="file" name="design[]" id="designInput" style="display:none;" accept="image/*" multiple>
          <i class="fas fa-plus"></i>
          <span>Click to upload</span>
        </label>
        <div id="previewContainer" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;"></div>
        <label>Target Date:</label>
        <small style="color: #666; display: block; margin-bottom: 6px; font-style: italic; font-size: 11px;">
          <i class="fas fa-info-circle"></i> Please select a date at least 1 week from today to allow time for production.
        </small>
        <div class="row">
          <input type="date" name="date_needed" required>
          <input type="time" name="time_needed" required>
        </div>
      </div>
      <div class="form-section">
        <h3>Customer Details</h3>
        <label>Name:</label>
        <input type="text" name="customer_name" placeholder="Input Name" required>
        <label>Note:</label>
        <textarea name="note" placeholder="Input Description"></textarea>
        
        <!-- Hidden fields - will be filled during acceptance -->
        <input type="hidden" name="address" value="">
        <input type="hidden" name="email" value="">
        <input type="hidden" name="delivery_method" value="">
      </div>
      <button type="submit" class="submit-btn" id="submitBtn" title="Submit Request">
        <i class="fas fa-check"></i>
      </button>
    </form>
  </div>
  
  <!-- Confirmation Modal -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border: none;">
        <div class="modal-header" style="background: linear-gradient(135deg, #5b6b46 0%, #6d8050 100%); color: white; border: none; padding: 15px 20px;">
          <h5 class="modal-title" style="font-size: 1.1rem; font-weight: 600;">
            <i class="fas fa-clipboard-check me-2"></i>Confirm Your Request
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="font-size: 1rem;"></button>
        </div>
        <div class="modal-body" style="padding: 20px; background: #fafafa;">
          <div style="text-align: center; margin-bottom: 15px;">
            <i class="fas fa-file-contract" style="font-size: 2rem; color: #5b6b46; margin-bottom: 8px;"></i>
            <p style="color: #666; font-size: 0.9rem; margin: 0;">Please review your subcontract request details</p>
          </div>
          
          <div style="background: white; padding: 15px; border-radius: 10px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
              <div style="padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #5b6b46;">
                <p style="margin: 0; color: #888; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">What for</p>
                <p style="margin: 3px 0 0 0; font-weight: 600; font-size: 0.9rem; color: #333;" id="confirmWhatFor"></p>
              </div>
              <div style="padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #5b6b46;">
                <p style="margin: 0; color: #888; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Quantity</p>
                <p style="margin: 3px 0 0 0; font-weight: 600; font-size: 0.9rem; color: #333;" id="confirmQuantity"></p>
              </div>
              <div style="padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #5b6b46;">
                <p style="margin: 0; color: #888; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Customer Name</p>
                <p style="margin: 3px 0 0 0; font-weight: 600; font-size: 0.9rem; color: #333;" id="confirmCustomer"></p>
              </div>
            </div>
            <div style="padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #5b6b46; margin-top: 12px;">
              <p style="margin: 0; color: #888; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Target Date & Time</p>
              <p style="margin: 3px 0 0 0; font-weight: 600; font-size: 0.9rem; color: #333;" id="confirmDate"></p>
            </div>
          </div>
          
          <div class="alert" style="background: #e8f4f8; border: 2px solid #5b6b46; border-radius: 8px; padding: 10px 12px; margin: 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <i class="fas fa-info-circle" style="font-size: 1.1rem; color: #5b6b46;"></i>
              <div>
                <p style="margin: 0; font-weight: 600; color: #5b6b46; font-size: 0.85rem;">What happens next?</p>
                <p style="margin: 3px 0 0 0; color: #555; font-size: 0.8rem;">Your request will be reviewed by our admin team. Once approved, you'll receive notification about the delivery details.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border: none; padding: 12px 20px; background: white; justify-content: space-between;">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="padding: 8px 20px; border-radius: 8px; font-weight: 600; border: 2px solid #ddd; transition: all 0.3s; font-size: 0.85rem;">
            <i class="fas fa-times me-2"></i>Cancel
          </button>
          <button type="button" class="btn" id="confirmSubmitBtn" style="background: linear-gradient(135deg, #5b6b46 0%, #6d8050 100%); color: white; padding: 8px 24px; border-radius: 8px; font-weight: 600; border: none; box-shadow: 0 4px 12px rgba(91, 107, 70, 0.3); transition: all 0.3s; font-size: 0.85rem;">
            <i class="fas fa-check-circle me-2"></i>Confirm & Submit
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <style>
    #confirmModal {
      z-index: 9999 !important;
    }
    
    #confirmModal .modal-backdrop {
      z-index: 9998 !important;
    }
    
    #confirmModal .modal-dialog {
      animation: slideDown 0.4s ease-out;
      z-index: 10000;
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    
    #confirmModal .btn-light:hover {
      background: #e9ecef;
      border-color: #adb5bd;
      transform: translateY(-2px);
    }
    
    #confirmSubmitBtn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(91, 107, 70, 0.4);
    }
    
    @media (max-width: 768px) {
      #confirmModal .modal-body > div:first-child {
        grid-template-columns: 1fr !important;
      }
    }
  </style>
  
  <?php if (isset($error_message)): ?>
  <div class="modal fade show" id="errorModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius: 15px;">
        <div class="modal-header" style="background: #dc3545; color: white; border: none;">
          <h5 class="modal-title"><i class="fas fa-exclamation-circle me-2"></i>Error</h5>
        </div>
        <div class="modal-body" style="padding: 30px; text-align: center;">
          <p><?php echo $error_message; ?></p>
        </div>
        <div class="modal-footer" style="border: none;">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('errorModal').style.display='none'">Close</button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Set minimum date to 1 week from today
    const dateInput = document.querySelector('input[name="date_needed"]');
    if (dateInput) {
      const today = new Date();
      const oneWeekFromToday = new Date(today.getTime() + (7 * 24 * 60 * 60 * 1000));
      const minDate = oneWeekFromToday.toISOString().split('T')[0];
      dateInput.setAttribute('min', minDate);
    }
    
    // Prevent typing quantity greater than 100
    const quantityInput = document.querySelector('input[name="quantity"]');
    if (quantityInput) {
      quantityInput.addEventListener('input', function(e) {
        let value = parseInt(this.value);
        if (value > 100) {
          this.value = 100;
        }
        if (value < 0) {
          this.value = '';
        }
      });
    }
    
    // Confirmation modal before submit
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submitBtn');
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = new bootstrap.Modal(confirmModalEl, {
      backdrop: 'static',
      keyboard: false
    });
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    
    submitBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      // Validate form first
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }
      
      // Get form values
      const whatFor = form.querySelector('[name="what_for"]').value;
      const quantity = parseInt(form.querySelector('[name="quantity"]').value);
      
      // Validate quantity
      if (quantity < 1 || quantity > 100) {
        alert('Quantity must be between 1 and 100');
        return;
      }
      const customerName = form.querySelector('[name="customer_name"]').value;
      const dateNeeded = form.querySelector('[name="date_needed"]').value;
      const timeNeeded = form.querySelector('[name="time_needed"]').value;
      
      // Populate modal
      document.getElementById('confirmWhatFor').textContent = whatFor;
      document.getElementById('confirmQuantity').textContent = quantity;
      document.getElementById('confirmCustomer').textContent = customerName;
      
      // Format date
      const date = new Date(dateNeeded + 'T' + timeNeeded);
      const formattedDate = date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + 
                           ' at ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
      document.getElementById('confirmDate').textContent = formattedDate;
      
      // Show modal
      confirmModal.show();
    });
    
    // Handle confirm button
    confirmSubmitBtn.addEventListener('click', function() {
      confirmModal.hide();
      // Small delay to allow modal to close smoothly
      setTimeout(function() {
        form.submit();
      }, 300);
    });
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
    // Handle multiple image upload preview (max 5)
    const designInput = document.getElementById('designInput');
    const uploadBox = document.getElementById('uploadBox');
    const previewContainer = document.getElementById('previewContainer');
    
    if (designInput && uploadBox && previewContainer) {
      designInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        
        // Limit to 5 files
        if (files.length > 5) {
          alert('Maximum 5 images allowed. Only the first 5 will be uploaded.');
          // Create a new FileList with only the first 5 files
          const dt = new DataTransfer();
          files.slice(0, 5).forEach(file => dt.items.add(file));
          designInput.files = dt.files;
        }
        
        // Clear previous previews
        previewContainer.innerHTML = '';
        
        // Display previews for each file (max 5)
        const filesToShow = files.slice(0, 5);
        filesToShow.forEach((file, index) => {
          if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
              const previewWrapper = document.createElement('div');
              previewWrapper.style.position = 'relative';
              previewWrapper.style.display = 'inline-block';
              
              const img = document.createElement('img');
              img.src = event.target.result;
              img.style.width = '80px';
              img.style.height = '80px';
              img.style.objectFit = 'cover';
              img.style.borderRadius = '12px';
              img.style.boxShadow = '0 4px 12px rgba(91, 107, 70, 0.2)';
              img.style.border = '2px solid #e0e0e0';
              img.style.transition = 'all 0.3s';
              img.onmouseover = function() { this.style.transform = 'scale(1.05)'; this.style.boxShadow = '0 6px 20px rgba(91, 107, 70, 0.3)'; };
              img.onmouseout = function() { this.style.transform = 'scale(1)'; this.style.boxShadow = '0 4px 12px rgba(91, 107, 70, 0.2)'; };
              
              // Add remove button
              const removeBtn = document.createElement('button');
              removeBtn.innerHTML = '<i class="fas fa-times"></i>';
              removeBtn.type = 'button';
              removeBtn.style.position = 'absolute';
              removeBtn.style.top = '-10px';
              removeBtn.style.right = '-10px';
              removeBtn.style.width = '22px';
              removeBtn.style.height = '22px';
              removeBtn.style.borderRadius = '50%';
              removeBtn.style.background = 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)';
              removeBtn.style.color = 'white';
              removeBtn.style.border = '2px solid white';
              removeBtn.style.cursor = 'pointer';
              removeBtn.style.fontSize = '12px';
              removeBtn.style.lineHeight = '1';
              removeBtn.style.display = 'flex';
              removeBtn.style.alignItems = 'center';
              removeBtn.style.justifyContent = 'center';
              removeBtn.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
              removeBtn.style.transition = 'all 0.3s';
              removeBtn.onmouseover = function() { this.style.transform = 'scale(1.15)'; this.style.boxShadow = '0 4px 12px rgba(231, 76, 60, 0.4)'; };
              removeBtn.onmouseout = function() { this.style.transform = 'scale(1)'; this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)'; };
              
              removeBtn.addEventListener('click', function() {
                // Remove this preview
                previewWrapper.remove();
                
                // Update the file input
                const dt = new DataTransfer();
                const currentFiles = Array.from(designInput.files);
                currentFiles.forEach((f, i) => {
                  if (i !== index) {
                    dt.items.add(f);
                  }
                });
                designInput.files = dt.files;
                
                // Show upload box if no images left
                if (designInput.files.length === 0) {
                  uploadBox.style.display = 'flex';
                }
              });
              
              previewWrapper.appendChild(img);
              previewWrapper.appendChild(removeBtn);
              previewContainer.appendChild(previewWrapper);
            };
            reader.readAsDataURL(file);
          }
        });
        
        // Hide upload box if files are selected
        if (filesToShow.length > 0) {
          uploadBox.style.display = 'none';
        }
      });
      
      // Label already triggers input naturally, no need for additional click handler
      // uploadBox.addEventListener('click', function() {
      //   designInput.click();
      // });
    }
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