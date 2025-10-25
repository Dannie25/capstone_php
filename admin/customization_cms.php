<?php
session_start();
require_once '../db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Create shirt_parts table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS shirt_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_type ENUM('neck', 'sleeve', 'fit') NOT NULL,
    part_value VARCHAR(20) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_part (part_type, part_value)
)";
$conn->query($createTableSQL);

// Create shirt_part_labels table for dynamic options (key -> label)
$createLabelsSQL = "CREATE TABLE IF NOT EXISTS shirt_part_labels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_type ENUM('neck','sleeve','fit') NOT NULL,
    part_value VARCHAR(20) NOT NULL,
    label VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_label (part_type, part_value)
)";
$conn->query($createLabelsSQL);

$success = '';
$error = '';

// Define default options
$neckTypes = ['vneck' => 'V-Neck', 'round' => 'Round', 'turtle' => 'Turtle', 'polo' => 'Polo'];
$sleeveTypes = ['long' => 'Long', 'short' => 'Short', 'half' => 'Half', 'sleeveless' => 'Sleeveless'];
$fitTypes = ['bodyfit' => 'Body Fit', 'slimfit' => 'Slim Fit', 'loose' => 'Loose'];

// Merge dynamic options from DB labels
$labelsRes = $conn->query("SELECT part_type, part_value, label FROM shirt_part_labels ORDER BY part_type, label");
if ($labelsRes) {
    while ($lr = $labelsRes->fetch_assoc()) {
        if ($lr['part_type'] === 'neck') { $neckTypes[$lr['part_value']] = $lr['label']; }
        if ($lr['part_type'] === 'sleeve') { $sleeveTypes[$lr['part_value']] = $lr['label']; }
        if ($lr['part_type'] === 'fit') { $fitTypes[$lr['part_value']] = $lr['label']; }
    }
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_part'])) {
    $partType = $_POST['part_type'] ?? '';
    $partValue = $_POST['part_value'] ?? '';
    
    if ($partType && $partValue && isset($_FILES['part_image']) && $_FILES['part_image']['error'] === UPLOAD_ERR_OK) {
        // Create upload directory
        $uploadDir = '../img/shirt_parts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Name format: parttype-partvalue.extension (e.g., neck-round.png)
            $fileName = $partType . '-' . $partValue . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['part_image']['tmp_name'], $targetPath)) {
                $imagePath = 'img/shirt_parts/' . $fileName;
                
                // Insert or update database
                $stmt = $conn->prepare("INSERT INTO shirt_parts (part_type, part_value, image_path) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE image_path = ?, updated_at = CURRENT_TIMESTAMP");
                $stmt->bind_param("ssss", $partType, $partValue, $imagePath, $imagePath);
                
                if ($stmt->execute()) {
                    $success = "Image uploaded successfully!";
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $stmt->close();
            } else {
                $error = "Error moving uploaded file. Check folder permissions for img/shirt_parts/.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
        }
    } else {
        // Provide detailed error if upload failed
        if (!isset($_FILES['part_image'])) {
            $error = "No file received. Please choose an image.";
        } else {
            $code = $_FILES['part_image']['error'];
            $map = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
            ];
            if ($code !== UPLOAD_ERR_OK) {
                $error = isset($map[$code]) ? $map[$code] : ('Upload error code: ' . $code);
            } else {
                $error = "Please select a part and upload an image.";
            }
        }
    }
}

// Handle add new option (key/label)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_option'])) {
    $optType = $_POST['opt_type'] ?? '';
    $optKey = strtolower(trim($_POST['opt_key'] ?? ''));
    $optLabel = trim($_POST['opt_label'] ?? '');
    if (in_array($optType, ['neck','sleeve','fit']) && $optKey !== '' && $optLabel !== '') {
        // basic key sanitization: allow letters, numbers, dash, underscore
        if (!preg_match('/^[a-z0-9_-]{2,20}$/', $optKey)) {
            $error = 'Invalid key. Use 2-20 chars: a-z, 0-9, dash or underscore.';
        } else {
            $stmt = $conn->prepare("INSERT INTO shirt_part_labels (part_type, part_value, label) VALUES (?,?,?) ON DUPLICATE KEY UPDATE label = VALUES(label)");
            $stmt->bind_param("sss", $optType, $optKey, $optLabel);
            if ($stmt->execute()) {
                $success = 'Option saved successfully!';
            } else {
                $error = 'Error saving option: ' . $conn->error;
            }
            $stmt->close();
        }
    } else {
        $error = 'Please provide type, key, and label.';
    }
}

