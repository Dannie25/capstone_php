<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "capstone_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get all products
$query = "SELECT id, image FROM products";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching products: " . mysqli_error($conn));
}

$updated = 0;
$not_found = [];

echo "Starting image path update...<br><br>";

while ($row = mysqli_fetch_assoc($result)) {
    $product_id = $row['id'];
    $current_path = $row['image'];
    
    // Skip if no image path
    if (empty($current_path)) {
        $not_found[] = "Product ID $product_id: No image path";
        continue;
    }
    
    // Check if the current path exists
    if (file_exists("../" . $current_path)) {
        echo "Path already correct for Product ID $product_id: $current_path<br>";
        continue;
    }
    
    // Try to find the image in common directories
    $filename = basename($current_path);
    $possible_locations = [
        'img/' . $filename,
        'admin/img/' . $filename,
        'uploads/' . $filename,
        'products/' . $filename,
        'assets/img/products/' . $filename,
        'images/products/' . $filename
    ];
    
    $found = false;
    foreach ($possible_locations as $location) {
        if (file_exists("../" . $location)) {
            // Update the database with the correct path
            $update_query = "UPDATE products SET image = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $location, $product_id);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "Updated Product ID $product_id: $location<br>";
                $updated++;
                $found = true;
                break;
            } else {
                echo "Error updating Product ID $product_id: " . mysqli_error($conn) . "<br>";
            }
        }
    }
    
    if (!$found) {
        $not_found[] = "Product ID $product_id: Could not find image ($filename)";
    }
}

echo "<br>Update complete!<br>";
echo "Updated $updated products.<br>";

if (!empty($not_found)) {
    echo "<br>Could not find images for the following products:<br>";
    echo implode("<br>", $not_found);
}

mysqli_close($conn);
?>
