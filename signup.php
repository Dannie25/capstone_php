<?php
session_start();
include 'db.php';
include 'includes/address_functions.php';

// Create users table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

$message = '';
$error = '';

if (isset($_POST['signup'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $name = $first_name . ' ' . $last_name; // Combine for users table
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $postal_code = trim($_POST['postal_code']);
    
    // Location fields
    $region_code = trim($_POST['region_code'] ?? '');
    $region_name = trim($_POST['region_name'] ?? '');
    $province_code = trim($_POST['province_code'] ?? '');
    $province_name = trim($_POST['province_name'] ?? '');
    $city_code = trim($_POST['city_code'] ?? '');
    $city_name = trim($_POST['city_name'] ?? '');
    $barangay_code = trim($_POST['barangay_code'] ?? '');
    $barangay_name = trim($_POST['barangay_name'] ?? '');
    
    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($phone) || empty($address) || empty($postal_code) || empty($region_code) || empty($city_code) || empty($barangay_code)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email address already exists. Please use a different email.';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                // Auto-login the user after successful registration
                $user_id = $conn->insert_id;
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_phone'] = $phone;
                
                // Save user's address as default address for checkout
                $address_data = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'city' => $city_name,
                    'postal_code' => $postal_code,
                    'region_code' => $region_code,
                    'region_name' => $region_name,
                    'province_code' => $province_code,
                    'province_name' => $province_name,
                    'city_code' => $city_code,
                    'city_name' => $city_name,
                    'barangay_code' => $barangay_code,
                    'barangay_name' => $barangay_name
                ];
                saveCustomerAddress($user_id, $address_data, true);
                
                // Redirect to home page
                header('Location: home.php');
                exit();
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - MTC Clothing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 20px 0;
        }

        /* Background decorative elements */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(217, 230, 167, 0.3) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -30%;
            right: -30%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(166, 189, 162, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo {
            position: absolute;
            top: 30px;
            left: 30px;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            z-index: 10;
        }

        .logo .mtc {
            color: #7fb069;
        }

        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
            position: relative;
            z-index: 5;
            border: 1px solid rgba(255, 255, 255, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .form-group select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group select:focus {
            border-color: #7fb069;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 3px rgba(127, 176, 105, 0.1);
        }

        .form-group select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
            }
        }

        .signup-title {
            font-size: 28px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #7fb069;
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 0 0 3px rgba(127, 176, 105, 0.1);
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
            font-size: 18px;
        }

        .signup-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #7fb069 0%, #6a9c57 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(127, 176, 105, 0.3);
        }

        .signup-btn:active {
            transform: translateY(0);
        }

        .divider {
            margin: 25px 0;
            position: relative;
            color: #aaa;
            font-size: 14px;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e8ed;
        }

        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 15px;
        }

        .login-link {
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #7fb069;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-home {
            position: absolute;
            top: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.9);
            color: #7fb069;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .back-home:hover {
            background: #7fb069;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="logo">
        <span class="mtc">MTC</span> Clothing
    </div>

    <a href="home.php" class="back-home">← Back to Home</a>

    <div class="signup-container">
        <h1 class="signup-title">Sign up to MTC Clothing</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <div class="input-wrapper">
                        <input type="text" id="first_name" name="first_name" value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>" required>
                        <span class="input-icon">👤</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <div class="input-wrapper">
                        <input type="text" id="last_name" name="last_name" value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>" required>
                        <span class="input-icon">👤</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    <span class="input-icon">✉️</span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" required>
                    <span class="input-icon" onclick="togglePassword()">👁️</span>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-wrapper">
                    <input type="tel" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" placeholder="+63 912 345 6789" required>
                    <span class="input-icon">📱</span>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Street Address</label>
                <div class="input-wrapper">
                    <input type="text" id="address" name="address" value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>" placeholder="House/Unit, Street, Subdivision" required>
                    <span class="input-icon">🏠</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="region_select">Region *</label>
                    <select id="region_select" name="region" required>
                        <option value="">Select Region</option>
                    </select>
                    <input type="hidden" id="region_code" name="region_code">
                    <input type="hidden" id="region_name" name="region_name">
                </div>
                <div class="form-group">
                    <label for="province_select">Province</label>
                    <select id="province_select" name="province" disabled>
                        <option value="">Select Province</option>
                    </select>
                    <input type="hidden" id="province_code" name="province_code">
                    <input type="hidden" id="province_name" name="province_name">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city_select">City/Municipality *</label>
                    <select id="city_select" name="city_select" disabled required>
                        <option value="">Select City/Municipality</option>
                    </select>
                    <input type="hidden" id="city_code" name="city_code">
                    <input type="hidden" id="city_name" name="city_name">
                </div>
                <div class="form-group">
                    <label for="barangay_select">Barangay *</label>
                    <select id="barangay_select" name="barangay" disabled required>
                        <option value="">Select Barangay</option>
                    </select>
                    <input type="hidden" id="barangay_code" name="barangay_code">
                    <input type="hidden" id="barangay_name" name="barangay_name">
                </div>
            </div>

            <div class="form-group">
                <label for="postal_code">Postal Code</label>
                <div class="input-wrapper">
                    <input type="text" id="postal_code" name="postal_code" value="<?php echo isset($postal_code) ? htmlspecialchars($postal_code) : ''; ?>" placeholder="1000" required>
                    <span class="input-icon">📮</span>
                </div>
            </div>

            <button type="submit" name="signup" class="signup-btn">Sign up</button>
        </form>

        <div class="divider">
            <span>Or sign up with</span>
        </div>

        <div class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.input-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                icon.textContent = '👁️';
            }
        }

        // Auto-hide success message after 5 seconds
        const successMessage = document.querySelector('.message.success');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 300);
            }, 5000);
        }

        // PSGC API endpoints
        const PSGC = {
            regions: 'https://psgc.gitlab.io/api/regions/',
            regionProvinces: (code) => `https://psgc.gitlab.io/api/regions/${code}/provinces/`,
            regionCities: (code) => `https://psgc.gitlab.io/api/regions/${code}/cities-municipalities/`,
            provinceCities: (code) => `https://psgc.gitlab.io/api/provinces/${code}/cities-municipalities/`,
            cityBarangays: (code) => `https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`
        };

        // DOM Elements
        const regionSelect = document.getElementById('region_select');
        const provinceSelect = document.getElementById('province_select');
        const citySelect = document.getElementById('city_select');
        const barangaySelect = document.getElementById('barangay_select');
        const regionCodeInput = document.getElementById('region_code');
        const regionNameInput = document.getElementById('region_name');
        const provinceCodeInput = document.getElementById('province_code');
        const provinceNameInput = document.getElementById('province_name');
        const cityCodeInput = document.getElementById('city_code');
        const cityNameInput = document.getElementById('city_name');
        const barangayCodeInput = document.getElementById('barangay_code');
        const barangayNameInput = document.getElementById('barangay_name');

        // Update hidden inputs
        function updateHiddenInputs() {
            const regionOption = regionSelect.options[regionSelect.selectedIndex];
            if (regionOption && regionOption.value) {
                regionCodeInput.value = regionSelect.value;
                regionNameInput.value = regionOption.textContent;
            }

            const provinceOption = provinceSelect.options[provinceSelect.selectedIndex];
            if (provinceOption && provinceOption.value) {
                provinceCodeInput.value = provinceSelect.value;
                provinceNameInput.value = provinceOption.textContent;
            }

            const cityOption = citySelect.options[citySelect.selectedIndex];
            if (cityOption && cityOption.value) {
                cityCodeInput.value = citySelect.value;
                cityNameInput.value = cityOption.textContent;
            }

            const barangayOption = barangaySelect.options[barangaySelect.selectedIndex];
            if (barangayOption && barangayOption.value) {
                barangayCodeInput.value = barangaySelect.value;
                barangayNameInput.value = barangayOption.textContent;
            }
        }

        // Load regions on page load
        async function loadRegions() {
            try {
                const response = await fetch(PSGC.regions);
                const regions = await response.json();
                regions.sort((a, b) => a.name.localeCompare(b.name));

                regions.forEach(region => {
                    const option = document.createElement('option');
                    option.value = region.code;
                    option.textContent = region.name;
                    regionSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading regions:', error);
            }
        }

        async function loadProvinces(regionCode) {
            try {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                provinceSelect.disabled = true;
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                citySelect.disabled = true;

                const response = await fetch(PSGC.regionProvinces(regionCode));
                const provinces = await response.json();

                if (provinces && provinces.length > 0) {
                    provinces.sort((a, b) => a.name.localeCompare(b.name));
                    provinces.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.code;
                        option.textContent = province.name;
                        provinceSelect.appendChild(option);
                    });
                    provinceSelect.disabled = false;
                } else {
                    // No provinces (e.g., NCR), load cities directly
                    await loadCitiesFromRegion(regionCode);
                }
            } catch (error) {
                console.error('Error loading provinces:', error);
                // Try loading cities directly if provinces fail
                await loadCitiesFromRegion(regionCode);
            }
        }

        async function loadCitiesFromRegion(regionCode) {
            try {
                const response = await fetch(PSGC.regionCities(regionCode));
                const cities = await response.json();
                cities.sort((a, b) => a.name.localeCompare(b.name));

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.code;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });

                citySelect.disabled = false;
            } catch (error) {
                console.error('Error loading cities:', error);
            }
        }

        async function loadCities(provinceCode) {
            try {
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                citySelect.disabled = true;

                const response = await fetch(PSGC.provinceCities(provinceCode));
                const cities = await response.json();
                cities.sort((a, b) => a.name.localeCompare(b.name));

                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.code;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });

                citySelect.disabled = false;
            } catch (error) {
                console.error('Error loading cities:', error);
            }
        }

        async function loadBarangays(cityCode) {
            try {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;

                const response = await fetch(PSGC.cityBarangays(cityCode));
                const barangays = await response.json();
                barangays.sort((a, b) => a.name.localeCompare(b.name));

                barangays.forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay.code;
                    option.textContent = barangay.name;
                    barangaySelect.appendChild(option);
                });

                barangaySelect.disabled = false;
            } catch (error) {
                console.error('Error loading barangays:', error);
            }
        }

        // Event Listeners
        regionSelect.addEventListener('change', async (e) => {
            const regionCode = e.target.value;
            if (regionCode) {
                await loadProvinces(regionCode);
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                updateHiddenInputs();
            } else {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                provinceSelect.disabled = true;
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                citySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
            }
        });

        provinceSelect.addEventListener('change', async (e) => {
            const provinceCode = e.target.value;
            if (provinceCode) {
                await loadCities(provinceCode);
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
                updateHiddenInputs();
            } else {
                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                citySelect.disabled = true;
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
            }
        });

        citySelect.addEventListener('change', async (e) => {
            const cityCode = e.target.value;
            if (cityCode) {
                await loadBarangays(cityCode);
                updateHiddenInputs();
            } else {
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                barangaySelect.disabled = true;
            }
        });

        barangaySelect.addEventListener('change', () => {
            updateHiddenInputs();
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadRegions();
        });
    </script>
</body>
</html>