// Handle delete option (label mapping AND associated image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_option'])) {
    $optType = $_POST['opt_type'] ?? '';
    $optKey = $_POST['opt_key'] ?? '';
    if (in_array($optType, ['neck','sleeve','fit']) && $optKey !== '') {
        // First, delete the associated image if it exists
        $imgStmt = $conn->prepare("SELECT id, image_path FROM shirt_parts WHERE part_type = ? AND part_value = ?");
        $imgStmt->bind_param("ss", $optType, $optKey);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();
        
        if ($imgRow = $imgResult->fetch_assoc()) {
            $imagePath = '../' . $imgRow['image_path'];
            // Delete from database
            $delImgStmt = $conn->prepare("DELETE FROM shirt_parts WHERE id = ?");
            $delImgStmt->bind_param("i", $imgRow['id']);
            $delImgStmt->execute();
            $delImgStmt->close();
            
            // Delete physical file
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $imgStmt->close();
        
        // Then delete the label mapping
        $stmt = $conn->prepare("DELETE FROM shirt_part_labels WHERE part_type = ? AND part_value = ?");
        $stmt->bind_param("ss", $optType, $optKey);
        if ($stmt->execute()) {
            $success = 'Option and associated image removed successfully.';
        } else {
            $error = 'Error removing option: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_part'])) {
    $id = intval($_POST['part_id']);
    
    $stmt = $conn->prepare("SELECT image_path FROM shirt_parts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $imagePath = '../' . $row['image_path'];
        
        $deleteStmt = $conn->prepare("DELETE FROM shirt_parts WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $success = "Image deleted successfully!";
        } else {
            $error = "Error deleting image: " . $conn->error;
        }
        $deleteStmt->close();
    }
    $stmt->close();
}

