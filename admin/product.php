<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Ensure max_per_order column exists
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS max_per_order INT NULL DEFAULT NULL AFTER price");

// Function to update product sizes
function updateProductSizes($conn, $product_id, $sizes) {
    // Delete existing sizes
    $conn->query("DELETE FROM product_sizes WHERE product_id = $product_id");
    
    // Add new sizes
    if (!empty($sizes)) {
        $sizes_array = is_array($sizes) ? $sizes : explode(',', $sizes);
        $stmt = $conn->prepare("INSERT INTO product_sizes (product_id, size) VALUES (?, ?)");
        foreach ($sizes_array as $size) {
            $size = trim($size);
            if (!empty($size)) {
                $stmt->bind_param("is", $product_id, $size);
                $stmt->execute();
            }
        }
    }
}

// Function to update product colors with quantities and images
function updateProductColors($conn, $product_id, $colors, $color_quantities = [], $color_images = []) {
    // Delete existing colors
    $conn->query("DELETE FROM product_colors WHERE product_id = $product_id");
    
    // Add new colors with quantities and images
    if (!empty($colors)) {
        $stmt = $conn->prepare("INSERT INTO product_colors (product_id, color, quantity, color_image) VALUES (?, ?, ?, ?)");
        foreach ($colors as $color) {
            $color = trim($color);
            if (!empty($color)) {
                $qty = isset($color_quantities[$color]) && is_numeric($color_quantities[$color]) ? 
                       (int)$color_quantities[$color] : 0;
                $color_img = isset($color_images[$color]) ? $color_images[$color] : null;
                $stmt->bind_param("isis", $product_id, $color, $qty, $color_img);
                $stmt->execute();
            }
        }
    }
}

// Function to update color-size inventory matrix
function updateColorSizeInventory($conn, $product_id, $colors, $sizes, $inventory_data) {
    // Delete existing inventory for this product
    $conn->query("DELETE FROM product_color_size_inventory WHERE product_id = $product_id");
    
    // Insert new inventory data
    if (!empty($colors) && !empty($sizes) && !empty($inventory_data)) {
        $stmt = $conn->prepare("INSERT INTO product_color_size_inventory (product_id, color, size, quantity) VALUES (?, ?, ?, ?)");
        foreach ($colors as $color) {
            $color = trim($color);
            if (!empty($color)) {
                foreach ($sizes as $size) {
                    $size = trim($size);
                    if (!empty($size)) {
                        $key = $color . '_' . $size;
                        $qty = isset($inventory_data[$key]) && is_numeric($inventory_data[$key]) ? 
                               (int)$inventory_data[$key] : 0;
                        $stmt->bind_param("issi", $product_id, $color, $size, $qty);
                        $stmt->execute();
                    }
                }
            }
        }
    }
}

// Function to handle multiple product images
function handleProductImages($conn, $product_id, $files, $existing_images = []) {
    $uploaded_images = [];
    
    // Handle new image uploads
    if (isset($files['name']) && is_array($files['name'])) {
        $file_count = count($files['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] == 0 && !empty($files['name'][$i])) {
                $imageFileType = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $new_filename = uniqid() . '.' . $imageFileType;
                $target_file = "../img/" . $new_filename;
                
                if (move_uploaded_file($files['tmp_name'][$i], $target_file)) {
                    $uploaded_images[] = $new_filename;
                }
            }
        }
    }
    
    // Combine existing and new images
    $all_images = array_merge($existing_images, $uploaded_images);
    
    // Delete all existing product_images records for this product
    $conn->query("DELETE FROM product_images WHERE product_id = $product_id");
    
    // Insert all images (all images are equal, no main image concept)
    if (!empty($all_images)) {
        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image) VALUES (?, ?)");
        foreach ($all_images as $img) {
            $stmt->bind_param("is", $product_id, $img);
            $stmt->execute();
        }
    }
    
    return $all_images;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_product'])) {
    }
    if (isset($_POST['update_product'])) {
        $id = $_POST['product_id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $subcategory = $_POST['subcategory'] ?? '';
        $price = $_POST['price'];
        $max_per_order = isset($_POST['max_per_order']) && $_POST['max_per_order'] !== '' ? intval($_POST['max_per_order']) : null;
        $description = $_POST['description'] ?? '';
        $material = $_POST['material'] ?? '';
        $sizes = isset($_POST['sizes']) && is_array($_POST['sizes']) ? $_POST['sizes'] : [];
        $colors = isset($_POST['colors']) && is_array($_POST['colors']) ? $_POST['colors'] : [];
        
        // Handle discount fields
        $discount_enabled = isset($_POST['discount_enabled']) ? 1 : 0;
        $discount_type = $discount_enabled ? ($_POST['discount_type'] ?? null) : null;
        $discount_value = $discount_enabled ? ($_POST['discount_value'] ?? null) : null;
        
        // Update product details (without image for now)
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, subcategory = ?, price = ?, description = ?, material = ?, discount_enabled = ?, discount_type = ?, discount_value = ? WHERE id = ?");
        $stmt->bind_param("sssssssssi", $name, $category, $subcategory, $price, $description, $material, $discount_enabled, $discount_type, $discount_value, $id);
        
        if ($stmt->execute()) {
            // Handle multiple product images
            $existing_product_images = [];
            if (isset($_POST['existing_images']) && is_array($_POST['existing_images'])) {
                $existing_product_images = $_POST['existing_images'];
            }
            
            // Check if at least one image exists (either existing or new)
            $has_new_images = isset($_FILES['product_images']) && 
                             isset($_FILES['product_images']['name']) && 
                             is_array($_FILES['product_images']['name']) && 
                             !empty($_FILES['product_images']['name'][0]) && 
                             $_FILES['product_images']['error'][0] !== UPLOAD_ERR_NO_FILE;
            
            $total_images = count($existing_product_images) + ($has_new_images ? count(array_filter($_FILES['product_images']['name'])) : 0);
            
            if ($total_images === 0) {
                $error = "<div class='alert alert-danger'>Please keep or upload at least one product image.</div>";
            } else {
                if ($has_new_images) {
                    handleProductImages($conn, $id, $_FILES['product_images'], $existing_product_images);
                } else if (!empty($existing_product_images)) {
                    // No new uploads but preserve existing
                    handleProductImages($conn, $id, ['name' => [], 'tmp_name' => [], 'error' => []], $existing_product_images);
                }
            // Update sizes
            updateProductSizes($conn, $id, $sizes);
            
            // Handle color image uploads
            $color_images = [];
            
            // First, get existing color images to preserve them
            $existing_images = [];
            $existing_result = $conn->query("SELECT color, color_image FROM product_colors WHERE product_id = $id");
            if ($existing_result) {
                while ($row = $existing_result->fetch_assoc()) {
                    if (!empty($row['color_image'])) {
                        $existing_images[$row['color']] = $row['color_image'];
                    }
                }
            }
            
            // Process new uploads
            if (isset($_FILES['color_images'])) {
                foreach ($_FILES['color_images']['name'] as $color => $filename) {
                    if ($_FILES['color_images']['error'][$color] == 0) {
                        $imageFileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $new_filename = 'color_' . uniqid() . '.' . $imageFileType;
                        $target_file = "../img/" . $new_filename;
                        if (move_uploaded_file($_FILES['color_images']['tmp_name'][$color], $target_file)) {
                            $color_images[$color] = $new_filename;
                        }
                    }
                }
            }
            
            // Merge existing images with new uploads (new uploads take priority)
            foreach ($colors as $color) {
                if (!isset($color_images[$color]) && isset($existing_images[$color])) {
                    $color_images[$color] = $existing_images[$color];
                }
            }
            
                // Update colors with quantities and images
                $color_qty = $_POST['color_qty'] ?? [];
                updateProductColors($conn, $id, $colors, $color_qty, $color_images);
                
                // Update color-size inventory matrix
                $inventory_data = $_POST['inventory'] ?? [];
                
                // DEBUG: Uncomment to see what's being submitted
                // echo "<pre>Colors: "; print_r($colors); echo "</pre>";
                // echo "<pre>Sizes: "; print_r($sizes); echo "</pre>";
                // echo "<pre>Inventory Data: "; print_r($inventory_data); echo "</pre>";
                // die();
                
                updateColorSizeInventory($conn, $id, $colors, $sizes, $inventory_data);
                
                $message = "<div class='alert alert-success'>Product updated successfully!</div>";
            }
        } else {
            $error = "<div class='alert alert-danger'>Error updating product: " . $conn->error . "</div>";
        }
    }
    
    // Handle add new product
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $subcategory = $_POST['subcategory'] ?? '';
        $price = $_POST['price'];
        $description = $_POST['description'] ?? '';
        $material = $_POST['material'] ?? '';
        $sizes = $_POST['sizes'] ?? [];
        $colors = $_POST['colors'] ?? [];
        $color_qty = $_POST['color_qty'] ?? [];
        
        // Check if at least one image is uploaded
        $has_images = isset($_FILES['product_images']) && 
                      isset($_FILES['product_images']['name']) && 
                      is_array($_FILES['product_images']['name']) && 
                      !empty($_FILES['product_images']['name'][0]) && 
                      $_FILES['product_images']['error'][0] !== UPLOAD_ERR_NO_FILE;
        
        if (!$has_images) {
            $error = "<div class='alert alert-danger'>Please select at least one product image.</div>";
        }
        
        // Handle discount fields
        $discount_enabled = isset($_POST['discount_enabled']) ? 1 : 0;
        $discount_type = $discount_enabled ? ($_POST['discount_type'] ?? null) : null;
        $discount_value = $discount_enabled ? ($_POST['discount_value'] ?? null) : null;
        
        // Insert new product (image will be set by handleProductImages)
        if ($has_images) {
            $stmt = $conn->prepare("INSERT INTO products (name, category, subcategory, price, description, material, image, discount_enabled, discount_type, discount_value) VALUES (?, ?, ?, ?, ?, ?, '', ?, ?, ?)");
            $stmt->bind_param("sssdsssss", $name, $category, $subcategory, $price, $description, $material, $discount_enabled, $discount_type, $discount_value);
            
            if ($stmt->execute()) {
                $product_id = $conn->insert_id;
                
                // Handle multiple product images
                handleProductImages($conn, $product_id, $_FILES['product_images']);
                
                // Add sizes
                updateProductSizes($conn, $product_id, $sizes);
                
                // Handle color image uploads
                $color_images = [];
                if (isset($_FILES['color_images'])) {
                    foreach ($_FILES['color_images']['name'] as $color => $filename) {
                        if ($_FILES['color_images']['error'][$color] == 0) {
                            $imageFileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $new_filename = 'color_' . uniqid() . '.' . $imageFileType;
                            $target_file = "../img/" . $new_filename;
                            if (move_uploaded_file($_FILES['color_images']['tmp_name'][$color], $target_file)) {
                                $color_images[$color] = $new_filename;
                            }
                        }
                    }
                }
                
                // Add colors with quantities and images
                if (!empty($colors)) {
                    $stmt = $conn->prepare("INSERT INTO product_colors (product_id, color, quantity, color_image) VALUES (?, ?, ?, ?)");
                    foreach ($colors as $color) {
                        $qty = isset($color_qty[$color]) && is_numeric($color_qty[$color]) ? (int)$color_qty[$color] : 0;
                        $color_img = isset($color_images[$color]) ? $color_images[$color] : null;
                        $stmt->bind_param("isis", $product_id, $color, $qty, $color_img);
                        $stmt->execute();
                    }
                }
                
                // Add color-size inventory matrix
                $inventory_data = $_POST['inventory'] ?? [];
                
                // DEBUG: Uncomment to see what's being submitted
                // echo "<pre>Colors: "; print_r($colors); echo "</pre>";
                // echo "<pre>Sizes: "; print_r($sizes); echo "</pre>";
                // echo "<pre>Inventory Data: "; print_r($inventory_data); echo "</pre>";
                // die();
                
                updateColorSizeInventory($conn, $product_id, $colors, $sizes, $inventory_data);
                
                $message = "<div class='alert alert-success'>Product added successfully!</div>";
            } else {
                $error = "<div class='alert alert-danger'>Error adding product: " . $conn->error . "</div>";
            }
        }
    }
}

