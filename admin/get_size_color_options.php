<?php
// AJAX endpoint for CMS sizes/colors
require_once '../db.php';
header('Content-Type: application/json');

$sizes = [];
$res = $conn->query("SELECT size FROM sizes ORDER BY size");
while ($row = $res->fetch_assoc()) $sizes[] = $row['size'];

$colors = [];
$res = $conn->query("SELECT color FROM colors ORDER BY color");
while ($row = $res->fetch_assoc()) $colors[] = $row['color'];

echo json_encode([
  'sizes' => $sizes,
  'colors' => $colors
]);
