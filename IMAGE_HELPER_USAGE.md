# Image Helper Usage Guide

Ang `image_helper.php` ay isang centralized solution para sa lahat ng product image display issues sa iyong MTC Clothing project.

## Quick Start

### 1. Include ang helper file sa iyong PHP page:

```php
<?php
include 'includes/image_helper.php';
?>
```

### 2. Gamitin ang functions para sa images:

#### Simple Image Display
```php
<!-- Old way (may problema sa paths) -->
<img src="<?php echo $row['image']; ?>" alt="Product">

<!-- New way (automatic path correction) -->
<img src="<?php echo getProductImagePath($row['image']); ?>" alt="Product">

<!-- Or use the render function with auto-fallback -->
<?php echo renderProductImage($row['image'], 'Product Name', 'product-img', 'width: 100%;'); ?>
```

#### Get All Product Images (with gallery support)
```php
<?php
$allImages = getAllProductImages($conn, $productId, $row['image']);

foreach ($allImages as $img) {
    echo '<img src="' . $img['path'] . '" alt="' . $img['label'] . '">';
}
?>
```

## Available Functions

### `getProductImagePath($imagePath, $checkExists = true)`
Automatically finds the correct path for any product image.

**Parameters:**
- `$imagePath` - Image path from database
- `$checkExists` - Check if file exists (default: true)

**Returns:** Correct image path

**Example:**
```php
$correctPath = getProductImagePath('product123.jpg');
// Returns: /capstone_php/img/product123.jpg (if exists)
// Or: /capstone_php/admin/product123.jpg (if exists there)
// Or: placeholder image (if not found)
```

### `renderProductImage($imagePath, $alt, $class, $style)`
Outputs complete img tag with error handling.

**Example:**
```php
echo renderProductImage(
    $row['image'], 
    'Product Name', 
    'product-image', 
    'width: 100%; height: auto;'
);
```

### `getAllProductImages($conn, $productId, $mainImage)`
Gets all images for a product (main + additional + color images).

**Example:**
```php
$images = getAllProductImages($conn, $productId, $row['image']);
// Returns array of ['path' => '...', 'label' => '...']
```

### `getProductImages($conn, $productId)`
Gets additional images from product_images table.

### `getProductColorImages($conn, $productId)`
Gets color variant images.

### `getPlaceholderImage()`
Returns placeholder image path.

## Benefits

✅ **Works from any file location** - Admin pages, customer pages, subdirectories
✅ **Automatic path detection** - Checks img/, admin/, admin/img/, uploads/
✅ **Fallback support** - Shows placeholder if image not found
✅ **Error handling** - JavaScript onerror fallback
✅ **Consistent display** - Same image logic across all pages

## Migration Examples

### Example 1: Product Listing (products.php, men.php, women.php)

**Before:**
```php
<?php
$imgSrc = $row['image'];
if (!empty($imgSrc) && !preg_match('/^(img\/|admin\/|https?:\/\/)/i', $imgSrc)) {
    $imgSrc = 'img/' . $imgSrc;
}
?>
<img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
```

**After:**
```php
<?php include_once 'includes/image_helper.php'; ?>
<img src="<?php echo getProductImagePath($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">

<!-- Or even simpler: -->
<?php echo renderProductImage($row['image'], $row['name'], 'product-image'); ?>
```

### Example 2: Product Detail Page

**Before:**
```php
$imgs = [];
$productImgRes = $conn->query("SELECT image FROM product_images WHERE product_id = " . (int)$product['id']);
while ($row = $productImgRes->fetch_assoc()) {
    $imagePath = $row['image'];
    if (strpos($imagePath, 'img/') !== 0) {
        $imagePath = 'img/' . $imagePath;
    }
    $imgs[] = $imagePath;
}
```

**After:**
```php
<?php 
include_once 'includes/image_helper.php';
$allImages = getAllProductImages($conn, $product['id'], $product['image']);
?>

<?php foreach ($allImages as $img): ?>
    <img src="<?php echo $img['path']; ?>" alt="<?php echo $img['label']; ?>">
<?php endforeach; ?>
```

### Example 3: Admin Pages

**Before:**
```php
<img src="../img/<?php echo $row['image']; ?>" alt="Product">
```

**After:**
```php
<?php include_once '../includes/image_helper.php'; ?>
<img src="<?php echo getProductImagePath($row['image']); ?>" alt="Product">
```

## Testing

Test kung gumagana sa lahat ng pages:
1. ✅ Customer pages (products.php, men.php, women.php, arrivals.php)
2. ✅ Product detail page
3. ✅ Admin pages (product.php, orders.php)
4. ✅ Cart and checkout pages
5. ✅ Order history pages

## Troubleshooting

**Problem:** Images still not showing
**Solution:** Check if `includes/image_helper.php` is included at the top of your file

**Problem:** Wrong path in admin pages
**Solution:** Use `include_once '../includes/image_helper.php';` (with ../)

**Problem:** Placeholder showing instead of image
**Solution:** Check if image file actually exists in img/ or admin/ directories
