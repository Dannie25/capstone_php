<?php
session_start();
include '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get request ID from URL
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id <= 0) {
    header("Location: orders.php#customization");
    exit();
}

// First, check if the customization request exists
$stmt = $conn->prepare("SELECT * FROM customization_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php#customization");
    exit();
}

$request = $result->fetch_assoc();
$stmt->close();

// Initialize user data with defaults
$user = [
    'name' => $request['customer_name'] ?? 'Guest User',
    'email' => $request['email'] ?? 'No email',
    'phone' => 'N/A' // Default value
];

// If we have a user_id, try to get the latest info from users and customer_addresses tables
if (!empty($request['user_id'])) {
    // Get basic user info
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $request['user_id']);
    if ($stmt->execute()) {
        $user_result = $stmt->get_result();
        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $user['name'] = $user_data['name'] ?? $user['name'];
            $user['email'] = $user_data['email'] ?? $user['email'];
        }
    }
    $stmt->close();
    
    // Try to get phone number from customer_addresses (most recent address)
    $stmt = $conn->prepare(
        "SELECT phone FROM customer_addresses 
         WHERE user_id = ? AND phone IS NOT NULL AND phone != '' 
         ORDER BY is_default DESC, updated_at DESC, id DESC LIMIT 1"
    );
    $stmt->bind_param("i", $request['user_id']);
    if ($stmt->execute()) {
        $phone_result = $stmt->get_result();
        if ($phone_result->num_rows > 0) {
            $phone_data = $phone_result->fetch_assoc();
            $user['phone'] = $phone_data['phone'];
        }
    }
    $stmt->close();
}

