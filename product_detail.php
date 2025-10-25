<?php
include 'db.php';
include 'includes/inventory_helper.php';
session_start();

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = intval($_GET['id']);

// Check if product_sizes and product_colors tables exist
$tables_exist = true;
$check_tables = $conn->query("SHOW TABLES LIKE 'product_sizes'");
if ($check_tables->num_rows == 0) {
    $tables_exist = false;
} else {
    $check_tables = $conn->query("SHOW TABLES LIKE 'product_colors'");
    if ($check_tables->num_rows == 0) {
        $tables_exist = false;
    }
}

// Get product details with additional fields if tables exist
if ($tables_exist) {
    $sql = "SELECT p.*, p.discount_enabled, p.discount_type, p.discount_value,
                   (SELECT GROUP_CONCAT(DISTINCT size) FROM product_sizes WHERE product_id = p.id) as sizes,
                   (SELECT GROUP_CONCAT(CONCAT(color, ':', quantity)) FROM product_colors WHERE product_id = p.id) as colors_with_qty,
                   (SELECT GROUP_CONCAT(DISTINCT color) FROM product_colors WHERE product_id = p.id) as colors
            FROM products p 
            WHERE p.id = ?";
} else {
    $sql = "SELECT * FROM products WHERE id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit();
}

$product = $result->fetch_assoc();
// Initialize empty arrays if sizes/colors don't exist
if (!isset($product['sizes'])) $product['sizes'] = '';
if (!isset($product['colors'])) $product['colors'] = '';
if (!isset($product['colors_with_qty'])) $product['colors_with_qty'] = '';

// Fetch color images separately
$color_images = [];
if ($tables_exist) {
    $color_img_sql = "SELECT color, color_image FROM product_colors WHERE product_id = ? AND color_image IS NOT NULL";
    $color_img_stmt = $conn->prepare($color_img_sql);
    $color_img_stmt->bind_param("i", $product_id);
    $color_img_stmt->execute();
    $color_img_result = $color_img_stmt->get_result();
    while ($row = $color_img_result->fetch_assoc()) {
        $color_images[$row['color']] = $row['color_image'];
    }
}

// Get inventory matrix data (color-size combinations)
$inventory_matrix = getProductInventory($conn, $product_id);
$has_inventory_matrix = !empty($inventory_matrix);

// Get product feedback from users who ordered this product with size and color details
$feedback_sql = "SELECT f.*, u.name, u.email, f.created_at,
                 GROUP_CONCAT(DISTINCT CONCAT_WS('|', oi.size, oi.color, oi.quantity) SEPARATOR ';;') as order_details
                 FROM order_feedback f
                 INNER JOIN users u ON f.user_id = u.id
                 INNER JOIN order_items oi ON f.order_id = oi.order_id
                 WHERE oi.product_id = ? AND f.product_id IS NULL
                 GROUP BY f.id
                 ORDER BY f.created_at DESC";
$feedback_stmt = $conn->prepare($feedback_sql);
$feedback_stmt->bind_param("i", $product_id);
$feedback_stmt->execute();
$feedback_result = $feedback_stmt->get_result();
$feedbacks = [];
while ($row = $feedback_result->fetch_assoc()) {
    $feedbacks[] = $row;
}

