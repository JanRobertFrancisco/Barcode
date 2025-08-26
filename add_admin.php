<?php
session_start();
ob_start();

// Database connection
$conn = null;
try {
    $conn = new mysqli("localhost", "root", "", "admin");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Only allow logged-in admins
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// ✅ Handle Add Admin Form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "❌ All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match.";
    } else {
        // Check duplicate username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "❌ Username already exists!";
        } else {
            // Insert new admin
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $role = "admin";

            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);

            if ($stmt->execute()) {
                $success = "✅ New admin added successfully!";
                header("Location: admin_management.php?success=" . urlencode($success));
                exit();
            } else {
                $error = "❌ Error adding admin.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin | Admin Panel</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            margin-left: 250px;
            padding: 30px;
            transition: var(--transition);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            padding: 20px 25px;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .input-with-eyelash {
            position: relative;
        }
        
        .eyelash-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            display: flex;
            justify-content: space-between;
            pointer-events: none;
            z-index: 2;
        }
        
        .eyelash {
            height: 8px;
            flex: 1;
            margin: 0 2px;
            background: linear-gradient(to bottom, var(--primary) 0%, rgba(67, 97, 238, 0.7) 100%);
            border-bottom-left-radius: 50% 80%;
            border-bottom-right-radius: 50% 80%;
            transform: scaleY(0.9);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .eyelash:nth-child(2n) {
            background: linear-gradient(to bottom, var(--secondary) 0%, rgba(63, 55, 201, 0.7) 100%);
            height: 7px;
        }
        
        .eyelash:nth-child(3n) {
            background: linear-gradient(to bottom, var(--success) 0%, rgba(76, 201, 240, 0.7) 100%);
            height: 6px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
            background: #f9fafc;
            position: relative;
            z-index: 1;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background: #fff;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: var(--gray);
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: rgba(76, 201, 240, 0.15);
            color: #0c5460;
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: rgba(247, 37, 133, 0.15);
            color: #721c24;
            border-left: 4px solid var(--danger);
        }
        
        .password-strength {
            height: 5px;
            background: #eee;
            border-radius: 3px;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
            display: none;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            border-radius: 3px;
            transition: width 0.3s;
        }
        
        .password-requirements {
            margin-top: 15px;
            font-size: 14px;
            color: var(--gray);
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        
        .requirement i {
            font-size: 12px;
        }
        
        .requirement.met {
            color: #28a745;
        }
        
        @media (max-width: 992px) {
            .container {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-user-plus"></i> Add New Admin</h1>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-shield"></i> Admin Information
            </div>
            <div class="card-body">
                <form method="POST" id="addAdminForm">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-user"></i> Username</label>
                        <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                        <div class="input-with-eyelash">
                            <div class="eyelash-container" id="passwordEyelash"></div>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement" id="lengthReq"><i class="fas fa-circle"></i> At least 8 characters</div>
                            <div class="requirement" id="numberReq"><i class="fas fa-circle"></i> Contains a number</div>
                            <div class="requirement" id="specialReq"><i class="fas fa-circle"></i> Contains a special character</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                        <div class="input-with-eyelash">
                            <div class="eyelash-container" id="confirmPasswordEyelash"></div>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn"><i class="fas fa-plus-circle"></i> Add Admin</button>
                </form>
            </div>
        </div>

        <a href="admin_management.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Admin Management</a>
    </div>

    <script>
        // Create eyelash effect for password fields
        function createEyelash(containerId) {
            const container = document.getElementById(containerId);
            const eyelashCount = 15; // Number of eyelashes
            
            for (let i = 0; i < eyelashCount; i++) {
                const eyelash = document.createElement('div');
                eyelash.classList.add('eyelash');
                container.appendChild(eyelash);
            }
        }
        
        // Initialize eyelash effects
        createEyelash('passwordEyelash');
        createEyelash('confirmPasswordEyelash');
        
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthContainer = document.getElementById('passwordStrength');
        const lengthReq = document.getElementById('lengthReq');
        const numberReq = document.getElementById('numberReq');
        const specialReq = document.getElementById('specialReq');
        
        passwordInput.addEventListener('focus', function() {
            strengthContainer.style.display = 'block';
        });
        
        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;
            
            // Check length
            if (password.length >= 8) {
                strength += 25;
                lengthReq.classList.add('met');
                lengthReq.innerHTML = '<i class="fas fa-check-circle"></i> At least 8 characters';
            } else {
                lengthReq.classList.remove('met');
                lengthReq.innerHTML = '<i class="fas fa-circle"></i> At least 8 characters';
            }
            
            // Check for numbers
            if (/\d/.test(password)) {
                strength += 25;
                numberReq.classList.add('met');
                numberReq.innerHTML = '<i class="fas fa-check-circle"></i> Contains a number';
            } else {
                numberReq.classList.remove('met');
                numberReq.innerHTML = '<i class="fas fa-circle"></i> Contains a number';
            }
            
            // Check for special characters
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                strength += 25;
                specialReq.classList.add('met');
                specialReq.innerHTML = '<i class="fas fa-check-circle"></i> Contains a special character';
            } else {
                specialReq.classList.remove('met');
                specialReq.innerHTML = '<i class="fas fa-circle"></i> Contains a special character';
            }
            
            // Check for uppercase and lowercase
            if (password.length > 0 && /[a-z]/.test(password) && /[A-Z]/.test(password)) {
                strength += 25;
            }
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.style.background = '#f72585';
            } else if (strength < 75) {
                strengthBar.style.background = '#ffaa00';
            } else {
                strengthBar.style.background = '#4cc9f0';
            }
        });
        
        // Form validation
        document.getElementById('addAdminForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>