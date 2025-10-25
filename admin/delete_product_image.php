<?php
// admin/delete_product_image.php
session_start();
include '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}

$id = isset($_POST['imgid']) ? intval($_POST['imgid']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image ID']);
    exit();
}

$res = $conn->query("SELECT image FROM product_images WHERE id = $id");
if ($row = $res->fetch_assoc()) {
    $img = $row['image'];
    $conn->query("DELETE FROM product_images WHERE id = $id");
    // Optionally delete the file from disk
    $imgPath = '../img/' . $img;
    if (file_exists($imgPath)) @unlink($imgPath);
    $altPath = '../' . $img;
    if (file_exists($altPath)) @unlink($altPath);
    echo json_encode(['success' => true]);
    exit();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Image not found']);
    exit();
}
