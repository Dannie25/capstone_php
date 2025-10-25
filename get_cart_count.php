<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

// Start session
session_start();

// Function to send JSON response and exit
function sendJsonResponse($data) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    echo json_encode($data);
    exit();
}

try {
    $response = ['count' => 0];
    
    if (isset($_SESSION['user_id'])) {
        // Include required files
        require_once 'db.php';
        require_once 'cart_functions.php';
        
        // Get cart count
        $response['count'] = getCartCount();
    }
    
    // Send the response
    sendJsonResponse($response);
    
} catch (Exception $e) {
    // Log the error
    error_log('Get cart count error: ' . $e->getMessage());
    
    // Send error response
    sendJsonResponse([
        'status' => 'error',
        'message' => 'An error occurred while getting cart count',
        'count' => 0,
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
