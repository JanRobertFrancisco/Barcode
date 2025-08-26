<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check user permissions if needed (uncomment if you have role-based access)
/*
if ($_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}
*/

include 'db.php';

// Initialize variables
$settings = [];
$success = '';
$error = '';
$receipt_header = '';
$receipt_footer = '';

// Fetch current receipt settings
$result = $conn->query("SELECT * FROM receipt_settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    // Initialize with empty values if no settings exist
    $settings = ['receipt_header' => '', 'receipt_footer' => ''];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security validation failed. Please try again.";
    } else {
        $receipt_header = trim($_POST['receipt_header']);
        $receipt_footer = trim($_POST['receipt_footer']);
        
        // Validate inputs
        if (empty($receipt_header) || empty($receipt_footer)) {
            $error = "Both header and footer are required.";
        } else {
            // Check if settings already exist
            $check = $conn->query("SELECT id FROM receipt_settings LIMIT 1");
            
            if ($check && $check->num_rows > 0) {
                // Update existing settings
                $stmt = $conn->prepare("UPDATE receipt_settings SET receipt_header=?, receipt_footer=?, updated_at=NOW() WHERE id=?");
                $stmt->bind_param("ssi", $receipt_header, $receipt_footer, $settings['id']);
            } else {
                // Insert new settings
                $stmt = $conn->prepare("INSERT INTO receipt_settings (receipt_header, receipt_footer, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->bind_param("ss", $receipt_header, $receipt_footer);
            }
            
            if ($stmt->execute()) {
                $success = "Receipt settings saved successfully.";
                // Refresh settings
                $result = $conn->query("SELECT * FROM receipt_settings LIMIT 1");
                if ($result && $result->num_rows > 0) {
                    $settings = $result->fetch_assoc();
                }
            } else {
                $error = "Failed to save receipt settings: " . $conn->error;
            }
            
            $stmt->close();
        }
    }
}

// Generate CSRF token for this request
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --border-color: #ddd;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f2f5;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: margin-left 0.3s;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        input[type="text"], textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .char-count {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }
        
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: var(--secondary-color);
        }
        
        .success {
            background-color: #d4edda;
            color: var(--success-color);
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid var(--success-color);
        }
        
        .error {
            background-color: #f8d7da;
            color: var(--error-color);
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid var(--error-color);
        }
        
        .preview-section {
            margin-top: 40px;
        }
        
        .preview-container {
            border: 1px dashed var(--border-color);
            padding: 20px;
            margin-top: 15px;
            background: white;
            border-radius: 4px;
        }
        
        .preview-header, .preview-footer {
            text-align: center;
            padding: 10px;
            background: var(--light-gray);
            margin-bottom: 15px;
        }
        
        .preview-content {
            padding: 15px;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .last-updated {
            font-size: 14px;
            color: #777;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2>Receipt Settings</h2>
        
        <div class="card">
            <?php if($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="receiptSettingsForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="receipt_header">Receipt Header:</label>
                    <textarea name="receipt_header" id="receipt_header" required><?php echo htmlspecialchars($settings['receipt_header'] ?? ''); ?></textarea>
                    <div class="char-count">Character count: <span id="headerCharCount">0</span></div>
                </div>
                
                <div class="form-group">
                    <label for="receipt_footer">Receipt Footer:</label>
                    <textarea name="receipt_footer" id="receipt_footer" required><?php echo htmlspecialchars($settings['receipt_footer'] ?? ''); ?></textarea>
                    <div class="char-count">Character count: <span id="footerCharCount">0</span></div>
                </div>
                
                <?php if(isset($settings['updated_at'])): ?>
                    <div class="last-updated">
                        Last updated: <?php echo date('F j, Y, g:i a', strtotime($settings['updated_at'])); ?>
                    </div>
                <?php endif; ?>

                <div class="actions">
                    <button type="submit">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.reload()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </form>
        </div>
        
        <div class="preview-section card">
            <h3>Receipt Preview</h3>
            <p>See how your receipt will look with the current settings:</p>
            
            <div class="preview-container">
                <div class="preview-header" id="previewHeader">
                    <?php echo nl2br(htmlspecialchars($settings['receipt_header'] ?? 'Your receipt header will appear here')); ?>
                </div>
                
                <div class="preview-content">
                    <p><strong>Sample Receipt Content</strong></p>
                    <p>Item 1: $10.00</p>
                    <p>Item 2: $15.50</p>
                    <p>Total: $25.50</p>
                </div>
                
                <div class="preview-footer" id="previewFooter">
                    <?php echo nl2br(htmlspecialchars($settings['receipt_footer'] ?? 'Your receipt footer will appear here')); ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update character count and preview in real-time
        document.addEventListener('DOMContentLoaded', function() {
            const headerTextarea = document.getElementById('receipt_header');
            const footerTextarea = document.getElementById('receipt_footer');
            const headerCharCount = document.getElementById('headerCharCount');
            const footerCharCount = document.getElementById('footerCharCount');
            const previewHeader = document.getElementById('previewHeader');
            const previewFooter = document.getElementById('previewFooter');
            
            // Initialize character counts
            headerCharCount.textContent = headerTextarea.value.length;
            footerCharCount.textContent = footerTextarea.value.length;
            
            // Add event listeners for real-time updates
            headerTextarea.addEventListener('input', function() {
                headerCharCount.textContent = this.value.length;
                previewHeader.innerHTML = this.value.replace(/\n/g, '<br>');
            });
            
            footerTextarea.addEventListener('input', function() {
                footerCharCount.textContent = this.value.length;
                previewFooter.innerHTML = this.value.replace(/\n/g, '<br>');
            });
            
            // Form validation
            document.getElementById('receiptSettingsForm').addEventListener('submit', function(e) {
                const header = headerTextarea.value.trim();
                const footer = footerTextarea.value.trim();
                
                if (!header || !footer) {
                    e.preventDefault();
                    alert('Both header and footer are required.');
                    return false;
                }
                
                if (header.length > 500) {
                    e.preventDefault();
                    alert('Header is too long. Maximum 500 characters allowed.');
                    return false;
                }
                
                if (footer.length > 500) {
                    e.preventDefault();
                    alert('Footer is too long. Maximum 500 characters allowed.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>