<?php 
session_start();
require 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Get user by email only
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Store user session details
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'customer') {
                header("Location: home.php");
            } elseif ($user['role'] === 'shop_admin') {
                header("Location: shop_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No user found with this email.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>AutoCare Login</title>
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

        .login-container {
            background: rgba(20, 20, 20, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.7);
            width: 380px;
            border: 2px solid #d35400;
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

        .error-message {
            color: #e74c3c;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
        }

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
    <div class="login-container">
        <h2><span style="color:#ecf0f1;">Auto</span>Care Login</h2>
        <?php if (!empty($error)) echo "<div class='error-message'>$error</div>"; ?>
        <form method="POST" action="">
            <label>Email:</label><br>
            <input type="email" name="email" placeholder="Enter your email" required><br>

            <label>Password:</label><br>
            <input type="password" name="password" placeholder="Enter your password" required><br>

            <button type="submit">Login</button>
        </form>
        
        <div class="signup-link">
            <p>Don’t have an account? 
                <a href="register.php">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>
