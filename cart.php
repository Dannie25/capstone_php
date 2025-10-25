<?php
session_start();
include 'db.php';
include 'cart_functions.php';
include_once 'includes/image_helper.php';
include_once 'includes/inventory_helper.php';

// Handle remove from cart
if (isset($_POST['remove_item']) && isset($_POST['cart_id'])) {
    $cart_id = $_POST['cart_id'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    header("Location: cart.php");
    exit();
}

// Handle update quantity
if (isset($_POST['update_quantity']) && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cart_id = $_POST['cart_id'];
    $quantity = max(1, intval($_POST['quantity']));
    
    // Get cart item details to validate against available stock
    $stmt = $conn->prepare("SELECT product_id, color, size FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_item = $result->fetch_assoc();
    $stmt->close();
    
    if ($cart_item) {
        $product_id = $cart_item['product_id'];
        $color = $cart_item['color'];
        $size = $cart_item['size'];
        
        // Validate quantity against available stock
        $available_qty = null;
        
        // Check color-size inventory matrix first
        if ($color && $size) {
            $available_qty = getAvailableQuantity($conn, $product_id, $color, $size);
        }
        // Fallback to color-only for old products
        else if ($color) {
            $stmt = $conn->prepare("SELECT quantity FROM product_colors WHERE product_id = ? AND color = ?");
            $stmt->bind_param("is", $product_id, $color);
            $stmt->execute();
            $stmt->bind_result($available_qty);
            $stmt->fetch();
            $stmt->close();
        }
        
        // If requested quantity exceeds available stock, cap it at available stock
        if ($available_qty !== null && $quantity > $available_qty) {
            $quantity = max(1, $available_qty);
            $_SESSION['cart_message'] = "Quantity adjusted to available stock ($available_qty units).";
        }
        
        // Update cart with validated quantity
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
        $stmt->execute();
    }
    
    header("Location: cart.php");
    exit();
}

$cart_items = getCartItems();
$subtotal = 0;
// $shipping removed
$tax_rate = 0.12; // 12% tax rate

// Get available quantities for each cart item
foreach ($cart_items as &$cart_item) {
    $available_qty = null;
    
    // Check color-size inventory matrix first
    if ($cart_item['color'] && $cart_item['size']) {
        $available_qty = getAvailableQuantity($conn, $cart_item['product_id'], $cart_item['color'], $cart_item['size']);
    }
    // Fallback to color-only for old products
    else if ($cart_item['color']) {
        $stmt = $conn->prepare("SELECT quantity FROM product_colors WHERE product_id = ? AND color = ?");
        $stmt->bind_param("is", $cart_item['product_id'], $cart_item['color']);
        $stmt->execute();
        $stmt->bind_result($available_qty);
        $stmt->fetch();
        $stmt->close();
    }
    
    $cart_item['max_quantity'] = $available_qty ? $available_qty : 999;
}
unset($cart_item);

// Calculate subtotal
foreach ($cart_items as &$item) {
    // Fetch discount fields for each product
    $stmt = $conn->prepare("SELECT discount_enabled, discount_type, discount_value FROM products WHERE id = ?");
    $stmt->bind_param("i", $item['product_id']);
    $stmt->execute();
    $stmt->bind_result($discount_enabled, $discount_type, $discount_value);
    $stmt->fetch();
    $stmt->close();
    $item['orig_price'] = $item['price'];
    if ($discount_enabled && $discount_type && $discount_value > 0) {
        if ($discount_type === 'percent') {
            $item['price'] = $item['orig_price'] * (1 - ($discount_value / 100));
            $item['discount_label'] = $discount_value . '% OFF';
        } else {
            $item['price'] = max($item['orig_price'] - $discount_value, 0);
            $item['discount_label'] = '₱' . number_format($discount_value, 2) . ' OFF';
        }
    } else {
        $item['discount_label'] = '';
    }
    $subtotal += $item['price'] * $item['quantity'];
}
unset($item);

// Calculate tax and total
$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MTC Clothing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --light-gray: #e9ecef;
            --border-color: #dee2e6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 15px;
        }
        
        .cart-header {
            text-align: center;
            margin: 10px 0 20px;
            color: var(--primary-color);
            font-size: 24px;
        }
        
        .cart-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Cart Items Section */
        .cart-items {
            flex: 2;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px;
        }
        
        .cart-item {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-checkbox {
            margin-right: 10px;
            align-self: center;
        }
        
        .item-image {
            width: 80px;
            height: 95px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 12px;
        }
        
        .item-details {
            flex-grow: 1;
        }
        
        .item-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .item-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 10px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }
        
        .quantity-btn {
            width: 26px;
            height: 26px;
            background: var(--light-gray);
            border: 1px solid var(--border-color);
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        
        .quantity-input {
            width: 45px;
            height: 26px;
            text-align: center;
            margin: 0 4px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 13px;
        }
        
        .remove-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Order Summary Section */
        .order-summary {
            flex: 1;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .summary-title {
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--primary-color);
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 16px;
            margin: 12px 0;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }
        
        .checkout-btn {
            width: 100%;
            padding: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background-color 0.3s;
        }
        
        .checkout-btn:hover {
            background-color: #4a5a36;
        }
        
        .payment-methods {
            margin-top: 15px;
            text-align: center;
        }
        
        .payment-methods p {
            margin-bottom: 10px;
            color: #6c757d;
            font-size: 13px;
        }
        
        .payment-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .payment-icon {
            font-size: 24px;
            color: #6c757d;
        }
        
        .empty-cart {
            text-align: center;
            padding: 35px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .empty-cart i {
            font-size: 45px;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        
        .empty-cart h2 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .continue-shopping {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .continue-shopping:hover {
            background-color: #4a5a36;
            color: white;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .cart-container {
                flex-direction: column;
            }
            
            .cart-items, .order-summary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1 class="cart-header">Shopping Cart</h1>
        
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #ffeaa7; font-size: 13px;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION['cart_message']); ?>
            </div>
            <?php unset($_SESSION['cart_message']); ?>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added anything to your cart yet.</p>
                <a href="home.php" class="continue-shopping">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-container">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php
                    // Get the referer URL if available
                    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'home.php';
                    // If coming from cart actions, default to women's section
                    if (strpos($referer, 'cart.php') !== false || strpos($referer, 'add_to_cart.php') !== false) {
                        $back_url = 'women.php'; // Default to women's section
                    } else {
                        $back_url = $referer;
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars($back_url); ?>" class="continue-shopping" style="display: inline-flex; align-items: center; margin-bottom: 12px; text-decoration: none;">
                        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
                        Continue Shopping
                    </a>
                    <?php foreach ($cart_items as $item): 
                        // Get Image 1 from product_images table
                        $display_image = getCatalogImage($conn, $item['product_id'], $item['image']);
                    ?>
                        <div class="cart-item">
                            <div class="item-checkbox">
                                <input type="checkbox" class="select-item" data-cart-id="<?php echo $item['id']; ?>" checked>
                            </div>
                            <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" 
                               title="View product details"
                               style="display: block; flex-shrink: 0;">
                                <img src="<?php echo htmlspecialchars($display_image); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="item-image" 
                                     style="cursor: pointer; transition: transform 0.2s;"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'"
                                     onerror="this.onerror=null; this.src='<?php echo getPlaceholderImage(); ?>';">
                            </a>
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <?php if (!empty($item['size']) || !empty($item['color'])): ?>
                                    <div class="item-attributes" style="font-size: 12px; color: #666; margin: 3px 0 6px;">
                                        <?php if (!empty($item['size'])): ?>
                                            <span>Size: <?php echo htmlspecialchars($item['size']); ?></span><br>
                                        <?php endif; ?>
                                        <?php if (!empty($item['color'])): ?>
                                            <span>Color: <?php echo htmlspecialchars($item['color']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="item-price">
                                    <?php if (!empty($item['discount_label'])): ?>
                                        <span style="color:#888;text-decoration:line-through;font-size:12px;">₱<?php echo number_format($item['orig_price'], 2); ?></span>
                                        <span style="color:#e44d26;">₱<?php echo number_format($item['price'], 2); ?></span>
                                        <span style="background:#f9e8d2;color:#b65c00;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:8px;">
                                            <?php echo $item['discount_label']; ?>
                                        </span>
                                    <?php else: ?>
                                        ₱<?php echo number_format($item['price'], 2); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <form method="post" class="quantity-form">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn" onclick="decrementQuantity(this)">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['max_quantity']; ?>" class="quantity-input" data-max="<?php echo $item['max_quantity']; ?>" onchange="validateQuantity(this); this.form.submit()">
                                        <button type="button" class="quantity-btn" onclick="incrementQuantity(this)">+</button>
                                        <input type="hidden" name="update_quantity" value="1">
                                    </div>
                                </form>
                                
                                <form method="post" class="remove-form">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_item" class="remove-btn">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                            <div class="item-total">
                                <?php if (!empty($item['discount_label'])): ?>
                                    <span style="color:#888;text-decoration:line-through;font-size:13px;">₱<?php echo number_format($item['orig_price'] * $item['quantity'], 2); ?></span><br>
                                    <span style="color:#e44d26;">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                <?php else: ?>
                                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
    <h2 class="summary-title">Order Summary</h2>
    <?php foreach ($cart_items as $item): ?>
        <div class="order-item" style="display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #eee;">
            <div class="item-info">
                <div class="item-name" style="font-weight:500; font-size:13px;"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="item-details" style="font-size:12px; color:#666;">
                    Qty: <?php echo $item['quantity']; ?> ×
                    <?php if (!empty($item['discount_label'])): ?>
                        <span style="color:#888;text-decoration:line-through;font-size:11px;">₱<?php echo number_format($item['orig_price'], 2); ?></span>
                        <span style="color:#e44d26;">₱<?php echo number_format($item['price'], 2); ?></span>
                        <span style="background:#f9e8d2;color:#b65c00;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:8px;">
                            <?php echo $item['discount_label']; ?>
                        </span>
                    <?php else: ?>
                        ₱<?php echo number_format($item['price'], 2); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="item-total" style="font-size:13px; color:#e44d26; font-weight:600; min-width:70px; text-align:right;">
                <?php if (!empty($item['discount_label'])): ?>
                    <span style="color:#888;text-decoration:line-through;font-size:12px;">₱<?php echo number_format($item['orig_price'] * $item['quantity'], 2); ?></span><br>
                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                <?php else: ?>
                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div style="margin-top: 12px; padding-top: 8px; border-top: 1px solid #eee;"></div>
    <div class="summary-row">
        <span>Subtotal</span>
        <span id="summary-subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
    </div>
    <div class="summary-row">
        <span>Tax (12%)</span>
        <span id="summary-tax">₱<?php echo number_format($tax, 2); ?></span>
    </div>
    <div class="summary-row summary-total">
        <span>Total</span>
        <span id="summary-total">₱<?php echo number_format($total, 2); ?></span>
    </div>
                    
                    <a href="checkout.php" class="checkout-btn" style="text-decoration: none; display: block; text-align: center;">Proceed to Checkout</a>
                    
                    <div class="payment-methods">
                        <p>Available Payment Methods</p>
                        <div class="payment-icons" style="flex-direction: column; align-items: center;">
                            <div style="font-size:13px; color:#495057; margin-bottom:6px;">
                                <i class="fas fa-qrcode me-2"></i> GCash (Scan QR at checkout)
                            </div>
                            <div style="font-size:13px; color:#495057;">
                                <i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Quantity validation functions
        function validateQuantity(input) {
            const max = parseInt(input.getAttribute('data-max')) || 999;
            const min = parseInt(input.getAttribute('min')) || 1;
            let value = parseInt(input.value) || min;
            
            if (value > max) {
                input.value = max;
                alert('Maximum available quantity is ' + max);
                return false;
            }
            if (value < min) {
                input.value = min;
                return false;
            }
            return true;
        }
        
        function incrementQuantity(btn) {
            const input = btn.previousElementSibling;
            const max = parseInt(input.getAttribute('data-max')) || 999;
            const current = parseInt(input.value) || 1;
            
            if (current < max) {
                input.value = current + 1;
                // Trigger change event and submit
                const form = input.closest('form');
                if (form) {
                    form.submit();
                }
            } else {
                alert('Maximum available quantity is ' + max);
            }
        }
        
        function decrementQuantity(btn) {
            const input = btn.nextElementSibling;
            const min = parseInt(input.getAttribute('min')) || 1;
            const current = parseInt(input.value) || 1;
            
            if (current > min) {
                input.value = current - 1;
                // Trigger change event and submit
                const form = input.closest('form');
                if (form) {
                    form.submit();
                }
            }
        }
        
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Update cart count in header
        function updateCartCount() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.count;
                        cartCount.style.display = data.count > 0 ? 'flex' : 'none';
                    }
                });
        }

        // Selection-based totals and checkout
        (function() {
            const TAX_RATE = <?php echo json_encode($tax_rate); ?>; // 0.12
            const SHIPPING = 0; // Shipping removed

            function parsePeso(text) {
                if (!text) return 0;
                const num = text.replace(/[^0-9.]/g, '');
                return parseFloat(num || '0');
            }

            function formatPeso(n) {
                return '₱' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            function getItemSubtotal(itemEl) {
                const priceText = itemEl.querySelector('.item-price')?.textContent || '0';
                const price = parsePeso(priceText);
                const qty = parseInt(itemEl.querySelector('.quantity-input')?.value || '1', 10) || 1;
                return price * qty;
            }

            function updateSummary() {
                const items = Array.from(document.querySelectorAll('.cart-item'));
                let subtotal = 0;
                items.forEach(item => {
                    const cb = item.querySelector('.select-item');
                    if (cb && cb.checked) {
                        subtotal += getItemSubtotal(item);
                    }
                });
                const tax = subtotal * TAX_RATE;
                const total = subtotal + SHIPPING + tax;
                const subEl = document.getElementById('summary-subtotal');
                const shipEl = document.getElementById('summary-shipping');
                const taxEl = document.getElementById('summary-tax');
                const totalEl = document.getElementById('summary-total');
                if (subEl) subEl.textContent = formatPeso(subtotal);
                if (shipEl) shipEl.textContent = formatPeso(SHIPPING);
                if (taxEl) taxEl.textContent = formatPeso(tax);
                if (totalEl) totalEl.textContent = formatPeso(total);
            }

            // Attach listeners to checkboxes and quantity changes
            function bindItemListeners() {
                document.querySelectorAll('.select-item').forEach(cb => {
                    cb.addEventListener('change', updateSummary);
                });
                document.querySelectorAll('.quantity-input').forEach(input => {
                    input.addEventListener('change', updateSummary);
                });
            }

            // Intercept Proceed to Checkout link
            function bindCheckoutRedirect() {
                const link = document.querySelector('a.checkout-btn');
                if (!link) return;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedIds = Array.from(document.querySelectorAll('.select-item:checked'))
                        .map(cb => cb.getAttribute('data-cart-id'))
                        .filter(Boolean);
                    if (selectedIds.length === 0) {
                        alert('Please select at least one item to proceed to checkout.');
                        return;
                    }
                    const url = new URL(this.href, window.location.origin);
                    url.searchParams.set('items', selectedIds.join(','));
                    window.location.href = url.toString();
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                bindItemListeners();
                bindCheckoutRedirect();
                updateSummary();
                updateCartCount();
            });
        })();
    </script>
</body>
</html>