// Merge user data into request array
$request = array_merge($request, $user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customization Request #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?> - MTC Clothing Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #5b6b46;
            --secondary-color: #d9e6a7;
            --light-gray: #f8f9fa;
        }
        
        body {
            background-color: var(--light-gray);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #c8d99a 100%);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background-color: #4a5a36;
            color: white;
            transform: translateY(-2px);
        }
        
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-in_progress {
            background-color: #cfe2ff;
            color: #084298;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .design-preview {
            max-width: 100%;
            max-height: 400px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-gallery img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .image-gallery img:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="admin-title">
                    <i class="fas fa-paint-brush me-3"></i>Customization Request #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?>
                </h1>
                <a href="orders.php#customization" class="back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <!-- Request Details -->
            <div class="col-md-8">
                <div class="detail-card">
                    <h3 class="mb-4"><i class="fas fa-info-circle me-2"></i>Request Details</h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Product Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['product_type'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-shopping-cart me-2"></i>Order Information</h5>
                    <div class="row">
                        <?php if (!empty($request['quantity'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Quantity</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['quantity']); ?> piece(s)</div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['budget_min']) || !empty($request['budget_max'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Budget Range</div>
                            <div class="detail-value">
                                <?php 
                                if (!empty($request['budget_min']) && !empty($request['budget_max'])) {
                                    echo '₱' . number_format($request['budget_min'], 2) . ' - ₱' . number_format($request['budget_max'], 2);
                                } elseif (!empty($request['budget_min'])) {
                                    echo '₱' . number_format($request['budget_min'], 2) . '+';
                                } elseif (!empty($request['budget_max'])) {
                                    echo 'Up to ₱' . number_format($request['budget_max'], 2);
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['deadline'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Deadline</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($request['deadline'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>Payment & Delivery</h5>
                    <div class="row">
                        <?php if (!empty($request['payment_method'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value">
                                <?php 
                                $payment = $request['payment_method'];
                                echo $payment === 'cod' ? 'Cash on Delivery' : ($payment === 'gcash' ? 'GCash' : ucfirst($payment));
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['delivery_mode'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Delivery Mode</div>
                            <div class="detail-value">
                                <?php 
                                $delivery = $request['delivery_mode'];
                                echo $delivery === 'pickup' ? 'Pick Up' : ($delivery === 'lalamove' ? 'Lalamove' : ($delivery === 'jnt' ? 'J&T Express' : ucfirst($delivery)));
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($request['delivery_address']) && $request['delivery_mode'] !== 'pickup'): ?>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value">
                                <div class="p-3" style="background:#f8f9fa;border-radius:8px;white-space:pre-line;">
                                    <?php echo htmlspecialchars($request['delivery_address']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-tshirt me-2"></i>Design Specifications</h5>
                    <div class="row">
                        <?php if (!empty($request['garment_style'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Garment Style</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['garment_style']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['neckline_type'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Neckline</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['neckline_type']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['sleeve_type'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Sleeve Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['sleeve_type']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['fit_type'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Fit</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['fit_type']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['fabric_type'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Fabric Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['fabric_type']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['color_preference_1'])): ?>
                        <div class="col-md-4">
                            <div class="detail-label">Color</div>
                            <div class="detail-value">
                                <span style="display:inline-block;width:30px;height:30px;background:<?php echo htmlspecialchars($request['color_preference_1']); ?>;border:2px solid #ddd;border-radius:5px;vertical-align:middle;margin-right:8px;"></span>
                                <?php echo htmlspecialchars($request['color_preference_1']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-ruler me-2"></i>Measurements (cm)</h5>
                    <div class="row">
                        <?php if (!empty($request['chest_width'])): ?>
                        <div class="col-md-3">
                            <div class="detail-label">Chest</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['chest_width']); ?> cm</div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['waist_width'])): ?>
                        <div class="col-md-3">
                            <div class="detail-label">Waist</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['waist_width']); ?> cm</div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['hip_width'])): ?>
                        <div class="col-md-3">
                            <div class="detail-label">Hip</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['hip_width']); ?> cm</div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['shoulder_width'])): ?>
                        <div class="col-md-3">
                            <div class="detail-label">Shoulder</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['shoulder_width']); ?> cm</div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['sleeve_length'])): ?>
                        <div class="col-md-3">
                            <div class="detail-label">Sleeve Length</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['sleeve_length']); ?> cm</div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['garment_length'])): ?>
                        <div class="col-md-3">
                            <div class="detail-label">Garment Length</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['garment_length']); ?> cm</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($request['special_instructions'])): ?>
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-comment-dots me-2"></i>Special Instructions</h5>
                    <div class="p-3" style="background:#f8f9fa;border-radius:8px;">
                        <?php echo nl2br(htmlspecialchars($request['special_instructions'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['reference_image_path'])): ?>
                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-images me-2"></i>Reference Images</h5>
                    <div class="image-gallery">
                        <img src="../<?php echo htmlspecialchars($request['reference_image_path']); ?>" 
                             alt="Reference Image" 
                             onclick="window.open('../<?php echo htmlspecialchars($request['reference_image_path']); ?>', '_blank')">
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['description'])): ?>
                    <div class="detail-label">Description</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($request['description'])); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['design_file'])): ?>
                    <div class="detail-label">Design File</div>
                    <div class="detail-value">
                        <a href="../<?php echo htmlspecialchars($request['design_file']); ?>" target="_blank" class="btn btn-outline-primary mb-2">
                            <i class="fas fa-download me-2"></i>Download Design
                        </a>
                        <div>
                            <img src="../<?php echo htmlspecialchars($request['design_file']); ?>" alt="Design Preview" class="design-preview">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['reference_images'])): ?>
                    <div class="detail-label">Reference Images</div>
                    <div class="detail-value">
                        <div class="image-gallery">
                            <?php 
                            $images = explode(',', $request['reference_images']);
                            foreach ($images as $image): 
                                $image = trim($image);
                                if (!empty($image)):
                            ?>
                                <img src="../<?php echo htmlspecialchars($image); ?>" 
                                     alt="Reference Image" 
                                     onclick="window.open('../<?php echo htmlspecialchars($image); ?>', '_blank')">
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Customer Details -->
            <div class="col-md-4">
                <div class="detail-card">
                    <h3 class="mb-4"><i class="fas fa-user me-2"></i>Customer Details</h3>
                    
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo isset($request['name']) ? htmlspecialchars($request['name']) : 'Not provided'; ?></div>
                    
                    <div class="detail-label">Email</div>
                    <div class="detail-value">
                        <?php if (isset($request['email']) && !empty($request['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($request['email']); ?>">
                                <?php echo htmlspecialchars($request['email']); ?>
                            </a>
                        <?php else: ?>
                            Not provided
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?php echo isset($request['phone']) && !empty($request['phone']) ? htmlspecialchars($request['phone']) : 'Not provided'; ?></div>
                    
                    <div class="detail-label">Request Date</div>
                    <div class="detail-value"><?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></div>
                </div>
                
                <!-- Action Buttons -->
                <?php if ($request['status'] === 'pending'): ?>
                    <button class="btn btn-primary btn-custom w-100 mb-2" onclick="updateStatus('in_progress')">
                        <i class="fas fa-spinner me-2"></i>Mark as In Progress
                    </button>
                    <button class="btn btn-danger btn-custom w-100" onclick="updateStatus('cancelled')">
                        <i class="fas fa-times me-2"></i>Cancel Request
                    </button>
                <?php elseif ($request['status'] === 'in_progress'): ?>
                    <button class="btn btn-success btn-custom w-100 mb-2" onclick="updateStatus('completed')">
                        <i class="fas fa-check me-2"></i>Mark as Completed
                    </button>
                    <button class="btn btn-danger btn-custom w-100" onclick="updateStatus('cancelled')">
                        <i class="fas fa-times me-2"></i>Cancel Request
                    </button>
                <?php elseif ($request['status'] === 'completed'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>This request has been completed.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function updateStatus(newStatus) {
            const statusText = newStatus.replace('_', ' ');
            const buttons = document.querySelectorAll('button[onclick^="updateStatus"]');
            
            try {
                // For cancellation, prompt for a reason
                let cancelReason = '';
                if (newStatus === 'cancelled') {
                    cancelReason = prompt('Please provide a reason for cancellation:');
                    if (cancelReason === null) {
                        return; // User clicked cancel
                    }
                    if (cancelReason.trim() === '') {
                        alert('Cancellation reason is required');
                        return;
                    }
                }

                if (newStatus !== 'cancelled' && !confirm(`Are you sure you want to mark this request as ${statusText}?`)) {
                    return;
                }

                // Show loading state
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                });

                // Create form data
                const formData = new FormData();
                formData.append('request_id', '<?php echo $request_id; ?>');
                formData.append('status', newStatus);
                if (newStatus === 'cancelled') {
                    formData.append('cancel_reason', cancelReason);
                }

                const response = await fetch('update_customization_status.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                if (data && data.success) {
                    const successMessage = newStatus === 'cancelled' 
                        ? 'Request has been cancelled successfully!'
                        : 'Status updated successfully!';
                    alert(successMessage);
                    window.location.reload();
                } else {
                    throw new Error(data && data.message ? data.message : 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + (error.message || 'Failed to update status. Please try again.'));
                // Re-enable buttons on error
                buttons.forEach(btn => {
                    btn.disabled = false;
                    if (newStatus === 'cancelled') {
                        btn.innerHTML = '<i class="fas fa-times me-2"></i>Cancel Request';
                    } else if (newStatus === 'in_progress') {
                        btn.innerHTML = '<i class="fas fa-spinner me-2"></i>Mark as In Progress';
                    } else if (newStatus === 'completed') {
                        btn.innerHTML = '<i class="fas fa-check me-2"></i>Mark as Completed';
                    }
                });
            }
        }
    </script>
</body>
</html>