// Handle delete product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $id");
    $conn->query("DELETE FROM product_sizes WHERE product_id = $id");
    $conn->query("DELETE FROM product_colors WHERE product_id = $id");
    $message = "<div class='alert alert-success'>Product deleted successfully!</div>";
}

// Get all products
$sql = "SELECT p.*, 
               (SELECT GROUP_CONCAT(DISTINCT size) FROM product_sizes WHERE product_id = p.id) as sizes,
               (SELECT GROUP_CONCAT(DISTINCT color) FROM product_colors WHERE product_id = p.id) as colors
        FROM products p 
        ORDER BY p.id DESC";
$products = $conn->query($sql);

// Get all unique categories and subcategories
$categories = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
$subcategories = $conn->query("SELECT DISTINCT subcategory FROM products WHERE subcategory IS NOT NULL AND subcategory != ''");

// Get all available sizes and colors
$all_sizes = $conn->query("SELECT DISTINCT size FROM product_sizes ORDER BY size");
$all_colors = $conn->query("SELECT DISTINCT color FROM product_colors ORDER BY color");
// CMS-managed sizes/colors
$cms_sizes = [];
$res = $conn->query("SELECT size FROM sizes ORDER BY size");
while ($row = $res->fetch_assoc()) $cms_sizes[] = $row['size'];
$cms_colors = [];
$res = $conn->query("SELECT color FROM colors ORDER BY color");
while ($row = $res->fetch_assoc()) $cms_colors[] = $row['color'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MTC Clothing - Product Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/sidebar.css">
  <style>
    :root {
      --primary-color: #5b6b46;
      --primary-light: #d9e6a7;
      --primary-dark: #4a5a37;
      --light-gray: #f8f8f8;
      --dark-gray: #333;
      --white: #fff;
      --border-color: #e0e0e0;
      --text-color: #333;
      --text-light: #666;
      --success-color: #4CAF50;
      --danger-color: #f44336;
      --warning-color: #ff9800;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }
    
    body {
      background: #fafafa;
      color: var(--text-color);
      line-height: 1.6;
    }
    
    .container {
      width: 95%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }
    
    header {
      background: var(--primary-light);
      color: var(--dark-gray);
      padding: 15px 0;
      margin-bottom: 30px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    h1 {
      color: var(--dark-gray);
      margin-bottom: 20px;
    }
    
    .btn {
      display: inline-block;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      color: white;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-1px);
    }
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #5a6268;
      transform: translateY(-1px);
    }
    
    .btn-danger {
      background-color: var(--danger-color);
      color: white;
    }
    
    .btn-danger:hover {
      background-color: #d32f2f;
      transform: translateY(-1px);
    }
    
    .card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 20px;
      overflow: hidden;
      border: 1px solid var(--border-color);
    }
    
    .card-header {
      background-color: var(--primary-light);
      color: var(--dark-gray);
      padding: 15px 20px;
      font-size: 18px;
      font-weight: 600;
      border-bottom: 1px solid rgba(0,0,0,0.1);
    }
    
    .card-body {
      padding: 20px;
    }
    
    .table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .table th, .table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--border-color);
    }
    
    .table th {
      background-color: var(--light-gray);
      font-weight: 600;
      color: var(--dark-gray);
    }
    
    .table tr:hover {
      background-color: rgba(217, 230, 167, 0.1);
    }
    
    .img-thumbnail {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
      border: 1px solid var(--border-color);
    }
    
    .alert {
      padding: 12px 15px;
      border-radius: 4px;
      margin-bottom: 20px;
    }
    
    .alert-success {
      background-color: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
    }
    
    .alert-danger {
      background-color: #ffebee;
      color: #c62828;
      border: 1px solid #ffcdd2;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: var(--dark-gray);
    }
    
    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      font-size: 14px;
      transition: border-color 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(91, 107, 70, 0.25);
      outline: none;
    }
    
    .select2-container--default .select2-selection--multiple {
      border: 1px solid var(--border-color);
      min-height: 38px;
      border-radius: 4px;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      background-color: var(--primary-color);
      border: 1px solid var(--primary-dark);
      color: white;
      padding: 2px 8px;
      border-radius: 4px;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
      color: white;
      margin-right: 5px;
    }
    
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      overflow-y: auto;
    }
    
    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 25px;
      width: 90%;
      max-width: 700px;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      position: relative;
    }
    
    .close {
      position: absolute;
      right: 20px;
      top: 15px;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
      color: var(--text-light);
      transition: color 0.3s;
    }
    
    .close:hover {
      color: var(--dark-gray);
    }
    
    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
      margin-right: 5px;
      margin-bottom: 5px;
    }
    
    .badge-success {
      background-color: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
    }
    
    .badge-info {
      background-color: #e3f2fd;
      color: #1565c0;
      border: 1px solid #bbdefb;
    }
    
    .badge-warning {
      background-color: #fff8e1;
      color: #ff8f00;
      border: 1px solid #ffecb3;
    }
    
    .action-buttons .btn {
      padding: 5px 10px;
      font-size: 12px;
      margin-right: 5px;
    }
    
    /* Color-Size Matrix Styles */
    .inventory-matrix {
      margin: 20px 0;
      overflow-x: auto;
    }
    
    .inventory-matrix table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .inventory-matrix th {
      background: #2c2c2c;
      color: white;
      padding: 12px 8px;
      text-align: center;
      font-weight: 600;
      border: 1px solid #444;
      font-size: 14px;
    }
    
    .inventory-matrix th:first-child {
      background: transparent;
      border: none;
    }
    
    .inventory-matrix td {
      padding: 8px;
      text-align: center;
      border: 1px solid #ddd;
      background: white;
    }
    
    .inventory-matrix td:first-child {
      background: #2c2c2c;
      color: white;
      font-weight: 600;
      text-align: center;
      border: 1px solid #444;
      font-size: 14px;
    }
    
    .inventory-matrix input[type="number"] {
      width: 70px;
      padding: 6px 8px;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      text-align: center;
      font-size: 14px;
    }
    
    .inventory-matrix input[type="number"]:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 2px rgba(91, 107, 70, 0.1);
    }
    
    .inventory-matrix tr:hover td {
      background-color: rgba(217, 230, 167, 0.1);
    }
    
    .inventory-matrix tr:hover td:first-child {
      background: #3a3a3a;
    }
    
    .inventory-matrix-container {
      background: white;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid var(--border-color);
      margin-bottom: 20px;
    }
    
    .inventory-matrix-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .inventory-matrix-header h4 {
      margin: 0;
      color: var(--dark-gray);
    }
    
    @media (max-width: 768px) {
      .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      
      .modal-content {
        width: 95%;
        margin: 10% auto;
        padding: 15px;
      }
      
      .header-content {
        flex-direction: column;
        gap: 10px;
      }
      
      .btn {
        padding: 8px 15px;
        font-size: 13px;
      }
      
      .inventory-matrix input[type="number"] {
        width: 60px;
        font-size: 13px;
      }
    }
  </style>