// Get all uploaded parts
$uploadedParts = ['neck' => [], 'sleeve' => [], 'fit' => []];
$result = $conn->query("SELECT * FROM shirt_parts ORDER BY part_type, part_value");
while ($row = $result->fetch_assoc()) {
    $uploadedParts[$row['part_type']][$row['part_value']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customization CMS - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #c5e1a5 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255,255,255,0.8);
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: #718096;
            font-size: 14px;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #8bc34a 0%, #6aa84f 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(91,107,70,0.3);
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(91,107,70,0.4);
            background: linear-gradient(135deg, #6aa84f 0%, #8bc34a 100%);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 2px solid #9ae6b4;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 2px solid #fc8181;
        }
        
        .category-section {
            background: white;
            padding: 28px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        .category-section h2 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 3px solid #8bc34a;
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .parts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .part-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            padding: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.2s;
            position: relative;
        }
        
        .part-card:hover {
            border-color: #8bc34a;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 195, 74, 0.2);
        }
        
        .delete-card-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(229, 62, 62, 0.95);
            color: white;
            border: none;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 20;
        }
        
        .delete-card-btn:hover {
            background: #c53030;
            transform: scale(1.15);
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.4);
        }
        
        .delete-card-btn i {
            font-size: 14px;
        }
        
        .part-card h3 {
            color: #2d3748;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
            border: 2px solid #e9ecef;
            position: relative;
        }
        
        .delete-icon-overlay {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(229, 62, 62, 0.95);
            color: white;
            border: none;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }
        
        .delete-icon-overlay:hover {
            background: #c53030;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.4);
        }
        
        .delete-icon-overlay i {
            font-size: 16px;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .image-preview.empty {
            border: 2px dashed #cbd5e0;
            color: #a0aec0;
            font-size: 14px;
            font-weight: 600;
        }
        
        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .file-input-wrapper {
            position: relative;
        }
        
        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
        }
        
        .upload-btn {
            padding: 12px 16px;
            background: linear-gradient(135deg, #8bc34a 0%, #6aa84f 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(139,195,74,0.3);
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139,195,74,0.4);
            background: linear-gradient(135deg, #6aa84f 0%, #8bc34a 100%);
        }
        
        .delete-btn {
            padding: 10px 16px;
            background: #e53e3e;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(229,62,62,0.25);
        }
        
        .delete-btn:hover {
            background: #c53030;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(229,62,62,0.35);
        }
        
        .delete-btn i {
            margin-right: 5px;
        }
        
        .remove-option-btn {
            padding: 8px 14px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            margin-top: 8px;
            box-shadow: 0 2px 6px rgba(245,158,11,0.25);
        }
        
        .remove-option-btn:hover {
            background: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(245,158,11,0.35);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 20px;
            background: white;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255,255,255,0.8);
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: 800;
            color: #6aa84f;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 13px;
            color: #718096;
            font-weight: 500;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .badge-neck { background: #dcedc8; color: #2d3e2d; }
        .badge-sleeve { background: #c8e6c9; color: #2d3e2d; }
        .badge-fit { background: #f0f4c3; color: #3a3e2d; }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-palette"></i> Customization CMS - Shirt Parts</h1>
        </div>
        
        <div class="content-body">
            <div class="container-fluid">
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['debug'])): ?>
            <?php
            $debug = [];
            $debug['upload_max_filesize'] = ini_get('upload_max_filesize');
            $debug['post_max_size'] = ini_get('post_max_size');
            $debug['file_uploads'] = ini_get('file_uploads');
            $dirPath = realpath(__DIR__ . '/../img/shirt_parts');
            $debug['upload_dir_realpath'] = $dirPath ?: '(not found)';
            $debug['upload_dir_exists'] = is_dir(__DIR__ . '/../img/shirt_parts') ? 'yes' : 'no';
            $debug['upload_dir_writable'] = is_writable(__DIR__ . '/../img/shirt_parts') ? 'yes' : 'no';
            $filesList = @glob(__DIR__ . '/../img/shirt_parts/*');
            $debug['files_count'] = is_array($filesList) ? count($filesList) : 0;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['part_image'])) {
                $debug['last_upload_error_code'] = $_FILES['part_image']['error'];
                $debug['last_upload_tmp_name'] = $_FILES['part_image']['tmp_name'];
                $debug['last_upload_type'] = $_FILES['part_image']['type'] ?? '';
                $debug['last_upload_size'] = $_FILES['part_image']['size'] ?? 0;
                $debug['last_upload_name'] = $_FILES['part_image']['name'] ?? '';
            }
            ?>
            <div class="category-section" style="border: 2px dashed #e53e3e;">
                <h2>🛠 Debug Info</h2>
                <pre style="white-space: pre-wrap; font-size: 12px; background: #fff; padding: 10px; border-radius: 8px; border:1px solid #eee;">
<?php echo htmlspecialchars(print_r($debug, true)); ?>

