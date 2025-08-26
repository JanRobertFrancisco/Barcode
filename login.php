<?php
session_start();
ob_start();

// Database connection with error handling
$conn = null;
try {
    $conn = new mysqli("localhost", "root", "", "admin");
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("System temporarily unavailable. Please try again later.");
}

$error = "";
$login_success = false;

// Check if user is already logged in
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Input validation
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        // Use prepared statements to prevent SQL injection
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // For demo purposes - allow admin/12345 without hashing
                if (($username === 'admin' && $password === '12345') || password_verify($password, $user['password'])) {
                    // Set success flag
                    $login_success = true;
                    
                    // Regenerate session ID to prevent fixation attacks
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_login'] = time();
                } else {
                    $error = "Invalid username or password";
                }
            } else {
                $error = "Invalid username or password";
            }
            $stmt->close();
        } else {
            $error = "System error. Please try again later.";
        }
    }
}

$conn->close();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sari-Sari Store Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #dbeafe;
            --accent-color: #93c5fd;
            --dark-blue: #1e3a8a;
            --light-blue: #3b82f6;
            --success-color: #059669;
            --warning-color: #f59e0b;
            --info-color: #0ea5e9;
        }
        
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 25%, #60a5fa 50%, #93c5fd 75%, #dbeafe 100%);
            font-family: 'Nunito', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            z-index: -1;
        }
        
        .login-card {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(147, 197, 253, 0.1), transparent);
            transform: rotate(45deg);
            animation: shimmer 6s infinite linear;
            z-index: 0;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        .login-card:hover {
            transform: translateY(-10px) scale(1.01);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .logo {
            width: 90px;
            height: 90px;
            background: var(--primary-color);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.3);
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: rotate(15deg) scale(1.1);
        }
        
        .store-name {
            font-weight: 800;
            font-size: 28px;
            background: linear-gradient(45deg, var(--primary-color), var(--light-blue));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .store-tagline {
            color: var(--light-blue);
            font-weight: 500;
            font-size: 14px;
            position: relative;
            display: inline-block;
        }
        
        .store-tagline::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 25%;
            width: 50%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
        }
        
        .form-control {
            border-radius: 10px;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(30, 64, 175, 0.25);
            background: #fff;
            transform: translateY(-2px);
        }
        
        .input-group-text {
            background: transparent;
            border-radius: 10px 0 0 10px;
            border: 2px solid #e9ecef;
            border-right: none;
            transition: all 0.3s;
        }
        
        .form-control:focus + .input-group-text,
        .form-control:focus ~ .input-group-text {
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--light-blue));
            border: none;
            padding: 15px;
            font-weight: 700;
            border-radius: 10px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.3);
            letter-spacing: 0.5px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.4);
            background: linear-gradient(135deg, var(--light-blue), var(--primary-color));
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), var(--accent-color));
            border: none;
            padding: 15px;
            font-weight: 700;
            border-radius: 10px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.4);
            background: linear-gradient(135deg, var(--accent-color), var(--success-color));
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6e707e;
            z-index: 5;
            background: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .password-toggle:hover {
            background: var(--secondary-color);
            transform: translateY(-50%) scale(1.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            text-align: center;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .centered-alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            min-width: 300px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideIn 0.5s ease-out;
            display: none;
            border-radius: 15px;
            border: none;
        }
        
        @keyframes slideIn {
            from {
                top: -100px;
                opacity: 0;
            }
            to {
                top: 20px;
                opacity: 1;
            }
        }
        
        .floating-items {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }
        
        .floating-item {
            position: absolute;
            width: 30px;
            height: 30px;
            background-size: contain;
            background-repeat: no-repeat;
            opacity: 0.7;
            animation: float 15s infinite linear;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
            }
        }
        
        .floating-item:nth-child(1) {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%231e40af"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7z"/><circle cx="12" cy="12" r="3"/></svg>');
            top: 10%;
            left: 10%;
            animation-duration: 20s;
        }
        
        .floating-item:nth-child(2) {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%231e3a8a"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>');
            top: 20%;
            right: 15%;
            animation-duration: 25s;
        }
        
        .floating-item:nth-child(3) {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23059669"><path d="M7.5 12.5h-1V15h1v-2.5zm0-3h-1V12h1V9.5zm0-3h-1V9h1V6.5zm2-3h-1V6h1V3.5zm0 3h-1V9h1V6.5zm0 3h-1V12h1V9.5zm0 3h-1V15h1v-2.5zm2-9h-1V6h1V3.5zm0 3h-1V9h1V6.5zm0 3h-1V12h1V9.5zm0 3h-1V15h1v-2.5zm2-9h-1V6h1V3.5zm0 3h-1V9h1V6.5zm0 3h-1V12h1V9.5zm0 3h-1V15h1v-2.5zm5-10.5V6h-3.5V3.5H21zm0 3V9h-3.5V6.5H21zm0 3V12h-3.5V9.5H21zm0 3V15h-3.5v-2.5H21z"/></svg>');
            bottom: 15%;
            left: 15%;
            animation-duration: 18s;
        }
        
        .floating-item:nth-child(4) {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%233b82f6"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.39-2.1 1.39-1.6 0-2.23-.72-2.32-1.64H8.04c.1 1.7 1.36 2.66 2.86 2.97V19h2.34v-1.67c1.52-.29 2.72-1.16 2.73-2.77-.01-2.2-1.9-2.96-3.66-3.42z"/></svg>');
            bottom: 25%;
            right: 20%;
            animation-duration: 22s;
        }
        
        @media (max-width: 576px) {
            .login-card {
                margin: 20px;
                padding: 25px;
            }
            
            .centered-alert {
                left: 20px;
                right: 20px;
                transform: none;
                min-width: auto;
            }
            
            .logo {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }
            
            .store-name {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<!-- Floating decorative items -->
<div class="floating-items">
    <div class="floating-item"></div>
    <div class="floating-item"></div>
    <div class="floating-item"></div>
    <div class="floating-item"></div>
</div>

<!-- Centered Alert (initially hidden) -->
<div id="loginAlert" class="alert alert-info centered-alert alert-dismissible fade" role="alert">
    <i class="fas fa-spinner fa-spin me-2"></i>
    <strong>Logging in...</strong> Please wait while we process your request.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Success Alert (initially hidden) -->
<div id="successAlert" class="alert alert-success centered-alert alert-dismissible fade" role="alert" style="display: none;">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Login Successful!</strong> Redirecting to dashboard...
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="login-card">
    <div class="logo-container">
        <div class="logo">
            <i class="fas fa-store"></i>
        </div>
        <h1 class="store-name">Sari-Sari Store</h1>
        <p class="store-tagline">Your neighborhood convenience</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" id="loginForm">
        <input type="hidden" name="login" value="1">
        
        <div class="mb-4">
            <label for="username" class="form-label fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="username" id="username" placeholder="Enter your username" 
                       required class="form-control" autocomplete="username">
            </div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label fw-semibold">Password</label>
            <div class="password-container">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password" placeholder="Enter your password" 
                           required class="form-control" autocomplete="current-password">
                </div>
                <span class="password-toggle" id="passwordToggle">
                    <i class="far fa-eye"></i>
                </span>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 mb-3" id="loginButton">
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
        
        <div class="text-center">
            <a href="show_products.php" class="btn btn-success w-100">
                <i class="fas fa-shopping-basket me-2"></i>View Products Without Login
            </a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password visibility
    document.getElementById('passwordToggle').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Show centered alert when login button is clicked
    document.getElementById('loginButton').addEventListener('click', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username) {
            e.preventDefault();
            showAlert('Please enter your username', 'danger');
            document.getElementById('username').focus();
            return false;
        }
        
        if (!password) {
            e.preventDefault();
            showAlert('Please enter your password', 'danger');
            document.getElementById('password').focus();
            return false;
        }
        
        // If validation passes, show the logging in alert
        showAlert('Logging in... Please wait while we process your request.', 'info');
    });
    
    // Function to show alert
    function showAlert(message, type) {
        const alertDiv = document.getElementById('loginAlert');
        
        // Remove previous alert classes
        alertDiv.classList.remove('alert-info', 'alert-danger', 'alert-success', 'alert-warning');
        
        // Add appropriate class based on type
        alertDiv.classList.add('alert-' + type);
        
        // Set message
        let icon = '';
        switch(type) {
            case 'danger':
                icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                break;
            case 'success':
                icon = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'warning':
                icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            default:
                icon = '<i class="fas fa-spinner fa-spin me-2"></i>';
        }
        
        alertDiv.innerHTML = `
            ${icon}
            <strong>${message}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Show alert with animation
        alertDiv.style.display = 'block';
        alertDiv.classList.add('show');
        
        // Auto-dismiss alert after 5 seconds if it's not an error
        if (type !== 'danger') {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }
    }
    
    // Check if login was successful and show success alert
    <?php if ($login_success): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Show success alert
        const successAlert = document.getElementById('successAlert');
        successAlert.style.display = 'block';
        successAlert.classList.add('show');
        
        // Redirect to dashboard after 2 seconds
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 2000);
    });
    <?php endif; ?>
</script>
</body>
</html>