</head>
<body>

<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  
  <div class="main-content">
    <div class="content-header">
      <h1><i class="bi bi-box-seam"></i> Product Management</h1>
    </div>
    
    <div class="content-body">
      <div class="container-fluid">
    <?php if (!empty($message)) echo $message; ?>
    <?php if (!empty($error)) echo $error; ?>
    
    <div class="card">
      <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <span>Product List</span>
          <button onclick="openAddModal()" class="btn btn-primary" style="margin-left: auto;">
            <i class="fas fa-plus"></i> Add New Product
          </button>
        </div>
      </div>
      <script>
      function openSizeColorCMS() {
        window.open('size_color_cms.php', 'SizeColorCMS', 'width=700,height=600,resizable=yes,scrollbars=yes');
      }
      </script>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Subcategory</th>
                <th>Price</th>
                <th>Sizes</th>
                <th>Colors</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while($product = $products->fetch_assoc()): ?>
                <tr>
                  <td>
<?php
  // Get all product images
  $productId = (int)$product['id'];
  $imgResult = $conn->query("SELECT image FROM product_images WHERE product_id = $productId ORDER BY id");
  $productImages = [];
  if ($imgResult) {
    while ($imgRow = $imgResult->fetch_assoc()) {
      $productImages[] = $imgRow['image'];
    }
  }
  
  if (!empty($productImages)) {
    $firstImg = $productImages[0];
    $imgPath = '../img/' . $firstImg;
    $totalImages = count($productImages);
    $imagesJson = htmlspecialchars(json_encode($productImages), ENT_QUOTES, 'UTF-8');
    echo '<div style="position: relative; width: 60px; height: 60px; cursor: pointer;" onclick="viewProductImages(' . $productId . ', ' . $imagesJson . ')" title="Click to view all ' . $totalImages . ' image(s)">';
    if (file_exists($imgPath)) {
      echo '<img src="' . $imgPath . '" alt="Product Image" class="img-thumbnail" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">';
    }
    if ($totalImages > 1) {
      echo '<div style="position: absolute; bottom: 2px; right: 2px; background: rgba(0,0,0,0.8); color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: bold;">+' . ($totalImages - 1) . '</div>';
    }
    echo '</div>';
  } else {
    echo '<div style="width: 60px; height: 60px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 4px;"><i class="fas fa-image" style="font-size: 24px; color: #999;"></i></div>';
  }