Files in img/shirt_parts/:
<?php 
if (is_array($filesList)) {
    foreach ($filesList as $f) { echo htmlspecialchars(basename($f)) . "\n"; }
}
?>
                </pre>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo count($uploadedParts['neck']); ?>/<?php echo count($neckTypes); ?></div>
                <div class="label">Neck Styles</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo count($uploadedParts['sleeve']); ?>/<?php echo count($sleeveTypes); ?></div>
                <div class="label">Sleeve Types</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo count($uploadedParts['fit']); ?>/<?php echo count($fitTypes); ?></div>
                <div class="label">Fit Styles</div>
            </div>
        </div>
        
        <!-- Neck Styles Section -->
        <div class="category-section">
            <h2>👔 Neck Styles</h2>
            <form method="POST" class="upload-form" style="margin-bottom:15px;">
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input type="hidden" name="opt_type" value="neck">
                    <input type="text" name="opt_key" placeholder="key (e.g., round)" required style="flex:1; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    <input type="text" name="opt_label" placeholder="Label (e.g., Round Neck)" required style="flex:2; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    <button type="submit" name="add_option" class="upload-btn">Add Option</button>
                </div>
            </form>
            <div class="parts-grid">
                <?php foreach ($neckTypes as $key => $label): ?>
                    <?php $uploaded = isset($uploadedParts['neck'][$key]) ? $uploadedParts['neck'][$key] : null; ?>
                    <div class="part-card">
                        <?php if (!in_array($key, ['vneck','round','turtle','polo'])): ?>
                            <form method="POST" onsubmit="return confirm('Delete this entire card including the image? This action cannot be undone.');" style="display: inline;">
                                <input type="hidden" name="opt_type" value="neck">
                                <input type="hidden" name="opt_key" value="<?php echo $key; ?>">
                                <button type="submit" name="delete_option" class="delete-card-btn" title="Delete Card">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <h3><?php echo $label; ?></h3>
                        <span class="badge badge-neck"><?php echo $key; ?></span>
                        
                        <div class="image-preview <?php echo !$uploaded ? 'empty' : ''; ?>">
                            <?php if ($uploaded): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this image? This action cannot be undone.');" style="display: inline;">
                                    <input type="hidden" name="part_id" value="<?php echo $uploaded['id']; ?>">
                                    <button type="submit" name="delete_part" class="delete-icon-overlay" title="Delete Image">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                                <img src="../<?php echo htmlspecialchars($uploaded['image_path']); ?>?v=<?php echo isset($uploaded['updated_at']) ? strtotime($uploaded['updated_at']) : time(); ?>" alt="<?php echo $label; ?>">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <input type="hidden" name="part_type" value="neck">
                            <input type="hidden" name="part_value" value="<?php echo $key; ?>">
                            <div class="file-input-wrapper">
                                <input type="file" name="part_image" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_part" class="upload-btn">
                                <?php echo $uploaded ? 'Replace Image' : 'Upload Image'; ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Sleeve Types Section -->
        <div class="category-section">
            <h2>👕 Sleeve Length</h2>
            <form method="POST" class="upload-form" style="margin-bottom:15px;">
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input type="hidden" name="opt_type" value="sleeve">
                    <input type="text" name="opt_key" placeholder="key (e.g., long)" required style="flex:1; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    <input type="text" name="opt_label" placeholder="Label (e.g., Long Sleeve)" required style="flex:2; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    <button type="submit" name="add_option" class="upload-btn">Add Option</button>
                </div>
            </form>
            <div class="parts-grid">
                <?php foreach ($sleeveTypes as $key => $label): ?>
                    <?php $uploaded = isset($uploadedParts['sleeve'][$key]) ? $uploadedParts['sleeve'][$key] : null; ?>
                    <div class="part-card">
                        <?php if (!in_array($key, ['long','short','half','sleeveless'])): ?>
                            <form method="POST" onsubmit="return confirm('Delete this entire card including the image? This action cannot be undone.');" style="display: inline;">
                                <input type="hidden" name="opt_type" value="sleeve">
                                <input type="hidden" name="opt_key" value="<?php echo $key; ?>">
                                <button type="submit" name="delete_option" class="delete-card-btn" title="Delete Card">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <h3><?php echo $label; ?></h3>
                        <span class="badge badge-sleeve"><?php echo $key; ?></span>
                        
                        <div class="image-preview <?php echo !$uploaded ? 'empty' : ''; ?>">
                            <?php if ($uploaded): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this image? This action cannot be undone.');" style="display: inline;">
                                    <input type="hidden" name="part_id" value="<?php echo $uploaded['id']; ?>">
                                    <button type="submit" name="delete_part" class="delete-icon-overlay" title="Delete Image">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                                <img src="../<?php echo htmlspecialchars($uploaded['image_path']); ?>?v=<?php echo isset($uploaded['updated_at']) ? strtotime($uploaded['updated_at']) : time(); ?>" alt="<?php echo $label; ?>">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <input type="hidden" name="part_type" value="sleeve">
                            <input type="hidden" name="part_value" value="<?php echo $key; ?>">
                            <div class="file-input-wrapper">
                                <input type="file" name="part_image" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_part" class="upload-btn">
                                <?php echo $uploaded ? 'Replace Image' : 'Upload Image'; ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Fit Styles Section -->
        <div class="category-section">
            <h2>📏 Fit Styles</h2>
            <form method="POST" class="upload-form" style="margin-bottom:15px;">
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input type="hidden" name="opt_type" value="fit">
                    <input type="text" name="opt_key" placeholder="key (e.g., bodyfit)" required style="flex:1; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    <input type="text" name="opt_label" placeholder="Label (e.g., Body Fit)" required style="flex:2; padding:10px; border:2px solid #e2e8f0; border-radius:8px;">
                    <button type="submit" name="add_option" class="upload-btn">Add Option</button>
                </div>
            </form>
            <div class="parts-grid">
                <?php foreach ($fitTypes as $key => $label): ?>
                    <?php $uploaded = isset($uploadedParts['fit'][$key]) ? $uploadedParts['fit'][$key] : null; ?>
                    <div class="part-card">
                        <?php if (!in_array($key, ['bodyfit','slimfit','loose'])): ?>
                            <form method="POST" onsubmit="return confirm('Delete this entire card including the image? This action cannot be undone.');" style="display: inline;">
                                <input type="hidden" name="opt_type" value="fit">
                                <input type="hidden" name="opt_key" value="<?php echo $key; ?>">
                                <button type="submit" name="delete_option" class="delete-card-btn" title="Delete Card">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <h3><?php echo $label; ?></h3>
                        <span class="badge badge-fit"><?php echo $key; ?></span>
                        
                        <div class="image-preview <?php echo !$uploaded ? 'empty' : ''; ?>">
                            <?php if ($uploaded): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this image? This action cannot be undone.');" style="display: inline;">
                                    <input type="hidden" name="part_id" value="<?php echo $uploaded['id']; ?>">
                                    <button type="submit" name="delete_part" class="delete-icon-overlay" title="Delete Image">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                                <img src="../<?php echo htmlspecialchars($uploaded['image_path']); ?>?v=<?php echo isset($uploaded['updated_at']) ? strtotime($uploaded['updated_at']) : time(); ?>" alt="<?php echo $label; ?>">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <input type="hidden" name="part_type" value="fit">
                            <input type="hidden" name="part_value" value="<?php echo $key; ?>">
                            <div class="file-input-wrapper">
                                <input type="file" name="part_image" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_part" class="upload-btn">
                                <?php echo $uploaded ? 'Replace Image' : 'Upload Image'; ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX Upload Handler
