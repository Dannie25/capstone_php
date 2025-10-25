<?php
session_start();
require_once '../db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ;
    header('Location: login.php');
    exit();
}
if (isset($_POST['delete_site_content'])) {
    $content_key = trim($_POST['content_key']);
    if ($content_key !== '') {
        $stmt = $conn->prepare("DELETE FROM site_content WHERE content_key = ?");
        $stmt->bind_param("s", $content_key);
        $stmt->execute();
        $stmt->close();
        $success = "Site content deleted!";
    }
}
// Section/table selector: home => site_content, about => about_content, map => about_map, chatbot => chatbot_faqs
$section = 'home';
if (isset($_GET['section'])) {
    if ($_GET['section'] === 'about') $section = 'about';
    elseif ($_GET['section'] === 'map') $section = 'map';
    elseif ($_GET['section'] === 'chatbot') $section = 'chatbot';
}
$tableName = $section === 'about' ? 'about_content' : ($section === 'map' ? 'about_map' : ($section === 'chatbot' ? 'chatbot_faqs' : 'site_content'));

// Handle content edits (generic for both tables), keep backward compatibility
$success = '';
$error = '';
if (isset($_POST['save_content']) || isset($_POST['save_site_content'])) {
    $currentSection = 'home';
    if (isset($_POST['section'])) {
        if ($_POST['section'] === 'about') $currentSection = 'about';
        elseif ($_POST['section'] === 'map') $currentSection = 'map';
        elseif ($_POST['section'] === 'chatbot') $currentSection = 'chatbot';
    }
    $currentTable = $currentSection === 'about' ? 'about_content' : ($currentSection === 'map' ? 'about_map' : ($currentSection === 'chatbot' ? 'chatbot_faqs' : 'site_content'));
    $content_key = trim($_POST['content_key'] ?? '');
    $content_value = $_POST['content_value'] ?? '';
    if ($content_key !== '') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM {$currentTable} WHERE content_key = ?");
        $stmt->bind_param("s", $content_key);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $stmt = $conn->prepare("UPDATE {$currentTable} SET content_value = ? WHERE content_key = ?");
            $stmt->bind_param("ss", $content_value, $content_key);
        } else {
            $stmt = $conn->prepare("INSERT INTO {$currentTable} (content_key, content_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $content_key, $content_value);
        }
        $stmt->execute();
        $stmt->close();
        $success = ucfirst($currentSection) . " content saved!";
        // Keep on same section after save
        $section = $currentSection;
        $tableName = $currentTable;
    }
}

// Handle deletes (generic)
if (isset($_POST['delete_content']) || isset($_POST['delete_site_content'])) {
    $currentSection = 'home';
    if (isset($_POST['section'])) {
        if ($_POST['section'] === 'about') $currentSection = 'about';
        elseif ($_POST['section'] === 'map') $currentSection = 'map';
    }
    $currentTable = $currentSection === 'about' ? 'about_content' : ($currentSection === 'map' ? 'about_map' : 'site_content');
    $content_key = trim($_POST['content_key'] ?? '');
    if ($content_key !== '') {
        $stmt = $conn->prepare("DELETE FROM {$currentTable} WHERE content_key = ?");
        $stmt->bind_param("s", $content_key);
        $stmt->execute();
        $stmt->close();
        $success = ucfirst($currentSection) . " content deleted!";
        $section = $currentSection;
        $tableName = $currentTable;
    }
}