?>
                  </td>
                  <td><?php echo htmlspecialchars($product['name']); ?></td>
                  <td><?php echo htmlspecialchars($product['category']); ?></td>
                  <td><?php echo !empty($product['subcategory']) ? htmlspecialchars($product['subcategory']) : '-'; ?></td>
                  <td>₱<?php echo number_format($product['price'], 2); ?></td>
                  <td>
                    <?php if (!empty($product['sizes'])): ?>
                      <?php 
                        $sizes = explode(',', $product['sizes']);
                        foreach ($sizes as $size): 
                      ?>
                        <span class="badge badge-success"><?php echo htmlspecialchars(trim($size)); ?></span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($product['colors'])): ?>
                      <?php 
                        $colors = explode(',', $product['colors']);
                        foreach ($colors as $color): 
                          $trimmedColor = trim($color);
                      ?>
                        <span class="badge badge-info"><?php echo htmlspecialchars($trimmedColor); ?></span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                  <td class="action-buttons">
                    <button onclick="editProduct(<?php echo $product['id']; ?>)" class="btn btn-primary btn-sm">
                      <i class="fas fa-edit"></i>
                    </button>
                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>


  <!-- Add Product Modal -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('addModal')">&times;</span>
      <h2>Add New Product</h2>
      <form method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(event, 'add')">
        <input type="hidden" name="add_product" value="1">
        
        <div class="form-group">
          <label for="name">Product Name *</label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="category">Category *</label>
              <select id="category" name="category" class="form-control" required>
                <option value="">Select Category</option>
                <option value="Men">Men</option>
                <option value="Women">Women</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="subcategory">Subcategory</label>
              <select id="subcategory" name="subcategory" class="form-control">
                <option value="">Select Subcategory</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="price">Price (₱) *</label>
          <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
        </div>

        
        <div class="form-group">
          <label for="material">Material</label>
          <input type="text" id="material" name="material" class="form-control" placeholder="e.g., 100% Cotton">
        </div>

        <div class="form-group">
          <label><input type="checkbox" id="discount_enabled" name="discount_enabled" value="1" onchange="document.getElementById('discountFields').style.display = this.checked ? '' : 'none';"> Enable Discount</label>
        </div>
        <div class="form-group" id="discountFields" style="display:none;">
          <label for="discount_type">Discount Type</label>
          <select id="discount_type" name="discount_type" class="form-control">
            <option value="">Select type</option>
            <option value="percent">Percent (%)</option>
            <option value="fixed">Fixed Amount</option>
          </select>
          <label for="edit_discount_value" style="margin-top:10px;">Discount Value</label>
          <input type="number" id="edit_discount_value" name="discount_value" class="form-control" step="0.01" min="0">
        </div>
        
        <div style="margin-bottom:15px;text-align:right">
          <button type="button" class="btn btn-secondary" onclick="openSizeColorCMS()">
            <i class="fas fa-cogs"></i> Manage Sizes & Colors
          </button>
        </div>

        <div class="form-group">
          <label>Available Sizes</label>
          <div id="size-list">
            <?php 
              foreach ($cms_sizes as $size): ?>
              <div style="display:flex;align-items:center;margin-bottom:6px;gap:10px;">
                <input type="checkbox" name="sizes[]" value="<?php echo $size; ?>" id="add_size_<?php echo $size; ?>" onchange="updateAddInventoryMatrix()">
                <label for="add_size_<?php echo $size; ?>" style="min-width:40px;margin:0 8px 0 0;"> <?php echo $size; ?> </label>
              </div>
            <?php endforeach; ?>
          </div>
          <small class="text-muted">Check a size to enable.</small>
        </div>
        
        <div class="form-group">
          <label>Available Colors & Images</label>
          <div id="color-list">
            <?php 
              foreach ($cms_colors as $color): ?>
              <div style="display:flex;align-items:center;margin-bottom:10px;gap:10px;padding:8px;border:1px solid #e0e0e0;border-radius:4px;">
                <input type="checkbox" name="colors[]" value="<?php echo $color; ?>" id="add_color_<?php echo $color; ?>" onchange="updateAddInventoryMatrix()">
                <label for="add_color_<?php echo $color; ?>" style="min-width:70px;margin:0 8px 0 0;font-weight:500;"> <?php echo $color; ?> </label>
                <input type="file" name="color_images[<?php echo $color; ?>]" accept="image/*" class="form-control" style="width:200px;" disabled>
                <div class="color-img-preview" style="width:40px;height:40px;border:1px solid #ccc;border-radius:4px;overflow:hidden;display:none;">
                  <img src="" style="width:100%;height:100%;object-fit:cover;">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <small class="text-muted">Check a color and optionally upload an image to identify the color.</small>
        </div>
        
        <!-- Color-Size Inventory Matrix -->
        <div class="inventory-matrix-container" id="add-inventory-matrix-container" style="display:none;">
          <div class="inventory-matrix-header">
            <h4>📦 Quantity per Size & Color</h4>
          </div>
          <div class="inventory-matrix">
            <table id="add-inventory-matrix-table">
              <thead>
                <tr>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
          <small class="text-muted">Enter the quantity available for each size-color combination.</small>
        </div>
        <script>
        // Function to update Add modal inventory matrix
        function updateAddInventoryMatrix() {
          const selectedColors = [];
          const selectedSizes = [];
          
          // Get selected colors
          document.querySelectorAll('#color-list input[type=checkbox]:checked').forEach(cb => {
            selectedColors.push(cb.value);
          });
          
          // Get selected sizes
          document.querySelectorAll('#size-list input[type=checkbox]:checked').forEach(cb => {
            selectedSizes.push(cb.value);
          });
          
          // Show/hide matrix container
          const container = document.getElementById('add-inventory-matrix-container');
          if (selectedColors.length > 0 && selectedSizes.length > 0) {
            container.style.display = 'block';
            buildInventoryMatrix('add-inventory-matrix-table', selectedColors, selectedSizes, {});
          } else {
            container.style.display = 'none';
          }
          
          // Enable/disable file inputs based on color checkbox
          document.querySelectorAll('#color-list input[type=checkbox]').forEach(cb => {
            const parent = cb.parentNode;
            const fileInput = parent.querySelector('input[type=file]');
            if (fileInput) {
              fileInput.disabled = !cb.checked;
              if (!cb.checked) fileInput.value = '';
            }
          });
        }
        
        // Function to build the inventory matrix table
        function buildInventoryMatrix(tableId, colors, sizes, existingData) {
          const table = document.getElementById(tableId);
          if (!table) return;
          
          const thead = table.querySelector('thead tr');
          const tbody = table.querySelector('tbody');
          
          // Clear existing content
          thead.innerHTML = '<th style="width:120px;"></th>'; // Empty corner cell
          tbody.innerHTML = '';
          
          // Add color headers
          colors.forEach(color => {
            const th = document.createElement('th');
            th.textContent = color;
            th.style.textAlign = 'center';
            thead.appendChild(th);
          });
          
          // Add size rows
          sizes.forEach(size => {
            const tr = document.createElement('tr');
            
            // Size label in first column (same styling as color headers)
            const tdSize = document.createElement('td');
            tdSize.textContent = size;
            tr.appendChild(tdSize);
            
            // Quantity inputs for each color
            colors.forEach(color => {
              const td = document.createElement('td');
              const input = document.createElement('input');
              input.type = 'number';
              input.name = 'inventory[' + color + '_' + size + ']';
              input.min = '0';
              input.value = existingData[color + '_' + size] || '0';
              input.placeholder = '0';
              td.appendChild(input);
              tr.appendChild(td);
            });
            
            tbody.appendChild(tr);
          });
        }
        
        // Enable quantity input and file input only if color is checked
        function bindColorQtyHandlers(container) {
          container.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
            cb.addEventListener('change', function() {
              var parent = cb.parentNode;
              var qtyInput = parent.querySelector('input[type=number]');
              var fileInput = parent.querySelector('input[type=file]');
              if (qtyInput) {
                qtyInput.disabled = !cb.checked;
                if (!cb.checked) qtyInput.value = '';
              }
              if (fileInput) {
                fileInput.disabled = !cb.checked;
                if (!cb.checked) fileInput.value = '';
              }
              // Update last edit colors if in edit modal
              if (container.id === 'edit-color-quantity-list' && typeof updateLastEditColors === 'function') {
                updateLastEditColors();
              }
            });
          });
          // Also bind to quantity inputs
          container.querySelectorAll('input[type=number]').forEach(function(input) {
            input.addEventListener('input', function() {
              if (container.id === 'edit-color-quantity-list' && typeof updateLastEditColors === 'function') {
                updateLastEditColors();
              }
            });
          });
          // File input change handlers are now handled by global event delegation below
        }

        function renderOptions() {
          fetch('get_size_color_options.php')
            .then(res => res.json())
            .then(data => {
              // Save current state before re-rendering (Add modal)
              const addColorStates = {};
              const addColorFiles = {}; // Store file objects
              document.querySelectorAll('#color-quantity-list > div').forEach(div => {
                const checkbox = div.querySelector('input[type=checkbox]');
                const qtyInput = div.querySelector('input[type=number]');
                const fileInput = div.querySelector('input[type=file]');
                const preview = div.querySelector('.color-img-preview');
                const img = preview ? preview.querySelector('img') : null;
                if (checkbox) {
                  addColorStates[checkbox.value] = {
                    checked: checkbox.checked,
                    qty: qtyInput ? qtyInput.value : '',
                    previewSrc: img ? img.src : '',
                    previewVisible: preview ? preview.style.display !== 'none' : false
                  };
                  // Store file if exists
                  if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    addColorFiles[checkbox.value] = fileInput.files[0];
                  }
                }
              });
              
              // Save current state for Edit modal
              const editColorFiles = {};
              document.querySelectorAll('#edit-color-quantity-list > div').forEach(div => {
                const checkbox = div.querySelector('input[type=checkbox]');
                const fileInput = div.querySelector('input[type=file]');
                if (checkbox && fileInput && fileInput.files && fileInput.files.length > 0) {
                  editColorFiles[checkbox.value] = fileInput.files[0];
                }
              });

              // Sizes
              let sizeHtml = '';
              data.sizes.forEach(size => {
                sizeHtml += `<div style="display:flex;align-items:center;margin-bottom:6px;gap:10px;">
                  <input type="checkbox" name="sizes[]" value="${size}" id="add_size_${size}" onchange="updateAddInventoryMatrix()">
                  <label for="add_size_${size}" style="min-width:40px;margin:0 8px 0 0;"> ${size} </label>
                </div>`;
              });
              let editSizeHtml = '';
              data.sizes.forEach(size => {
                editSizeHtml += `<div style="display:flex;align-items:center;margin-bottom:6px;gap:10px;">
                  <input type="checkbox" name="sizes[]" value="${size}" id="edit_size_${size}" onchange="updateEditInventoryMatrix()">
                  <label for="edit_size_${size}" style="min-width:40px;margin:0 8px 0 0;"> ${size} </label>
                </div>`;
              });
              document.getElementById('size-list').innerHTML = sizeHtml;
              document.getElementById('edit-size-list').innerHTML = editSizeHtml;

              // Colors
              let colorHtml = '';
              data.colors.forEach(color => {
                const state = addColorStates[color] || {};
                colorHtml += `<div style="display:flex;align-items:center;margin-bottom:10px;gap:10px;padding:8px;border:1px solid #e0e0e0;border-radius:4px;">
                  <input type="checkbox" name="colors[]" value="${color}" id="add_color_${color}" ${state.checked ? 'checked' : ''} onchange="updateAddInventoryMatrix()">
                  <label for="add_color_${color}" style="min-width:70px;margin:0 8px 0 0;font-weight:500;"> ${color} </label>
                  <input type="number" name="color_qty[${color}]" min="0" class="form-control" placeholder="Qty" style="width:90px;" value="${state.qty || ''}" ${state.checked ? '' : 'disabled'}>
                  <input type="file" name="color_images[${color}]" accept="image/*" class="form-control" style="width:200px;" ${state.checked ? '' : 'disabled'}>
                  <div class="color-img-preview" style="width:40px;height:40px;border:1px solid #ccc;border-radius:4px;overflow:hidden;display:${state.previewVisible ? 'block' : 'none'};">
                    <img src="${state.previewSrc || ''}" style="width:100%;height:100%;object-fit:cover;">
                  </div>
                </div>`;
              });
              let editColorHtml = '';
              data.colors.forEach(color => {
                editColorHtml += `<div style="display:flex;align-items:center;margin-bottom:10px;gap:10px;padding:8px;border:1px solid #e0e0e0;border-radius:4px;">
                  <input type="checkbox" name="colors[]" value="${color}" id="edit_color_${color}" onchange="updateEditInventoryMatrix()">
                  <label for="edit_color_${color}" style="min-width:70px;margin:0 8px 0 0;font-weight:500;">${color}</label>
                  <input type="number" name="color_qty[${color}]" min="0" class="form-control" placeholder="Qty" style="width:90px;" disabled>
                  <input type="file" name="color_images[${color}]" accept="image/*" class="form-control" style="width:200px;" disabled>
                  <div class="color-img-preview" style="width:40px;height:40px;border:1px solid #ccc;border-radius:4px;overflow:hidden;display:none;">
                    <img src="" style="width:100%;height:100%;object-fit:cover;">
                  </div>
                </div>`;
              });
              document.getElementById('color-quantity-list').innerHTML = colorHtml;
              document.getElementById('edit-color-quantity-list').innerHTML = editColorHtml;
              
              // Re-bind handlers first
              bindColorQtyHandlers(document.getElementById('color-quantity-list'));
              bindColorQtyHandlers(document.getElementById('edit-color-quantity-list'));
              
              // Restore file inputs for Add modal (files are not preserved in innerHTML)
              setTimeout(() => {
                Object.keys(addColorFiles).forEach(color => {
                  const fileInput = document.querySelector(`#color-quantity-list input[name="color_images[${color}]"]`);
                  if (fileInput) {
                    const dt = new DataTransfer();
                    dt.items.add(addColorFiles[color]);
                    fileInput.files = dt.files;
                    // Trigger change event to show preview
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                  }
                });
                
                // Restore file inputs for Edit modal
                Object.keys(editColorFiles).forEach(color => {
                  const fileInput = document.querySelector(`#edit-color-quantity-list input[name="color_images[${color}]"]`);
                  if (fileInput) {
                    const dt = new DataTransfer();
                    dt.items.add(editColorFiles[color]);
                    fileInput.files = dt.files;
                    // Trigger change event to show preview
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                  }
                });
              }, 50);

              // Bind size checkbox handlers for edit modal
              document.querySelectorAll('#edit-size-list input[type=checkbox]').forEach(function(cb) {
                cb.addEventListener('change', function() {
                  if (typeof updateLastEditSizes === 'function') {
                    updateLastEditSizes();
                  }
                });
              });

              // Restore checked sizes in edit modal
              if (window._lastEditSizes) {
                window._lastEditSizes.forEach(function(size) {
                  var cb = document.querySelector(`#edit-size-list #edit_size_${size}`);
                  if (cb) {
                    cb.checked = true;
                  }
                });
              }

              // Restore checked colors, quantities, and images in edit modal
              if (window._lastEditColors) {
                window._lastEditColors.forEach(function(c) {
                  var cb = document.querySelector(`#edit-color-quantity-list #edit_color_${c.color}`);
                  if (cb) {
                    cb.checked = true;
                    var parent = cb.parentNode;
                    var qtyInput = parent.querySelector('input[type=number]');
                    var fileInput = parent.querySelector('input[type=file]');
                    var preview = parent.querySelector('.color-img-preview');
                    if (qtyInput) {
                      qtyInput.disabled = false;
                      qtyInput.value = c.quantity || 0;
                    }
                    if (fileInput) {
                      fileInput.disabled = false;
                    }
                    if (c.color_image && preview) {
                      preview.querySelector('img').src = '../img/' + c.color_image;
                      preview.style.display = 'block';
                    }
                  }
                });
              }
            });
        }
        // Only poll when modal is not open to avoid losing file selections
        let pollInterval = setInterval(function() {
          const addModalOpen = document.getElementById('addModal').style.display === 'block';
          const editModalOpen = document.getElementById('editModal').style.display === 'block';
          if (!addModalOpen && !editModalOpen) {
            renderOptions();
          }
        }, 5000);
        renderOptions(); // Initial

        // Global event delegation for color image preview
        document.addEventListener('change', function(e) {
          // Check if the changed element is a color image file input
          if (e.target.type === 'file' && e.target.name && e.target.name.startsWith('color_images[')) {
            console.log('Color image file selected:', e.target.files);
            var parent = e.target.parentNode;
            var preview = parent.querySelector('.color-img-preview');
            var imgElement = preview ? preview.querySelector('img') : null;
            
            if (e.target.files && e.target.files[0]) {
              if (preview && imgElement) {
                var reader = new FileReader();
                reader.onload = function(evt) {
                  console.log('Setting preview image');
                  imgElement.src = evt.target.result;
                  preview.style.display = 'block';
                  console.log('Preview should be visible now');
                };
                reader.onerror = function(err) {
                  console.error('FileReader error:', err);
                };
                reader.readAsDataURL(e.target.files[0]);
              } else {
                console.error('Preview elements not found');
              }
            }
          }
        });

        // Existing logic for subcategory dropdown
        const subcatOptions = {
          'Men': ['Shirts', 'Polos', 'Pants', 'Shorts', 'Hoodies'],
          'Women': ['Crop Tops', 'Dresses', 'Tops', 'Pants', 'Skirts']
        };
        const categorySelect = document.getElementById('category');
        const subcatSelect = document.getElementById('subcategory');
        if (categorySelect && subcatSelect) {
          categorySelect.addEventListener('change', function() {
            const val = this.value;
            subcatSelect.innerHTML = '<option value="">Select Subcategory</option>';
            if (subcatOptions[val]) {
              subcatOptions[val].forEach(function(sc) {
                const opt = document.createElement('option');
                opt.value = sc;
                opt.textContent = sc;
                subcatSelect.appendChild(opt);
              });
            }
          });
        }
      </script>
        
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
          <label>Selected Product Images</label>
          <div id="add_selected_images" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; min-height: 50px; padding: 10px; border: 1px dashed #ddd; border-radius: 4px;">
            <div style="color: #999; font-size: 13px; align-self: center;">No images selected yet</div>
          </div>
          <label for="product_images">Add Product Images *</label>
          <input type="file" id="product_images" name="product_images[]" class="form-control" accept="image/*" multiple>
          <small class="text-muted">Upload product images (you can select multiple). All images are equal - no main image. Recommended size: 800x800px</small>
        </div>
        <script>
        let addFormSelectedFiles = [];
        
        document.getElementById('product_images').addEventListener('change', function(e) {
          const newFiles = Array.from(this.files);
          
          // Add new files to the array
          newFiles.forEach(file => {
            addFormSelectedFiles.push(file);
          });
          
          // Clear the file input so user can select more
          this.value = '';
          
          // Update preview
          updateAddFormPreview();
        });
        
        function updateAddFormPreview() {
          const container = document.getElementById('add_selected_images');
          container.innerHTML = '';
          
          if (addFormSelectedFiles.length === 0) {
            container.innerHTML = '<div style="color: #999; font-size: 13px; align-self: center;">No images selected yet</div>';
            document.getElementById('product_images').required = true;
            return;
          }
          
          document.getElementById('product_images').required = false;
          
          addFormSelectedFiles.forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = function(evt) {
              const div = document.createElement('div');
              div.style.cssText = 'position:relative;width:80px;height:80px;';
              div.innerHTML = `
                <img src="${evt.target.result}" style="width:100%;height:100%;object-fit:cover;border:1px solid #ccc;border-radius:4px;">
                <button type="button" onclick="removeAddFormImage(${idx})" style="position:absolute;top:-5px;right:-5px;background:#d9534f;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;line-height:1;">&times;</button>
                <div style="position:absolute;bottom:2px;left:2px;background:rgba(0,0,0,0.7);color:white;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:bold;">Image ${idx + 1}</div>
              `;
              container.appendChild(div);
            };
            reader.readAsDataURL(file);
          });
        }
        
        function removeAddFormImage(index) {
          if (confirm('Remove this image?')) {
            addFormSelectedFiles.splice(index, 1);
            updateAddFormPreview();
          }
        }
        
        // Before form submit, create a new FileList from our array
        document.querySelector('#addModal form').addEventListener('submit', function(e) {
          if (addFormSelectedFiles.length === 0) {
            alert('Please select at least one image');
            e.preventDefault();
            return false;
          }
          
          // Create a DataTransfer to build a new FileList
          const dt = new DataTransfer();
          addFormSelectedFiles.forEach(file => {
            dt.items.add(file);
          });
          
          // Set the files to the actual input
          const fileInput = document.getElementById('product_images');
          fileInput.files = dt.files;
        });
        </script>
        
        <div style="margin-top: 20px; text-align: right;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Product</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('editModal')">&times;</span>
      <h2>Edit Product</h2>
      <form id="editForm" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(event, 'edit')">
        <input type="hidden" name="update_product" value="1">
        <input type="hidden" id="edit_id" name="product_id">
        
        <div class="form-group">
          <label for="edit_name">Product Name *</label>
          <input type="text" id="edit_name" name="name" class="form-control" required>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="edit_category">Category *</label>
              <select id="edit_category" name="category" class="form-control" required>
                <option value="">Select Category</option>
                <option value="Men">Men</option>
                <option value="Women">Women</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="edit_subcategory">Subcategory</label>
