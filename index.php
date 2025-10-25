
<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Test Product Listing</title>
</head>
<body>
  <h1>Product List</h1>
  <ul>
    <?php
      $result = $conn->query("SELECT * FROM products");
      while($row = $result->fetch_assoc()):
    ?>
      <li>
        <?php echo $row['name']; ?> - ₱<?php echo $row['price']; ?>
        <img src="<?php echo $row['image']; ?>" width="50">
      </li>
    <?php endwhile; ?>
  </ul>
</body>
</html>