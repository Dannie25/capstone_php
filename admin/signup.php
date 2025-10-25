<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MTC Clothing - Sign Up</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      text-align: center;
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
    input[type="text"], input[type="password"] {
      padding: 10px;
      margin: 10px;
      width: 250px;
    }
    .btn {
      padding: 10px 20px;
      margin: 10px;
      cursor: pointer;
    }
    .error {
      color: red;
      font-size: 14px;
      margin-top: -5px;
    }
    .input-wrapper {
      position: relative;
      display: inline-block;
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
    <h2>Create Your Account</h2>
    <form id="signupForm" onsubmit="return validatePasswords();">
      <input type="text" name="new_username" id="new_username" placeholder="Enter username" required><br>

      <div class="input-wrapper">
        <input type="password" name="new_password" id="new_password" placeholder="Enter password" required>
        <img src="img/eye.png" class="eye-icon" onclick="toggleVisibility('new_password')">
      </div><br>

      <div class="input-wrapper">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
        <img src="img/eye.png" class="eye-icon" onclick="toggleVisibility('confirm_password')">
      </div>
      <div id="errorMsg" class="error"></div><br>

      <button class="btn" type="submit">Sign Up</button>
    </form>
  </div>

  <script>
    function toggleVisibility(id) {
      const input = document.getElementById(id);
      input.type = input.type === "password" ? "text" : "password";
    }

    function validatePasswords() {
      const pwd1 = document.getElementById("new_password").value;
      const pwd2 = document.getElementById("confirm_password").value;
      const errorMsg = document.getElementById("errorMsg");

      const strongPwd = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

      if (!strongPwd.test(pwd1)) {
        errorMsg.textContent = "❌ Password must be at least 8 characters, with 1 uppercase, 1 lowercase, and 1 special character.";
        return false;
      }

      if (pwd1 !== pwd2) {
        errorMsg.textContent = "❌ Passwords do not match.";
        return false;
      }

      errorMsg.textContent = "";
      alert("✅ Account successfully created!");
      window.location.href = "login.php"; // Redirect to login page
      return false;
    }
  </script>
</body>
</html>