document.addEventListener('DOMContentLoaded', function() {
    // Get all upload forms
    const uploadForms = document.querySelectorAll('form[enctype="multipart/form-data"]');
    
    uploadForms.forEach(form => {
        // Only handle forms with upload_part button
        if (!form.querySelector('button[name="upload_part"]')) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[name="upload_part"]');
            const originalBtnText = submitBtn.innerHTML;
            const partType = formData.get('part_type');
            const partValue = formData.get('part_value');
            const fileInput = this.querySelector('input[type="file"]');
            
            // Validate file is selected
            if (!fileInput.files || fileInput.files.length === 0) {
                showNotification('Please select an image file', 'error');
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
            
            // Send AJAX request
            fetch('ajax_upload_part.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification(data.message, 'success');
                    
                    // Update the image preview
                    const imagePreview = form.closest('.part-card').querySelector('.image-preview');
                    if (imagePreview) {
                        imagePreview.classList.remove('empty');
                        imagePreview.innerHTML = `<img src="../${data.data.image_path}?v=${data.data.timestamp}" alt="${partValue}">`;
                    }
                    
                    // Reset file input
                    fileInput.value = '';
                    
                    // Update button text to "Replace Image" if it was "Upload Image"
                    if (originalBtnText.includes('Upload Image')) {
                        submitBtn.innerHTML = 'Replace Image';
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showNotification('An error occurred during upload. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable button
                submitBtn.disabled = false;
                if (submitBtn.innerHTML.includes('Uploading')) {
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        });
    });
});

// Notification function
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.ajax-notification');
    existingNotifications.forEach(n => n.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `ajax-notification alert alert-${type === 'success' ? 'success' : 'error'}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    `;
    notification.innerHTML = `
        <strong>${type === 'success' ? '✓' : '✗'}</strong> ${message}
        <button onclick="this.parentElement.remove()" style="float: right; background: none; border: none; font-size: 20px; cursor: pointer; color: inherit; padding: 0; margin-left: 10px;">&times;</button>
    `;
    
    // Add CSS animation
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}
</script>

</body>
</html>