// Ensure chatbot table exists if accessing chatbot section
if ($section === 'chatbot') {
    // Import recommended FAQs
    if (isset($_POST['import_defaults'])) {
        // Define recommended FAQs (question, answer)
        $defaults = [
            ['What are your store hours?', 'Our online store is open 24/7. Support hours are Monday–Friday, 9:00 AM–6:00 PM.'],
            ['How long does shipping take?', 'Standard shipping usually takes 3–7 business days, depending on your location and courier timelines.'],
            ['How much is the shipping fee?', 'Shipping fee is calculated at checkout based on your address and courier rates.'],
            ['Do you deliver to my area?', 'We ship nationwide. Enter your address at checkout to confirm delivery and fees.'],
            ['How can I track my order?', 'Go to My Orders after logging in to view status and tracking info once available.'],
            ['What payment methods do you accept?', 'We accept GCash and other available payment options shown at checkout. COD may be available in select locations.'],
            ['Do you accept Cash on Delivery (COD)?', 'Yes, COD is available for select areas. You’ll see the option at checkout if eligible.'],
            ['Can I pay via GCash?', 'Yes. Choose GCash at checkout and follow the instructions. Upload proof if prompted.'],
            ['What is your return policy?', 'Returns are accepted within 7 days of delivery for unused items with tags and original packaging. Contact support to start a return.'],
            ['How do I request an exchange?', 'Contact us with your order number and item details. Exchanges depend on stock availability.'],
            ['Can I cancel my order?', 'You can cancel before the order is processed or shipped. Visit My Orders or contact support.'],
            ['How do I change my delivery address after ordering?', 'If the order isn’t shipped yet, contact support immediately so we can update the address.'],
            ['Do you offer size guides?', 'Yes. Check the Size Guide on the product page. If unsure, message us your measurements for assistance.'],
            ['What are the fabric and care instructions?', 'Fabric details and care instructions are listed on each product page. Generally, wash gently and avoid high heat.'],
            ['Do you have custom clothing or personalization?', 'Yes. Visit the Create Your Style page for customization requests and details.'],
            ['Do you accept bulk or sub-contract (SUB-CON) orders?', 'We handle bulk/SUB-CON orders. Visit the SUB-CON page or contact us with your requirements.'],
            ['An item I want is out of stock. Will it be restocked?', 'Popular items are restocked when possible. Click “Notify me” if available or check back soon.'],
            ['Do you offer discounts or promotions?', 'Yes. Follow our announcements and check the homepage for ongoing promos and voucher codes.'],
            ['I entered a promo code but it didn’t work. Why?', 'Codes may have conditions (minimum spend, specific items, expiry). Check the terms and try again.'],
            ['How can I contact customer support?', 'Use the Help & Support page, the chat/inquiry page, or email us. We respond during support hours.'],
        ];

        // Get current max sort_order
        $maxOrder = 0;
        if ($res = $conn->query("SELECT COALESCE(MAX(sort_order), 0) AS m FROM chatbot_faqs")) {
            $row = $res->fetch_assoc();
            $maxOrder = (int)$row['m'];
            $res->close();
        }
        $inserted = 0;
        // Prepare statements
        $check = $conn->prepare("SELECT id FROM chatbot_faqs WHERE question = ? LIMIT 1");
        $ins = $conn->prepare("INSERT INTO chatbot_faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, 1)");
        foreach ($defaults as $idx => $pair) {
            [$q,$a] = $pair;
            $check->bind_param('s', $q);
            $check->execute();
            $check->store_result();
            if ($check->num_rows === 0) {
                $order = $maxOrder + $idx + 1;
                $ins->bind_param('ssi', $q, $a, $order);
                $ins->execute();
                $inserted++;
            }
            $check->free_result();
        }
        $check->close();
        $ins->close();
        $success = $inserted > 0 ? ("Imported $inserted FAQs") : 'All recommended FAQs already exist';
    }
    $conn->query("CREATE TABLE IF NOT EXISTS chatbot_faqs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(255) NOT NULL,
        answer TEXT NOT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Handle Chatbot FAQ actions
if ($section === 'chatbot') {
    if (isset($_POST['add_faq'])) {
        $q = trim($_POST['question'] ?? '');
        $a = trim($_POST['answer'] ?? '');
        $sort = intval($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($q !== '' && $a !== '') {
            $stmt = $conn->prepare("INSERT INTO chatbot_faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssii", $q, $a, $sort, $active);
            $stmt->execute();
            $stmt->close();
            $success = "FAQ added";
        } else {
            $error = "Question and Answer are required";
        }
    }
    if (isset($_POST['update_faq'])) {
        $id = intval($_POST['id'] ?? 0);
        $q = trim($_POST['question'] ?? '');
        $a = trim($_POST['answer'] ?? '');
        $sort = intval($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($id > 0 && $q !== '' && $a !== '') {
            $stmt = $conn->prepare("UPDATE chatbot_faqs SET question=?, answer=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssiii", $q, $a, $sort, $active, $id);
            $stmt->execute();
            $stmt->close();
            $success = "FAQ updated";
        } else {
            $error = "Invalid FAQ data";
        }
    }
    if (isset($_POST['delete_faq'])) {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM chatbot_faqs WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $success = "FAQ deleted";
        }
    }
}

// Load data for selected section
$site_content = [];
if ($section === 'chatbot') {
    $result = $conn->query("SELECT * FROM chatbot_faqs ORDER BY sort_order ASC, id ASC");
    while ($row = $result->fetch_assoc()) { $site_content[] = $row; }
} else {
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY content_key");
    while ($row = $result->fetch_assoc()) { $site_content[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management System</title>
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
        
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --border-color: #dee2e6;
            --text-muted: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(91, 107, 70, 0.2);
            margin-bottom: 40px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            color: white;
            font-weight: 700;
            margin: 0;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-title i {
            font-size: 1.8rem;
        }
        
        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(91, 107, 70, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            border: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            padding: 20px 25px;
            border-bottom: none;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Table Styling */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }
        
        /* Scrollable table body with sticky header (approx 6 rows visible) */
        .table-container {
            max-height: 480px;
            overflow: auto;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            background: white;
        }
        .table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        
        .table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%);
            color: #5b6b46;
            font-weight: 700;
            padding: 16px;
            text-align: left;
            border-bottom: 2px solid #d9e6a7;
            white-space: nowrap;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            background: white;
            font-size: 14px;
        }

        /* Truncate long content values but show full on hover via title */
        .editable-text {
            max-width: 520px; /* adjust as needed */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table tbody tr {
            transition: all 0.3s;
        }
        
        .table tbody tr:hover td {
            background-color: rgba(217, 230, 167, 0.15);
            transform: scale(1.01);
        }
        
        /* Buttons */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 18px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background: linear-gradient(135deg, #0069d9 0%, #004085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
        }
        
        .btn i {
            margin-right: 6px;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71d2a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
        
        .action-buttons .btn {
            padding: 6px 12px;
            min-width: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .action-buttons .btn i {
            margin: 0;
        }
        
        .action-buttons .btn-danger {
            min-width: auto;
            width: 34px;
            padding: 6px;
        }
        
        .btn-outline-primary {
            color: #5b6b46;
            border: 2px solid #5b6b46;
            background: white;
            font-weight: 600;
        }
        
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            border-color: #5b6b46;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(91, 107, 70, 0.3);
        }
        
        .back-btn {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            box-shadow: 0 4px 12px rgba(91, 107, 70, 0.2);
            font-weight: 600;
            font-size: 14px;
        }
        
        .back-btn:hover {
            background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
            box-shadow: 0 6px 16px rgba(91, 107, 70, 0.3);
        }
        
        /* Form Elements */
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-control:focus {
            border-color: #5b6b46;
            box-shadow: 0 0 0 3px rgba(91, 107, 70, 0.15);
            outline: none;
            transform: translateY(-1px);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: 8px;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
            
            .table-responsive {
                border: 1px solid var(--border-color);
                border-radius: 6px;
                overflow: hidden;
            }
        }
        
        /* Add margin to the form section to create space below the header */
        .form-section {
            padding: 25px;
            margin-top: 0;
        }
        
        .alert {
            border-radius: 12px;
            padding: 16px 20px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .edit-content {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(91, 107, 70, 0.2);
        }
        
        .edit-content:hover, .edit-content:focus {
            background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(91, 107, 70, 0.3);
        }
        
        .edit-faq {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(91, 107, 70, 0.2);
        }
        
        .edit-faq:hover {
            background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(91, 107, 70, 0.3);
        }
        
        /* Search Bar */
        .input-group {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            border: none;
            padding: 12px 16px;
            font-size: 16px;
        }
        
        .input-group .form-control {
            border: 2px solid #e8e8e8;
            border-left: none;
            border-radius: 0 12px 12px 0 !important;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #d9e6a7;
            margin-bottom: 20px;
        }
        
        .empty-state h4 {
            color: #5b6b46;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .empty-state p {
            color: #666;
            font-size: 16px;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 20px 25px;
            border: none;
        }
        
        .modal-title {
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e8e8e8;
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #545b62 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(108, 117, 125, 0.4);
        }
        
        /* Form Floating */
        .form-floating > label {
            color: #5b6b46;
            font-weight: 600;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #5b6b46;
        }
        
        /* Content Key Badge */
        .content-key {
            font-weight: 600;
            color: #5b6b46;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1><i class="bi bi-file-earmark-text"></i> Content Management System</h1>
        </div>
        
        <div class="content-body">
            <div class="container-fluid">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Content / Chatbot Card -->
        <div class="content-card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-plus-circle text-success"></i>
                    <?php if ($section==='chatbot'): ?>Add New Chatbot FAQ<?php else: ?>Add New Content (<?php echo $section==='about' ? 'About' : 'Home'; ?>)<?php endif; ?>
                </h3>
            </div>
            <div class="form-section">
                <?php if ($section==='chatbot'): ?>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="section" value="chatbot">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" name="question" class="form-control" id="faqQuestion" placeholder="Question" required>
                            <label for="faqQuestion"><i class="fas fa-question-circle me-2"></i>Question</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" name="sort_order" class="form-control" id="faqOrder" placeholder="Order" value="0">
                            <label for="faqOrder"><i class="fas fa-sort-numeric-down me-2"></i>Order</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating">
                            <textarea name="answer" class="form-control" id="faqAnswer" placeholder="Answer" style="height: 120px" required></textarea>
                            <label for="faqAnswer"><i class="fas fa-reply me-2"></i>Answer</label>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-center gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="faqActive" checked>
                            <label class="form-check-label" for="faqActive">Active</label>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end">
                        <button type="submit" name="add_faq" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save FAQ
                        </button>
                    </div>
                </form>
                <form method="POST" class="mt-2">
                    <input type="hidden" name="section" value="chatbot">
                    <button type="submit" name="import_defaults" class="btn btn-outline-primary">
                        <i class="fas fa-download me-1"></i> Import Recommended FAQs
                    </button>
                </form>
                <?php else: ?>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                    <div class="col-md-5">
                        <div class="form-floating">
                            <input type="text" name="content_key" class="form-control" id="contentKey" placeholder="Content Key" required>
                            <label for="contentKey">
                                <i class="fas fa-key me-2"></i>Content Key
                            </label>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Examples — Home: hero_title, new_arrivals_title | About: about_title, about_story_p1 | Map: about_map_lat, about_map_lng, about_map_zoom, about_map_popup
                        </small>
                    </div>
                    <div class="col-md-5">
                        <div class="form-floating">
                            <input type="text" name="content_value" class="form-control" id="contentValue" placeholder="Content Value" required>
                            <label for="contentValue">
                                <i class="fas fa-edit me-2"></i>Content Value
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-start">
                        <button type="submit" name="save_content" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="d-flex gap-2 mb-4 justify-content-center">
            <a href="cms.php?section=home" class="back-btn" style="<?php echo $section==='home' ? '' : 'opacity:.7'; ?>">
                <i class="fas fa-home me-2"></i>
                Home Content
            </a>
            <a href="cms.php?section=about" class="back-btn" style="<?php echo $section==='about' ? '' : 'opacity:.7'; ?>">
                <i class="fas fa-info-circle me-2"></i>
                About Content
            </a>
            <a href="cms.php?section=map" class="back-btn" style="<?php echo $section==='map' ? '' : 'opacity:.7'; ?>">
                <i class="fas fa-map-marker-alt me-2"></i>
                Map Content
            </a>
            <a href="cms.php?section=chatbot" class="back-btn" style="<?php echo $section==='chatbot' ? '' : 'opacity:.7'; ?>">
                <i class="fas fa-robot me-2"></i>
                Chatbot FAQs
            </a>
            <a href="gcash_cms.php" class="back-btn">
                <i class="fas fa-qrcode me-2"></i>Manage GCash QR Code
            </a>
        </div>
        <!-- Content/FAQ List Card -->
        <div class="content-card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-list text-primary"></i>
                    <?php if ($section==='chatbot'): ?>Existing Chatbot FAQs<?php else: ?>Existing Content (<?php echo $section==='about' ? 'About' : 'Home'; ?>)<?php endif; ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($site_content); ?> items</span>
                </h3>
            </div>
            <!-- Search Filter -->  
        <div class="px-3 py-2">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="contentSearch" class="form-control" placeholder="<?php echo $section==='chatbot' ? 'Search question or answer...' : 'Search content key or value...'; ?>">
            </div>
        </div>
        <div class="table-container">
                <?php if (empty($site_content)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4><?php echo $section==='chatbot' ? 'No FAQs found' : 'No content found'; ?></h4>
                        <p><?php echo $section==='chatbot' ? 'Start by adding your first FAQ above.' : 'Start by adding your first piece of content above.'; ?></p>
                    </div>
                <?php else: ?>
                    <?php if ($section==='chatbot'): ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width:45%"><i class="fas fa-question-circle me-2"></i>Question</th>
                                <th style="width:35%"><i class="fas fa-reply me-2"></i>Answer</th>
                                <th style="width:10%"><i class="fas fa-sort me-2"></i>Order</th>
                                <th style="width:10%"><i class="fas fa-toggle-on me-2"></i>Active</th>
                                <th><i class="fas fa-tools me-2"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($site_content as $row): ?>
                                <tr>
                                    <td class="content-key" title="<?php echo htmlspecialchars($row['question']); ?>"><?php echo htmlspecialchars($row['question']); ?></td>
                                    <td class="editable-text" title="<?php echo htmlspecialchars($row['answer']); ?>"><?php echo htmlspecialchars($row['answer']); ?></td>
                                    <td><?php echo (int)$row['sort_order']; ?></td>
                                    <td><?php echo ((int)$row['is_active']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn edit-faq" 
                                                data-id="<?php echo (int)$row['id']; ?>"
                                                data-question="<?php echo htmlspecialchars($row['question']); ?>"
                                                data-answer="<?php echo htmlspecialchars($row['answer']); ?>"
                                                data-sort="<?php echo (int)$row['sort_order']; ?>"
                                                data-active="<?php echo (int)$row['is_active']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Delete this FAQ?');" class="d-inline">
                                                <input type="hidden" name="section" value="chatbot">
                                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                <button type="submit" name="delete_faq" class="btn btn-danger btn-sm" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="fas fa-key me-2"></i>Content Key</th>
                                <th><i class="fas fa-edit me-2"></i>Content Value</th>
                                <th><i class="fas fa-tools me-2"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($site_content as $row): ?>
                                <tr>
                                    <td>
                                        <span class="content-key">
                                            <?php echo htmlspecialchars($row['content_key']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="editable-text" data-key="<?php echo htmlspecialchars($row['content_key']); ?>" title="<?php echo htmlspecialchars($row['content_value']); ?>">
                                            <?php echo htmlspecialchars($row['content_value']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn edit-content" 
                                                data-key="<?php echo htmlspecialchars($row['content_key']); ?>"
                                                data-value="<?php echo htmlspecialchars($row['content_value']); ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this content?');" class="d-inline">
                                                <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                                                <input type="hidden" name="content_key" value="<?php echo htmlspecialchars($row['content_key']); ?>">
                                                <button type="submit" name="delete_content" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Content Modal -->
    <div id="editModal" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Content
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editContentForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                        <input type="hidden" name="content_key" id="editContentKey">
                        <div class="mb-3">
                            <label for="editContentValue" class="form-label">Content Value</label>
                            <textarea class="form-control" id="editContentValue" name="content_value" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="save_content" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit FAQ Modal -->
    <div id="editFaqModal" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-robot me-2"></i>Edit FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editFaqForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="section" value="chatbot">
                        <input type="hidden" name="id" id="editFaqId">
                        <div class="mb-3">
                            <label class="form-label" for="editFaqQuestion">Question</label>
                            <input class="form-control" type="text" id="editFaqQuestion" name="question" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="editFaqAnswer">Answer</label>
                            <textarea class="form-control" id="editFaqAnswer" name="answer" rows="4" required></textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="editFaqOrder">Order</label>
                                <input class="form-control" type="number" id="editFaqOrder" name="sort_order" value="0">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editFaqActive" name="is_active">
                                    <label class="form-check-label" for="editFaqActive">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_faq" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap modal
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        
        // Handle edit button click
        document.querySelectorAll('.edit-content').forEach(button => {
            button.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                const value = this.getAttribute('data-value');
                
                document.getElementById('editContentKey').value = key;
                document.getElementById('editContentValue').value = value;
                
                editModal.show();
            });
        });
        // Search filter for content/faq table
        document.getElementById('contentSearch').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('.table tbody tr').forEach(row => {
                const key = row.querySelector('.content-key')?.textContent.toLowerCase() || '';
                const value = row.querySelector('.editable-text')?.textContent.toLowerCase() || '';
                if (key.includes(filter) || value.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Edit FAQ modal handling
        const editFaqModalEl = document.getElementById('editFaqModal');
        if (editFaqModalEl) {
            const editFaqModal = new bootstrap.Modal(editFaqModalEl);
            document.querySelectorAll('.edit-faq').forEach(btn => {
                btn.addEventListener('click', function(){
                    document.getElementById('editFaqId').value = this.getAttribute('data-id');
                    document.getElementById('editFaqQuestion').value = this.getAttribute('data-question');
                    document.getElementById('editFaqAnswer').value = this.getAttribute('data-answer');
                    document.getElementById('editFaqOrder').value = this.getAttribute('data-sort');
                    document.getElementById('editFaqActive').checked = this.getAttribute('data-active') === '1';
                    editFaqModal.show();
                });
            });
        }
    </script>
            </div>
        </div>
    </div>
</div>
</body>
</html>