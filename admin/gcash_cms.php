<?php
// gcash_cms.php - Manage GCash QR Code and Customer-Facing Content
session_start();
require_once '../db.php';

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Initialize default content if not exists
$default_content = [
    'gcash_page_title' => 'GCash Payment',
    'gcash_qr_heading' => 'Scan QR Code',
    'gcash_qr_description' => 'Use your GCash app to scan this QR code',
    'gcash_instructions_title' => 'Payment Instructions',
    'gcash_instruction_step1' => 'Open your GCash app and scan the QR code above',
    'gcash_instruction_step2' => 'Complete the payment of the total amount shown',
    'gcash_instruction_step3' => 'Copy the Reference Number from your GCash receipt',
    'gcash_instruction_step4' => 'Enter the reference number below to complete your order',
    'gcash_reference_label' => 'GCash Reference Number',
    'gcash_reference_placeholder' => 'Enter your 13-digit reference number',
    'gcash_reference_help' => 'The reference number can be found in your GCash transaction receipt',
    'gcash_button_text' => 'Confirm Payment',
    'gcash_amount_label' => 'Total Amount to Pay'
];

foreach ($default_content as $key => $value) {
    $check = $conn->prepare("SELECT id FROM cms_settings WHERE setting_key = ?");
    $check->bind_param('s', $key);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO cms_settings (setting_key, setting_value) VALUES (?, ?)");
        $insert->bind_param('ss', $key, $value);
        $insert->execute();
    }
}

// Handle content update
if (isset($_POST['update_content'])) {
    $conn->begin_transaction();
    try {
        foreach ($default_content as $key => $default_value) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                $stmt = $conn->prepare("UPDATE cms_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->bind_param('ss', $value, $key);
                $stmt->execute();
            }
        }
        $conn->commit();
        $success = 'Content updated successfully!';
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Failed to update content: ' . $e->getMessage();
    }
}

// Handle upload/update QR
if (isset($_POST['upload_qr'])) {
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (in_array($ext, $allowed)) {
            $uploadDir = '../img/';
            $filename = 'gcash_qr_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $targetPath)) {
                // Remove old QR if exists
                $result = $conn->query("SELECT image_path FROM gcash_qr ORDER BY id DESC LIMIT 1");
                if ($row = $result->fetch_assoc()) {
                    $oldPath = $uploadDir . basename($row['image_path']);
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $conn->query("DELETE FROM gcash_qr");
                $stmt = $conn->prepare("INSERT INTO gcash_qr (image_path) VALUES (?)");
                $stmt->bind_param('s', $filename);
                $stmt->execute();
                $stmt->close();
                $success = 'GCash QR code uploaded!';
            } else {
                $error = 'Failed to upload image.';
            }
        } else {
            $error = 'Invalid file type.';
        }
    } else {
        $error = 'No file selected or upload error.';
    }
}

// Handle delete QR
if (isset($_POST['delete_qr'])) {
    $result = $conn->query("SELECT image_path FROM gcash_qr ORDER BY id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $file = '../img/' . basename($row['image_path']);
        if (file_exists($file)) unlink($file);
    }
    $conn->query("DELETE FROM gcash_qr");
    $success = 'GCash QR code deleted.';
}

// Get current QR
$currentQR = null;
$result = $conn->query("SELECT * FROM gcash_qr ORDER BY id DESC LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $currentQR = $row['image_path'];
}

