<?php
session_start();
require_once '../db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
// Handle add, edit, delete
$success = '';
$error = '';
if (isset($_POST['add_size'])) {
    $name = trim($_POST['size_name'] ?? '');
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT IGNORE INTO sizes (size) VALUES (?)");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
        $success = 'Size added!';
    }
}
if (isset($_POST['delete_size'])) {
    $id = intval($_POST['size_id']);
    $conn->query("DELETE FROM sizes WHERE id=$id");
    $success = 'Size deleted!';
}
if (isset($_POST['add_color'])) {
    $name = trim($_POST['color_name'] ?? '');
    if ($name !== '') {
        $stmt = $conn->prepare("INSERT IGNORE INTO colors (color) VALUES (?)");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
        $success = 'Color added!';
    }
}
if (isset($_POST['delete_color'])) {
    $id = intval($_POST['color_id']);
    $conn->query("DELETE FROM colors WHERE id=$id");
    $success = 'Color deleted!';
}
$sizes = $conn->query("SELECT * FROM sizes ORDER BY size");
$colors = $conn->query("SELECT * FROM colors ORDER BY color");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Sizes & Colors</title>
    <style>
        body { font-family: Arial,sans-serif; background: #f8f8f8; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 30px; }
        h2 { margin-top: 0; color: #5b6b46; }
        form { margin-bottom: 20px; }
        input[type=text] { padding: 7px; border-radius: 4px; border: 1px solid #bbb; }
        button { padding: 6px 15px; border-radius: 4px; border: none; background: #5b6b46; color: #fff; cursor: pointer; }
        button.delete { background: #d9534f; }
        ul { list-style: none; padding: 0; }
        li { margin-bottom: 8px; }
        .row { display: flex; gap: 30px; }
        .list-box { flex: 1; background: #fafafa; border-radius: 5px; padding: 15px; }
        .msg { color: #388e3c; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Manage Sizes & Colors</h2>
    <?php if ($success) echo '<div class="msg">' . htmlspecialchars($success) . '</div>'; ?>
    <div class="row">
        <div class="list-box">
            <form method="POST">
                <input type="text" name="size_name" placeholder="Add new size" required>
                <button type="submit" name="add_size">Add</button>
            </form>
            <ul>
                <?php while($sz = $sizes->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($sz['size']); ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="size_id" value="<?php echo $sz['id']; ?>">
                            <button type="submit" name="delete_size" class="delete">Delete</button>
                        </form>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <div class="list-box">
            <form method="POST">
                <input type="text" name="color_name" placeholder="Add new color" required>
                <button type="submit" name="add_color">Add</button>
            </form>
            <ul>
                <?php while($clr = $colors->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($clr['color']); ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="color_id" value="<?php echo $clr['id']; ?>">
                            <button type="submit" name="delete_color" class="delete">Delete</button>
                        </form>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
