<?php
session_start();
include 'db.php';

// Require login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "Please fill in all fields.";
        header("Location: profile.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match.";
        header("Location: profile.php");
        exit();
    }

    if (strlen($new_password) < 6) {
        $_SESSION['error_message'] = "New password must be at least 6 characters long.";
        header("Location: profile.php");
        exit();
    }

    try {
        // Check if users table exists and verify current password
        $users_table_exists = false;
        try {
            $result = $conn->query("SHOW TABLES LIKE 'users'");
            $users_table_exists = $result->num_rows > 0;
        } catch (Exception $e) {
            // Table doesn't exist, will use customer_addresses only
        }

        if ($users_table_exists) {
            // Get current password hash from users table
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($current_password, $user['password'])) {
                $_SESSION['error_message'] = "Current password is incorrect.";
                header("Location: profile.php");
                exit();
            }

            // Update password in users table
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // If users table doesn't exist, we can't change password
            $_SESSION['error_message'] = "Password change is not available. Please contact support.";
            header("Location: profile.php");
            exit();
        }

        // Success
        $_SESSION['success_message'] = "Password updated successfully!";
        header("Location: profile.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating password: " . $e->getMessage();
        header("Location: profile.php");
        exit();
    }
}

// If not POST request, redirect back
header("Location: profile.php");
exit();
?>
