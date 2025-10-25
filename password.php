<?php
session_start();
include 'db.php';

// Require login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
  $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'password.php');
  header("Location: login.php?redirect={$redirect}");
  exit();
}

$user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error_message'] = 'Password must be at least 6 characters.';
    } else {
        // Check current password
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed);
        if ($stmt->fetch() && password_verify($current_password, $hashed)) {
            $stmt->close();
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $new_hash, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Password updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to update password.';
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = 'Current password is incorrect.';
        }
    }
    header('Location: password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Change Password - MTC Clothing</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body { 
      font-family: 'Segoe UI', Arial, sans-serif;
      background: linear-gradient(135deg, #f8f9fa 0%, #eaf6e8 100%);
      min-height: 100vh;
      padding: 20px;
    }
    
    .pw-container {
      max-width: 500px;
      margin: 48px auto;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(91,107,70,0.15);
      padding: 40px;
      position: relative;
    }
    
    .pw-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
    }
    
    .pw-header i {
      color: #5b6b46;
      font-size: 2em;
    }
    
    .pw-header h1 {
      font-size: 1.75em;
      font-weight: 700;
      color: #3c4c2b;
      margin: 0;
    }
    
    label {
      display: block;
      margin: 18px 0 8px 0;
      font-weight: 600;
      color: #5b6b46;
      font-size: 1em;
    }
    
    .pw-input-group {
      position: relative;
      width: 100%;
    }
    
    .pw-input-group input[type="password"],
    .pw-input-group input[type="text"] {
      width: 100%;
      padding: 14px 45px 14px 16px;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      font-size: 1em;
      background: white;
      transition: all 0.3s;
      box-sizing: border-box;
    }
    .pw-input-group input[type="password"]:focus,
    .pw-input-group input[type="text"]:focus {
      border-color: #5b6b46;
      outline: none;
      box-shadow: 0 0 0 3px rgba(91, 107, 70, 0.15);
      transform: translateY(-1px);
    }
    
    .pw-input-group .fa-eye, .pw-input-group .fa-eye-slash {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
      cursor: pointer;
      font-size: 1.2em;
      transition: color 0.3s;
    }
    
    .pw-input-group .fa-eye:hover,
    .pw-input-group .fa-eye-slash:hover {
      color: #5b6b46;
    }
    .pw-btn {
      background: linear-gradient(135deg, #5b6b46 0%, #7a8f5e 100%);
      color: white;
      border: none;
      padding: 16px 0;
      border-radius: 12px;
      width: 100%;
      font-size: 1.1em;
      font-weight: 700;
      margin-top: 28px;
      box-shadow: 0 6px 20px rgba(91, 107, 70, 0.3);
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .pw-btn:hover {
      background: linear-gradient(135deg, #4a5a38 0%, #6a7f4e 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(91, 107, 70, 0.4);
    }
    
    .pw-btn:active {
      transform: translateY(0);
    }
    .pw-alert {
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 1em;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
      border: none;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .pw-alert-success { 
      background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
      color: #155724;
    }
    
    .pw-alert-error { 
      background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
      color: #721c24;
    }
    .pw-tip {
      margin-top: 24px;
      color: #666;
      font-size: 0.95em;
      text-align: center;
      padding: 12px;
      background: #f8f9fa;
      border-radius: 10px;
      border-left: 4px solid #5b6b46;
    }
    
    .pw-tip i {
      color: #5b6b46;
      margin-right: 6px;
    }
    
    @media (max-width: 600px) {
      body {
        padding: 10px;
      }
      
      .pw-container { 
        padding: 30px 25px;
        margin: 20px auto;
      }
      
      .pw-header h1 {
        font-size: 1.5em;
      }
      
      .pw-header i {
        font-size: 1.6em;
      }
    }
  </style>
</head>
<body>
  <div class="pw-container">
    <div class="pw-header">
      <i class="fas fa-key"></i>
      <h1>Change Password</h1>
    </div>
    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="pw-alert pw-alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="pw-alert pw-alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <form method="post" action="password.php" autocomplete="off">
      <label for="current_password">Current Password</label>
      <div class="pw-input-group">
        <input type="password" id="current_password" name="current_password" required>
        <i class="fa fa-eye" onclick="togglePw('current_password', this)"></i>
      </div>
      <label for="new_password">New Password</label>
      <div class="pw-input-group">
        <input type="password" id="new_password" name="new_password" required>
        <i class="fa fa-eye" onclick="togglePw('new_password', this)"></i>
      </div>
      <label for="confirm_password">Confirm New Password</label>
      <div class="pw-input-group">
        <input type="password" id="confirm_password" name="confirm_password" required>
        <i class="fa fa-eye" onclick="togglePw('confirm_password', this)"></i>
      </div>
      <button class="pw-btn" type="submit"><i class="fas fa-sync-alt"></i> Update Password</button>
    </form>
    <div class="pw-tip"><i class="fas fa-info-circle"></i> Password must be at least 6 characters long.</div>
  </div>
  <script>
    function togglePw(field, icon) {
      var input = document.getElementById(field);
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>
