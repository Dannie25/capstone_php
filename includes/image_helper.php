<?php
/**
 * Image Helper Functions
 * Centralized image path handling to ensure images display correctly across all pages
 */

/**
 * Get the correct image path for product images
 * Works from any file location in the project
 * 
 * @param string $imagePath The image path from database
 * @param bool $checkExists Whether to check if file exists (default: true)
 * @return string The correct image path to use in src attribute
 */
function getProductImagePath($imagePath, $checkExists = true) {
    // Return placeholder if empty
    if (empty($imagePath)) {
        return getPlaceholderImage();
    }
    
    // If it's already a full URL, return as is
    if (preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }
    
    // Remove any leading slashes or backslashes
    $imagePath = ltrim($imagePath, '/\\');
    
    // Get the base path relative to document root
    $basePath = getBasePath();
    
    // Define possible image directories in order of priority
    $possiblePaths = [
        $imagePath,                    // Direct path (e.g., img/product.jpg)
        'img/' . $imagePath,           // img directory
        'admin/' . $imagePath,         // admin directory
        'admin/img/' . $imagePath,     // admin/img directory
        'uploads/' . $imagePath,       // uploads directory
    ];
    
    // If we need to check file existence
    if ($checkExists) {
        foreach ($possiblePaths as $path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $basePath . $path;
            if (file_exists($fullPath)) {
                return $basePath . $path;
            }
        }
        // If no file found, return placeholder
        return getPlaceholderImage();
    }
    
    // If not checking existence, use smart detection
    // If path already has a directory prefix, use it
    if (preg_match('/^(img\/|admin\/|uploads\/)/i', $imagePath)) {
        return $basePath . $imagePath;
    }
    
    // Default to img/ directory
    return $basePath . 'img/' . $imagePath;
}

/**
 * Get placeholder image path
 * 
 * @return string Path to placeholder image
 */
function getPlaceholderImage() {
    $basePath = getBasePath();
    
    // Check for common placeholder images
    $placeholders = [
        'img/no-image.jpg',
        'img/placeholder.png',
        'admin/img/no-image.jpg',
    ];
    
    foreach ($placeholders as $placeholder) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $basePath . $placeholder)) {
            return $basePath . $placeholder;
        }
    }
    
    // Return data URI for a simple gray placeholder
    return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="400"%3E%3Crect width="400" height="400" fill="%23f0f0f0"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="18" fill="%23999"%3ENo Image%3C/text%3E%3C/svg%3E';
}

/**
 * Get the base path for the application
 * Automatically detects if we're in a subdirectory
 * 
 * @return string Base path with leading and trailing slash
 */
function getBasePath() {
    // Get the current script's directory
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    
    // If we're in a subdirectory (e.g., /capstone_php/), use it
    if ($scriptPath !== '/' && strpos($scriptPath, '/capstone_php') !== false) {
        return '/capstone_php/';
    }
    
    // If we're in admin directory
    if (strpos($scriptPath, '/admin') !== false) {
        return '/capstone_php/';
    }
    
    // Default to root
    return '/capstone_php/';
}

/**
 * Get the first image from product_images table (Image 1)
 * This is the primary display image for product catalogs
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @return string|null First image path or null if not found
 */
function getFirstProductImage($conn, $productId) {
    $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY id LIMIT 1");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        if (!empty($row['image'])) {
            return getProductImagePath($row['image']);
        }
    }
    
    $stmt->close();
    return null;
}

/**
 * Get multiple product images from product_images table
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @return array Array of image paths
 */
function getProductImages($conn, $productId) {
    $images = [];
    
    $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ? ORDER BY id");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['image'])) {
            $images[] = getProductImagePath($row['image']);
        }
    }
    
    $stmt->close();
    
    return $images;
}

/**
 * Get product color images
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @return array Array with color names as keys and image paths as values
 */
