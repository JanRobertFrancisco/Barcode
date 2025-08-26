<?php
session_start();
include 'db.php';

// Security check – only admin can access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get admin ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_management.php");
    exit();
}

$id = intval($_GET['id']);
$success = '';
$error = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch admin info
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("❌ Admin not found.");
}

// Update admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("⚠️ Invalid request.");
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username)) {
        $error = "❌ Username cannot be empty.";
    } else {
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error = "⚠️ Password must be at least 6 characters.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ? AND role = 'admin'");
                $stmt->bind_param("ssi", $username, $hashed, $id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ? AND role = 'admin'");
            $stmt->bind_param("si", $username, $id);
        }

        if (empty($error)) {
            if ($stmt->execute()) {
                $success = "✅ Admin updated successfully!";
            } else {
                $error = "❌ Error updating admin: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Admin</title>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            margin: 0;
            transition: background 0.3s, color 0.3s;
            color: var(--text);
        }
        :root {
            --bg: #f5f7fb;
            --card-bg: rgba(255, 255, 255, 0.9);
            --text: #2c3e50;
            --primary: #4361ee;
            --secondary: #6c757d;
        }
        [data-theme="dark"] {
            --bg: #1e1e2f;
            --card-bg: rgba(30, 30, 47, 0.95);
            --text: #eaeaea;
            --primary: #5a7dff;
            --secondary: #9aa0ac;
        }
        .container {
            margin-left: 250px;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            max-width: 550px;
            width: 100%;
            animation: fadeIn 0.4s ease;
            position: relative;
        }
        h2 { margin-bottom: 20px; text-align: center; }
        .toggle-mode {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            font-size: 20px;
        }
        .input-wrapper { position: relative; }
        .input {
            width: 100%;
            padding: 12px 40px 12px 38px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            transition: all 0.2s;
        }
        .input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67,97,238,0.2);
            outline: none;
        }
        .input-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            font-size: 16px;
            color: #666;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 14px;
        }
        .btn {
            padding: 12px 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4361ee, #5a7dff);
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(67,97,238,0.4); }
        .btn-secondary {
            background: var(--secondary);
            color: #fff;
            text-decoration: none;
            margin-left: 10px;
        }
        .msg {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            animation: fadeIn 0.3s ease;
        }
        .success { background: #d1e7dd; color: #0f5132; }
        .error { background: #f8d7da; color: #842029; }
        @keyframes fadeIn {
            from {opacity:0; transform:translateY(-5px);}
            to {opacity:1; transform:translateY(0);}
        }
        @media (max-width: 768px) {
            .container { margin-left: 0; padding: 20px; }
            .card { padding: 20px; border-radius: 15px; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="container">
        <div class="card">
            <span class="toggle-mode" onclick="toggleTheme()">🌙</span>
            <h2>✏️ Edit Admin</h2>

            <?php if ($success): ?><div class="msg success" id="msg"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg error" id="msg"><?= $error ?></div><?php endif; ?>

            <form method="POST" onsubmit="return confirm('Update this admin?');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                <label>Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" name="username" class="input" value="<?= htmlspecialchars($admin['username']); ?>" required>
                </div>

                <label>New Password (leave blank to keep current)</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" class="input" id="password">
                    <span class="password-toggle" onclick="togglePassword()">👁</span>
                </div>

                <button type="submit" class="btn btn-primary">💾 Save</button>
                <a href="admin_management.php" class="btn btn-secondary">⬅ Back</a>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById("password");
            const toggle = document.querySelector(".password-toggle");
            if (input.type === "password") {
                input.type = "text";
                toggle.textContent = "🙈";
            } else {
                input.type = "password";
                toggle.textContent = "👁";
            }
        }
        setTimeout(() => {
            const msg = document.getElementById("msg");
            if (msg) msg.style.display = "none";
        }, 4000);
        function toggleTheme() {
            const body = document.body;
            if (body.getAttribute("data-theme") === "dark") {
                body.removeAttribute("data-theme");
                document.querySelector(".toggle-mode").textContent = "🌙";
            } else {
                body.setAttribute("data-theme", "dark");
                document.querySelector(".toggle-mode").textContent = "☀️";
            }
        }
    </script>
</body>
</html>