<select id="edit_subcategory" name="subcategory" class="form-control">
  <option value="">Select Subcategory</option>
</select>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label for="edit_price">Price (₱) *</label>
          <input type="number" id="edit_price" name="price" class="form-control" step="0.01" min="0" required>
        </div>

        
        <div class="form-group">
          <label for="edit_material">Material</label>
          <input type="text" id="edit_material" name="material" class="form-control" placeholder="e.g., 100% Cotton">
        </div>

        <div class="form-group">
          <label><input type="checkbox" id="edit_discount_enabled" name="discount_enabled" value="1" onchange="document.getElementById('editDiscountFields').style.display = this.checked ? '' : 'none';"> Enable Discount</label>
        </div>
        <div class="form-group" id="editDiscountFields" style="display:none;">
          <label for="edit_discount_type">Discount Type</label>
          <select id="edit_discount_type" name="discount_type" class="form-control">
            <option value="">Select type</option>
            <option value="percent">Percent (%)</option>
            <option value="fixed">Fixed Amount</option>
          </select>
          <label for="edit_discount_value" style="margin-top:10px;">Discount Value</label>
          <input type="number" id="edit_discount_value" name="discount_value" class="form-control" step="0.01" min="0">
        </div>
        
<div style="margin-bottom:10px;text-align:right">
  <button type="button" class="btn btn-secondary" onclick="openSizeColorCMS()">
    <i class="fas fa-cogs"></i> Manage Sizes & Colors
  </button>