function getProductColorImages($conn, $productId) {
    $colorImages = [];
    
    $stmt = $conn->prepare("SELECT color, color_image FROM product_colors WHERE product_id = ? AND color_image IS NOT NULL AND color_image != '' ORDER BY id");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $colorImages[$row['color']] = getProductImagePath($row['color_image']);
    }
    
    $stmt->close();
    
    return $colorImages;
}

/**
 * Get all product images including main image, additional images, and color images
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @param string $mainImage Main product image from products table
 * @return array Array of arrays with 'path' and 'label' keys
 */
function getAllProductImages($conn, $productId, $mainImage = null) {
    $allImages = [];
    
    // Add main image if provided
    if (!empty($mainImage)) {
        $allImages[] = [
            'path' => getProductImagePath($mainImage),
            'label' => 'Main Image'
        ];
    }
    
    // Get additional images from product_images table
    $images = getProductImages($conn, $productId);
    $counter = 1;
    foreach ($images as $image) {
        $allImages[] = [
            'path' => $image,
            'label' => 'Image ' . $counter
        ];
        $counter++;
    }
    
    // Get color images
    $colorImages = getProductColorImages($conn, $productId);
    foreach ($colorImages as $color => $image) {
        $allImages[] = [
            'path' => $image,
            'label' => $color
        ];
    }
    
    return $allImages;
}

/**
 * Get catalog display image for a product
 * Prioritizes Image 1 from product_images table, falls back to main image
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @param string $fallbackImage Fallback image from products.image field
 * @return string Image path to display
 */
function getCatalogImage($conn, $productId, $fallbackImage = '') {
    // Try to get Image 1 from product_images table first
    $firstImage = getFirstProductImage($conn, $productId);
    
    if ($firstImage !== null) {
        return $firstImage;
    }
    
    // Fall back to main product image if no Image 1
    if (!empty($fallbackImage)) {
        return getProductImagePath($fallbackImage);
    }
    
    // Last resort: placeholder
    return getPlaceholderImage();
}

/**
 * Output an img tag with proper error handling
 * 
 * @param string $imagePath Image path from database
 * @param string $alt Alt text
 * @param string $class CSS class
 * @param string $style Inline styles
 * @return string HTML img tag
 */
function renderProductImage($imagePath, $alt = '', $class = '', $style = '') {
    $src = getProductImagePath($imagePath);
    $alt = htmlspecialchars($alt);
    $class = htmlspecialchars($class);
    
    $html = '<img src="' . htmlspecialchars($src) . '" alt="' . $alt . '"';
    
    if (!empty($class)) {
        $html .= ' class="' . $class . '"';
    }
    
    if (!empty($style)) {
        $html .= ' style="' . htmlspecialchars($style) . '"';
    }
    
    // Add onerror handler to show placeholder if image fails to load
    $placeholder = getPlaceholderImage();
    $html .= ' onerror="this.onerror=null; this.src=\'' . $placeholder . '\';"';
    
    $html .= '>';
    
    return $html;
}

/**
 * Render catalog image for product listings
 * Automatically uses Image 1 from product_images if available
 * 
 * @param mysqli $conn Database connection
 * @param int $productId Product ID
 * @param string $fallbackImage Fallback image from products.image
 * @param string $alt Alt text
 * @param string $class CSS class
 * @param string $style Inline styles
 * @return string HTML img tag
 */
function renderCatalogImage($conn, $productId, $fallbackImage, $alt = '', $class = '', $style = '') {
    $imagePath = getCatalogImage($conn, $productId, $fallbackImage);
    $alt = htmlspecialchars($alt);
    $class = htmlspecialchars($class);
    
    $html = '<img src="' . htmlspecialchars($imagePath) . '" alt="' . $alt . '"';
    
    if (!empty($class)) {
        $html .= ' class="' . $class . '"';
    }
    
    if (!empty($style)) {
        $html .= ' style="' . htmlspecialchars($style) . '"';
    }
    
    // Add onerror handler to show placeholder if image fails to load
    $placeholder = getPlaceholderImage();
    $html .= ' onerror="this.onerror=null; this.src=\'' . $placeholder . '\';"';
    
    $html .= '>';
    
    return $html;
}
