 <?php
// Start session
session_start();

// Include database connection
require_once 'db.php';

// Check if user is logged in (supports AJAX JSON response)
$isAjaxAuth = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['ajax_submit']) && $_POST['ajax_submit'] == '1')
);
if (!isset($_SESSION['user_id'])) {
    if ($isAjaxAuth) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'unauthenticated' => true, 'login' => 'login.php?redirect=customization.php']);
        exit();
    }
    header("Location: login.php?redirect=customization.php");
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $isAjax = isset($_POST['ajax_submit']) && $_POST['ajax_submit'] == '1';
    $userId = $_SESSION['user_id'];
    
    // Basic Garment Details
    $productType = $_POST['product_type'] ?? '';
    $garmentStyle = $_POST['garment_style'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Measurements
    $measurements = [
        'chest' => !empty($_POST['chest_width']) ? (float)$_POST['chest_width'] : null,
        'waist' => !empty($_POST['waist_width']) ? (float)$_POST['waist_width'] : null,
        'hip' => !empty($_POST['hip_width']) ? (float)$_POST['hip_width'] : null,
        'shoulder' => !empty($_POST['shoulder_width']) ? (float)$_POST['shoulder_width'] : null,
        'sleeve' => !empty($_POST['sleeve_length']) ? (float)$_POST['sleeve_length'] : null,
        'length' => !empty($_POST['garment_length']) ? (float)$_POST['garment_length'] : null
    ];
    
    // Design Elements
    $design = [
        'neckline' => $_POST['neckline_type'] ?? null,
        'sleeve' => $_POST['sleeve_type'] ?? null,
        'fit' => $_POST['fit_type'] ?? null,
        'fabric' => $_POST['fabric_type'] ?? null,
        'color1' => $_POST['color_preference_1'] ?? null,
        'color2' => $_POST['color_preference_2'] ?? null,
        'pattern' => $_POST['pattern_type'] ?? null
    ];
    
    // Additional Information
    $specialInstructions = $_POST['special_instructions'] ?? '';
    
    // Budget and Timeline
    $budgetMin = !empty($_POST['budget_min']) ? (float)$_POST['budget_min'] : null;
    $budgetMax = !empty($_POST['budget_max']) ? (float)$_POST['budget_max'] : null;
    $quantity = !empty($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    
    // Purpose and Occasion
    $garmentPurpose = $_POST['garment_purpose'] ?? null;
    $occasion = $_POST['occasion'] ?? null;
    
    // Fabric Preferences
    $fabricWeight = $_POST['fabric_weight'] ?? null;
    
    // Additional Measurements
    $neckCircumference = !empty($_POST['neck_circumference']) ? (float)$_POST['neck_circumference'] : null;
    $armCircumference = !empty($_POST['arm_circumference']) ? (float)$_POST['arm_circumference'] : null;
    $wristCircumference = !empty($_POST['wrist_circumference']) ? (float)$_POST['wrist_circumference'] : null;
    $inseamLength = !empty($_POST['inseam_length']) ? (float)$_POST['inseam_length'] : null;
    
    // Handle canvas preview upload (priority - this is the main design preview)
    $imagePath = '';
    if (isset($_FILES['canvas_preview']) && $_FILES['canvas_preview']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/custom_requests/' . $userId . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = 'canvas_' . uniqid() . '.png';
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['canvas_preview']['tmp_name'], $targetPath)) {
            $imagePath = $targetPath;
        }
    }
    
    // Handle reference image upload (fallback if no canvas)
    if (empty($imagePath) && isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/custom_requests/' . $userId . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['reference_image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['reference_image']['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['reference_image']['tmp_name'], $targetPath)) {
                $imagePath = $targetPath;
            } else {
                $message = 'Error uploading file. Please try again.';
                $messageType = 'error';
                if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$message]); exit(); }
            }
        } else {
            $message = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            $messageType = 'error';
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$message]); exit(); }
        }
    }
    
    // Handle additional image uploads
    $imagePath2 = '';
    $imagePath3 = '';
    
    if (isset($_FILES['reference_image_2']) && $_FILES['reference_image_2']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/custom_requests/' . $userId . '/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName2 = uniqid() . '_' . basename($_FILES['reference_image_2']['name']);
        if (move_uploaded_file($_FILES['reference_image_2']['tmp_name'], $uploadDir . $fileName2)) {
            $imagePath2 = $uploadDir . $fileName2;
        }
    }
    
    if (isset($_FILES['reference_image_3']) && $_FILES['reference_image_3']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/custom_requests/' . $userId . '/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName3 = uniqid() . '_' . basename($_FILES['reference_image_3']['name']);
        if (move_uploaded_file($_FILES['reference_image_3']['tmp_name'], $uploadDir . $fileName3)) {
            $imagePath3 = $uploadDir . $fileName3;
        }
    }
    
    // If no errors, save to database
    if (empty($message)) {
        // Check if enhanced columns exist
        $checkCol = $conn->query("SHOW COLUMNS FROM customization_requests LIKE 'fabric_type'");
        $hasEnhancedColumns = ($checkCol && $checkCol->num_rows > 0);
        
        if ($hasEnhancedColumns) {
            // Use enhanced INSERT with all new columns
            $stmt = $conn->prepare("INSERT INTO customization_requests (
                user_id, product_type, garment_style, garment_purpose, occasion, description,
                chest_width, waist_width, hip_width, neck_circumference, inseam_length, 
                arm_circumference, wrist_circumference, shoulder_width, sleeve_length, garment_length,
                neckline_type, sleeve_type, fit_type, fabric_type, fabric_weight,
                color_preference_1, color_preference_2, pattern_type,
                budget_min, budget_max, quantity, deadline,
                special_instructions, reference_image_path, reference_image_2, reference_image_3, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')");
            
            $stmt->bind_param(
                "isssssddddddddddssssssssdddsssss",
                $userId, $productType, $garmentStyle, $garmentPurpose, $occasion, $description,
                $measurements['chest'], $measurements['waist'], $measurements['hip'], 
                $neckCircumference, $inseamLength, $armCircumference, $wristCircumference,
                $measurements['shoulder'], $measurements['sleeve'], $measurements['length'],
                $design['neckline'], $design['sleeve'], $design['fit'], $design['fabric'], $fabricWeight,
                $design['color1'], $design['color2'], $design['pattern'],
                $budgetMin, $budgetMax, $quantity, $deadline,
                $specialInstructions, $imagePath, $imagePath2, $imagePath3
            );
        } else {
            // Fallback: Use basic INSERT with only existing columns
            $stmt = $conn->prepare("INSERT INTO customization_requests (
                user_id, product_type, garment_style, description,
                chest_width, waist_width, hip_width, shoulder_width, sleeve_length, garment_length,
                neckline_type, sleeve_type, fit_type,
                special_instructions, reference_image_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')");
            
            $stmt->bind_param(
                "isssddddddsssss",
                $userId, $productType, $garmentStyle, $description,
                $measurements['chest'], $measurements['waist'], $measurements['hip'], 
                $measurements['shoulder'], $measurements['sleeve'], $measurements['length'],
                $design['neckline'], $design['sleeve'], $design['fit'],
                $specialInstructions, $imagePath
            );
        }
        
        // $isAjax defined above
        if ($stmt->execute()) {
            $requestId = $conn->insert_id;
            // Create a user notification for customization request submission
            if (isset($_SESSION['user_id'])) {
                $uid = $_SESSION['user_id'];
                $notif_msg = "Your customization request #" . $requestId . " has been submitted and is pending approval.";
                $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'customization', ?)");
                if ($notifStmt) {
                    $notifStmt->bind_param('is', $uid, $notif_msg);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            }
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true, 'request_id' => $requestId]);
                exit();
            } else {
                // Redirect to My Orders with a success indicator
                if (!headers_sent()) {
                    header("Location: my_orders.php?submitted=1&request_id=" . urlencode($requestId) . "#customization");
                    exit();
                } else {
                    echo '<script>alert("Request submitted successfully. Redirecting to My Orders..."); window.location.href = "my_orders.php?submitted=1&request_id=' . htmlspecialchars($requestId) . '#customization";</script>';
                    exit();
                }
            }
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'error' => $conn->error]);
                exit();
            } else {
                $message = "Error submitting request: " . $conn->error;
                $messageType = 'error';
            }
        }
        
        $stmt->close();
        // If this was an AJAX call and we reached here without exiting, return a JSON error
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>$message ?: 'Unable to submit request.']); exit(); }
    }
}

// Set page title
$pageTitle = "Custom Garment Design | MTC Clothing";

// Dynamic option labels: defaults + admin-added labels
$neckTypes = ['vneck' => 'V-Neck', 'round' => 'Round', 'turtle' => 'Turtle', 'polo' => 'Polo'];
$sleeveTypes = ['long' => 'Long', 'short' => 'Short', 'half' => 'Half', 'sleeveless' => 'Sleeveless'];
$fitTypes = ['bodyfit' => 'Body Fit', 'slimfit' => 'Slim Fit', 'loose' => 'Loose'];

$labelsRes = $conn->query("SELECT part_type, part_value, label FROM shirt_part_labels ORDER BY part_type, label");
if ($labelsRes) {
    while ($lr = $labelsRes->fetch_assoc()) {
        if ($lr['part_type'] === 'neck') { $neckTypes[$lr['part_value']] = $lr['label']; }
        if ($lr['part_type'] === 'sleeve') { $sleeveTypes[$lr['part_value']] = $lr['label']; }
        if ($lr['part_type'] === 'fit') { $fitTypes[$lr['part_value']] = $lr['label']; }
    }
}

// Determine defaults preferring common ones if available
$defaultNeck = isset($neckTypes['round']) ? 'round' : (function($arr){ foreach ($arr as $k => $v) { return $k; } return 'round'; })($neckTypes);
$defaultSleeve = isset($sleeveTypes['short']) ? 'short' : (function($arr){ foreach ($arr as $k => $v) { return $k; } return 'short'; })($sleeveTypes);
$defaultFit = isset($fitTypes['bodyfit']) ? 'bodyfit' : (function($arr){ foreach ($arr as $k => $v) { return $k; } return 'bodyfit'; })($fitTypes);

