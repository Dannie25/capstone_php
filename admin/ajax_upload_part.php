<?php
session_start();
require_once '../db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['part_type']) && isset($_POST['part_value'])) {
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
                    // Get the updated record
                    $getStmt = $conn->prepare("SELECT * FROM shirt_parts WHERE part_type = ? AND part_value = ?");
                    $getStmt->bind_param("ss", $partType, $partValue);
                    $getStmt->execute();
                    $result = $getStmt->get_result();
                    $uploadedData = $result->fetch_assoc();
                    $getStmt->close();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Image uploaded successfully!',
                        'data' => [
                            'image_path' => $imagePath,
                            'updated_at' => $uploadedData['updated_at'],
                            'timestamp' => strtotime($uploadedData['updated_at'])
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Error moving uploaded file. Check folder permissions for img/shirt_parts/.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
        }
    } else {
        // Provide detailed error if upload failed
        if (!isset($_FILES['part_image'])) {
            echo json_encode(['success' => false, 'message' => 'No file received. Please choose an image.']);
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
                $message = isset($map[$code]) ? $map[$code] : ('Upload error code: ' . $code);
            } else {
                $message = "Please select a part and upload an image.";
            }
            echo json_encode(['success' => false, 'message' => $message]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