</div>
<script>
function openSizeColorCMS() {
  window.open('size_color_cms.php', 'SizeColorCMS', 'width=700,height=600,resizable=yes,scrollbars=yes');
}
</script>

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <label>Available Sizes</label>
      <div id="edit-size-list">
        <?php 
          $editSizes = isset($product['sizes']) ? explode(',', $product['sizes']) : [];
foreach ($cms_sizes as $size): ?>
  <div style="display:flex;align-items:center;margin-bottom:6px;gap:10px;">
    <input type="checkbox" name="sizes[]" value="<?php echo $size; ?>" id="edit_size_<?php echo $size; ?>" <?php echo in_array($size, $editSizes) ? 'checked' : ''; ?> onchange="updateEditInventoryMatrix()">
    <label for="edit_size_<?php echo $size; ?>" style="min-width:40px;margin:0 8px 0 0;"> <?php echo $size; ?> </label>
  </div>
<?php endforeach; ?>
      </div>
      <small class="text-muted">Check a size to enable.</small>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="form-group">
      <label>Available Colors & Images</label>
      <div id="edit-color-list">
        <?php 
          $editColors = [];
$editColorImages = [];
if (isset($product['id'])) {
  $res = $conn->query("SELECT color, color_image FROM product_colors WHERE product_id = " . (int)$product['id']);
  while ($res && ($row = $res->fetch_assoc())) {
    $editColors[] = $row['color'];
    $editColorImages[$row['color']] = $row['color_image'];
  }
}
foreach ($cms_colors as $color): 
  $hasImage = isset($editColorImages[$color]) && !empty($editColorImages[$color]);
  $imagePath = $hasImage ? '../img/' . $editColorImages[$color] : '';
?>
  <div style="display:flex;align-items:center;margin-bottom:10px;gap:10px;padding:8px;border:1px solid #e0e0e0;border-radius:4px;">
    <input type="checkbox" name="colors[]" value="<?php echo $color; ?>" id="edit_color_<?php echo $color; ?>" <?php echo in_array($color, $editColors) ? 'checked' : ''; ?> onchange="updateEditInventoryMatrix()">
    <label for="edit_color_<?php echo $color; ?>" style="min-width:70px;margin:0 8px 0 0;font-weight:500;"><?php echo $color; ?></label>
    <input type="file" name="color_images[<?php echo $color; ?>]" accept="image/*" class="form-control" style="width:200px;" <?php echo in_array($color, $editColors) ? '' : 'disabled'; ?>>
    <div class="color-img-preview" style="width:40px;height:40px;border:1px solid #ccc;border-radius:4px;overflow:hidden;<?php echo $hasImage ? '' : 'display:none;'; ?>">
      <img src="<?php echo $imagePath; ?>" style="width:100%;height:100%;object-fit:cover;">
    </div>
  </div>
<?php endforeach; ?>
      </div>
      <small class="text-muted">Check a color and optionally upload an image to identify the color.</small>
    </div>
  </div>
</div>

<!-- Color-Size Inventory Matrix for Edit -->
<div class="inventory-matrix-container" id="edit-inventory-matrix-container" style="display:none;">
  <div class="inventory-matrix-header">
    <h4>📦 Quantity per Size & Color</h4>
  </div>
  <div class="inventory-matrix">
    <table id="edit-inventory-matrix-table">
      <thead>
        <tr>
          <th></th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
  <small class="text-muted">Enter the quantity available for each size-color combination.</small>
</div>
<script>
// Function to update Edit modal inventory matrix
function updateEditInventoryMatrix() {
  const selectedColors = [];
  const selectedSizes = [];
  
  // Get selected colors
  document.querySelectorAll('#edit-color-list input[type=checkbox]:checked').forEach(cb => {
    selectedColors.push(cb.value);
  });
  
  // Get selected sizes
  document.querySelectorAll('#edit-size-list input[type=checkbox]:checked').forEach(cb => {
    selectedSizes.push(cb.value);
  });
  
  // Show/hide matrix container
  const container = document.getElementById('edit-inventory-matrix-container');
  if (selectedColors.length > 0 && selectedSizes.length > 0) {
    container.style.display = 'block';
    // Preserve existing values when rebuilding
    let existingData = {};
    
    // First, try to get values from current form inputs (user may have edited them)
    document.querySelectorAll('#edit-inventory-matrix-table input[type=number]').forEach(input => {
      const match = input.name.match(/inventory\[(.+)\]/);
      if (match) {
        existingData[match[1]] = input.value;
      }
    });
    
    // If no form data yet, use the loaded inventory data from the product
    if (Object.keys(existingData).length === 0 && window._editInventoryData) {
      existingData = window._editInventoryData;
    }
    
    buildInventoryMatrix('edit-inventory-matrix-table', selectedColors, selectedSizes, existingData);
  } else {
    container.style.display = 'none';
  }
  
  // Enable/disable file inputs based on color checkbox
  document.querySelectorAll('#edit-color-list input[type=checkbox]').forEach(cb => {
    const parent = cb.parentNode;
    const fileInput = parent.querySelector('input[type=file]');
    if (fileInput) {
      fileInput.disabled = !cb.checked;
      if (!cb.checked) fileInput.value = '';
    }
  });
  
  // Update window._lastEditSizes and _lastEditColors for AJAX polling
  updateLastEditSizes();
  updateLastEditColors();
}

