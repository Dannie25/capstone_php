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

// Fetch subcontract request details
$sql = "SELECT * FROM subcontract_requests WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php#subcontract");
    exit();
}

$request = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Subcontract Request #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?> - MTC Clothing Admin</title>
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
            object-fit: cover;
        }
        
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .image-modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .image-modal-close:hover {
            color: #bbb;
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
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="admin-title">
                    <i class="fas fa-handshake me-3"></i>Subcontract Request #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?>
                </h1>
                <a href="orders.php#subcontract" class="back-btn">
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
                            <div class="detail-label">What For</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['what_for']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Quantity</div>
                            <div class="detail-value"><?php echo $request['quantity']; ?> pieces</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-label">Date Needed</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($request['date_needed'])); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Time Needed</div>
                            <div class="detail-value"><?php echo date('g:i A', strtotime($request['time_needed'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo $request['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($request['note'])): ?>
                    <div class="detail-label">Additional Notes</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($request['note'])); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($request['design_file'])): ?>
                    <div class="detail-label">Design Files</div>
                    <div class="detail-value">
                        <?php
                        // Check if design_file is JSON array or single file
                        $design_files = json_decode($request['design_file'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($design_files)) {
                            // Multiple files
                            echo '<div class="row g-3">';
                            foreach ($design_files as $index => $file) {
                                echo '<div class="col-md-6">';
                                echo '<div class="card">';
                                echo '<img src="../' . htmlspecialchars($file) . '" alt="Design ' . ($index + 1) . '" class="card-img-top design-preview" style="cursor: pointer;" onclick="openImageModal(\'' . htmlspecialchars($file) . '\')">';
                                echo '<div class="card-body text-center">';
                                echo '<a href="../' . htmlspecialchars($file) . '" target="_blank" class="btn btn-sm btn-outline-primary">';
                                echo '<i class="fas fa-download me-1"></i>Download';
                                echo '</a>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            // Single file (legacy)
                            echo '<a href="../' . htmlspecialchars($request['design_file']) . '" target="_blank" class="btn btn-outline-primary">';
                            echo '<i class="fas fa-download me-2"></i>Download Design';
                            echo '</a>';
                            echo '<div class="mt-3">';
                            echo '<img src="../' . htmlspecialchars($request['design_file']) . '" alt="Design Preview" class="design-preview" style="cursor: pointer;" onclick="openImageModal(\'' . htmlspecialchars($request['design_file']) . '\')">';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Customer Details -->
            <div class="col-md-4">
                <div class="detail-card">
                    <h3 class="mb-4"><i class="fas fa-user me-2"></i>Customer Details</h3>
                    
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($request['customer_name']); ?></div>
                    
                    <div class="detail-label">Email</div>
                    <div class="detail-value">
                        <a href="mailto:<?php echo htmlspecialchars($request['email']); ?>">
                            <?php echo htmlspecialchars($request['email']); ?>
                        </a>
                    </div>
                    
                    <?php if (!empty($request['address'])): ?>
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($request['address'])); ?></div>
                    <?php endif; ?>
                    
                    <div class="detail-label">Request Date</div>
                    <div class="detail-value"><?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></div>
                </div>
                
                <!-- Price Setting -->
                <?php if ($request['status'] === 'submitted' || $request['status'] === 'approved'): ?>
                <div class="detail-card">
                    <h3 class="mb-4"><i class="fas fa-tag me-2"></i>Set Price</h3>
                    
                    <?php if (!empty($request['quoted_price'])): ?>
                    <div class="alert alert-info mb-3">
                        <strong>Current Price:</strong> ₱<?php echo number_format($request['quoted_price'], 2); ?>
                        <?php if (!empty($request['admin_notes'])): ?>
                        <br><small><?php echo htmlspecialchars($request['admin_notes']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" id="priceInput" step="0.01" min="0" 
                               value="<?php echo $request['quoted_price'] ?? ''; ?>" placeholder="Enter price">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notesInput" rows="3" placeholder="Add notes about the price or requirements"><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                    </div>
                    <button class="btn btn-success btn-custom w-100" onclick="setPrice()">
                        <i class="fas fa-check me-2"></i><?php echo !empty($request['quoted_price']) ? 'Update Price' : 'Set Price'; ?>
                    </button>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="detail-card">
                    <h3 class="mb-4"><i class="fas fa-cog me-2"></i>Actions</h3>
                    
                    <?php if ($request['status'] === 'submitted'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>Please set a price for this request above.
                        </div>
                        <button class="btn btn-danger btn-custom w-100" onclick="updateStatus('cancelled')">
                            <i class="fas fa-times me-2"></i>Cancel Request
                        </button>
                    <?php elseif ($request['status'] === 'approved'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-clock me-2"></i>Waiting for customer to accept the price.
                        </div>
                        <button class="btn btn-danger btn-custom w-100" onclick="updateStatus('cancelled')">
                            <i class="fas fa-times me-2"></i>Cancel Request
                        </button>
                    <?php elseif ($request['status'] === 'verifying'): ?>
                        <div class="alert alert-primary">
                            <i class="fas fa-hourglass-half me-2"></i>Customer accepted. Verifying payment...
                        </div>
                        <button class="btn btn-primary btn-custom w-100 mb-2" onclick="updateStatus('in_progress')">
                            <i class="fas fa-cog me-2"></i>Mark as In Progress
                        </button>
                        <button class="btn btn-danger btn-custom w-100" onclick="updateStatus('cancelled')">
                            <i class="fas fa-times me-2"></i>Cancel Request
                        </button>
                    <?php elseif ($request['status'] === 'in_progress'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-cog me-2"></i>Request is in progress...
                        </div>
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
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle me-2"></i>This request has been cancelled.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
        <img class="image-modal-content" id="modalImage">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setPrice() {
            const price = document.getElementById('priceInput').value;
            const notes = document.getElementById('notesInput').value;
            
            if (!price || parseFloat(price) <= 0) {
                alert('Please enter a valid price');
                return;
            }
            
            if (!confirm('Are you sure you want to set the price to ₱' + parseFloat(price).toFixed(2) + '?')) {
                return;
            }
            
            fetch('update_subcontract_price.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=<?php echo $request_id; ?>&price=${price}&notes=${encodeURIComponent(notes)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Price set successfully! Customer will be notified.');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to set price'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while setting the price.');
            });
        }
        
        function updateStatus(newStatus) {
            const statusText = newStatus.replace('_', ' ');
            if (confirm(`Are you sure you want to mark this request as ${statusText}?`)) {
                fetch('update_subcontract_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=<?php echo $request_id; ?>&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status updated successfully!');
                        window.location.reload();
                    } else {
                        alert('Error updating status: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the status.');
                });
            }
        }
        
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = '../' + imageSrc;
        }
        
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // Close modal when clicking outside the image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
