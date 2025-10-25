<?php
session_start();
include '../db.php';

$error = '';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Simple admin credentials check (you can modify this)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['is_admin'] = true;
        $_SESSION['show_chatbot_greeting'] = true;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MTC Clothing</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    header {
      background-color: #333;
      color: white;
      padding: 20px 0;
      font-size: 24px;
    }
    .container {
      margin-top: 50px;
    }
    .input-wrapper {
      position: relative;
      display: inline-block;
    }
    input[type="text"], input[type="password"] {
      padding: 10px;
      margin: 10px;
      width: 250px;
    }
    .btn {
      padding: 10px 20px;
      margin: 10px 5px;
      cursor: pointer;
    }
    .button-row {
      display: flex;
      justify-content: center;
      margin-top: 10px;
    }
    .eye-icon {
      position: absolute;
      top: 50%;
      right: 25px;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <header>MTC Clothing</header>

  <div class="container">
    <h2>Welcome Back Admin!</h2>
    
    <?php if ($error): ?>
      <div style="color: red; margin: 10px; padding: 10px; background: #ffe6e6; border: 1px solid #ff0000; border-radius: 5px;">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="username" placeholder="Enter your username"><br>

      <div class="input-wrapper">
        <input type="password" name="password" id="password" placeholder="Enter your password">
        <img src="img/eye.png" alt="Show password" class="eye-icon" onclick="togglePassword()">
      </div><br>

      <div class="button-row">
        <button class="btn" type="submit" name="login">Log in</button>
        <button class="btn" type="button" onclick="window.location.href='signup.php'">Sign Up</button>
      </div>
    </form>
  </div>

  <script>
    function togglePassword() {
      const pwd = document.getElementById("password");
      pwd.type = pwd.type === "password" ? "text" : "password";
    }
  </script>
</body>
</html>