// Update window._lastEditSizes when size checkboxes change
function updateLastEditSizes() {
  const checkedSizes = [];
  document.querySelectorAll('#edit-size-list input[type=checkbox]:checked').forEach(function(cb) {
    checkedSizes.push(cb.value);
  });
  window._lastEditSizes = checkedSizes;
}

// Update window._lastEditColors when color checkboxes change
function updateLastEditColors() {
  const checkedColors = [];
  document.querySelectorAll('#edit-color-list input[type=checkbox]:checked').forEach(function(cb) {
    const parent = cb.parentNode;
    const preview = parent.querySelector('.color-img-preview');
    const imgSrc = preview && preview.style.display !== 'none' ? preview.querySelector('img').src : '';
    const colorImage = imgSrc ? imgSrc.split('/').pop() : null;
    checkedColors.push({
      color: cb.value,
      quantity: 0, // Not used anymore, kept for compatibility
      color_image: colorImage
    });
  });
  window._lastEditColors = checkedColors;
}
</script>
        
        <div class="form-group">
          <label for="edit_description">Description</label>
          <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
          <label>Current Product Images</label>
          <div id="edit_current_images" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;"></div>
          <label for="edit_product_images">Add More Images</label>
          <input type="file" id="edit_product_images" name="product_images[]" class="form-control" accept="image/*" multiple>
          <small class="text-muted">Upload additional product images (you can select multiple). All images are equal - no main image. Leave blank to keep current images only.</small>
          <div id="edit_new_image_preview" style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;"></div>
        </div>
        <script>
        document.getElementById('edit_product_images').addEventListener('change', function(e) {
          const preview = document.getElementById('edit_new_image_preview');
          preview.innerHTML = '';
          if (this.files) {
            const existingCount = document.querySelectorAll('#edit_current_images > div').length;
            Array.from(this.files).forEach((file, idx) => {
              const reader = new FileReader();
              reader.onload = function(evt) {
                const div = document.createElement('div');
                div.style.cssText = 'position:relative;width:80px;height:80px;';
                div.innerHTML = `
                  <img src="${evt.target.result}" style="width:100%;height:100%;object-fit:cover;border:1px solid #ccc;border-radius:4px;">
                  <div style="position:absolute;bottom:2px;left:2px;background:rgba(0,0,0,0.7);color:white;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:bold;">Image ${existingCount + idx + 1}</div>
                `;
                preview.appendChild(div);
              };
              reader.readAsDataURL(file);
            });
          }
        });
        </script>
        
        <div style="margin-top: 20px; text-align: right;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Product</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    // Initialize Select2
    $(document).ready(function() {
      $('.select2').select2({
        placeholder: 'Select options',
        allowClear: true,
        width: '100%'
      });

      // Dependent subcategory logic
      const menSubcats = ['Shirts','Polos','Pants','Shorts','Hoodies'];
      const womenSubcats = ['Crop Tops','Dresses','Tops','Pants','Skirts'];
      function updateSubcat(catVal, subcatSel, selectedVal = '') {
        let opts = '<option value="">Select Subcategory</option>';
        let arr = [];
        if (catVal === 'Men') arr = menSubcats;
        else if (catVal === 'Women') arr = womenSubcats;
        arr.forEach(function(subcat) {
          opts += `<option value="${subcat}"${selectedVal===subcat?' selected':''}>${subcat}</option>`;
        });
        $(subcatSel).html(opts);
      }
      // For add product
      $('#category').on('change', function() {
        updateSubcat(this.value, '#subcategory');
      });
      // For edit product
      $('#edit_category').on('change', function() {
        updateSubcat(this.value, '#edit_subcategory');
      });
      // Set initial on modal open (edit)
      window.setEditSubcat = function(cat, subcat) {
        updateSubcat(cat, '#edit_subcategory', subcat);
        // Ensure the correct value is selected in the dropdown after options are set
        if (subcat) {
          $('#edit_subcategory').val(subcat);
        }
      };
      // Ensure subcategory is set before submitting edit form
      $('#editForm').on('submit', function() {
        var subcat = $('#edit_subcategory').val();
        $('<input>').attr({type: 'hidden', name: 'subcategory', value: subcat}).appendTo(this);
      });

    });
    

    // Open Add Modal
    function openAddModal() {
      // Reset the form and selected files
      addFormSelectedFiles = [];
      updateAddFormPreview();
      document.querySelector('#addModal form').reset();
      
      document.getElementById('addModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
      // Bind handlers for color image preview
      const addColorList = document.getElementById('color-quantity-list');
      if (addColorList) {
        bindColorQtyHandlers(addColorList);
      }
    }
    
    // Close Modal
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
    }
    
    // Edit Product
    function editProduct(id) {
      console.log('Editing product ID:', id); // Debug log
      
      fetch(`get_product.php?id=${id}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(product => {
          console.log('Product data received:', product); // Debug log
          
          // Populate form fields
          document.getElementById('edit_id').value = product.id;
          document.getElementById('edit_name').value = product.name || '';
          document.getElementById('edit_category').value = product.category || '';
          // Set category and subcategory options
window.setEditSubcat(product.category, product.subcategory);
          document.getElementById('edit_price').value = parseFloat(product.price || 0).toFixed(2);
          document.getElementById('edit_material').value = product.material || '';
          document.getElementById('edit_description').value = product.description || '';
          
          // Display current product images
          const currentImagesContainer = document.getElementById('edit_current_images');
          currentImagesContainer.innerHTML = '';
          if (product.product_images && product.product_images.length > 0) {
            product.product_images.forEach((img, idx) => {
              const div = document.createElement('div');
              div.style.cssText = 'position:relative;width:80px;height:80px;';
              div.innerHTML = `
                <img src="../img/${img}" style="width:100%;height:100%;object-fit:cover;border:1px solid #ccc;border-radius:4px;">
                <button type="button" onclick="removeExistingImage('${img}')" style="position:absolute;top:-5px;right:-5px;background:#d9534f;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:12px;cursor:pointer;line-height:1;">&times;</button>
                <div style="position:absolute;bottom:2px;left:2px;background:rgba(0,0,0,0.7);color:white;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:bold;">Image ${idx + 1}</div>
                <input type="hidden" name="existing_images[]" value="${img}" id="existing_img_${idx}">
              `;
              currentImagesContainer.appendChild(div);
            });
          } else {
            currentImagesContainer.innerHTML = '<p style="color:#999;font-size:13px;">No images uploaded yet</p>';
          }

          // Set discount fields
          document.getElementById('edit_discount_enabled').checked = !!(product.discount_enabled && product.discount_enabled !== '0');
          document.getElementById('editDiscountFields').style.display = document.getElementById('edit_discount_enabled').checked ? '' : 'none';
          document.getElementById('edit_discount_type').value = product.discount_type || '';
          document.getElementById('edit_discount_value').value = product.discount_value !== null && product.discount_value !== undefined ? product.discount_value : '';

          // Save last loaded sizes for AJAX polling restore
          window._lastEditSizes = Array.isArray(product.sizes) ? product.sizes : [];

          // Set sizes
          if (product.sizes && product.sizes.length > 0) {
            // Uncheck all first
            document.querySelectorAll('#edit-size-list input[type=checkbox]').forEach(cb => { cb.checked = false; });
            // Check those in product.sizes
            product.sizes.forEach(size => {
              const cb = document.querySelector(`#edit-size-list #edit_size_${size}`);
              if (cb) cb.checked = true;
            });
          } else {
            document.querySelectorAll('#edit-size-list input[type=checkbox]').forEach(cb => { cb.checked = false; });
          }

          // Save last loaded colors for AJAX polling restore
          window._lastEditColors = Array.isArray(product.colors) ? product.colors.map(c => ({color: c.color, quantity: 0, color_image: c.color_image})) : [];

          // Set colors and images
          if (product.colors && product.colors.length > 0) {
            // Reset all checkboxes and images
            document.querySelectorAll('#edit-color-list input[type=checkbox]').forEach(cb => {
              cb.checked = false;
              const parent = cb.parentNode;
              const fileInput = parent.querySelector('input[type=file]');
              const preview = parent.querySelector('.color-img-preview');
              fileInput.disabled = true;
              if (preview) {
                preview.style.display = 'none';
                preview.querySelector('img').src = '';
              }
            });
            // Check the colors that exist and set their images
            product.colors.forEach(colorData => {
              const checkbox = document.querySelector(`#edit-color-list #edit_color_${colorData.color}`);
              if (checkbox) {
                checkbox.checked = true;
                const parent = checkbox.parentNode;
                const fileInput = parent.querySelector('input[type=file]');
                const preview = parent.querySelector('.color-img-preview');
                fileInput.disabled = false;
                if (colorData.color_image && preview) {
                  preview.querySelector('img').src = '../img/' + colorData.color_image;
                  preview.style.display = 'block';
                }
              }
            });
          } else {
            // No colors, uncheck all
            document.querySelectorAll('#edit-color-list input[type=checkbox]').forEach(cb => {
              cb.checked = false;
              const parent = cb.parentNode;
              const fileInput = parent.querySelector('input[type=file]');
              const preview = parent.querySelector('.color-img-preview');
              fileInput.disabled = true;
              if (preview) {
                preview.style.display = 'none';
                preview.querySelector('img').src = '';
              }
            });
          }
          
          // Load inventory data and build matrix
          if (product.inventory && Object.keys(product.inventory).length > 0) {
            // Store inventory data for matrix building
            window._editInventoryData = product.inventory;
          } else {
            window._editInventoryData = {};
          }
          
          // Build the inventory matrix with existing data
          updateEditInventoryMatrix();
          
          // Show modal
          document.getElementById('editModal').style.display = 'block';
          document.body.style.overflow = 'hidden';
        })
        .catch(error => {
          console.error('Error fetching product:', error);
          alert('Error loading product data. Please check the console for details.');
        });
    }
    
    // Remove existing image function
    function removeExistingImage(imageName) {
      if (confirm('Are you sure you want to remove this image?')) {
        // Find and remove the hidden input and the parent div
        const inputs = document.querySelectorAll('input[name="existing_images[]"]');
        inputs.forEach(input => {
          if (input.value === imageName) {
            input.parentElement.remove();
          }
        });
      }
    }
    
    // Form validation
    function validateForm(formType) {
      const name = document.getElementById(`${formType === 'add' ? '' : 'edit_'}name`).value.trim();
      const price = document.getElementById(`${formType === 'add' ? '' : 'edit_'}price`).value.trim();
      
      if (!name) {
        alert('Please enter product name');
        return false;
      }
      
      if (!price || isNaN(price) || parseFloat(price) <= 0) {
        alert('Please enter a valid price');
        return false;
      }
      
      if (formType === 'add') {
        // Check if files were selected using our custom array
        if (!addFormSelectedFiles || addFormSelectedFiles.length === 0) {
          alert('Please select at least one product image');
          return false;
        }
      } else if (formType === 'edit') {
        // Check if at least one existing image or new image exists
        const existingImages = document.querySelectorAll('input[name="existing_images[]"]').length;
        const newImages = document.getElementById('edit_product_images').files.length;
        
        if (existingImages === 0 && newImages === 0) {
          alert('Please keep or upload at least one product image');
          return false;
        }
      }
      
      return true;
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    }
    
    // View Product Images Modal
    let currentImageIndex = 0;
    let currentProductImages = [];
    
    function viewProductImages(productId, images) {
      currentProductImages = images;
      currentImageIndex = 0;
      
      const modal = document.getElementById('imageViewerModal');
      if (modal) {
        showImageAtIndex(0);
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
      }
    }
    
    function showImageAtIndex(index) {
      if (index < 0) index = currentProductImages.length - 1;
      if (index >= currentProductImages.length) index = 0;
      currentImageIndex = index;
      
      const imgElement = document.getElementById('viewerImage');
      const labelElement = document.getElementById('viewerLabel');
      
      if (imgElement && currentProductImages[index]) {
        imgElement.src = '../img/' + currentProductImages[index];
        if (labelElement) {
          labelElement.textContent = 'Image ' + (index + 1) + ' of ' + currentProductImages.length;
        }
      }
    }
    
    function prevImage() {
      showImageAtIndex(currentImageIndex - 1);
    }
    
    function nextImage() {
      showImageAtIndex(currentImageIndex + 1);
    }
    
    function closeImageViewer() {
      const modal = document.getElementById('imageViewerModal');
      if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    }
    
    // Confirmation Dialog Functions
    let pendingFormSubmit = null;
    
    function handleFormSubmit(event, formType) {
      event.preventDefault();
      
      // First validate the form
      if (!validateForm(formType)) {
        return false;
      }
      
      // Store the form for later submission
      pendingFormSubmit = {
        form: event.target,
        type: formType
      };
      
      // Show confirmation dialog
      const action = formType === 'add' ? 'add this product' : 'save changes to this product';
      document.getElementById('confirmMessage').textContent = `Are you sure you want to ${action}?`;
      document.getElementById('confirmDialog').style.display = 'block';
      
      return false;
    }
    
    function confirmAction() {
      if (pendingFormSubmit) {
        // Remove the onsubmit handler temporarily to avoid loop
        const form = pendingFormSubmit.form;
        const originalOnsubmit = form.onsubmit;
        form.onsubmit = null;
        
        // Submit the form
        form.submit();
      }
      closeConfirmDialog();
    }
    
    function cancelAction() {
      pendingFormSubmit = null;
      closeConfirmDialog();
    }
    
    function closeConfirmDialog() {
      document.getElementById('confirmDialog').style.display = 'none';
    }
  </script>

  <!-- Confirmation Dialog Modal -->
  <div id="confirmDialog" class="modal">
    <div class="modal-content" style="max-width: 450px; text-align: center;">
      <h3 style="margin-bottom: 20px; color: var(--dark-gray);">Confirm Action</h3>
      <p id="confirmMessage" style="font-size: 16px; margin-bottom: 30px; color: var(--text-color);">Are you sure you want to proceed?</p>
      <div style="display: flex; gap: 10px; justify-content: center;">
        <button onclick="cancelAction()" class="btn btn-secondary" style="min-width: 100px;">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button onclick="confirmAction()" class="btn btn-primary" style="min-width: 100px;">
          <i class="fas fa-check"></i> Confirm
        </button>
      </div>
    </div>
  </div>

  <!-- Image Viewer Modal -->
  <div id="imageViewerModal" class="modal">
    <div class="modal-content" style="max-width: 800px; padding: 0; background: transparent; box-shadow: none;">
      <div style="background: white; border-radius: 8px; overflow: hidden;">
        <div style="padding: 15px; background: var(--primary-light); display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; color: var(--dark-gray);">Product Images</h3>
          <span class="close" onclick="closeImageViewer()" style="position: static; font-size: 28px;">&times;</span>
        </div>
        <div style="position: relative; background: #f5f5f5; min-height: 400px; display: flex; align-items: center; justify-content: center;">
          <img id="viewerImage" src="" alt="Product Image" style="max-width: 100%; max-height: 600px; display: block; margin: 0 auto;">
          <button onclick="prevImage()" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 20px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">&#8592;</button>
          <button onclick="nextImage()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 20px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">&#8594;</button>
        </div>
        <div style="padding: 15px; background: white; text-align: center;">
          <span id="viewerLabel" style="font-weight: 600; color: var(--dark-gray);">Image 1 of 1</span>
        </div>
      </div>
    </div>
  </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
