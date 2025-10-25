<?php
include 'db.php';

// First, let's see all products
echo "<h2>Current Products:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Action</th></tr>";

$result = $conn->query("SELECT * FROM products");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['category'] . "</td>";
        echo "<td>₱" . $row['price'] . "</td>";
        echo "<td><a href='?delete=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this product?\")' style='color: red;'>Delete</a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>No products found</td></tr>";
}
echo "</table>";

// Handle deletion
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Get product name before deleting for confirmation
    $result = $conn->query("SELECT name FROM products WHERE id = $product_id");
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $product_name = $product['name'];
        
        // Delete the product
        $delete_result = $conn->query("DELETE FROM products WHERE id = $product_id");
        
        if ($delete_result) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb; border-radius: 5px;'>";
            echo "✅ Product '$product_name' has been successfully deleted!";
            echo "</div>";
            echo "<script>setTimeout(function(){ window.location.href = 'delete_product.php'; }, 2000);</script>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border: 1px solid #f5c6cb; border-radius: 5px;'>";
            echo "❌ Error deleting product: " . $conn->error;
            echo "</div>";
        }
    }
}

echo "<br><p><a href='check_products.php' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>← Back to Product List</a></p>";
echo "<p><a href='admin/dashboard.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Go to Admin Dashboard</a></p>";
?>