// Get current content
$content = [];
foreach ($default_content as $key => $default_value) {
    $stmt = $conn->prepare("SELECT setting_value FROM cms_settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $content[$key] = $row['setting_value'];
    } else {
        $content[$key] = $default_value;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash CMS - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --border-color: #dee2e6;
        }
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c8d99a 100%);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-title {
            color: var(--primary-color);
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }
        .back-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--primary-color);
            transition: all 0.2s;
        }
        .back-btn:hover {
            background-color: #4a5a36;
            color: white;
            transform: translateY(-1px);
            text-decoration: none;
        }
        .content-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
            font-weight: 600;
        }
        .card-body {
            padding: 24px;
        }
        .qr-img {
            max-width: 320px; 
            max-height: 320px; 
            border-radius: 12px; 
            border: 1px solid #dee2e6; 
            background: #fff;
        }
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        .form-control, .form-control:focus {
            border-radius: 6px;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(91,107,70,0.15);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #4a5a36;
            border-color: #4a5a36;
        }
        .section-divider {
            border-top: 2px solid var(--border-color);
            margin: 24px 0;
            padding-top: 24px;
        }
        .help-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 4px;
        }
        .preview-badge {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-qr-code"></i> GCash Payment Management</h1>
        </div>
        
        <div class="content-body">
            <div class="container-fluid">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- QR Code Management -->
        <div class="content-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-qrcode me-2"></i>GCash QR Code</span>
                <?php if ($currentQR): ?>
                    <a href="../img/<?= htmlspecialchars($currentQR) ?>" target="_blank" class="btn btn-light btn-sm">
                        <i class="fas fa-eye me-1"></i>View Full Size
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 text-center mb-3 mb-md-0">
                        <?php if ($currentQR): ?>
                            <img src="../img/<?= htmlspecialchars($currentQR) ?>" alt="GCash QR" class="qr-img d-block mx-auto mb-3">
                            <form method="POST" onsubmit="return confirm('Delete current QR code?');" class="d-inline">
                                <button type="submit" name="delete_qr" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i>Delete QR Code
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No QR code uploaded yet.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3"><?= $currentQR ? 'Update' : 'Upload' ?> QR Code</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select QR Code Image</label>
                                <input type="file" name="qr_image" accept="image/*" class="form-control" required>
                                <div class="help-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Accepted formats: JPG, PNG, GIF, WEBP, BMP
                                </div>
                            </div>
                            <button type="submit" name="upload_qr" class="btn btn-primary w-100">
                                <i class="fas fa-upload me-2"></i><?= $currentQR ? 'Update' : 'Upload' ?> QR Code
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Management -->
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-edit me-2"></i>Edit Customer-Facing Content
                <span class="preview-badge ms-2">Customer View</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <!-- Page Title -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-heading me-2"></i>Page Title
                        </label>
                        <input type="text" name="gcash_page_title" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_page_title']) ?>" required>
                        <div class="help-text">The main title shown at the top of the payment page</div>
                    </div>

                    <!-- Amount Label -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave me-2"></i>Amount Display Label
                        </label>
                        <input type="text" name="gcash_amount_label" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_amount_label']) ?>" required>
                        <div class="help-text">Label shown above the total amount</div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- QR Section -->
                    <h5 class="mb-3"><i class="fas fa-qrcode me-2"></i>QR Code Section</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">QR Section Heading</label>
                        <input type="text" name="gcash_qr_heading" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_qr_heading']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">QR Section Description</label>
                        <input type="text" name="gcash_qr_description" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_qr_description']) ?>" required>
                        <div class="help-text">Short description below the QR heading</div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Instructions Section -->
                    <h5 class="mb-3"><i class="fas fa-list-ol me-2"></i>Payment Instructions</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Instructions Title</label>
                        <input type="text" name="gcash_instructions_title" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_instructions_title']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Step 1</label>
                        <input type="text" name="gcash_instruction_step1" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_instruction_step1']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Step 2</label>
                        <input type="text" name="gcash_instruction_step2" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_instruction_step2']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Step 3</label>
                        <input type="text" name="gcash_instruction_step3" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_instruction_step3']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Step 4</label>
                        <input type="text" name="gcash_instruction_step4" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_instruction_step4']) ?>" required>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Reference Number Section -->
                    <h5 class="mb-3"><i class="fas fa-hashtag me-2"></i>Reference Number Form</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Reference Number Label</label>
                        <input type="text" name="gcash_reference_label" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_reference_label']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference Number Placeholder</label>
                        <input type="text" name="gcash_reference_placeholder" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_reference_placeholder']) ?>" required>
                        <div class="help-text">Placeholder text shown inside the input field</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference Number Help Text</label>
                        <input type="text" name="gcash_reference_help" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_reference_help']) ?>" required>
                        <div class="help-text">Help text shown below the input field</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Submit Button Text</label>
                        <input type="text" name="gcash_button_text" class="form-control" 
                               value="<?= htmlspecialchars($content['gcash_button_text']) ?>" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="update_content" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Save All Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Link -->
        <div class="text-center mb-4">
            <a href="../gcash.php" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-eye me-2"></i>Preview Customer Page
            </a>
            <div class="help-text mt-2">
                <i class="fas fa-info-circle me-1"></i>
                Note: Preview requires an active checkout session
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            </div>
        </div>
    </div>
</div>
</body>
</html>