// Calculate average rating
$avg_rating = 0;
$total_ratings = 0;
foreach ($feedbacks as $fb) {
    if ($fb['rating'] !== null && $fb['rating'] > 0) {
        $avg_rating += $fb['rating'];
        $total_ratings++;
    }
}
if ($total_ratings > 0) {
    $avg_rating = $avg_rating / $total_ratings;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php 
$page_title = $product['name'] . ' - Product Details';
include 'header.php'; 
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - MTC Clothing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Global site theme */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; }

        /* Product detail layout */
        .product-detail-container { max-width: 1000px; margin: 25px auto; padding: 25px; background: white; border-radius: 14px; box-shadow: 0 5px 20px rgba(91, 107, 70, 0.1); }
        .product-detail { display: flex; gap: 28px; }
        .product-image { flex: 1; }
        .product-info { flex: 1; }

        /* Gallery */
        #productGallery { position: relative; margin-bottom: 15px; }
        .gallery-img { width:100%; max-width:400px; height:auto; border-radius:8px; display:block; transition: opacity 0.3s ease; }
        #galleryPrev, #galleryNext { position:absolute; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.9); border:none; border-radius:50%; padding:8px 12px; cursor:pointer; font-size:20px; box-shadow:0 2px 8px rgba(0,0,0,0.2); transition:all 0.3s; }
        #galleryPrev { left:10px; }
        #galleryNext { right:10px; }
        #imageLabel { position:absolute; bottom:10px; left:10px; background:rgba(0,0,0,0.7); color:white; padding:6px 12px; border-radius:4px; font-size:12px; font-weight:600; }

        #thumbnailGallery { display:flex; flex-direction:row; gap:8px; overflow-x:auto; padding:5px; }
        .thumbnail-wrapper { cursor:pointer; border:3px solid #ddd; border-radius:6px; overflow:hidden; transition:all 0.3s; flex-shrink:0; width:80px; height:80px; position:relative; }
        .thumbnail-wrapper img { width:100%; height:100%; object-fit:cover; display:block; }
        .thumbnail-wrapper div { position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.7); color:white; font-size:9px; padding:2px 4px; text-align:center; font-weight:600; }
        .thumbnail-wrapper:hover { border-color:#4CAF50; transform:scale(1.05); }

        /* Product info */
        .product-info h1 { font-size:23px; margin-bottom:10px; color:#2c3e50; font-weight:700; letter-spacing:-0.5px; }
        .price { font-size:21px; margin:12px 0; font-weight:700; }
        .product-meta { margin:14px 0; background:#f8f9fa; padding:13px; border-radius:8px; border-left:3px solid #5b6b46; }
        .description h3 { font-size:16px; margin-bottom:8px; color:#2c3e50; font-weight:700; }
        .description p { background:#f8f9fa; padding:10px; border-radius:7px; font-size:13px; color:#555; }

        select, input[type="number"], input[type="text"] { width:100%; padding:9px 12px; border:2px solid #e0e0e0; border-radius:7px; font-size:13px; background:white; transition: all 0.2s; }
        select:focus, input[type="number"]:focus { border-color:#5b6b46; box-shadow:0 0 0 3px rgba(91,107,70,0.12); outline:none; transform:translateY(-1px); }

        .add-to-cart-btn { background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; padding: 11px 24px; border: none; border-radius: 9px; cursor: pointer; font-size: 14px; font-weight: 700; transition: all 0.3s; width:100%; max-width:260px; box-shadow: 0 4px 14px rgba(91, 107, 70, 0.3); text-transform: uppercase; letter-spacing: 0.4px; display:flex; align-items:center; justify-content:center; gap:7px; }
        .add-to-cart-btn:hover { background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(91, 107, 70, 0.4); }

        .back-btn, .view-cart-btn { display:inline-flex; align-items:center; gap:5px; padding:9px 18px; border-radius:7px; font-weight:600; font-size:13px; transition: all 0.3s; text-decoration:none; }
        .back-btn { background:#f0f0f0; color:#333; border:2px solid #e0e0e0; }
        .view-cart-btn { background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); color:white; box-shadow: 0 4px 12px rgba(33,150,243,0.3); }

        /* Reviews */
        .reviews-section { margin-top:35px; padding:20px; background:#f8f9fa; border-radius:8px; border:1px solid #e0e0e0; }
        .review-card { background:white; padding:16px; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.06); border-left:3px solid #4CAF50; transition: all 0.2s ease; }
        .review-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important; transform: translateY(-2px); }

        @media (max-width: 768px) {
            .product-detail { flex-direction: column; gap:20px; }
            .product-detail-container { padding:18px; margin:15px 12px; }
            .product-info h1 { font-size:20px; }
            .price { font-size:18px; }
            #thumbnailGallery { gap:6px; }
        }
    </style>
</head>

<body>
    <div class="product-detail-container" style="max-width: 1000px; margin: 25px auto; padding: 25px; background: white; border-radius: 14px; box-shadow: 0 5px 20px rgba(91, 107, 70, 0.1);">
        <div class="product-detail" style="display: flex; gap: 28px;">
            <div class="product-image" style="flex: 1;">
<?php
// Get all product images from product_images table (no main image concept)
$imgs = [];
$imgLabels = [];
$productImgRes = $conn->query("SELECT image FROM product_images WHERE product_id = " . (int)$product['id'] . " ORDER BY id");
if ($productImgRes) {
    $imgCounter = 1;
    while ($row = $productImgRes->fetch_assoc()) {
        if (!empty($row['image'])) {
            // Add img/ prefix if not already present
            $imagePath = $row['image'];
            if (strpos($imagePath, 'img/') !== 0 && strpos($imagePath, '/') !== 0) {
                $imagePath = 'img/' . $imagePath;
            }
            $imgs[] = $imagePath;
            $imgLabels[] = 'Image ' . $imgCounter;
            $imgCounter++;
        }
    }
}

// Then get all color images
$colorImgRes = $conn->query("SELECT color, color_image FROM product_colors WHERE product_id = " . (int)$product['id'] . " AND color_image IS NOT NULL AND color_image != '' ORDER BY id");
while ($colorImgRes && ($row = $colorImgRes->fetch_assoc())) {
    $imgs[] = 'img/' . $row['color_image'];
    $imgLabels[] = $row['color'];
}
?>
<div style="max-width:400px;">
  <!-- Product Image Display -->
  <div id="productGallery" style="position:relative;margin-bottom:15px;">
    <?php if (count($imgs) > 0): ?>
      <?php foreach ($imgs as $i => $img): ?>
        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($imgLabels[$i]); ?>" class="gallery-img" data-index="<?php echo $i; ?>" data-label="<?php echo htmlspecialchars($imgLabels[$i]); ?>" style="width:100%;max-width:400px;height:auto;border-radius:8px;display:<?php echo $i === 0 ? 'block' : 'none'; ?>;">
      <?php endforeach; ?>
    <?php else: ?>
      <div style="width:100%;max-width:400px;height:400px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999;">
        <div style="text-align:center;">
          <i class="fas fa-image" style="font-size:48px;margin-bottom:10px;"></i>
          <p>No images available</p>
        </div>
      </div>
    <?php endif; ?>
    <?php if (count($imgs) > 1): ?>
      <button id="galleryPrev" style="position:absolute;top:50%;left:10px;transform:translateY(-50%);background:rgba(255,255,255,0.9);border:none;border-radius:50%;padding:8px 12px;cursor:pointer;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,0.2);transition:all 0.3s;">&#8592;</button>
      <button id="galleryNext" style="position:absolute;top:50%;right:10px;transform:translateY(-50%);background:rgba(255,255,255,0.9);border:none;border-radius:50%;padding:8px 12px;cursor:pointer;font-size:20px;box-shadow:0 2px 8px rgba(0,0,0,0.2);transition:all 0.3s;">&#8594;</button>
    <?php endif; ?>
    <!-- Image Label -->
    <?php if (count($imgs) > 0): ?>
    <div id="imageLabel" style="position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,0.7);color:white;padding:6px 12px;border-radius:4px;font-size:12px;font-weight:600;"><?php echo htmlspecialchars($imgLabels[0]); ?></div>
    <?php endif; ?>
  </div>
  
  <!-- Thumbnail Images Row -->
  <?php if (count($imgs) > 1): ?>
  <div id="thumbnailGallery" style="display:flex;flex-direction:row;gap:8px;overflow-x:auto;padding:5px;">
    <?php foreach ($imgs as $i => $img): ?>
      <div class="thumbnail-wrapper" data-index="<?php echo $i; ?>" title="<?php echo htmlspecialchars($imgLabels[$i]); ?>" style="cursor:pointer;border:3px solid <?php echo $i === 0 ? '#4CAF50' : '#ddd'; ?>;border-radius:6px;overflow:hidden;transition:all 0.3s;flex-shrink:0;width:80px;height:80px;position:relative;">
        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($imgLabels[$i]); ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
        <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.7);color:white;font-size:9px;padding:2px 4px;text-align:center;font-weight:600;"><?php echo htmlspecialchars($imgLabels[$i]); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<style>
  #galleryPrev:hover, #galleryNext:hover {
    background:rgba(255,255,255,1);
    transform:translateY(-50%) scale(1.1);
  }
  .thumbnail-wrapper:hover {
    border-color:#4CAF50 !important;
    transform:scale(1.05);
  }
  #thumbnailGallery::-webkit-scrollbar {
    height: 6px;
  }
  #thumbnailGallery::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }
  #thumbnailGallery::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
  }
  #thumbnailGallery::-webkit-scrollbar-thumb:hover {
    background: #555;
  }
  #thumbnailGallery {
    scrollbar-width: thin;
    scrollbar-color: #888 #f1f1f1;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var imgs = document.querySelectorAll('.gallery-img');
  var thumbnails = document.querySelectorAll('.thumbnail-wrapper');
  var idx = 0;
  var prev = document.getElementById('galleryPrev');
  var next = document.getElementById('galleryNext');
  var imageLabel = document.getElementById('imageLabel');
  
  function showImg(i) {
    imgs.forEach((img, j) => { 
      img.style.display = (i === j) ? 'block' : 'none'; 
    });
    thumbnails.forEach((thumb, j) => {
      thumb.style.borderColor = (i === j) ? '#4CAF50' : '#ddd';
    });
    idx = i;
    
    // Update image label
    if (imageLabel && imgs[i]) {
      imageLabel.textContent = imgs[i].getAttribute('data-label') || 'Image ' + (i + 1);
    }
    
    // Scroll thumbnail into view
    if (thumbnails[i]) {
      thumbnails[i].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
  }
  
  // Arrow button navigation
  if (prev && next) {
    prev.onclick = function() { 
      idx = (idx - 1 + imgs.length) % imgs.length; 
      showImg(idx); 
    };
    next.onclick = function() { 
      idx = (idx + 1) % imgs.length; 
      showImg(idx); 
    };
  }
  
  // Thumbnail click navigation
  thumbnails.forEach((thumb, i) => {
    thumb.onclick = function() {
      showImg(i);
    };
  });
});
</script>
</div>
            <div class="product-info" style="flex: 1;">
                <h1 style="font-size: 23px; margin-bottom: 10px; color: #2c3e50; font-weight: 700; letter-spacing: -0.5px;"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="price" style="font-size: 21px; margin: 12px 0; font-weight: 700;">
                    <?php
                    if (!empty($product['discount_enabled']) && $product['discount_enabled'] != '0' && $product['discount_type'] && $product['discount_value'] > 0) {
                        $orig = $product['price'];
                        if ($product['discount_type'] === 'percent') {
                            $final = $orig * (1 - ($product['discount_value'] / 100));
                            $desc = $product['discount_value'] . '% OFF';
                        } else {
                            $final = max($orig - $product['discount_value'], 0);
                            $desc = '₱' . number_format($product['discount_value'], 2) . ' OFF';
                        }
                        echo '<span style="color:#888;text-decoration:line-through;font-size:14px;">₱' . number_format($orig, 2) . '</span> ';
                        echo '<span style="color:#e44d26;">₱' . number_format($final, 2) . '</span> ';
                        echo '<span style="background:linear-gradient(135deg, #ff9a44 0%, #fc6076 100%);color:white;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:6px;font-weight:600;box-shadow:0 2px 5px rgba(252, 96, 118, 0.3);">' . $desc . '</span>';
                    } else {
                        echo '<span style="color:#e44d26;">₱' . number_format($product['price'], 2) . '</span>';
                    }
                    ?>
                </div>
                
                <!-- Product Details -->
                <div class="product-meta" style="margin: 14px 0; background: #f8f9fa; padding: 13px; border-radius: 8px; border-left: 3px solid #5b6b46;">
                    <?php if (!empty($product['subcategory'])): ?>
                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <span style="font-weight: 700; color: #5b6b46; min-width: 110px; font-size: 13px;"><i class="fas fa-tag" style="margin-right: 5px;"></i>Type:</span> 
                        <span style="color: #555; font-weight: 500; font-size: 13px;"><?php echo htmlspecialchars(ucfirst($product['subcategory'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['sizes'])): ?>
                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <span style="font-weight: 700; color: #5b6b46; min-width: 110px; font-size: 13px;"><i class="fas fa-ruler-combined" style="margin-right: 5px;"></i>Available Sizes:</span> 
                        <span style="color: #555; font-weight: 500; font-size: 13px;"><?php echo htmlspecialchars(str_replace(',', ', ', $product['sizes'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['colors'])): ?>
                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <span style="font-weight: 700; color: #5b6b46; min-width: 110px; font-size: 13px;"><i class="fas fa-palette" style="margin-right: 5px;"></i>Available Colors:</span> 
                        <span style="color: #555; font-weight: 500; font-size: 13px;"><?php echo htmlspecialchars(str_replace(',', ', ', $product['colors'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['material'])): ?>
                    <div style="margin-bottom: 0; display: flex; align-items: center; gap: 6px;">
                        <span style="font-weight: 700; color: #5b6b46; min-width: 110px; font-size: 13px;"><i class="fas fa-cube" style="margin-right: 5px;"></i>Material:</span> 
                        <span style="color: #555; font-weight: 500; font-size: 13px;"><?php echo htmlspecialchars($product['material']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="description" style="margin: 14px 0; line-height: 1.6; color: #555; border-top: 2px solid #e8e8e8; padding-top: 14px;">
                    <h3 style="font-size: 16px; margin-bottom: 8px; color: #2c3e50; font-weight: 700;"><i class="fas fa-info-circle" style="margin-right: 5px; color: #5b6b46; font-size: 15px;"></i>Product Description</h3>
                    <p style="background: #f8f9fa; padding: 10px; border-radius: 7px; font-size: 13px;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <form id="addToCartForm" style="margin-top: 18px;">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <?php if (!empty($product['sizes'])): ?>
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label for="size" style="display: block; margin-bottom: 6px; font-weight: 700; color: #5b6b46; font-size: 13px;"><i class="fas fa-ruler-combined" style="margin-right: 5px;"></i>Size:</label>
                        <select id="size" name="size" required style="width: 100%; padding: 9px 12px; border: 2px solid #e0e0e0; border-radius: 7px; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: all 0.3s; background: white;">
                            <option value="">Select size</option>
                            <?php foreach (explode(',', $product['sizes']) as $size): ?>
                                <option value="<?php echo htmlspecialchars(trim($size)); ?>"><?php echo htmlspecialchars(trim($size)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['colors'])): ?>
                    <div class="form-group" style="margin-bottom: 13px;">
                        <label for="color" style="display: block; margin-bottom: 6px; font-weight: 700; color: #5b6b46; font-size: 13px;"><i class="fas fa-palette" style="margin-right: 5px;"></i>Color:</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                            <?php foreach (explode(',', $product['colors']) as $color): 
                                $color = trim($color);
                                $hasImage = isset($color_images[$color]);
                            ?>
                                <div class="color-option" 
                                     data-color="<?php echo htmlspecialchars($color); ?>" 
                                     data-color-image="<?php echo $hasImage ? htmlspecialchars($color_images[$color]) : ''; ?>"
                                     style="cursor: pointer; padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 7px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center; gap: 6px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.04);"
                                     onclick="selectColor('<?php echo htmlspecialchars($color); ?>', '<?php echo $hasImage ? htmlspecialchars($color_images[$color]) : ''; ?>')">
                                    <?php if ($hasImage): ?>
                                        <img src="img/<?php echo htmlspecialchars($color_images[$color]); ?>" 
                                             alt="<?php echo htmlspecialchars($color); ?>" 
                                             style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px; border: 1px solid #e0e0e0;">
                                    <?php endif; ?>
                                    <span style="font-size: 12px; font-weight: 500;"><?php echo htmlspecialchars($color); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="color" name="color" required>
                        <div id="colorError" style="color: #d32f2f; font-size: 12px; margin-top: 5px; display: none;">Please select a color</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="quantity" style="margin-bottom: 15px;">
                        <label for="quantity" style="display: block; margin-bottom: 6px; font-weight: 700; color: #5b6b46; font-size: 13px;"><i class="fas fa-shopping-basket" style="margin-right: 5px;"></i>Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" style="padding: 9px 12px; width: 85px; border: 2px solid #e0e0e0; border-radius: 7px; font-size: 13px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: all 0.3s;">
                        <div id="colorQtyMsg" style="margin-top: 5px; font-size: 11px; color: #5b6b46; font-weight: 500;"></div>
                    </div>
                    
                    <button type="submit" id="addToCartBtn" class="add-to-cart-btn" style="background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%); color: white; padding: 11px 24px; border: none; border-radius: 9px; cursor: pointer; font-size: 14px; font-weight: 700; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); width: 100%; max-width: 260px; box-shadow: 0 4px 14px rgba(91, 107, 70, 0.3); text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; justify-content: center; gap: 7px;">
                        <i class="fas fa-shopping-cart" style="font-size: 14px;"></i>
                        <span id="buttonText">Add to Cart</span>
                        <span id="buttonLoading" style="display: none;" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </button>
                    <div id="addToCartMessage" style="margin-top: 10px; padding: 10px 14px; border-radius: 8px; display: none; font-weight: 600; font-size: 13px;"></div>
                </form>
                
                <div style="margin-top: 16px; padding-top: 16px; border-top: 2px solid #e8e8e8;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="<?php echo ($product['category'] === 'Men') ? 'men.php' : 'women.php'; ?>" class="back-btn" style="display: inline-flex; align-items: center; gap: 5px; padding: 9px 18px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 7px; transition: all 0.3s; font-weight: 600; border: 2px solid #e0e0e0; font-size: 13px;">
                            <i class="fas fa-arrow-left"></i>Back to Shop
                        </a>
                        <a href="cart.php" class="view-cart-btn" style="display: inline-flex; align-items: center; gap: 5px; padding: 9px 18px; background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); color: white; text-decoration: none; border-radius: 7px; transition: all 0.3s; font-weight: 600; box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3); font-size: 13px;">
                            <i class="fas fa-shopping-cart"></i>View Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Reviews Section -->
        <?php if (count($feedbacks) > 0): ?>
        <div class="reviews-section" style="margin-top: 35px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
            <!-- Reviews Header with Summary -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="font-size: 20px; margin: 0 0 4px 0; color: #2c3e50; font-weight: 600;">
                        <i class="fas fa-star" style="color: #f39c12; margin-right: 6px; font-size: 16px;"></i>
                        Customer Reviews
                    </h2>
                    <p style="margin: 0; color: #7f8c8d; font-size: 12px;">See what our customers are saying</p>
                </div>
                
                <?php if ($total_ratings > 0): ?>
                <div style="text-align: center; background: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <div style="font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 3px;">
                        <?php echo number_format($avg_rating, 1); ?>
                    </div>
                    <div style="color: #f39c12; font-size: 14px; margin-bottom: 3px;">
                        <?php echo str_repeat('⭐', round($avg_rating)); ?>
                    </div>
                    <div style="color: #95a5a6; font-size: 11px;">
                        <?php echo $total_ratings; ?> <?php echo $total_ratings == 1 ? 'review' : 'reviews'; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Reviews List -->
            <div class="reviews-list" style="display: grid; gap: 12px;">
                <?php foreach ($feedbacks as $index => $feedback): ?>
                <div class="review-card" style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border-left: 3px solid #4CAF50; transition: all 0.2s ease;">
                    <div class="review-header" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                        <div style="flex: 1; min-width: 180px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($feedback['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #2c3e50; font-size: 14px; margin-bottom: 2px;">
                                        <?php echo htmlspecialchars($feedback['name']); ?>
                                    </div>
                                    <?php if ($feedback['rating'] !== null && $feedback['rating'] > 0): ?>
                                    <div style="color: #f39c12; font-size: 13px;">
                                        <?php echo str_repeat('⭐', (int)$feedback['rating']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="color: #95a5a6; font-size: 11px; background: #f8f9fa; padding: 4px 10px; border-radius: 12px;">
                            <?php echo date('M j, Y', strtotime($feedback['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="review-text" style="color: #555; line-height: 1.6; font-size: 13px; padding-left: 46px;">
                        <?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?>
                    </div>
                    
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f0f0f0; padding-left: 46px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #27ae60; font-size: 11px; font-weight: 500;">
                            <i class="fas fa-check-circle" style="font-size: 10px;"></i>
                            Verified Purchase
                        </span>
                        
                        <?php if (!empty($feedback['order_details'])): ?>
                            <?php 
                            $details = explode(';;', $feedback['order_details']);
                            foreach ($details as $detail) {
                                list($size, $color, $qty) = explode('|', $detail);
                                if (!empty($size) || !empty($color)) {
                                    echo '<span style="display: inline-flex; align-items: center; gap: 4px; background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: 500;">';
                                    if (!empty($size)) {
                                        echo '<i class="fas fa-ruler-combined" style="font-size: 9px;"></i> Size: ' . htmlspecialchars($size);
                                    }
                                    if (!empty($size) && !empty($color)) {
                                        echo ' • ';
                                    }
                                    if (!empty($color)) {
                                        echo '<i class="fas fa-palette" style="font-size: 9px;"></i> Color: ' . htmlspecialchars($color);
                                    }
                                    echo '</span>';
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Show More Button (if more than 5 reviews) -->
            <?php if (count($feedbacks) > 5): ?>
            <div style="text-align: center; margin-top: 16px;">
                <button onclick="toggleAllReviews()" id="showMoreBtn" style="background: #4CAF50; color: white; border: none; padding: 8px 20px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 1px 4px rgba(76, 175, 80, 0.3);">
                    <i class="fas fa-chevron-down" style="margin-right: 4px; font-size: 10px;"></i>
                    Show All (<?php echo count($feedbacks); ?>)
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
            .review-card:hover {
                box-shadow: 0 4px 16px rgba(0,0,0,0.12) !important;
                transform: translateY(-2px);
            }
            
            @media (max-width: 768px) {
                .reviews-section {
                    padding: 15px !important;
                }
                .review-text {
                    padding-left: 0 !important;
                }
                .review-card > div:last-child {
                    padding-left: 0 !important;
                }
            }
        </style>
        
        <script>
            // Initially hide reviews after 5th one
            <?php if (count($feedbacks) > 5): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const reviewCards = document.querySelectorAll('.review-card');
                reviewCards.forEach((card, index) => {
                    if (index >= 5) {
                        card.style.display = 'none';
                    }
                });
            });
            
            function toggleAllReviews() {
                const reviewCards = document.querySelectorAll('.review-card');
                const btn = document.getElementById('showMoreBtn');
                const allVisible = Array.from(reviewCards).every(card => card.style.display !== 'none');
                
                if (allVisible) {
                    reviewCards.forEach((card, index) => {
                        if (index >= 5) {
                            card.style.display = 'none';
                        }
                    });
                    btn.innerHTML = '<i class="fas fa-chevron-down" style="margin-right: 4px; font-size: 10px;"></i>Show All (<?php echo count($feedbacks); ?>)';
                } else {
                    reviewCards.forEach(card => {
                        card.style.display = 'block';
                    });
                    btn.innerHTML = '<i class="fas fa-chevron-up" style="margin-right: 4px; font-size: 10px;"></i>Show Less';
                }
            }
            <?php endif; ?>
        </script>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    
    <style>
        .color-option:hover {
            border-color: #5b6b46 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(91, 107, 70, 0.2);
        }
        .color-option.selected {
            border-color: #5b6b46 !important;
            background: linear-gradient(135deg, #f5f7f3 0%, #eaf6e8 100%) !important;
            box-shadow: 0 0 0 3px rgba(91, 107, 70, 0.2);
        }
        .gallery-img {
            transition: opacity 0.3s ease;
        }
        .gallery-img.changing {
            opacity: 0.5;
        }
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(91, 107, 70, 0.4);
        }
        .back-btn:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .view-cart-btn:hover {
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(33, 150, 243, 0.4);
        }
        select:focus, input[type="number"]:focus {
            border-color: #5b6b46;
            outline: none;
            box-shadow: 0 0 0 3px rgba(91, 107, 70, 0.15);
            transform: translateY(-1px);
        }
        @media (max-width: 768px) {
            .product-detail {
                flex-direction: column !important;
                gap: 20px !important;
            }
            .product-detail-container {
                padding: 18px !important;
                margin: 15px 12px !important;
            }
            .product-info h1 {
                font-size: 20px !important;
            }
            .price {
                font-size: 18px !important;
            }
        }
    </style>
    
    <script>
    // Store original gallery images
    let originalGalleryImages = [];
    let currentColorImage = null;
    let isColorImageActive = false;
    
    // Store original images on page load
    document.addEventListener('DOMContentLoaded', function() {
        const mainImages = document.querySelectorAll('.gallery-img');
        mainImages.forEach(img => {
            originalGalleryImages.push(img.src);
        });
    });
    
    // Function to select color
    function selectColor(color, colorImage) {
        // Update hidden input
        document.getElementById('color').value = color;
        
        // Update visual selection
        document.querySelectorAll('.color-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        document.querySelector(`.color-option[data-color="${color}"]`).classList.add('selected');
        
        // Hide error message
        document.getElementById('colorError').style.display = 'none';
        
        // Update available quantity based on color and size
        if (typeof window.updateAvailableQuantity === 'function') {
            window.updateAvailableQuantity();
        }
        
        // Navigate to color image in gallery if it exists
        if (colorImage && colorImage.trim() !== '') {
            const imgs = document.querySelectorAll('.gallery-img');
            const thumbnails = document.querySelectorAll('.thumbnail-wrapper');
            
            // Find the index of the color image
            let colorIndex = -1;
            imgs.forEach((img, index) => {
                const label = img.getAttribute('data-label');
                if (label === color) {
                    colorIndex = index;
                }
            });
            
            // If found, navigate to that image
            if (colorIndex !== -1) {
                imgs.forEach((img, j) => { 
                    img.style.display = (colorIndex === j) ? 'block' : 'none'; 
                });
                thumbnails.forEach((thumb, j) => {
                    thumb.style.borderColor = (colorIndex === j) ? '#4CAF50' : '#ddd';
                });
                
                // Update label
                const imageLabel = document.getElementById('imageLabel');
                if (imageLabel) {
                    imageLabel.textContent = color;
                }
                
                // Scroll thumbnail into view
                if (thumbnails[colorIndex]) {
                    thumbnails[colorIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }
        }
        
        // Update quantity limits
        setMaxQtyForColor();
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addToCartForm');
        const addToCartBtn = document.getElementById('addToCartBtn');
        const buttonText = document.getElementById('buttonText');
        const buttonLoading = document.getElementById('buttonLoading');
        const messageDiv = document.getElementById('addToCartMessage');
        const qtyInput = document.getElementById('quantity');
        const colorInput = document.getElementById('color');

        // Inventory matrix: color_size => quantity
        const inventoryMatrix = {};
        <?php if ($has_inventory_matrix): ?>
        <?php foreach ($inventory_matrix as $key => $qty): ?>
            inventoryMatrix[<?php echo json_encode($key); ?>] = <?php echo intval($qty); ?>;
        <?php endforeach; ?>
        <?php endif; ?>
        
        // Fallback: Color:quantity mapping (for products without matrix)
        const colorQtyMap = {};
        <?php if (!empty($product['colors_with_qty'])): ?>
        <?php foreach (explode(',', $product['colors_with_qty']) as $pair): list($color, $qty) = explode(':', $pair); ?>
            colorQtyMap[<?php echo json_encode(trim($color)); ?>] = <?php echo intval($qty); ?>;
        <?php endforeach; ?>
        <?php endif; ?>

        // Update quantity based on selected color and size
        window.updateAvailableQuantity = function() {
            const sizeInput = document.getElementById('size');
            const selectedColor = colorInput ? colorInput.value : '';
            const selectedSize = sizeInput ? sizeInput.value : '';
            const qtyMsg = document.getElementById('colorQtyMsg');
            
            // If we have inventory matrix and both color and size are selected
            if (Object.keys(inventoryMatrix).length > 0 && selectedColor && selectedSize) {
                const key = selectedColor + '_' + selectedSize;
                const availableQty = inventoryMatrix[key] || 0;
                
                qtyInput.max = availableQty;
                if (parseInt(qtyInput.value, 10) > availableQty) {
                    qtyInput.value = Math.max(1, availableQty);
                }
                
                if (qtyMsg) {
                    if (availableQty > 0) {
                        qtyMsg.textContent = `Maximum Order: ${availableQty} (${selectedColor} - ${selectedSize})`;
                        qtyMsg.style.color = '#5b6b46';
                    } else {
                        qtyMsg.textContent = `Out of stock (${selectedColor} - ${selectedSize})`;
                        qtyMsg.style.color = '#d32f2f';
                    }
                    qtyMsg.style.display = '';
                }
            }
            // Fallback to color-only logic
            else if (selectedColor && colorQtyMap[selectedColor] !== undefined) {
                qtyInput.max = colorQtyMap[selectedColor];
                if (parseInt(qtyInput.value, 10) > colorQtyMap[selectedColor]) {
                    qtyInput.value = colorQtyMap[selectedColor];
                }
                if (qtyMsg) {
                    qtyMsg.textContent = `Maximum Order: ${colorQtyMap[selectedColor]} (${selectedColor})`;
                    qtyMsg.style.color = '#5b6b46';
                    qtyMsg.style.display = '';
                }
            } else {
                qtyInput.removeAttribute('max');
                if (qtyMsg) qtyMsg.style.display = 'none';
            }
        }
        
        // Legacy function for backward compatibility
        window.setMaxQtyForColor = window.updateAvailableQuantity;
        
        // Add event listener for size selection
        const sizeInput = document.getElementById('size');
        if (sizeInput) {
            sizeInput.addEventListener('change', function() {
                window.updateAvailableQuantity();
            });
        }
        
        qtyInput.addEventListener('input', function() {
            if (qtyInput.max && parseInt(qtyInput.value, 10) > parseInt(qtyInput.max, 10)) {
                qtyInput.value = qtyInput.max;
            }
            if (parseInt(qtyInput.value, 10) < 1) {
                qtyInput.value = 1;
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate color selection if colors exist
            if (colorInput && !colorInput.value) {
                document.getElementById('colorError').style.display = 'block';
                return;
            }
            
            // Show loading state
            buttonText.style.display = 'none';
            buttonLoading.style.display = 'inline-block';
            addToCartBtn.disabled = true;
            messageDiv.style.display = 'none';
            
            const formData = new FormData(form);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                buttonText.style.display = 'inline';
                buttonLoading.style.display = 'none';
                addToCartBtn.disabled = false;
                
                // Show success/error message
                const ok = (typeof data.success !== 'undefined') ? !!data.success : (data.status === 'success');
                messageDiv.textContent = data.message || (ok ? 'Added to cart.' : 'Failed to add to cart.');
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = ok ? '#d4edda' : '#f8d7da';
                messageDiv.style.color = ok ? '#155724' : '#721c24';
                messageDiv.style.borderColor = ok ? '#c3e6cb' : '#f5c6cb';
                
                // Update cart count in header
                if (ok) {
                    updateCartCount();
                    // Reset form
                    form.reset();
                    if (qtyInput) qtyInput.value = 1;
                }
                
                // Hide message after 3 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                buttonText.style.display = 'inline';
                buttonLoading.style.display = 'none';
                addToCartBtn.disabled = false;
                
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.style.display = 'block';
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.borderColor = '#f5c6cb';
            });
        });
        
        // Function to update cart count
        function updateCartCount() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartCount = document.getElementById('cartCount');
                    if (data.count > 0) {
                        cartCount.textContent = data.count;
                        cartCount.style.display = 'inline-block';
                    } else {
                        cartCount.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        }
    });
    </script>
</body>
</html>