// Include header
include 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>" 
         style="padding: 15px; margin: 20px auto; max-width: 1400px; border-radius: 8px; 
                background-color: <?php echo $messageType === 'success' ? '#dff0d8' : '#f2dede'; ?>; 
                color: <?php echo $messageType === 'success' ? '#3c763d' : '#a94442'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<style>
    body {
        background: linear-gradient(135deg, #e8f5e9 0%, #c5e1a5 100%);
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .customization-container {
        max-width: 1600px;
        margin: 20px auto;
        padding: 20px;
        display: grid;
        grid-template-columns: 280px 1fr 380px;
        gap: 20px;
        align-items: start;
    }
    
    .custom-card {
        background: white;
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .custom-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }
    
    .page-title {
        text-align: center;
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0 0 30px 0;
        grid-column: 1 / -1;
        letter-spacing: -0.5px;
    }
    
    /* Left Panel - Color & Size */
    .left-panel {
        max-width: 100%;
        overflow: hidden;
    }
    
    .left-panel h3 {
        font-size: 20px;
        font-weight: 700;
        color: #2d3748;
        margin: 0 0 20px 0;
        padding-bottom: 12px;
        border-bottom: 3px solid #667eea;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .color-palette {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 24px;
    }
    
    .color-circle {
        width: 36px;
        pointer-events: auto !important;
        z-index: 9999;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        border: 3px solid transparent;
        transition: all 0.2s;
    }
    
    .color-circle:hover {
        transform: scale(1.1);
        border-color: #333;
    }
    
    .color-circle.active {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px white, 0 0 0 5px #667eea, 0 4px 12px rgba(102, 126, 234, 0.4) !important;
        transform: scale(1.15);
    }
    
    .size-section h4 {
        font-size: 16px;
        font-weight: 600;
        color: #2d3e2d;
        margin: 0 0 12px 0;
    }
    
    .size-type-btns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 16px;
    }
    
    .size-type-btn {
        padding: 10px;
        border: 2px solid #ddd;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .size-type-btn.active {
        background: #c5e1a5;
        border-color: #8bc34a;
        color: #2d3e2d;
    }
    
    .size-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    
    .size-btn {
        padding: 12px 8px;
        pointer-events: auto !important;
        z-index: 9999;
        border: 2px solid #ddd;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .size-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        color: white;
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    /* Center Panel - Preview */
    .center-panel {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 600px;
    }
    
    .preview-area {
        width: 100%;
        max-width: 600px;
        aspect-ratio: 4/5;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        position: relative;
        overflow: hidden;
        border: 3px solid #e9ecef;
        box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    .draggable-layer {
        touch-action: none;
        user-select: none;
        -webkit-user-drag: none;
        -webkit-user-select: none;
    }
    
    #shirtPreview {
        width: 100%;
        height: 100%;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: auto;
    }
    
    #previewImage {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        filter: drop-shadow(0 10px 30px rgba(0,0,0,0.2));
        transition: all 0.3s ease;
    }
    
    #svgOverlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
        max-width: 90%;
        max-height: 90%;
    }
    
    .selected-info {
        text-align: center;
        padding: 12px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        margin-top: 16px;
        border: 2px solid #dee2e6;
    }
    
    /* Right Panel - Logo & Shirt Type */
    .right-panel h3 {
        font-size: 20px;
        font-weight: 700;
        color: #2d3748;
        margin: 0 0 20px 0;
        padding-bottom: 12px;
        border-bottom: 3px solid #667eea;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .logo-upload-area {
        border: 3px dashed #cbd5e0;
        border-radius: 16px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .logo-upload-area:hover {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
    }
    
    .logo-upload-area:hover {
        border-color: #8bc34a;
        background: #f5f5f5;
    }
    
    .upload-icon {
        font-size: 48px;
        color: #ccc;
    }
    
    .customization-section {
        margin-bottom: 24px;
    }
    
    .customization-section {
        margin-bottom: 24px;
        padding: 20px;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 12px;
        border: 1px solid #e9ecef;
    }
    
    .customization-section h4 {
        font-size: 15px;
        font-weight: 600;
        color: #2d3e2d;
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .customization-section h4::before {
        content: '';
        width: 4px;
        height: 16px;
        background: #8bc34a;
        border-radius: 2px;
    }
    
    .option-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .option-btn {
        padding: 10px 12px;
        pointer-events: auto !important;
        z-index: 9999;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.2s;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .option-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, #c5e1a5 0%, #8bc34a 100%);
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .option-btn span {
        position: relative;
        z-index: 1;
    }
    
    .option-btn.active {
        border-color: #8bc34a;
        background: linear-gradient(135deg, #c5e1a5 0%, #8bc34a 100%);
        color: #2d3e2d;
        font-weight: 600;
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(139, 195, 74, 0.3);
    }
    
    .option-btn:not(.active):hover {
        border-color: #b8d496;
        background: #f9fdf7;
    }
    
    .preview-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #e8f5e9;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        color: #4a5a38;
        margin-top: 6px;
    }
    
    .submit-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 24px;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }
    
    .submit-btn:active {
        transform: translateY(0);
    }
    
    .submit-btn:hover {
        background: #4a5a38;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(91,107,70,0.3);
    }
    
    @media (max-width: 1200px) {
        .customization-container {
            grid-template-columns: 1fr;
            max-width: 600px;
        }
    }

    /* Friendly buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        font-weight: 700;
        cursor: pointer;
        border: 0;
        transition: transform .15s ease, box-shadow .15s ease, background .2s ease;
        user-select: none;
    }
    .btn:active { transform: translateY(0); }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        box-shadow: 0 6px 16px rgba(102,126,234,.35);
    }
    .btn-primary:hover { filter: brightness(1.05); }
    .btn-secondary {
        background: #fff;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .btn-secondary:hover { background: #f8fafc; }

    /* Modal styles */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        padding: 16px;
    }
    .modal-card {
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,.25);
        overflow: hidden;
        border: 1px solid #eef2f7;
        animation: modalIn .18s ease;
        display: flex;
        flex-direction: column;
    }
    @keyframes modalIn { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 18px;
        border-bottom: 1px solid #eef2f7;
        font-size: 18px;
        font-weight: 800;
        color: #2d3748;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    }
    .modal-close {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
        cursor: pointer;
        font-size: 20px;
        line-height: 20px;
        font-weight: 700;
        color: #4a5568;
    }
    .modal-body { 
        padding: 16px 18px; 
        overflow-y: auto;
        max-height: calc(90vh - 140px);
    }
    .modal-subtext { font-size: 13px; color: #4a5568; margin-bottom: 12px; }
    .modal-details {
        background:#f8f9fa;
        border:1px solid #e2e8f0;
        border-radius:12px;
        padding:16px;
        font-size:13px;
        color:#2d3e2d;
        line-height:1.5;
        margin-bottom:16px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .modal-details > div:last-child {
        grid-column: 1 / -1;
    }
    .modal-section {
        background: white;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    .modal-section-title {
        color: #667eea;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .modal-section-content {
        font-size: 12px;
        line-height: 1.6;
    }
    .modal-section-content > div {
        margin-bottom: 4px;
    }
    .modal-section-content strong {
        color: #2d3748;
        font-weight: 600;
        min-width: 90px;
        display: inline-block;
    }
    .modal-actions {
        display:flex;
        gap:10px;
        justify-content:flex-end;
        padding: 0 18px 18px 18px;
    }
    /* Toast */
    #toast {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #10b981;
        color: #fff;
        padding: 12px 16px;
        border-radius: 10px;
        box-shadow: 0 10px 24px rgba(16,185,129,.35);
        font-weight: 700;
        display: none;
        z-index: 100000;
    }
    
    /* Responsive modal for smaller screens */
    @media (max-width: 768px) {
        .modal-details {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 12px;
        }
        .modal-section {
            padding: 10px;
        }
        .modal-section-title {
            font-size: 12px;
        }
        .modal-section-content {
            font-size: 11px;
        }
        .modal-section-content strong {
            min-width: 70px;
        }
    }
</style>

<form method="POST" enctype="multipart/form-data" id="customizationForm">
    <h1 class="page-title">Create your own shirt design!</h1>
    
    <div class="customization-container">
        <!-- Left Panel: Color & Size -->
        <div class="left-panel custom-card">
            <h3>Color</h3>
            <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                <input type="color" id="colorPicker" value="#1a1a1a" style="width:42px; height:42px; padding:0; border:none; background:transparent; cursor:pointer; border-radius:8px; box-shadow: 0 2px 6px rgba(0,0,0,.08);">
                <div style="font-size:13px; color:#2d3e2d;">
                    <div style="font-weight:700;">Custom color</div>
                    <div id="colorHex" style="opacity:.8;">#1A1A1A</div>
                </div>
            </div>
            
            <div class="size-section">
                <h4>Size <span style="color: #e53e3e;">*</span></h4>
                <div class="size-type-btns">
                    <button type="button" class="size-type-btn" data-type="adult">Adult</button>
                    <button type="button" class="size-type-btn" data-type="kids">Kids</button>
                </div>
                
                <div class="size-grid">
                    <button type="button" class="size-btn" data-size="XS">XS</button>
                    <button type="button" class="size-btn" data-size="S">S</button>
                    <button type="button" class="size-btn" data-size="M">M</button>
                    <button type="button" class="size-btn" data-size="L">L</button>
                    <button type="button" class="size-btn" data-size="XL">XL</button>
                    <button type="button" class="size-btn" data-size="2XL">2XL</button>
                    <button type="button" class="size-btn" data-size="3XL">3XL</button>
                    <button type="button" class="size-btn" data-size="4XL">4XL</button>
                </div>
            </div>
            
            <!-- Main Measurements -->
            <div class="customization-section" style="margin-top: 20px;">
                <h4>Main Measurements (inches) <span style="color: #e53e3e;">*</span></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">
                    <div>
                        <label style="font-size: 11px; color: #666;">Chest</label>
                        <input type="number" name="chest_width" step="0.5" placeholder="38" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Waist</label>
                        <input type="number" name="waist_width" step="0.5" placeholder="32" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Hip</label>
                        <input type="number" name="hip_width" step="0.5" placeholder="36" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Shoulder</label>
                        <input type="number" name="shoulder_width" step="0.5" placeholder="16" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Sleeve Length</label>
                        <input type="number" name="sleeve_length" step="0.5" placeholder="24" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Garment Length</label>
                        <input type="number" name="garment_length" step="0.5" placeholder="28" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                </div>
            </div>
            
            <!-- Additional Measurements -->
            <div class="customization-section">
                <h4>Extra Measurements (inches) <span style="color: #e53e3e;">*</span></h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">
                    <div>
                        <label style="font-size: 11px; color: #666;">Neck</label>
                        <input type="number" name="neck_circumference" step="0.5" placeholder="14" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Arm</label>
                        <input type="number" name="arm_circumference" step="0.5" placeholder="12" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Wrist</label>
                        <input type="number" name="wrist_circumference" step="0.5" placeholder="7" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; color: #666;">Inseam</label>
                        <input type="number" name="inseam_length" step="0.5" placeholder="30" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;">
                    </div>
                </div>
            </div>
            
            <!-- Fabric Preferences -->
            <div class="customization-section">
                <h4>Fabric Preferences <span style="color: #e53e3e;">*</span></h4>
                <div style="margin-bottom: 10px;">
                    <label style="font-size: 11px; color: #666; display: block; margin-bottom: 4px;">Type</label>
                    <select name="fabric_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px;">
                        <option value="">Any</option>
                        <option value="cotton">Cotton</option>
                        <option value="polyester">Polyester</option>
                        <option value="cotton-blend">Cotton Blend</option>
                        <option value="dri-fit">Dri-Fit</option>
                        <option value="silk">Silk</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; color: #666; display: block; margin-bottom: 4px;">Weight</label>
                    <select name="fabric_weight" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px;">
                        <option value="">Any</option>
                        <option value="light">Light</option>
                        <option value="medium">Medium</option>
                        <option value="heavy">Heavy</option>
                    </select>
                </div>
            </div>
            
            <!-- Hidden form fields -->
            <input type="hidden" name="product_type" id="product_type" value="custom">
            <input type="hidden" name="neckline_type" id="neckline_type" value="<?php echo htmlspecialchars($defaultNeck); ?>">
            <input type="hidden" name="sleeve_type" id="sleeve_type" value="<?php echo htmlspecialchars($defaultSleeve); ?>">
            <input type="hidden" name="fit_type" id="fit_type" value="<?php echo htmlspecialchars($defaultFit); ?>">
            <input type="hidden" name="garment_style" id="garment_style" value="casual">
            <input type="hidden" name="color_preference_1" id="color_preference_1" value="#1a1a1a">
            <input type="hidden" name="size_selected" id="size_selected" value="M">
            <input type="hidden" name="size_type" id="size_type" value="adult">
            <input type="hidden" name="description" id="description" value="Custom modular shirt design">
        </div>
        
        <!-- Center Panel: Preview -->
        <div class="center-panel custom-card">
            <div class="preview-area" style="position: relative;">
                <div id="shirtPreview">
                
                <div id="shirtCanvas" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;"><div style="color: #999; font-size: 18px; text-align: center; padding: 40px;">👕<br>Click a button to preview your design</div></div>                </div>
                
                <!-- Layer Selector Thumbnails - Inside Preview Area -->
                <div id="layerSelector" style="position: absolute; bottom: 8px; left: 8px; right: 8px; padding: 6px 8px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 8px; border: 1px solid rgba(102, 126, 234, 0.3); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <h4 style="font-size: 9px; font-weight: 700; color: #2d3748; margin: 0 0 5px 0; display: flex; align-items: center; gap: 4px;">
                        <span style="width: 2px; height: 10px; background: #667eea; border-radius: 2px; display: inline-block;"></span>
                        Layers
                    </h4>
                    <div id="layerThumbnails" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px;">
                        <!-- Thumbnails will be added here dynamically -->
                    </div>
                </div>
            </div>
            
            <div class="selected-info">
                <span style="color: #666;">👁️ Your custom design preview</span>
            </div>
        </div>
        
        <!-- Right Panel: Customization Options -->
        <div class="right-panel custom-card">
            <h3>Build Your Garment</h3>
            
            <!-- Neck Type Selection -->
            <div class="customization-section">
                <h4>Neck Style <span style="color: #e53e3e;">*</span></h4>
                <div class="option-grid">
                    <?php foreach ($neckTypes as $key => $label): ?>
                        <button type="button" class="option-btn" data-neck="<?php echo htmlspecialchars($key); ?>">
                            <span><?php echo htmlspecialchars($label); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sleeve Type Selection -->
            <div class="customization-section">
                <h4>Sleeve Length <span style="color: #e53e3e;">*</span></h4>
                <div class="option-grid">
                    <?php foreach ($sleeveTypes as $key => $label): ?>
                        <button type="button" class="option-btn" data-sleeve="<?php echo htmlspecialchars($key); ?>">
                            <span><?php echo htmlspecialchars($label); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Fit Type Selection -->
            <div class="customization-section">
                <h4>Fit Style <span style="color: #e53e3e;">*</span></h4>
                <div class="option-grid">
                    <?php foreach ($fitTypes as $key => $label): ?>
                        <button type="button" class="option-btn" data-fit="<?php echo htmlspecialchars($key); ?>">
                            <span><?php echo htmlspecialchars($label); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Current Selection Display -->
            <div style="background: #f5f5f5; padding: 14px; border-radius: 10px; margin: 20px 0;">
                <div style="font-size: 12px; font-weight: 600; color: #666; margin-bottom: 8px;">CURRENT DESIGN:</div>
                <div style="font-size: 13px; color: #2d3e2d; line-height: 1.6;">
                    <div><strong>Neck:</strong> <span id="displayNeck" style="color: #999;">Not selected</span></div>
                    <div><strong>Sleeves:</strong> <span id="displaySleeve" style="color: #999;">Not selected</span></div>
                    <div><strong>Fit:</strong> <span id="displayFit" style="color: #999;">Not selected</span></div>
                </div>
            </div>
            
            <!-- Logo Upload -->
            <div class="customization-section">
                <h4>Add Your Logo/Design Reference</h4>
                <div class="logo-upload-area" id="logoUploadArea" style="aspect-ratio: 1; margin-top: 8px;">
                    <div class="upload-icon">+</div>
                    <input type="file" name="reference_image" id="reference_image" accept="image/*" style="display: none;">
                </div>
                <button type="button" id="uploadLogoBtn" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                    Upload Logo
                </button>
            </div>
            
            <!-- Special Instructions -->
            <div class="customization-section">
                <h4>Special Instructions</h4>
                <textarea name="special_instructions" rows="3" placeholder="Any specific requests..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; resize: vertical; font-family: inherit; font-size: 13px;"></textarea>
            </div>
            
            <button type="submit" name="submit_request" id="submitBtn" class="btn btn-primary" style="width: 100%; margin-top: 12px;" disabled>Submit Customization Request</button>
            <div id="validationMessage" style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; font-size: 13px; color: #856404; display: none;">
                <strong>⚠️ Required:</strong> Please select at least one design option (Neck, Sleeve, or Fit) to continue.
            </div>
        </div>
    </div>

    <!-- Payment & Delivery Modal -->
    <div id="paymentModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <span>Payment & Delivery Details</span>
                <button type="button" id="closePayment" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-subtext">Please provide your payment and delivery information.</div>
                
                <!-- Payment Method -->
                <div style="margin-bottom: 16px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Payment Method *</label>
                    <select id="paymentMethod" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                        <option value="">Select payment method</option>
                        <option value="cod">Cash on Delivery</option>
                        <option value="gcash">GCash</option>
                    </select>
                </div>
                
                <!-- Delivery Mode -->
                <div style="margin-bottom: 16px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">Delivery Mode *</label>
                    <select id="deliveryMode" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                        <option value="">Select delivery mode</option>
                        <option value="pickup">Pick Up</option>
                        <option value="lalamove">Lalamove</option>
                        <option value="jnt">J&T Express</option>
                    </select>
                </div>
                
                <!-- Delivery Address -->
                <div id="addressSection" style="margin-bottom: 16px; display: none;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px;">📍 Delivery Address</label>
                    <div id="addressDisplay" style="padding: 12px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px; display: none;">
                        <div id="addressText" style="color: #333; line-height: 1.6; white-space: pre-line;"></div>
                    </div>
                    <button type="button" id="changeAddressBtn" class="btn btn-secondary" style="width: 100%; display: none;">
                        ✏️ Change Address
                    </button>
                    <div id="noAddressMsg" style="padding: 12px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; color: #856404; display: none;">
                        <strong>⚠️ No address found.</strong> Please add an address in your profile first.
                    </div>
                    <input type="hidden" id="deliveryAddress" name="delivery_address">
                </div>
                
                <div id="paymentError" style="display: none; padding: 10px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00; font-size: 13px; margin-top: 10px;">
                    Please fill in all required fields.
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelPayment" class="btn btn-secondary">Cancel</button>
                <button type="button" id="proceedToConfirm" class="btn btn-primary">Continue to Confirmation</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <span>Confirm Your Request</span>
                <button type="button" id="closeConfirm" class="modal-close">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-subtext">Please review the details below. This request will be submitted for admin approval.</div>
                <div id="confirmPreview" style="text-align: center; margin-bottom: 16px; padding: 16px; background: #f8f9fa; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div style="font-weight: 700; font-size: 13px; color: #667eea; margin-bottom: 12px;">👕 YOUR DESIGN PREVIEW</div>
                    <div style="background: white; padding: 12px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <canvas id="confirmCanvas" style="max-width: 100%; height: auto; border-radius: 4px;"></canvas>
                    </div>
                </div>
                <div id="confirmDetails" class="modal-details"></div>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelConfirm" class="btn btn-secondary">Cancel</button>
                <button type="button" id="proceedSubmit" class="btn btn-primary">Submit Request</button>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("JS LOADED");
    console.log("Setup started");
    let shirtColor = '#1a1a1a';
    let neckType = null; // No default selection
    let sleeveType = null; // No default selection
    let fitType = null; // No default selection
    let uploadedLogo = null;
    let persistedLogoLayer = null; // keep logo layer across updates
    let updateCallCount = 0;
    
    // Track if user has made selections
    let sizeSelected = false;
    let sizeTypeSelected = false;
    
    // Form validation
    function validateForm() {
        const submitBtn = document.getElementById('submitBtn');
        const validationMsg = document.getElementById('validationMessage');
        
        // Check if user has selected size and size type
        const hasDesign = neckType && sleeveType && fitType;
        const hasSize = sizeSelected && sizeTypeSelected;
        
        // Check main measurements
        const chest = document.querySelector('input[name="chest_width"]').value;
        const waist = document.querySelector('input[name="waist_width"]').value;
        const hip = document.querySelector('input[name="hip_width"]').value;
        const shoulder = document.querySelector('input[name="shoulder_width"]').value;
        const sleeveLength = document.querySelector('input[name="sleeve_length"]').value;
        const garmentLength = document.querySelector('input[name="garment_length"]').value;
        
        // Check extra measurements
        const neck = document.querySelector('input[name="neck_circumference"]').value;
        const arm = document.querySelector('input[name="arm_circumference"]').value;
        const wrist = document.querySelector('input[name="wrist_circumference"]').value;
        const inseam = document.querySelector('input[name="inseam_length"]').value;
        
        // Check fabric preferences
        const fabricType = document.querySelector('select[name="fabric_type"]').value;
        const fabricWeight = document.querySelector('select[name="fabric_weight"]').value;
        
        const hasMainMeasurements = chest && waist && hip && shoulder && sleeveLength && garmentLength;
        const hasExtraMeasurements = neck && arm && wrist && inseam;
        const hasFabricPreferences = fabricType && fabricWeight;
        
        const allValid = hasDesign && hasSize && hasMainMeasurements && hasExtraMeasurements && hasFabricPreferences;
        
        if (allValid) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
            if (validationMsg) validationMsg.style.display = 'none';
        } else {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
            if (validationMsg) {
                validationMsg.style.display = 'block';
                let missing = [];
                if (!hasDesign) {
                    let designMissing = [];
                    if (!neckType) designMissing.push('Neck Style');
                    if (!sleeveType) designMissing.push('Sleeve Length');
                    if (!fitType) designMissing.push('Fit Style');
                    missing.push('Garment Design (' + designMissing.join(', ') + ')');
                }
                if (!hasSize) missing.push('Size & Size Type');
                if (!hasMainMeasurements) missing.push('Main Measurements');
                if (!hasExtraMeasurements) missing.push('Extra Measurements');
                if (!hasFabricPreferences) missing.push('Fabric Preferences');
                validationMsg.innerHTML = '<strong>⚠️ Required:</strong> Please complete: ' + missing.join(', ');
            }
        }
    }
    
    // Initial validation
    validateForm();
    
    // Draggable utility available to all layers (mouse + touch)
    function makeDraggable(element, container) {
        let isDragging = false;
        let startX = 0, startY = 0;
        let startLeft = 0, startTop = 0;
        element.isLocked = false; // Add lock state

        function getPoint(e) {
            if (e.touches && e.touches.length) {
                return { x: e.touches[0].clientX, y: e.touches[0].clientY };
            }
            return { x: e.clientX, y: e.clientY };
        }

        function dragStart(e) {
            // Don't drag if locked or if clicking on control buttons
            if (element.isLocked || e.target.classList.contains('layer-control-btn')) return;
            
            const p = getPoint(e);
            startX = p.x;
            startY = p.y;
            startLeft = parseFloat(element.style.left || '0');
            startTop = parseFloat(element.style.top || '0');
            isDragging = true;
            element.style.zIndex = 1000;
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', dragEnd);
            document.addEventListener('touchmove', drag, { passive: false });
            document.addEventListener('touchend', dragEnd);
        }

        function drag(e) {
            if (!isDragging || element.isLocked) return;
            if (e.cancelable) e.preventDefault();
            const p = getPoint(e);
            const dx = p.x - startX;
            const dy = p.y - startY;
            const cw = container.clientWidth;
            const ch = container.clientHeight;
            const ww = element.offsetWidth;
            const wh = element.offsetHeight;
            let newLeft = startLeft + dx;
            let newTop = startTop + dy;
            newLeft = Math.max(0, Math.min(newLeft, cw - ww));
            newTop = Math.max(0, Math.min(newTop, ch - wh));
            element.style.left = newLeft + 'px';
            element.style.top = newTop + 'px';
        }

        function dragEnd() {
            if (!isDragging) return;
            isDragging = false;
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('mouseup', dragEnd);
            document.removeEventListener('touchmove', drag);
            document.removeEventListener('touchend', dragEnd);
        }

        element.addEventListener('mousedown', dragStart);
        element.addEventListener('touchstart', dragStart, { passive: true });
    }
    
    // Function to add resize and lock controls to a layer
    function addLayerControls(wrapper, partName) {
        // Create control panel
        const controlPanel = document.createElement('div');
        controlPanel.className = 'layer-controls';
        controlPanel.style.position = 'absolute';
        controlPanel.style.top = '-40px';
        controlPanel.style.left = '50%';
        controlPanel.style.transform = 'translateX(-50%)';
        controlPanel.style.background = 'rgba(0, 0, 0, 0.8)';
        controlPanel.style.borderRadius = '8px';
        controlPanel.style.padding = '6px 10px';
        controlPanel.style.display = 'flex';
        controlPanel.style.gap = '8px';
        controlPanel.style.alignItems = 'center';
        controlPanel.style.zIndex = '10001';
        controlPanel.style.pointerEvents = 'auto';
        controlPanel.style.opacity = '0';
        controlPanel.style.transition = 'opacity 0.2s';
        
        // Part label
        const label = document.createElement('span');
        label.textContent = partName.toUpperCase();
        label.style.color = '#fff';
        label.style.fontSize = '11px';
        label.style.fontWeight = '700';
        label.style.marginRight = '4px';
        controlPanel.appendChild(label);
        
        // Zoom out button
        const zoomOutBtn = document.createElement('button');
        zoomOutBtn.type = 'button';
        zoomOutBtn.innerHTML = '➖';
        zoomOutBtn.className = 'layer-control-btn';
        zoomOutBtn.title = 'Zoom Out';
        styleControlButton(zoomOutBtn);
        zoomOutBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (wrapper.isLocked) return; // Don't allow resize when locked
            const img = wrapper.querySelector('img');
            if (img) {
                wrapper.zoomScale = Math.max(0.3, (wrapper.zoomScale || 1) - 0.1);
                const baseSize = partName === 'logo' ? 250 : 400;
                img.style.width = (baseSize * wrapper.zoomScale) + 'px';
                img.style.height = (baseSize * wrapper.zoomScale) + 'px';
                sizeLabel.textContent = Math.round(wrapper.zoomScale * 100) + '%';
            }
        };
        controlPanel.appendChild(zoomOutBtn);
        
        // Size label
        const sizeLabel = document.createElement('span');
        sizeLabel.textContent = '100%';
        sizeLabel.style.color = '#fff';
        sizeLabel.style.fontSize = '11px';
        sizeLabel.style.fontWeight = '600';
        sizeLabel.style.minWidth = '35px';
        sizeLabel.style.textAlign = 'center';
        controlPanel.appendChild(sizeLabel);
        wrapper.sizeLabel = sizeLabel;
        
        // Zoom in button
        const zoomInBtn = document.createElement('button');
        zoomInBtn.type = 'button';
        zoomInBtn.innerHTML = '➕';
        zoomInBtn.className = 'layer-control-btn';
        zoomInBtn.title = 'Zoom In';
        styleControlButton(zoomInBtn);
        zoomInBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (wrapper.isLocked) return; // Don't allow resize when locked
            const img = wrapper.querySelector('img');
            if (img) {
                wrapper.zoomScale = Math.min(3, (wrapper.zoomScale || 1) + 0.1);
                const baseSize = partName === 'logo' ? 250 : 400;
                img.style.width = (baseSize * wrapper.zoomScale) + 'px';
                img.style.height = (baseSize * wrapper.zoomScale) + 'px';
                sizeLabel.textContent = Math.round(wrapper.zoomScale * 100) + '%';
            }
        };
        controlPanel.appendChild(zoomInBtn);
        
        // Delete button (X)
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.innerHTML = '✕';
        deleteBtn.className = 'layer-control-btn';
        deleteBtn.title = 'Remove Layer';
        styleControlButton(deleteBtn);
        deleteBtn.style.background = '#e53e3e';
        deleteBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (confirm('Remove this ' + partName + ' layer?')) {
                // Remove the layer
                if (wrapper && wrapper.parentNode) {
                    wrapper.parentNode.removeChild(wrapper);
                }
                // Update thumbnails
                updateLayerThumbnails();
                // Reset the selection if it's a garment part
                if (partName === 'neck') {
                    neckType = null;
                    document.getElementById('displayNeck').innerHTML = '<span style="color: #999;">Not selected</span>';
                    document.querySelectorAll('[data-neck]').forEach(b => b.classList.remove('active'));
                } else if (partName === 'sleeve') {
                    sleeveType = null;
                    document.getElementById('displaySleeve').innerHTML = '<span style="color: #999;">Not selected</span>';
                    document.querySelectorAll('[data-sleeve]').forEach(b => b.classList.remove('active'));
                } else if (partName === 'fit') {
                    fitType = null;
                    document.getElementById('displayFit').innerHTML = '<span style="color: #999;">Not selected</span>';
                    document.querySelectorAll('[data-fit]').forEach(b => b.classList.remove('active'));
                } else if (partName === 'logo') {
                    persistedLogoLayer = null;
                    uploadedLogo = null;
                }
                validateForm();
            }
        };
        controlPanel.appendChild(deleteBtn);
        
        // Lock button
        const lockBtn = document.createElement('button');
        lockBtn.type = 'button';
        lockBtn.innerHTML = '🔓';
        lockBtn.className = 'layer-control-btn';
        lockBtn.title = 'Lock Position';
        styleControlButton(lockBtn);
        lockBtn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            wrapper.isLocked = !wrapper.isLocked;
            lockBtn.innerHTML = wrapper.isLocked ? '🔒' : '🔓';
            lockBtn.title = wrapper.isLocked ? 'Unlock (Position & Size Locked)' : 'Lock Position & Size';
            wrapper.style.cursor = wrapper.isLocked ? 'default' : 'move';
            lockBtn.style.background = wrapper.isLocked ? '#4CAF50' : 'rgba(255, 255, 255, 0.2)';
            
            // Disable/enable zoom buttons visually
            if (wrapper.isLocked) {
                zoomOutBtn.style.opacity = '0.4';
                zoomOutBtn.style.cursor = 'not-allowed';
                zoomOutBtn.title = 'Locked - Unlock to resize';
                zoomInBtn.style.opacity = '0.4';
                zoomInBtn.style.cursor = 'not-allowed';
                zoomInBtn.title = 'Locked - Unlock to resize';
            } else {
                zoomOutBtn.style.opacity = '1';
                zoomOutBtn.style.cursor = 'pointer';
                zoomOutBtn.title = 'Zoom Out';
                zoomInBtn.style.opacity = '1';
                zoomInBtn.style.cursor = 'pointer';
                zoomInBtn.title = 'Zoom In';
            }
        };
        controlPanel.appendChild(lockBtn);
        
        wrapper.appendChild(controlPanel);
        
        // Show controls on hover
        wrapper.addEventListener('mouseenter', () => {
            controlPanel.style.opacity = '1';
        });
        wrapper.addEventListener('mouseleave', () => {
            controlPanel.style.opacity = '0';
        });
    }
    
    function styleControlButton(btn) {
        btn.style.background = 'rgba(255, 255, 255, 0.2)';
        btn.style.border = '1px solid rgba(255, 255, 255, 0.3)';
        btn.style.borderRadius = '6px';
        btn.style.color = '#fff';
        btn.style.cursor = 'pointer';
        btn.style.fontSize = '14px';
        btn.style.padding = '4px 8px';
        btn.style.transition = 'all 0.2s';
        btn.style.lineHeight = '1';
        btn.onmouseenter = () => {
            btn.style.background = 'rgba(255, 255, 255, 0.3)';
            btn.style.transform = 'scale(1.1)';
        };
        btn.onmouseleave = () => {
            if (!btn.innerHTML.includes('🔒')) {
                btn.style.background = 'rgba(255, 255, 255, 0.2)';
            }
            btn.style.transform = 'scale(1)';
        };
    }
    
    // Function to update layer thumbnails
    function updateLayerThumbnails() {
        const thumbnailContainer = document.getElementById('layerThumbnails');
        if (!thumbnailContainer) return;
        
        thumbnailContainer.innerHTML = '';
        
        const canvas = document.getElementById('shirtCanvas');
        if (!canvas) return;
        
        const container = canvas.querySelector('.image-container');
        if (!container) return;
        
        const layers = container.querySelectorAll('.draggable-layer');
        
        layers.forEach((layer, index) => {
            const partName = layer.getAttribute('data-part');
            const img = layer.querySelector('img');
            if (!img) return;
            
            // Create thumbnail card
            const thumbCard = document.createElement('div');
            thumbCard.className = 'layer-thumbnail';
            thumbCard.style.cssText = `
                position: relative;
                padding: 4px;
                background: white;
                border: 1.5px solid #e2e8f0;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s;
                text-align: center;
            `;
            
            // Thumbnail image
            const thumbImg = document.createElement('img');
            thumbImg.src = img.src;
            thumbImg.style.cssText = `
                width: 100%;
                height: 40px;
                object-fit: contain;
                margin-bottom: 3px;
                pointer-events: none;
            `;
            thumbCard.appendChild(thumbImg);
            
            // Label
            const label = document.createElement('div');
            label.textContent = partName.toUpperCase();
            label.style.cssText = `
                font-size: 8px;
                font-weight: 700;
                color: #667eea;
                text-transform: uppercase;
                line-height: 1;
            `;
            thumbCard.appendChild(label);
            
            // Lock indicator
            if (layer.isLocked) {
                const lockIcon = document.createElement('div');
                lockIcon.innerHTML = '🔒';
                lockIcon.style.cssText = `
                    position: absolute;
                    top: 2px;
                    right: 2px;
                    font-size: 10px;
                `;
                thumbCard.appendChild(lockIcon);
            }
            
            // Click to bring layer to front and highlight
            thumbCard.onclick = () => {
                // Bring to front
                bringLayerToFront(layer);
                
                // Highlight selected thumbnail
                document.querySelectorAll('.layer-thumbnail').forEach(t => {
                    t.style.border = '2px solid #e2e8f0';
                    t.style.background = 'white';
                });
                thumbCard.style.border = '2px solid #667eea';
                thumbCard.style.background = 'linear-gradient(135deg, #667eea10 0%, #764ba210 100%)';
                
                // Show controls temporarily
                const controls = layer.querySelector('.layer-controls');
                if (controls) {
                    controls.style.opacity = '1';
                    setTimeout(() => {
                        controls.style.opacity = '0';
                    }, 2000);
                }
            };
            
            // Hover effect
            thumbCard.onmouseenter = () => {
                if (thumbCard.style.border !== '2px solid #667eea') {
                    thumbCard.style.border = '2px solid #cbd5e0';
                    thumbCard.style.transform = 'translateY(-2px)';
                    thumbCard.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                }
            };
            thumbCard.onmouseleave = () => {
                if (thumbCard.style.border !== '2px solid #667eea') {
                    thumbCard.style.border = '2px solid #e2e8f0';
                    thumbCard.style.transform = 'translateY(0)';
                    thumbCard.style.boxShadow = 'none';
                }
            };
            
            thumbnailContainer.appendChild(thumbCard);
        });
    }
    
    // Function to bring layer to front
    function bringLayerToFront(layer) {
        const canvas = document.getElementById('shirtCanvas');
        if (!canvas) return;
        
        const container = canvas.querySelector('.image-container');
        if (!container) return;
        
        // Get all layers
        const allLayers = Array.from(container.querySelectorAll('.draggable-layer'));
        
        // Find max z-index
        let maxZ = 0;
        allLayers.forEach(l => {
            const z = parseInt(l.style.zIndex) || 0;
            if (z > maxZ) maxZ = z;
        });
        
        // Set selected layer to highest z-index
        layer.style.zIndex = maxZ + 1;
        
        // Visual feedback - pulse effect
        layer.style.transform = 'scale(1.05)';
        setTimeout(() => {
            layer.style.transform = 'scale(1)';
        }, 200);
    }

    // Shirt image mapping - Local folder structure
    // Images should be in: img/shirts/{neck}-{sleeve}-{fit}.png
    const shirtImages = {
        'round-short-slimfit': 'img/shirts/round-short-slimfit.png',
        'round-short-bodyfit': 'img/shirts/round-short-bodyfit.png',
        'round-short-loose': 'img/shirts/round-short-loose.png',
        'round-long-slimfit': 'img/shirts/round-long-slimfit.png',
        'round-long-bodyfit': 'img/shirts/round-long-bodyfit.png',
        'round-long-loose': 'img/shirts/round-long-loose.png',
        'round-half-slimfit': 'img/shirts/round-half-slimfit.png',
        'round-half-bodyfit': 'img/shirts/round-half-bodyfit.png',
        'round-half-loose': 'img/shirts/round-half-loose.png',
        'round-sleeveless-slimfit': 'img/shirts/round-sleeveless-slimfit.png',
        'round-sleeveless-bodyfit': 'img/shirts/round-sleeveless-bodyfit.png',
        'round-sleeveless-loose': 'img/shirts/round-sleeveless-loose.png',
        'vneck-short-slimfit': 'img/shirts/vneck-short-slimfit.png',
        'vneck-short-bodyfit': 'img/shirts/vneck-short-bodyfit.png',
        'vneck-short-loose': 'img/shirts/vneck-short-loose.png',
        'vneck-long-slimfit': 'img/shirts/vneck-long-slimfit.png',
        'vneck-long-bodyfit': 'img/shirts/vneck-long-bodyfit.png',
        'vneck-long-loose': 'img/shirts/vneck-long-loose.png',
        'vneck-half-slimfit': 'img/shirts/vneck-half-slimfit.png',
        'vneck-half-bodyfit': 'img/shirts/vneck-half-bodyfit.png',
        'vneck-half-loose': 'img/shirts/vneck-half-loose.png',
        'vneck-sleeveless-slimfit': 'img/shirts/vneck-sleeveless-slimfit.png',
        'vneck-sleeveless-bodyfit': 'img/shirts/vneck-sleeveless-bodyfit.png',
        'vneck-sleeveless-loose': 'img/shirts/vneck-sleeveless-loose.png',
        'polo-short-slimfit': 'img/shirts/polo-short-slimfit.png',
        'polo-short-bodyfit': 'img/shirts/polo-short-bodyfit.png',
        'polo-short-loose': 'img/shirts/polo-short-loose.png',
        'polo-long-slimfit': 'img/shirts/polo-long-slimfit.png',
        'polo-long-bodyfit': 'img/shirts/polo-long-bodyfit.png',
        'polo-long-loose': 'img/shirts/polo-long-loose.png',
        'turtle-long-slimfit': 'img/shirts/turtle-long-slimfit.png',
        'turtle-long-bodyfit': 'img/shirts/turtle-long-bodyfit.png',
        'turtle-long-loose': 'img/shirts/turtle-long-loose.png',
        'turtle-half-slimfit': 'img/shirts/turtle-half-slimfit.png',
        'turtle-half-bodyfit': 'img/shirts/turtle-half-bodyfit.png',
        'turtle-half-loose': 'img/shirts/turtle-half-loose.png'
    };
    
    // Function to generate realistic SVG shirt template (like actual shirt mockup)
    function generateShirtSVG() {
        const width = 600;
        const height = 700;
        const centerX = width / 2;
        
        // Body dimensions based on fit
        let bodyWidth = 240;
        let shoulderWidth = 280;
        if (fitType === 'bodyfit') {
            bodyWidth = 200;
            shoulderWidth = 240;
        } else if (fitType === 'loose') {
            bodyWidth = 280;
            shoulderWidth = 320;
        }
        
        let svg = `<svg style="background: transparent;" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg">`;
        svg += `<defs><style>
            .shirt-body{fill:${shirtColor};stroke:#1a1a1a;stroke-width:6;stroke-linejoin:round;}
            .sleeve{fill:${shirtColor};stroke:#1a1a1a;stroke-width:6;stroke-linejoin:round;}
            .neck-outline{fill:none;stroke:#1a1a1a;stroke-width:5;}
            .detail-line{fill:none;stroke:#1a1a1a;stroke-width:3;}
        </style></defs>`;
        
        // Main coordinates
        const shoulderY = 120;
        const armholeY = 180;
        const bodyStartY = 180;
        const hemY = 620;
        const neckWidth = 85;
        const neckDepth = 45;
        
        // Sleeve dimensions
        let sleeveLength = 110; // short
        let sleeveWidth = 80;
        if (sleeveType === 'long') {
            sleeveLength = 420;
            sleeveWidth = 70;
        } else if (sleeveType === 'half') {
            sleeveLength = 220;
            sleeveWidth = 75;
        }
        
        // Draw sleeves first (behind body)
        if (sleeveType !== 'sleeveless') {
            // Left sleeve - curved and realistic
            let leftSleeveOuter = `M ${centerX - bodyWidth/2 - 5} ${shoulderY}`;
            leftSleeveOuter += ` Q ${centerX - bodyWidth/2 - 40} ${shoulderY - 8}, ${centerX - shoulderWidth/2} ${shoulderY + 10}`;
            leftSleeveOuter += ` Q ${centerX - shoulderWidth/2 - 25} ${shoulderY + 40}, ${centerX - shoulderWidth/2 - sleeveWidth} ${shoulderY + sleeveLength - 25}`;
            leftSleeveOuter += ` Q ${centerX - shoulderWidth/2 - sleeveWidth} ${shoulderY + sleeveLength - 10}, ${centerX - shoulderWidth/2 - sleeveWidth + 8} ${shoulderY + sleeveLength}`;
            leftSleeveOuter += ` L ${centerX - shoulderWidth/2 - 30} ${shoulderY + sleeveLength}`;
            leftSleeveOuter += ` Q ${centerX - shoulderWidth/2 - 20} ${shoulderY + sleeveLength - 5}, ${centerX - shoulderWidth/2 - 10} ${shoulderY + sleeveLength - 15}`;
            leftSleeveOuter += ` Q ${centerX - bodyWidth/2 + 10} ${shoulderY + sleeveLength/2 + 20}, ${centerX - bodyWidth/2} ${armholeY}`;
            leftSleeveOuter += ` L ${centerX - bodyWidth/2 - 5} ${shoulderY} Z`;
            svg += `<path d="${leftSleeveOuter}" class="sleeve"/>`;
            
            // Sleeve hem detail (left)
            svg += `<path d="M ${centerX - shoulderWidth/2 - sleeveWidth + 10} ${shoulderY + sleeveLength - 8} 
                    Q ${centerX - shoulderWidth/2 - sleeveWidth/2} ${shoulderY + sleeveLength - 5}, ${centerX - shoulderWidth/2 - 25} ${shoulderY + sleeveLength - 8}" class="detail-line"/>`;
            
            // Right sleeve - curved and realistic
            let rightSleeveOuter = `M ${centerX + bodyWidth/2 + 5} ${shoulderY}`;
            rightSleeveOuter += ` Q ${centerX + bodyWidth/2 + 40} ${shoulderY - 8}, ${centerX + shoulderWidth/2} ${shoulderY + 10}`;
            rightSleeveOuter += ` Q ${centerX + shoulderWidth/2 + 25} ${shoulderY + 40}, ${centerX + shoulderWidth/2 + sleeveWidth} ${shoulderY + sleeveLength - 25}`;
            rightSleeveOuter += ` Q ${centerX + shoulderWidth/2 + sleeveWidth} ${shoulderY + sleeveLength - 10}, ${centerX + shoulderWidth/2 + sleeveWidth - 8} ${shoulderY + sleeveLength}`;
            rightSleeveOuter += ` L ${centerX + shoulderWidth/2 + 30} ${shoulderY + sleeveLength}`;
            rightSleeveOuter += ` Q ${centerX + shoulderWidth/2 + 20} ${shoulderY + sleeveLength - 5}, ${centerX + shoulderWidth/2 + 10} ${shoulderY + sleeveLength - 15}`;
            rightSleeveOuter += ` Q ${centerX + bodyWidth/2 - 10} ${shoulderY + sleeveLength/2 + 20}, ${centerX + bodyWidth/2} ${armholeY}`;
            rightSleeveOuter += ` L ${centerX + bodyWidth/2 + 5} ${shoulderY} Z`;
            svg += `<path d="${rightSleeveOuter}" class="sleeve"/>`;
            
            // Sleeve hem detail (right)
            svg += `<path d="M ${centerX + shoulderWidth/2 + sleeveWidth - 10} ${shoulderY + sleeveLength - 8} 
                    Q ${centerX + shoulderWidth/2 + sleeveWidth/2} ${shoulderY + sleeveLength - 5}, ${centerX + shoulderWidth/2 + 25} ${shoulderY + sleeveLength - 8}" class="detail-line"/>`;
        }
        
        // Main shirt body - realistic T-shirt shape
        let bodyPath = `M ${centerX - bodyWidth/2} ${bodyStartY}`;
        bodyPath += ` L ${centerX - bodyWidth/2} ${hemY - 40}`;
        bodyPath += ` Q ${centerX - bodyWidth/2} ${hemY - 15}, ${centerX - bodyWidth/2 + 25} ${hemY}`;
        bodyPath += ` L ${centerX + bodyWidth/2 - 25} ${hemY}`;
        bodyPath += ` Q ${centerX + bodyWidth/2} ${hemY - 15}, ${centerX + bodyWidth/2} ${hemY - 40}`;
        bodyPath += ` L ${centerX + bodyWidth/2} ${bodyStartY}`;
        
        if (sleeveType === 'sleeveless') {
            // Armhole curves for sleeveless
            bodyPath += ` Q ${centerX + bodyWidth/2 + 15} ${bodyStartY - 20}, ${centerX + bodyWidth/2 + 25} ${shoulderY + 15}`;
            bodyPath += ` Q ${centerX + bodyWidth/2 + 30} ${shoulderY}, ${centerX + bodyWidth/2 + 20} ${shoulderY - 15}`;
        } else {
            bodyPath += ` L ${centerX + bodyWidth/2 + 5} ${shoulderY}`;
        }
        
        // Top shoulder curve
        bodyPath += ` Q ${centerX + bodyWidth/2 + 15} ${shoulderY - 25}, ${centerX + neckWidth} ${shoulderY - 30}`;
        
        // Neck opening based on type  
        if (neckType === 'vneck') {
            bodyPath += ` L ${centerX + neckWidth - 20} ${shoulderY - 25}`;
            bodyPath += ` L ${centerX} ${shoulderY + neckDepth + 30}`;
            bodyPath += ` L ${centerX - neckWidth + 20} ${shoulderY - 25}`;
        } else {
            bodyPath += ` Q ${centerX + neckWidth/2} ${shoulderY - 35}, ${centerX} ${shoulderY - 35}`;
            bodyPath += ` Q ${centerX - neckWidth/2} ${shoulderY - 35}, ${centerX - neckWidth} ${shoulderY - 30}`;
        }
        
        bodyPath += ` Q ${centerX - bodyWidth/2 - 15} ${shoulderY - 25}, ${centerX - bodyWidth/2 - 5} ${shoulderY}`;
        
        if (sleeveType === 'sleeveless') {
            bodyPath += ` Q ${centerX - bodyWidth/2 - 30} ${shoulderY}, ${centerX - bodyWidth/2 - 25} ${shoulderY + 15}`;
            bodyPath += ` Q ${centerX - bodyWidth/2 - 15} ${bodyStartY - 20}, ${centerX - bodyWidth/2} ${bodyStartY}`;
        } else {
            bodyPath += ` L ${centerX - bodyWidth/2} ${bodyStartY}`;
        }
        
        bodyPath += ` Z`;
        svg += `<path d="${bodyPath}" class="shirt-body"/>`;
        
        // Neck detail based on type
        if (neckType === 'round') {
            // Round neck with opening (arc, not full circle)
            const rx = neckWidth - 8;
            const ry = neckDepth - 10;
            const startAngle = 35;
            const endAngle = 145;
            const x1 = centerX + rx * Math.cos(startAngle * Math.PI / 180);
            const y1 = (shoulderY - 22) - ry * Math.sin(startAngle * Math.PI / 180);
            const x2 = centerX + rx * Math.cos(endAngle * Math.PI / 180);
            const y2 = (shoulderY - 22) - ry * Math.sin(endAngle * Math.PI / 180);
            svg += `<path d="M ${x1} ${y1} A ${rx} ${ry} 0 0 1 ${x2} ${y2}" class="neck-outline" fill="none"/>`;
            const rx2 = neckWidth - 12;
            const ry2 = neckDepth - 13;
            const x3 = centerX + rx2 * Math.cos(startAngle * Math.PI / 180);
            const y3 = (shoulderY - 19) - ry2 * Math.sin(startAngle * Math.PI / 180);
            const x4 = centerX + rx2 * Math.cos(endAngle * Math.PI / 180);
            const y4 = (shoulderY - 19) - ry2 * Math.sin(endAngle * Math.PI / 180);
            svg += `<path d="M ${x3} ${y3} A ${rx2} ${ry2} 0 0 1 ${x4} ${y4}" class="detail-line" fill="none"/>`;
        } else if (neckType === 'vneck') {
            svg += `<path d="M ${centerX - neckWidth + 15} ${shoulderY - 28} L ${centerX} ${shoulderY + neckDepth + 25} L ${centerX + neckWidth - 15} ${shoulderY - 28}" class="neck-outline"/>`;
            svg += `<path d="M ${centerX - neckWidth + 20} ${shoulderY - 24} L ${centerX} ${shoulderY + neckDepth + 20} L ${centerX + neckWidth - 20} ${shoulderY - 24}" class="detail-line"/>`;
        } else if (neckType === 'polo') {
            // Polo neck with opening
            const rx = neckWidth - 10;
            const ry = neckDepth - 12;
            const startAngle = 35;
            const endAngle = 145;
            const x1 = centerX + rx * Math.cos(startAngle * Math.PI / 180);
            const y1 = (shoulderY - 22) - ry * Math.sin(startAngle * Math.PI / 180);
            const x2 = centerX + rx * Math.cos(endAngle * Math.PI / 180);
            const y2 = (shoulderY - 22) - ry * Math.sin(endAngle * Math.PI / 180);
            svg += `<path d="M ${x1} ${y1} A ${rx} ${ry} 0 0 1 ${x2} ${y2}" class="neck-outline" fill="none"/>`;
            svg += `<path d="M ${centerX - 70} ${shoulderY - 32} L ${centerX - 80} ${shoulderY - 50} L ${centerX - 65} ${shoulderY - 55} L ${centerX - 55} ${shoulderY - 37}" fill="none" stroke="#1a1a1a" stroke-width="4"/>`;
            svg += `<path d="M ${centerX + 70} ${shoulderY - 32} L ${centerX + 80} ${shoulderY - 50} L ${centerX + 65} ${shoulderY - 55} L ${centerX + 55} ${shoulderY - 37}" fill="none" stroke="#1a1a1a" stroke-width="4"/>`;
            // Placket
            svg += `<line x1="${centerX}" y1="${shoulderY + 10}" x2="${centerX}" y2="${shoulderY + 150}" stroke="#1a1a1a" stroke-width="4"/>`;
            svg += `<circle cx="${centerX}" cy="${shoulderY + 40}" r="6" fill="none" stroke="#1a1a1a" stroke-width="2.5"/>`;
            svg += `<circle cx="${centerX}" cy="${shoulderY + 80}" r="6" fill="none" stroke="#1a1a1a" stroke-width="2.5"/>`;
            svg += `<circle cx="${centerX}" cy="${shoulderY + 120}" r="6" fill="none" stroke="#1a1a1a" stroke-width="2.5"/>`;
        } else if (neckType === 'turtle') {
            svg += `<rect x="${centerX - 65}" y="${shoulderY - 75}" width="130" height="70" rx="10" fill="none" stroke="#1a1a1a" stroke-width="5"/>`;
            // Opening line in front
            svg += `<line x1="${centerX}" y1="${shoulderY - 75}" x2="${centerX}" y2="${shoulderY - 25}" stroke="#1a1a1a" stroke-width="3"/>`;
            // Ribbing with gaps
            svg += `<line x1="${centerX - 65}" y1="${shoulderY - 55}" x2="${centerX - 5}" y2="${shoulderY - 55}" stroke="#1a1a1a" stroke-width="2.5"/>`;
            svg += `<line x1="${centerX + 5}" y1="${shoulderY - 55}" x2="${centerX + 65}" y2="${shoulderY - 55}" stroke="#1a1a1a" stroke-width="2.5"/>`;
            svg += `<line x1="${centerX - 65}" y1="${shoulderY - 35}" x2="${centerX - 5}" y2="${shoulderY - 35}" stroke="#1a1a1a" stroke-width="2.5"/>`;
            svg += `<line x1="${centerX + 5}" y1="${shoulderY - 35}" x2="${centerX + 65}" y2="${shoulderY - 35}" stroke="#1a1a1a" stroke-width="2.5"/>`;
            svg += `<line x1="${centerX - 65}" y1="${shoulderY - 15}" x2="${centerX - 5}" y2="${shoulderY - 15}" stroke="#1a1a1a" stroke-width="2.5"/>`;
            svg += `<line x1="${centerX + 5}" y1="${shoulderY - 15}" x2="${centerX + 65}" y2="${shoulderY - 15}" stroke="#1a1a1a" stroke-width="2.5"/>`;
        }
        
        svg += '</svg>';
        return svg;
    }
    
    // Function to update shirt preview - ONLY for specific part
    function updateShirtPreview(partType = null) {
        updateCallCount++;
        console.log('=== UPDATE SHIRT PREVIEW CALLED #' + updateCallCount + ' ===');
        console.log('Part Type:', partType);
        console.log('Current selection - Neck:', neckType, 'Sleeve:', sleeveType, 'Fit:', fitType);
        const canvas = document.getElementById('shirtCanvas');
        if (!canvas) {
            console.error('Canvas not found');
            return;
        }
        
        // If loading specific part, don't clear entire canvas - just remove that part
        console.log('Preparing canvas...');
        console.log('Canvas children BEFORE:', canvas.children.length);
        
        if (partType) {
            // Remove only the specific part type from container
            const container = canvas.querySelector('.image-container');
            if (container) {
                const existingPart = container.querySelector('[data-part="' + partType + '"]');
                if (existingPart) {
                    // Check if layer is locked
                    if (existingPart.isLocked) {
                        console.log('Cannot replace', partType, '- layer is locked');
                        alert('⚠️ This ' + partType + ' layer is locked!\n\nUnlock it first to change the design.');
                        return; // Stop the update
                    }
                    console.log('Removing existing', partType, 'layer');
                    container.removeChild(existingPart);
                }
            }
        } else {
            // Clear everything (for color changes)
            console.log('Clearing entire canvas...');
            // Preserve logo layer, if present
            const existingLogo = canvas.querySelector('[data-part="logo"]');
            if (existingLogo) {
                persistedLogoLayer = existingLogo;
            }
            canvas.innerHTML = '';
        }
        console.log('Canvas children AFTER:', canvas.children.length);
        
        // Build paths ONLY for the specific part clicked
        const extensions = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        let basePaths = {};
        
        if (partType === 'neck') {
            basePaths.neck = 'img/shirt_parts/neck-' + neckType;
        } else if (partType === 'sleeve') {
            basePaths.sleeve = 'img/shirt_parts/sleeve-' + sleeveType;
        } else if (partType === 'fit') {
            basePaths.fit = 'img/shirt_parts/fit-' + fitType;
        } else {
            // If no specific part, load all (for color changes)
            basePaths = {
                neck: 'img/shirt_parts/neck-' + neckType,
                sleeve: 'img/shirt_parts/sleeve-' + sleeveType,
                fit: 'img/shirt_parts/fit-' + fitType
            };
        }
        console.log('Loading parts:', Object.keys(basePaths));
        console.log('Image paths:', basePaths);
        
        let loadedImages = {neck: null, sleeve: null, fit: null};
        let loadCount = 0;
        let failCount = 0;
        const partsToLoad = Object.keys(basePaths).length;
        console.log('Total parts to load:', partsToLoad);
        
        function tryLoadPart(partName, basePath, extIndex = 0) {
            if (extIndex >= extensions.length) {
                console.log('Failed to load:', partName, '- tried all extensions');
                failCount++;
                checkAllLoaded();
                return;
            }
            
            const img = new Image();
            img.onload = function() {
                console.log('Successfully loaded:', partName, 'from', img.src);
                loadedImages[partName] = img;
                loadCount++;
                checkAllLoaded();
            };
            img.onerror = function() {
                tryLoadPart(partName, basePath, extIndex + 1);
            };
            // Add cache-busting parameter to force reload of updated images
            img.src = basePath + '.' + extensions[extIndex] + '?v=' + Date.now();
        }
        
        function checkAllLoaded() {
            console.log('Check all loaded - loadCount:', loadCount, 'failCount:', failCount, 'partsToLoad:', partsToLoad);
            if (loadCount + failCount >= partsToLoad) {
                console.log('All parts checked!');
                if (loadCount > 0) {
                    console.log('Calling layerImages with', loadCount, 'images');
                    layerImages();
                } else {
                    console.log('No images loaded, showing SVG fallback');
                    const svg = generateShirtSVG();
                    // Canvas already cleared, just set the SVG
                    canvas.innerHTML = svg;
                }
            }
        }
        
        function layerImages() {
            console.log('=== LAYERING IMAGES ===');
            console.log('Loaded images:', loadedImages);
            
            // Remove placeholder message if it exists
            const placeholder = canvas.querySelector('div[style*="color: #999"]');
            if (placeholder) {
                console.log('Removing placeholder message');
                canvas.removeChild(placeholder);
            }
            
            // Get existing container or create new one
            let container = canvas.querySelector('.image-container');
            if (!container) {
                console.log('Creating new container');
                container = document.createElement('div');
                container.className = 'image-container';
            } else {
                console.log('Using existing container');
            }
            container.style.position = 'relative';
            container.style.width = '100%';
            container.style.height = '100%';
            container.style.display = 'flex';
            container.style.alignItems = 'center';
            container.style.justifyContent = 'center';
            container.style.pointerEvents = 'auto';
            
            const layerOrder = ['fit', 'sleeve', 'neck'];
            let zIndexCounter = 1;
            
            layerOrder.forEach((part) => {
                if (loadedImages[part]) {
                    console.log('Adding layer for:', part);
                    const wrapper = document.createElement('div');
                    wrapper.className = 'draggable-layer';
                    wrapper.style.position = 'absolute';
                    wrapper.style.cursor = 'move';
                    wrapper.style.zIndex = zIndexCounter++;
                    wrapper.style.pointerEvents = 'auto';
                    wrapper.setAttribute('data-part', part);
                    
                    const img = document.createElement('img');
                    img.src = loadedImages[part].src;
                    img.style.width = '400px';
                    img.style.height = '400px';
                    img.style.objectFit = 'contain';
                    img.style.filter = 'drop-shadow(0 10px 30px rgba(0,0,0,0.2))';
                    img.style.pointerEvents = 'none';
                    img.style.userSelect = 'none';
                    img.style.transition = 'all 0.3s ease';
                    
                    // Store scale on wrapper
                    wrapper.zoomScale = 1;
                    
                    wrapper.appendChild(img);
                    
                    container.appendChild(wrapper);
                    
                    // Set initial centered position after appending
                    const setInitialPosition = () => {
                        const cw = container.clientWidth;
                        const ch = container.clientHeight;
                        const ww = wrapper.offsetWidth;
                        const wh = wrapper.offsetHeight;
                        const x = (cw - ww) / 2;
                        const y = (ch - wh) / 2;
                        wrapper.style.left = x + 'px';
                        wrapper.style.top = y + 'px';
                    };
                    if (img.complete) {
                        setInitialPosition();
                    } else {
                        img.onload = setInitialPosition;
                    }
                    
                    // Make the shirt part draggable
                    makeDraggable(wrapper, container);
                    
                    // Add resize and lock controls
                    addLayerControls(wrapper, part);

                }
            });
            
            console.log('Container children count:', container.children.length);
            
            // Only append if not already in canvas
            if (!container.parentElement) {
                console.log('Appending container to canvas...');
                canvas.appendChild(container);
            } else {
                console.log('Container already in canvas');
            }
            console.log('Canvas now has children:', canvas.children.length);

            // Re-attach persisted logo on top, if any
            if (persistedLogoLayer) {
                console.log('Re-attaching persisted logo');
                persistedLogoLayer.style.zIndex = 10000;
                container.appendChild(persistedLogoLayer);
            }
            
            // Update layer thumbnails
            updateLayerThumbnails();
        }
        
        // Load only the parts specified in basePaths
        Object.keys(basePaths).forEach(partName => {
            console.log('Starting to load:', partName);
            tryLoadPart(partName, basePaths[partName]);
        });
    }
    
    // Helper function to convert color to hue rotation
    function getHueRotation(hexColor) {
        // Convert hex to RGB
        const r = parseInt(hexColor.substr(1, 2), 16);
        const g = parseInt(hexColor.substr(3, 2), 16);
        const b = parseInt(hexColor.substr(5, 2), 16);
        
        // Simple hue calculation
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        const delta = max - min;
        
        if (delta === 0) return 0;
        
        let hue = 0;
        if (max === r) {
            hue = ((g - b) / delta) % 6;
        } else if (max === g) {
            hue = (b - r) / delta + 2;
        } else {
            hue = (r - g) / delta + 4;
        }
        
        hue = Math.round(hue * 60);
        if (hue < 0) hue += 360;
        
        return hue;
    }
    
    // Initialize preview - Don't show shirt initially
    // updateShirtPreview();
    
    // Color selection (swatches + custom color picker)
    const colorPicker = document.getElementById('colorPicker');
    const colorHex = document.getElementById('colorHex');
    const colorHidden = document.getElementById('color_preference_1');
    // Initialize picker from hidden field if available
    if (colorHidden && colorHidden.value) {
        try { colorPicker.value = colorHidden.value; colorHex.textContent = colorHidden.value.toUpperCase(); } catch(e) {}
    }
    // Removed swatch palette; using only colorPicker
    if (colorPicker) {
        colorPicker.addEventListener('input', function(){
            const picked = this.value;
            shirtColor = picked;
            if (colorHex) { colorHex.textContent = picked.toUpperCase(); }
            if (colorHidden) { colorHidden.value = picked; }
            const canvas = document.getElementById('shirtCanvas');
            if (canvas && canvas.children.length > 1) {
                updateShirtPreview();
            }
        });
    }
    
    // Size type selection
    document.querySelectorAll('.size-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.size-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('size_type').value = this.getAttribute('data-type');
            sizeTypeSelected = true;
            validateForm();
        });
    });
    
    // Size selection
    document.querySelectorAll('.size-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('size_selected').value = this.getAttribute('data-size');
            sizeSelected = true;
            validateForm();
        });
    });
    
    // Add validation listeners to all measurement inputs
    const measurementInputs = [
        'chest_width', 'waist_width', 'hip_width', 'shoulder_width', 'sleeve_length', 'garment_length',
        'neck_circumference', 'arm_circumference', 'wrist_circumference', 'inseam_length'
    ];
    
    measurementInputs.forEach(name => {
        const input = document.querySelector(`input[name="${name}"]`);
        if (input) {
            input.addEventListener('input', validateForm);
            input.addEventListener('change', validateForm);
        }
    });
    
    // Add validation listeners to fabric selects
    const fabricType = document.querySelector('select[name="fabric_type"]');
    const fabricWeight = document.querySelector('select[name="fabric_weight"]');
    if (fabricType) {
        fabricType.addEventListener('change', validateForm);
    }
    if (fabricWeight) {
        fabricWeight.addEventListener('change', validateForm);
    }
    
    // Neck type selection with toggle
    console.log("Setting up neck buttons");
    document.querySelectorAll('[data-neck]').forEach(btn => {
        console.log("Found neck button:", btn);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const clickedNeck = this.getAttribute("data-neck");
            console.log("NECK CLICKED:", clickedNeck);
            
            // Toggle: if already selected, unselect it
            if (this.classList.contains('active')) {
                this.classList.remove('active');
                neckType = null;
                document.getElementById('neckline_type').value = '';
                document.getElementById('displayNeck').innerHTML = '<span style="color: #999;">Not selected</span>';
                // Remove the neck layer from canvas
                const canvas = document.getElementById('shirtCanvas');
                const container = canvas?.querySelector('.image-container');
                const neckLayer = container?.querySelector('[data-part="neck"]');
                if (neckLayer) {
                    container.removeChild(neckLayer);
                    updateLayerThumbnails();
                }
            } else {
                document.querySelectorAll('[data-neck]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                neckType = clickedNeck;
                document.getElementById('neckline_type').value = neckType;
                document.getElementById('displayNeck').textContent = this.textContent.trim();
                updateShirtPreview('neck'); // Only show neck image
            }
            validateForm(); // Validate after selection
        });
    });
    
    // Sleeve type selection with toggle
    console.log('Setting up sleeve buttons');
    document.querySelectorAll('[data-sleeve]').forEach(btn => {
        console.log('Found sleeve button:', btn);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const clickedSleeve = this.getAttribute('data-sleeve');
            console.log('SLEEVE CLICKED:', clickedSleeve);
            
            // Toggle: if already selected, unselect it
            if (this.classList.contains('active')) {
                this.classList.remove('active');
                sleeveType = null;
                document.getElementById('sleeve_type').value = '';
                document.getElementById('displaySleeve').innerHTML = '<span style="color: #999;">Not selected</span>';
                // Remove the sleeve layer from canvas
                const canvas = document.getElementById('shirtCanvas');
                const container = canvas?.querySelector('.image-container');
                const sleeveLayer = container?.querySelector('[data-part="sleeve"]');
                if (sleeveLayer) {
                    container.removeChild(sleeveLayer);
                    updateLayerThumbnails();
                }
            } else {
                document.querySelectorAll('[data-sleeve]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                sleeveType = clickedSleeve;
                document.getElementById('sleeve_type').value = sleeveType;
                document.getElementById('displaySleeve').textContent = this.textContent.trim();
                console.log('Calling updateShirtPreview for sleeve...');
                updateShirtPreview('sleeve'); // Only show sleeve image
            }
            validateForm(); // Validate after selection
        });
    });
    
    // Fit type selection with toggle
    console.log('Setting up fit buttons');
    document.querySelectorAll('[data-fit]').forEach(btn => {
        console.log('Found fit button:', btn);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const clickedFit = this.getAttribute('data-fit');
            console.log('FIT CLICKED:', clickedFit);
            
            // Toggle: if already selected, unselect it
            if (this.classList.contains('active')) {
                this.classList.remove('active');
                fitType = null;
                document.getElementById('fit_type').value = '';
                document.getElementById('displayFit').innerHTML = '<span style="color: #999;">Not selected</span>';
                // Remove the fit layer from canvas
                const canvas = document.getElementById('shirtCanvas');
                const container = canvas?.querySelector('.image-container');
                const fitLayer = container?.querySelector('[data-part="fit"]');
                if (fitLayer) {
                    container.removeChild(fitLayer);
                    updateLayerThumbnails();
                }
            } else {
                document.querySelectorAll('[data-fit]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                fitType = clickedFit;
                document.getElementById('fit_type').value = fitType;
                document.getElementById('displayFit').textContent = this.textContent.trim();
                console.log('Calling updateShirtPreview for fit...');
                updateShirtPreview('fit'); // Only show fit image
            }
            validateForm(); // Validate after selection
        });
    });
    
    // Zoom Slider Control
    const zoomSlider = document.getElementById('zoomSlider');
    const zoomDisplay = document.getElementById('zoomDisplay');
    
    if (zoomSlider) {
        zoomSlider.addEventListener('input', function() {
            const zoomValue = this.value / 100;
            zoomDisplay.textContent = this.value + '%';
            
            // Apply zoom to ALL visible images
            const allImages = document.querySelectorAll('.draggable-layer');
            console.log('Applying zoom', zoomValue, 'to', allImages.length, 'images');
            
            allImages.forEach(wrapper => {
                const img = wrapper.querySelector('img');
                if (img) {
                    wrapper.zoomScale = zoomValue;
                    img.style.width = (400 * zoomValue) + 'px';
                    img.style.height = (400 * zoomValue) + 'px';
                    
                    // Update individual zoom label if it exists
                    if (wrapper.zoomLabel) {
                        wrapper.zoomLabel.textContent = Math.round(zoomValue * 100) + '%';
                    }
                }
            });
        });
    }

    // Logo upload
    const logoUploadArea = document.getElementById('logoUploadArea');
    const fileInput = document.getElementById('reference_image');
    const uploadLogoBtn = document.getElementById('uploadLogoBtn');

    // Helper to place logo on canvas
    function placeLogo(imgSrc, dropX = null, dropY = null) {
        const canvas = document.getElementById('shirtCanvas');
        if (!canvas) return;
        // Ensure canvas can anchor absolute children and isn't blocked by placeholder
        canvas.style.position = canvas.style.position || 'relative';
        const placeholderMsg = canvas.querySelector('div[style*="color: #999"]');
        if (placeholderMsg) {
            canvas.removeChild(placeholderMsg);
        }
        // Get or create container
        let container = canvas.querySelector('.image-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'image-container';
            container.style.position = 'relative';
            container.style.width = '100%';
            container.style.height = '100%';
            container.style.display = 'flex';
            container.style.alignItems = 'center';
            container.style.justifyContent = 'center';
            container.style.pointerEvents = 'auto';
            canvas.appendChild(container);
        }
        // Remove previous logo layer if any
        const oldLogo = container.querySelector('[data-part="logo"]');
        if (oldLogo) container.removeChild(oldLogo);

        const wrapper = document.createElement('div');
        wrapper.className = 'draggable-layer';
        wrapper.setAttribute('data-part', 'logo');
        wrapper.style.position = 'absolute';
        wrapper.style.top = '0px';
        wrapper.style.left = '0px';
        wrapper.style.transform = '';
        wrapper.style.cursor = 'move';
        wrapper.style.zIndex = 9999;
        wrapper.style.pointerEvents = 'auto';
        wrapper.zoomScale = 1;

        const img = document.createElement('img');
        img.src = imgSrc;
        img.style.width = '250px';
        img.style.height = '250px';
        img.style.objectFit = 'contain';
        img.style.pointerEvents = 'none';
        img.style.userSelect = 'none';
        img.style.transition = 'all 0.3s ease';
        wrapper.appendChild(img);

        // Add delete (X) button
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.title = 'Remove logo';
        closeBtn.textContent = '×';
        closeBtn.style.position = 'absolute';
        closeBtn.style.top = '6px';
        closeBtn.style.right = '6px';
        closeBtn.style.width = '24px';
        closeBtn.style.height = '24px';
        closeBtn.style.lineHeight = '20px';
        closeBtn.style.border = 'none';
        closeBtn.style.borderRadius = '50%';
        closeBtn.style.background = '#e53e3e';
        closeBtn.style.color = '#fff';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.fontWeight = '700';
        closeBtn.style.zIndex = '10001';
        closeBtn.style.pointerEvents = 'auto';
        closeBtn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            if (wrapper && wrapper.parentNode) {
                wrapper.parentNode.removeChild(wrapper);
            }
            persistedLogoLayer = null;
            uploadedLogo = null;
            const area = document.getElementById('logoUploadArea');
            if (area) {
                area.innerHTML = '<div class="upload-icon">+</div>';
                const input = document.getElementById('reference_image');
                if (input) {
                    input.value = '';
                    area.appendChild(input);
                }
            }
            // Update thumbnails after removing logo
            updateLayerThumbnails();
        });
        wrapper.appendChild(closeBtn);

        container.appendChild(wrapper);
        const setInitialPosition = () => {
            const cw = container.clientWidth;
            const ch = container.clientHeight;
            const ww = wrapper.offsetWidth;
            const wh = wrapper.offsetHeight;
            let x, y;
            if (dropX !== null && dropY !== null) {
                const rect = container.getBoundingClientRect();
                x = dropX - rect.left - ww / 2;
                y = dropY - rect.top - wh / 2;
            } else {
                x = (cw - ww) / 2;
                y = (ch - wh) / 2;
            }
            x = Math.max(0, Math.min(x, cw - ww));
            y = Math.max(0, Math.min(y, ch - wh));
            wrapper.style.left = x + 'px';
            wrapper.style.top = y + 'px';
        };
        if (img.complete) {
            setInitialPosition();
        } else {
            img.onload = setInitialPosition;
        }
        makeDraggable(wrapper, container);
        
        // Add resize and lock controls to logo
        addLayerControls(wrapper, 'logo');
        
        persistedLogoLayer = wrapper;
        
        // Update thumbnails to include logo
        updateLayerThumbnails();
    }
    if (uploadLogoBtn) {
        uploadLogoBtn.addEventListener('click', function() {
            if (uploadedLogo) {
                // Re-place existing uploaded logo without opening file chooser
                placeLogo(uploadedLogo);
            } else {
                fileInput.click();
            }
        });
    }

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                uploadedLogo = event.target.result;
                var imgSrc = event.target.result;
                logoUploadArea.innerHTML = "<img src=\"" + imgSrc + "\" style=\"max-width:100%;max-height:100%;object-fit:contain;border-radius:12px\">";
                // Ensure the file input remains inside the upload area (so it posts with the form)
                logoUploadArea.appendChild(fileInput);
                placeLogo(imgSrc);
            };
            reader.readAsDataURL(file);
        }
    });

    const shirtCanvas = document.getElementById('shirtCanvas');
    if (shirtCanvas) {
        shirtCanvas.addEventListener('dragover', function(e){ e.preventDefault(); });
        shirtCanvas.addEventListener('drop', function(e){
            e.preventDefault();
            const dt = e.dataTransfer;
            if (!dt || !dt.files || dt.files.length === 0) return;
            const file = dt.files[0];
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function(ev){
                uploadedLogo = ev.target.result;
                logoUploadArea.innerHTML = "<img src=\"" + uploadedLogo + "\" style=\"max-width:100%;max-height:100%;object-fit:contain;border-radius:12px\">";
                logoUploadArea.appendChild(fileInput);
                placeLogo(uploadedLogo, e.clientX, e.clientY);
            };
            reader.readAsDataURL(file);
        });
    }

    // Payment & Delivery Modal
    const paymentModal = document.getElementById('paymentModal');
    const paymentMethod = document.getElementById('paymentMethod');
    const deliveryMode = document.getElementById('deliveryMode');
    const deliveryAddress = document.getElementById('deliveryAddress');
    const addressSection = document.getElementById('addressSection');
    const paymentError = document.getElementById('paymentError');
    const cancelPayment = document.getElementById('cancelPayment');
    const proceedToConfirm = document.getElementById('proceedToConfirm');
    const closePayment = document.getElementById('closePayment');
    
    function openPaymentModal(){ paymentModal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function closePaymentModal(){ paymentModal.style.display = 'none'; document.body.style.overflow = ''; }
    
    // Fetch user's default address
    async function fetchUserAddress() {
        try {
            // First, get list of addresses to find default one
            const response = await fetch('get_user_addresses.php');
            const data = await response.json();
            
            console.log('Address data:', data); // Debug
            
            if (data.success && data.addresses && data.addresses.length > 0) {
                // Find default address or use first one
                const addr = data.addresses.find(a => a.is_default == 1) || data.addresses[0];
                console.log('Selected address:', addr); // Debug
                
                const fullAddress = `${addr.name || ''}\n${addr.phone || ''}\n${addr.street || ''}, ${addr.barangay || ''}, ${addr.city || ''}, ${addr.province || ''} ${addr.zip_code || ''}`;
                
                const addressTextEl = document.getElementById('addressText');
                const deliveryAddressEl = document.getElementById('deliveryAddress');
                const addressDisplayEl = document.getElementById('addressDisplay');
                const changeAddressBtnEl = document.getElementById('changeAddressBtn');
                const noAddressMsgEl = document.getElementById('noAddressMsg');
                
                if (addressTextEl) addressTextEl.textContent = fullAddress;
                if (deliveryAddressEl) deliveryAddressEl.value = fullAddress;
                if (addressDisplayEl) addressDisplayEl.style.display = 'block';
                if (changeAddressBtnEl) changeAddressBtnEl.style.display = 'block';
                if (noAddressMsgEl) noAddressMsgEl.style.display = 'none';
            } else {
                console.log('No addresses found');
                const addressDisplayEl = document.getElementById('addressDisplay');
                const changeAddressBtnEl = document.getElementById('changeAddressBtn');
                const noAddressMsgEl = document.getElementById('noAddressMsg');
                
                if (addressDisplayEl) addressDisplayEl.style.display = 'none';
                if (changeAddressBtnEl) changeAddressBtnEl.style.display = 'none';
                if (noAddressMsgEl) noAddressMsgEl.style.display = 'block';
            }
        } catch (error) {
            console.error('Error fetching address:', error);
            const noAddressMsgEl = document.getElementById('noAddressMsg');
            if (noAddressMsgEl) noAddressMsgEl.style.display = 'block';
        }
    }
    
    // Show/hide address field based on delivery mode
    if (deliveryMode) {
        deliveryMode.addEventListener('change', function() {
            if (this.value === 'pickup') {
                addressSection.style.display = 'none';
            } else if (this.value) {
                addressSection.style.display = 'block';
                fetchUserAddress(); // Fetch address when delivery mode is selected
            }
        });
    }
    
    // Change address button
    const changeAddressBtn = document.getElementById('changeAddressBtn');
    if (changeAddressBtn) {
        changeAddressBtn.addEventListener('click', function() {
            window.open('profile.php?tab=addresses', '_blank');
        });
    }
    
    // Confirmation modal before submit
    const formEl = document.getElementById('customizationForm');
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmDetails = document.getElementById('confirmDetails');
    const cancelConfirm = document.getElementById('cancelConfirm');
    const proceedSubmit = document.getElementById('proceedSubmit');
    const closeConfirm = document.getElementById('closeConfirm');
    function openConfirmModal(){ confirmationModal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    function closeConfirmModal(){ confirmationModal.style.display = 'none'; document.body.style.overflow = ''; }
    
    // Function to capture the shirt canvas and display in confirmation
    function captureCanvasPreview() {
        const sourceCanvas = document.getElementById('shirtCanvas');
        const confirmCanvas = document.getElementById('confirmCanvas');
        
        if (!sourceCanvas || !confirmCanvas) {
            console.error('Canvas elements not found');
            return;
        }
        
        // Get the dimensions of the source canvas
        const sourceRect = sourceCanvas.getBoundingClientRect();
        
        // Set canvas size (smaller for modal display)
        const scale = 0.5; // 50% of original size
        confirmCanvas.width = sourceRect.width * scale;
        confirmCanvas.height = sourceRect.height * scale;
        
        const ctx = confirmCanvas.getContext('2d');
        
        // Fill with white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, confirmCanvas.width, confirmCanvas.height);
        
        // Clone the entire shirtCanvas content
        const cloneContainer = sourceCanvas.cloneNode(true);
        
        // Use html2canvas if available, otherwise create a simple image capture
        if (typeof html2canvas !== 'undefined') {
            html2canvas(sourceCanvas, {
                backgroundColor: '#ffffff',
                scale: scale,
                logging: false
            }).then(canvas => {
                ctx.drawImage(canvas, 0, 0, confirmCanvas.width, confirmCanvas.height);
            });
        } else {
            // Fallback: Draw shirt preview elements manually
            // Get all images in the canvas
            const images = sourceCanvas.querySelectorAll('img');
            let loadedCount = 0;
            const totalImages = images.length;
            
            if (totalImages === 0) {
                // No images, just draw a placeholder
                ctx.fillStyle = '#f0f0f0';
                ctx.fillRect(0, 0, confirmCanvas.width, confirmCanvas.height);
                ctx.fillStyle = '#666';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('Your Design', confirmCanvas.width / 2, confirmCanvas.height / 2);
                return;
            }
            
            images.forEach((img, index) => {
                const tempImg = new Image();
                tempImg.crossOrigin = 'anonymous';
                tempImg.onload = function() {
                    const imgRect = img.getBoundingClientRect();
                    const canvasRect = sourceCanvas.getBoundingClientRect();
                    
                    // Calculate relative position
                    const x = (imgRect.left - canvasRect.left) * scale;
                    const y = (imgRect.top - canvasRect.top) * scale;
                    const w = imgRect.width * scale;
                    const h = imgRect.height * scale;
                    
                    ctx.drawImage(tempImg, x, y, w, h);
                    
                    loadedCount++;
                };
                tempImg.onerror = function() {
                    loadedCount++;
                };
                tempImg.src = img.src;
            });
        }
    }
    
    if (formEl && paymentModal && confirmationModal && confirmDetails && cancelConfirm && proceedSubmit) {
        formEl.addEventListener('submit', function(e){
            e.preventDefault();
            // Skip payment modal, go directly to confirmation
            const neck = document.getElementById('displayNeck') ? document.getElementById('displayNeck').textContent.trim() : '';
            const sleeve = document.getElementById('displaySleeve') ? document.getElementById('displaySleeve').textContent.trim() : '';
            const fit = document.getElementById('displayFit') ? document.getElementById('displayFit').textContent.trim() : '';
            const color = document.getElementById('color_preference_1') ? document.getElementById('color_preference_1').value : '';
            const size = document.getElementById('size_selected') ? document.getElementById('size_selected').value : '';
            const sizeType = document.getElementById('size_type') ? document.getElementById('size_type').value : '';
            const desc = document.getElementById('description') ? document.getElementById('description').value : '';
            const hasLogo = document.querySelector('#shirtCanvas [data-part="logo"] img') ? 'Yes' : 'No';
            
            // Get new fields
            const budgetMin = document.querySelector('[name="budget_min"]') ? document.querySelector('[name="budget_min"]').value : '';
            const budgetMax = document.querySelector('[name="budget_max"]') ? document.querySelector('[name="budget_max"]').value : '';
            const quantity = document.querySelector('[name="quantity"]') ? document.querySelector('[name="quantity"]').value : '1';
            const deadline = document.querySelector('[name="deadline"]') ? document.querySelector('[name="deadline"]').value : '';
            const purpose = document.querySelector('[name="garment_purpose"]') ? document.querySelector('[name="garment_purpose"]').selectedOptions[0].text : '';
            const occasion = document.querySelector('[name="occasion"]') ? document.querySelector('[name="occasion"]').value : '';
            const fabricType = document.querySelector('[name="fabric_type"]') ? document.querySelector('[name="fabric_type"]').selectedOptions[0].text : '';
            const fabricWeight = document.querySelector('[name="fabric_weight"]') ? document.querySelector('[name="fabric_weight"]').selectedOptions[0].text : '';
            const hasImage2 = document.querySelector('[name="reference_image_2"]') && document.querySelector('[name="reference_image_2"]').files.length > 0 ? 'Yes' : 'No';
            const hasImage3 = document.querySelector('[name="reference_image_3"]') && document.querySelector('[name="reference_image_3"]').files.length > 0 ? 'Yes' : 'No';
            const specialInstructions = document.querySelector('[name="special_instructions"]') ? document.querySelector('[name="special_instructions"]').value : '';
            
            // Get main measurements
            const chest = document.querySelector('[name="chest_width"]') ? document.querySelector('[name="chest_width"]').value : '';
            const waist = document.querySelector('[name="waist_width"]') ? document.querySelector('[name="waist_width"]').value : '';
            const hip = document.querySelector('[name="hip_width"]') ? document.querySelector('[name="hip_width"]').value : '';
            const shoulder = document.querySelector('[name="shoulder_width"]') ? document.querySelector('[name="shoulder_width"]').value : '';
            const sleeveLen = document.querySelector('[name="sleeve_length"]') ? document.querySelector('[name="sleeve_length"]').value : '';
            const garmentLen = document.querySelector('[name="garment_length"]') ? document.querySelector('[name="garment_length"]').value : '';
            const hasMainMeasurements = chest || waist || hip || shoulder || sleeveLen || garmentLen;
            
            // Get extra measurements
            const neckCirc = document.querySelector('[name="neck_circumference"]') ? document.querySelector('[name="neck_circumference"]').value : '';
            const armCirc = document.querySelector('[name="arm_circumference"]') ? document.querySelector('[name="arm_circumference"]').value : '';
            const wristCirc = document.querySelector('[name="wrist_circumference"]') ? document.querySelector('[name="wrist_circumference"]').value : '';
            const inseam = document.querySelector('[name="inseam_length"]') ? document.querySelector('[name="inseam_length"]').value : '';
            const hasExtraMeasurements = neckCirc || armCirc || wristCirc || inseam;
            
            let budgetText = '';
            if (budgetMin && budgetMax) {
                budgetText = '₱' + budgetMin + ' - ₱' + budgetMax;
            } else if (budgetMin) {
                budgetText = '₱' + budgetMin + '+';
            } else if (budgetMax) {
                budgetText = 'Up to ₱' + budgetMax;
            } else {
                budgetText = 'Not specified';
            }
            
            confirmDetails.innerHTML =
                '<div class="modal-section">' +
                    '<div class="modal-section-title">📐 DESIGN DETAILS</div>' +
                    '<div class="modal-section-content">' +
                        '<div><strong>Neck:</strong> ' + neck + '</div>' +
                        '<div><strong>Sleeves:</strong> ' + sleeve + '</div>' +
                        '<div><strong>Fit:</strong> ' + fit + '</div>' +
                        '<div><strong>Color:</strong> ' + color + '</div>' +
                        '<div><strong>Size:</strong> ' + size + ' (' + sizeType + ')</div>' +
                    '</div>' +
                '</div>' +
                (hasMainMeasurements ? '<div class="modal-section">' +
                    '<div class="modal-section-title">📏 MAIN MEASUREMENTS (inches)</div>' +
                    '<div class="modal-section-content">' +
                        (chest ? '<div><strong>Chest:</strong> ' + chest + '"</div>' : '') +
                        (waist ? '<div><strong>Waist:</strong> ' + waist + '"</div>' : '') +
                        (hip ? '<div><strong>Hip:</strong> ' + hip + '"</div>' : '') +
                        (shoulder ? '<div><strong>Shoulder:</strong> ' + shoulder + '"</div>' : '') +
                        (sleeveLen ? '<div><strong>Sleeve Length:</strong> ' + sleeveLen + '"</div>' : '') +
                        (garmentLen ? '<div><strong>Garment Length:</strong> ' + garmentLen + '"</div>' : '') +
                    '</div>' +
                '</div>' : '') +
                (hasExtraMeasurements ? '<div class="modal-section">' +
                    '<div class="modal-section-title">📐 EXTRA MEASUREMENTS (inches)</div>' +
                    '<div class="modal-section-content">' +
                        (neckCirc ? '<div><strong>Neck:</strong> ' + neckCirc + '"</div>' : '') +
                        (armCirc ? '<div><strong>Arm:</strong> ' + armCirc + '"</div>' : '') +
                        (wristCirc ? '<div><strong>Wrist:</strong> ' + wristCirc + '"</div>' : '') +
                        (inseam ? '<div><strong>Inseam:</strong> ' + inseam + '"</div>' : '') +
                    '</div>' +
                '</div>' : '') +
                '<div class="modal-section">' +
                    '<div class="modal-section-title">📦 ORDER DETAILS</div>' +
                    '<div class="modal-section-content">' +
                        '<div><strong>Quantity:</strong> ' + quantity + ' piece(s)</div>' +
                        (purpose && purpose !== 'Select' ? '<div><strong>Purpose:</strong> ' + purpose + '</div>' : '') +
                        (occasion ? '<div><strong>Occasion:</strong> ' + occasion + '</div>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="modal-section">' +
                    '<div class="modal-section-title">🧵 FABRIC & IMAGES</div>' +
                    '<div class="modal-section-content">' +
                        (fabricType && fabricType !== 'Any' ? '<div><strong>Fabric Type:</strong> ' + fabricType + '</div>' : '') +
                        (fabricWeight && fabricWeight !== 'Any' ? '<div><strong>Fabric Weight:</strong> ' + fabricWeight + '</div>' : '') +
                        '<div><strong>Logo/Main:</strong> ' + hasLogo + '</div>' +
                        '<div><strong>Additional:</strong> ' + (hasImage2 === 'Yes' || hasImage3 === 'Yes' ? 'Yes' : 'No') + '</div>' +
                    '</div>' +
                '</div>' +
                (specialInstructions ? '<div class="modal-section" style="grid-column: 1 / -1;">' +
                    '<div class="modal-section-title">📝 SPECIAL INSTRUCTIONS</div>' +
                    '<div class="modal-section-content"><em style="color: #666;">' + specialInstructions + '</em></div>' +
                '</div>' : '');
            
            // Capture and display the shirt canvas preview
            captureCanvasPreview();
            
            // Show confirmation modal directly
            openConfirmModal();
        });
        
        // Payment modal handlers
        cancelPayment.addEventListener('click', closePaymentModal);
        if (closePayment) closePayment.addEventListener('click', closePaymentModal);
        
        proceedToConfirm.addEventListener('click', function() {
            // Validate payment fields
            const payment = paymentMethod.value;
            const delivery = deliveryMode.value;
            const address = deliveryAddress.value;
            
            if (!payment || !delivery) {
                paymentError.style.display = 'block';
                return;
            }
            
            if (delivery !== 'pickup' && !address) {
                paymentError.style.display = 'block';
                return;
            }
            
            paymentError.style.display = 'none';
            
            // Get payment/delivery details for confirmation
            const paymentText = payment === 'cod' ? 'Cash on Delivery' : payment === 'gcash' ? 'GCash' : payment;
            const deliveryText = delivery === 'pickup' ? 'Pick Up' : delivery === 'lalamove' ? 'Lalamove' : delivery === 'jnt' ? 'J&T Express' : delivery;
            
            // Add payment & delivery to confirmation with better formatting
            const confirmHTML = confirmDetails.innerHTML;
            const paymentDeliveryHTML = '<div class="modal-section">' +
                '<div class="modal-section-title">💳 PAYMENT & DELIVERY</div>' +
                '<div class="modal-section-content">' +
                    '<div><strong>Payment:</strong> ' + paymentText + '</div>' +
                    '<div><strong>Delivery:</strong> ' + deliveryText + '</div>' +
                    (delivery !== 'pickup' && address ? '<div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #e9ecef;"><strong>Address:</strong><br><span style="color: #666; font-size: 11px;">' + address + '</span></div>' : '') +
                '</div>' +
            '</div>';
            
            // Insert before the status badge
            const parts = confirmHTML.split('<div style="grid-column: 1 / -1; text-align: center;">');
            confirmDetails.innerHTML = parts[0] + paymentDeliveryHTML + '<div style="grid-column: 1 / -1; text-align: center;">' + parts[1];
            
            // Capture and display the shirt canvas preview
            captureCanvasPreview();
            
            // Close payment modal and open confirmation
            closePaymentModal();
            openConfirmModal();
        });
        
        cancelConfirm.addEventListener('click', closeConfirmModal);
        if (closeConfirm) closeConfirm.addEventListener('click', closeConfirmModal);
        proceedSubmit.addEventListener('click', async function(){
            const btn = proceedSubmit;
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = 'Submitting...';
            const fd = new FormData(formEl);
            
            // Capture canvas as image blob
            const sourceCanvas = document.getElementById('shirtCanvas');
            if (sourceCanvas && typeof html2canvas !== 'undefined') {
                try {
                    const canvas = await html2canvas(sourceCanvas, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        logging: false
                    });
                    
                    // Convert canvas to blob
                    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                    if (blob) {
                        fd.append('canvas_preview', blob, 'canvas_preview.png');
                    }
                } catch (e) {
                    console.error('Canvas capture error:', e);
                }
            }
            
            // Ensure PHP detects the submit action
            fd.append('submit_request', '1');
            fd.append('ajax_submit', '1');
            try {
                const res = await fetch('customization.php', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const text = await res.text();
                let data = null;
                try { data = JSON.parse(text); } catch (e) { /* not JSON */ }
                if (!res.ok) {
                    alert('Submit failed (HTTP ' + res.status + ').');
                } else if (data && data.unauthenticated) {
                    // Not logged in, redirect to login page
                    window.location.href = data.login || 'login.php?redirect=customization.php';
                } else if (data && data.ok) {
                    closeConfirmModal();
                    const toast = document.getElementById('toast');
                    if (toast) {
                        toast.style.display = 'block';
                        setTimeout(() => { toast.style.display = 'none'; }, 1200);
                    }
                    setTimeout(() => {
                        window.location.href = 'my_orders.php?submitted=1&request_id=' + encodeURIComponent(data.request_id) + '#customization';
                    }, 900);
                } else {
                    // Show a snippet of server response to help debugging
                    const snippet = text ? text.slice(0, 300) : '';
                    alert('Failed to submit. ' + (data && data.error ? data.error : snippet));
                }
            } catch (err) {
                alert('Network error while submitting.');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
        confirmationModal.addEventListener('click', function(e){
            if (e.target === confirmationModal) {
                closeConfirmModal();
            }
        });
    }
});
</script>

<!-- html2canvas library for capturing canvas preview -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

</form>

<?php
// Include footer
include 'footer.php';
?>













