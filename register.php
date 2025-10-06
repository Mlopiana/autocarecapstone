<?php 
session_start();
require 'db_config.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'];
    $contact  = trim($_POST['contact_number']);

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, contact_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $contact);
            $stmt->execute();
            $user_id = $stmt->insert_id;

            if ($role === 'shop_admin') {
                $shop_name    = trim($_POST['shop_name']);
                $shop_address = trim($_POST['shop_address']);
                $latitude     = floatval($_POST['latitude']);
                $longitude    = floatval($_POST['longitude']);

                $stmt2 = $conn->prepare("INSERT INTO repair_shops (user_id, shop_name, address, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("issdd", $user_id, $shop_name, $shop_address, $latitude, $longitude);
                $stmt2->execute();
            }

            $conn->commit();
            $success = "Account created successfully. You can now <a href='login.php'>log in</a>.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AutoCare Register</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: url('garage-bg.jpg') no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .register-container {
            background: rgba(20, 20, 20, 0.95);
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.7);
            width: 400px;
            max-height: 90vh;
            overflow-y: auto;
            border: 2px solid #d35400;

            /* Hide scrollbar */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none;  /* IE 10+ */
        }

        .register-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #f39c12;
            font-size: 26px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        label {
            font-size: 14px;
            color: #ecf0f1;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            margin-bottom: 18px;
            border: 1px solid #7f8c8d;
            border-radius: 8px;
            box-sizing: border-box;
            background: #2c3e50;
            color: #ecf0f1;
        }

        input::placeholder {
            color: #bdc3c7;
        }

        .role-selection {
            margin-bottom: 15px;
            font-family: 'Courier New', monospace; /* font for role text and radio labels */
            font-size: 15px;
            color: #ecf0f1;
        }

        .role-selection label {
            font-weight: bold;
        }

        .role-selection input {
            margin-right: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #e67e22, #d35400);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0px 4px 12px rgba(0,0,0,0.5);
        }

        button:hover {
            background: linear-gradient(135deg, #d35400, #e67e22);
        }

        .message {
            text-align: center;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .error-message { color: #e74c3c; }
        .success-message { color: #2ecc71; }

        a {
            color: #f39c12;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            color: #e67e22;
        }

        .signup-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2><span style="color:#ecf0f1;">Auto</span>Care Register</h2>

        <?php if (!empty($error)): ?>
            <div class="message error-message"><?= $error ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="message success-message"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Name:</label>
            <input type="text" name="name" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>

            <label>Contact Number:</label>
            <input type="text" name="contact_number" required>

            <div class="role-selection">
                <label>Select Role:</label><br>
                <input type="radio" name="role" value="customer" checked onclick="toggleFields()"> Customer
                <input type="radio" name="role" value="shop_admin" onclick="toggleFields()"> Shop Owner
            </div>

            <div id="shopFields" style="display: none;">
                <label>Shop Name:</label>
                <input type="text" name="shop_name" id="shop_name">

                <label>Shop Address:</label>
                <input type="text" name="shop_address" id="shop_address">

                <label>Latitude:</label>
                <input type="text" name="latitude" id="latitude">

                <label>Longitude:</label>
                <input type="text" name="longitude" id="longitude">
            </div>

            <button type="submit">Register</button>
        </form>

        <div class="signup-link">
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </div>
    </div>

    <script>
        function toggleFields() {
            const role = document.querySelector('input[name="role"]:checked').value;
            const shopFields = document.getElementById('shopFields');
            const show = role === 'shop_admin';

            shopFields.style.display = show ? 'block' : 'none';
            document.getElementById('shop_name').required = show;
            document.getElementById('shop_address').required = show;
            document.getElementById('latitude').required = show;
            document.getElementById('longitude').required = show;
        }
        window.onload = toggleFields;
    </script>
</body>
</html>
