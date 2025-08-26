<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Fetch current settings and admin user
$settings = $conn->query("
    SELECT s.*, u.id AS admin_id, u.username, u.password
    FROM settings s
    JOIN users u ON s.admin_user_id = u.id
    LIMIT 1
")->fetch_assoc();

// Check if new columns exist in the database and set default values if not
$defaultSettings = [
    'receipt_width' => 300,
    'receipt_logo' => '🛒',
    'receipt_show_logo' => 1,
    'receipt_show_border' => 0,
    'receipt_font_size' => 14,
    'receipt_show_date' => 1,
    'receipt_message' => 'We appreciate your business!'
];

foreach ($defaultSettings as $key => $defaultValue) {
    if (!isset($settings[$key])) {
        $settings[$key] = $defaultValue;
    }
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = trim($_POST['store_name']);
    $store_address = trim($_POST['store_address']);
    $store_contact = trim($_POST['store_contact']);
    $currency_symbol = trim($_POST['currency_symbol']);
    $receipt_header = trim($_POST['receipt_header']);
    $receipt_footer = trim($_POST['receipt_footer']);
    $receipt_width = intval($_POST['receipt_width']);
    $receipt_logo = trim($_POST['receipt_logo']);
    $receipt_show_logo = isset($_POST['receipt_show_logo']) ? 1 : 0;
    $receipt_show_border = isset($_POST['receipt_show_border']) ? 1 : 0;
    $receipt_font_size = intval($_POST['receipt_font_size']);
    $receipt_show_date = isset($_POST['receipt_show_date']) ? 1 : 0;
    $receipt_message = trim($_POST['receipt_message']);

    // Validate inputs
    if (empty($store_name)) {
        $error = "Store name cannot be empty!";
    } else {
        // Use transaction for data consistency
        $conn->begin_transaction();
        
        try {
            if ($settings) {
                // First, check if we need to alter the table to add new columns
                $checkColumns = $conn->query("SHOW COLUMNS FROM settings LIKE 'receipt_width'");
                if ($checkColumns->num_rows == 0) {
                    // Add the new columns to the settings table
                    $alterTableSQL = "
                        ALTER TABLE settings 
                        ADD COLUMN receipt_width INT DEFAULT 300,
                        ADD COLUMN receipt_logo VARCHAR(10) DEFAULT '🛒',
                        ADD COLUMN receipt_show_logo TINYINT(1) DEFAULT 1,
                        ADD COLUMN receipt_show_border TINYINT(1) DEFAULT 0,
                        ADD COLUMN receipt_font_size INT DEFAULT 14,
                        ADD COLUMN receipt_show_date TINYINT(1) DEFAULT 1,
                        ADD COLUMN receipt_message TEXT
                    ";
                    $conn->query($alterTableSQL);
                }

                // Update settings with all fields
                $stmtSettings = $conn->prepare("UPDATE settings SET store_name=?, store_address=?, store_contact=?, currency_symbol=?, receipt_header=?, receipt_footer=?, receipt_width=?, receipt_logo=?, receipt_show_logo=?, receipt_show_border=?, receipt_font_size=?, receipt_show_date=?, receipt_message=? WHERE id=?");
                $stmtSettings->bind_param("ssssssisisiiis", $store_name, $store_address, $store_contact, $currency_symbol, $receipt_header, $receipt_footer, $receipt_width, $receipt_logo, $receipt_show_logo, $receipt_show_border, $receipt_font_size, $receipt_show_date, $receipt_message, $settings['id']);
                $stmtSettings->execute();
                $stmtSettings->close();
            } else {
                // Get admin user ID (assuming there's at least one admin)
                $adminResult = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
                if ($adminResult->num_rows > 0) {
                    $admin = $adminResult->fetch_assoc();
                    $admin_user_id = $admin['id'];
                    
                    // Check if we need to alter the table to add new columns
                    $checkColumns = $conn->query("SHOW COLUMNS FROM settings LIKE 'receipt_width'");
                    if ($checkColumns->num_rows == 0) {
                        // Add the new columns to the settings table
                        $alterTableSQL = "
                            ALTER TABLE settings 
                            ADD COLUMN receipt_width INT DEFAULT 300,
                            ADD COLUMN receipt_logo VARCHAR(10) DEFAULT '🛒',
                            ADD COLUMN receipt_show_logo TINYINT(1) DEFAULT 1,
                            ADD COLUMN receipt_show_border TINYINT(1) DEFAULT 0,
                            ADD COLUMN receipt_font_size INT DEFAULT 14,
                            ADD COLUMN receipt_show_date TINYINT(1) DEFAULT 1,
                            ADD COLUMN receipt_message TEXT
                        ";
                        $conn->query($alterTableSQL);
                    }

                    // Create settings row with all fields
                    $stmtSettings = $conn->prepare("INSERT INTO settings (store_name, store_address, store_contact, currency_symbol, receipt_header, receipt_footer, receipt_width, receipt_logo, receipt_show_logo, receipt_show_border, receipt_font_size, receipt_show_date, receipt_message, admin_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtSettings->bind_param("ssssssisisiiis", $store_name, $store_address, $store_contact, $currency_symbol, $receipt_header, $receipt_footer, $receipt_width, $receipt_logo, $receipt_show_logo, $receipt_show_border, $receipt_font_size, $receipt_show_date, $receipt_message, $admin_user_id);
                    $stmtSettings->execute();
                    $stmtSettings->close();
                } else {
                    throw new Exception("No admin user found in the system");
                }
            }
            
            $conn->commit();
            $success = "Settings updated successfully!";
            
            // Refresh settings data
            $settings = $conn->query("
                SELECT s.*, u.id AS admin_id, u.username, u.password
                FROM settings s
                JOIN users u ON s.admin_user_id = u.id
                LIMIT 1
            ")->fetch_assoc();
            
            // Update the settings array with default values for any missing columns
            foreach ($defaultSettings as $key => $defaultValue) {
                if (!isset($settings[$key])) {
                    $settings[$key] = $defaultValue;
                }
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error updating settings: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Sari-Sari Store</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    :root {
        --primary: #142883;
        --primary-light: #1d3ab3;
        --secondary: #2ecc71;
        --danger: #e74c3c;
        --dark: #333;
        --light: #f9f9f9;
        --gray: #ccc;
        --white: #fff;
        --shadow: 0 4px 15px rgba(0,0,0,0.1);
        --shadow-hover: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        min-height: 100vh;
        background: var(--light);
        color: var(--dark);
    }

    .main-content {
        margin-left: 220px;
        padding: 30px;
        flex-grow: 1;
        transition: margin 0.3s ease;
    }

    h1 {
        margin-bottom: 25px;
        font-size: 28px;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .settings-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .settings-form {
        background: var(--white);
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: var(--shadow);
        transition: box-shadow 0.3s ease;
        grid-column: 1;
    }
    
    .settings-form:hover {
        box-shadow: var(--shadow-hover);
    }
    
    .preview-pane {
        background: var(--white);
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: var(--shadow);
        grid-column: 2;
    }

    .form-section {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .form-section h3 {
        margin-top: 0;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .settings-form label {
        display: block;
        margin-top: 18px;
        font-weight: 600;
        color: var(--dark);
    }

    .settings-form input, 
    .settings-form textarea, 
    .settings-form select {
        width: 100%;
        padding: 12px;
        margin-top: 6px;
        border-radius: 6px;
        border: 1px solid var(--gray);
        font-size: 15px;
        transition: border 0.2s ease;
        box-sizing: border-box;
    }

    .settings-form input:focus,
    .settings-form textarea:focus,
    .settings-form select:focus {
        border-color: var(--primary);
        outline: none;
    }
    
    .settings-form textarea {
        min-height: 80px;
        resize: vertical;
    }
    
    .settings-form .checkbox-group {
        display: flex;
        align-items: center;
        margin-top: 15px;
    }
    
    .settings-form .checkbox-group input {
        width: auto;
        margin-right: 10px;
    }

    .settings-form button {
        margin-top: 25px;
        padding: 14px 22px;
        background: var(--primary);
        border: none;
        color: var(--white);
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: background 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .settings-form button:hover {
        background: var(--primary-light);
    }
    
    .alert {
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .success {
        background: var(--secondary);
        color: var(--white);
    }
    
    .error {
        background: var(--danger);
        color: var(--white);
    }
    
    .preview-receipt {
        border: 1px dashed var(--gray);
        padding: 15px;
        margin-top: 15px;
        font-family: monospace;
        background: var(--white);
        width: <?= $settings['receipt_width'] ?>px;
        font-size: <?= $settings['receipt_font_size'] ?>px;
        <?= $settings['receipt_show_border'] ? 'border: 1px solid #000;' : '' ?>
    }
    
    .preview-receipt .header {
        text-align: center;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .preview-receipt .logo {
        text-align: center;
        margin-bottom: 10px;
        font-size: 24px;
    }
    
    .preview-receipt .footer {
        text-align: center;
        margin-top: 10px;
        font-size: 12px;
    }
    
    .preview-receipt .items {
        margin: 10px 0;
        width: 100%;
    }
    
    .preview-receipt .items div {
        display: flex;
        justify-content: space-between;
    }
    
    .preview-receipt .total {
        border-top: 1px solid var(--gray);
        padding-top: 5px;
        margin-top: 10px;
        font-weight: bold;
    }
    
    .preview-receipt .date {
        text-align: center;
        margin-top: 5px;
        font-size: 12px;
    }
    
    .preview-receipt .message {
        text-align: center;
        margin-top: 10px;
        font-style: italic;
    }

    /* Tabs styling */
    .tabs {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--gray);
    }
    
    .tab-button {
        padding: 12px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: var(--dark);
        opacity: 0.7;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }
    
    .tab-button.active {
        opacity: 1;
        color: var(--primary);
        border-bottom: 3px solid var(--primary);
    }
    
    .tab-button:hover {
        opacity: 1;
        background: #f0f0f0;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* Range slider styling */
    .range-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .range-value {
        min-width: 40px;
        text-align: center;
        font-weight: bold;
    }
    
    /* Two-column layout for receipt settings */
    .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    /* Print test button */
    .print-test {
        margin-top: 15px;
        padding: 10px 15px;
        background: var(--primary-light);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .print-test:hover {
        background: var(--primary);
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    @media(max-width: 1024px) {
        .settings-container {
            grid-template-columns: 1fr;
        }
        
        .preview-pane {
            grid-column: 1;
            grid-row: 3;
        }
        
        .two-columns {
            grid-template-columns: 1fr;
        }
    }
    
    @media(max-width: 768px){
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        
        .tabs {
            flex-direction: column;
        }
        
        .tab-button {
            border-bottom: 1px solid var(--gray);
            border-left: 3px solid transparent;
        }
        
        .tab-button.active {
            border-bottom: 1px solid var(--gray);
            border-left: 3px solid var(--primary);
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h1><i class="fas fa-cog"></i> Store Settings</h1>

    <?php if($success): ?>
        <div class="alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php elseif($error): ?>
        <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="settings-container">
        <div class="settings-form">
            <div class="tabs">
                <button type="button" class="tab-button active" data-tab="store-info">Store Information</button>
                <button type="button" class="tab-button" data-tab="receipt-settings">Receipt Settings</button>
            </div>
            
            <form method="post" id="settingsForm">
                <div class="tab-content active" id="store-info-tab">
                    <div class="form-section">
                        <h3><i class="fas fa-store"></i> Store Information</h3>
                        
                        <label for="store_name">Store Name *</label>
                        <input type="text" name="store_name" id="store_name" value="<?= htmlspecialchars($settings['store_name'] ?? 'Sari-Sari Store'); ?>" required>

                        <label for="store_address">Store Address</label>
                        <textarea name="store_address" id="store_address"><?= htmlspecialchars($settings['store_address'] ?? ''); ?></textarea>

                        <label for="store_contact">Contact Information</label>
                        <input type="text" name="store_contact" id="store_contact" value="<?= htmlspecialchars($settings['store_contact'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="tab-content" id="receipt-settings-tab">
                    <div class="form-section">
                        <h3><i class="fas fa-receipt"></i> Receipt Layout & Design</h3>
                        
                        <div class="two-columns">
                            <div>
                                <label for="receipt_width">Receipt Width (px)</label>
                                <div class="range-container">
                                    <input type="range" name="receipt_width" id="receipt_width" min="200" max="500" value="<?= $settings['receipt_width']; ?>" oninput="updateReceiptPreview()">
                                    <span class="range-value" id="width_value"><?= $settings['receipt_width']; ?></span>
                                </div>
                            </div>
                            
                            <div>
                                <label for="receipt_font_size">Font Size (px)</label>
                                <div class="range-container">
                                    <input type="range" name="receipt_font_size" id="receipt_font_size" min="10" max="20" value="<?= $settings['receipt_font_size']; ?>" oninput="updateReceiptPreview()">
                                    <span class="range-value" id="font_size_value"><?= $settings['receipt_font_size']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <label for="receipt_logo">Logo (Text or Emoji)</label>
                        <input type="text" name="receipt_logo" id="receipt_logo" value="<?= htmlspecialchars($settings['receipt_logo']); ?>" placeholder="e.g., 🛒 or STORE" oninput="updateReceiptPreview()">
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="receipt_show_logo" id="receipt_show_logo" value="1" <?= $settings['receipt_show_logo'] ? 'checked' : '' ?> onchange="updateReceiptPreview()">
                            <label for="receipt_show_logo">Show Logo on Receipt</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="receipt_show_border" id="receipt_show_border" value="1" <?= $settings['receipt_show_border'] ? 'checked' : '' ?> onchange="updateReceiptPreview()">
                            <label for="receipt_show_border">Show Border Around Receipt</label>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3><i class="fas fa-cog"></i> Receipt Content</h3>
                        
                        <label for="currency_symbol">Currency Symbol</label>
                        <input type="text" name="currency_symbol" id="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '₱'); ?>" maxlength="3" oninput="updateReceiptPreview()">

                        <label for="receipt_header">Receipt Header Text</label>
                        <textarea name="receipt_header" id="receipt_header" oninput="updateReceiptPreview()"><?= htmlspecialchars($settings['receipt_header'] ?? 'Thank you for your purchase!'); ?></textarea>

                        <label for="receipt_footer">Receipt Footer Text</label>
                        <textarea name="receipt_footer" id="receipt_footer" oninput="updateReceiptPreview()"><?= htmlspecialchars($settings['receipt_footer'] ?? 'Please come again!'); ?></textarea>
                        
                        <label for="receipt_message">Thank You Message</label>
                        <textarea name="receipt_message" id="receipt_message" oninput="updateReceiptPreview()"><?= htmlspecialchars($settings['receipt_message']); ?></textarea>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="receipt_show_date" id="receipt_show_date" value="1" <?= $settings['receipt_show_date'] ? 'checked' : '' ?> onchange="updateReceiptPreview()">
                            <label for="receipt_show_date">Show Date/Time</label>
                        </div>
                    </div>
                </div>

                <button type="submit"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>

        <div class="preview-pane">
            <h3><i class="fas fa-receipt"></i> Receipt Preview</h3>
            <p>This is how your receipts will look with the current settings:</p>
            
            <div class="preview-receipt" id="receipt-preview">
                <?php if ($settings['receipt_show_logo']): ?>
                <div class="logo"><?= htmlspecialchars($settings['receipt_logo']) ?></div>
                <?php endif; ?>
                
                <div class="header"><?= htmlspecialchars($settings['store_name'] ?? 'Sari-Sari Store'); ?></div>
                <div class="header"><?= htmlspecialchars($settings['store_address'] ?? ''); ?></div>
                <div class="header"><?= htmlspecialchars($settings['store_contact'] ?? ''); ?></div>
                
                <div class="header"><?= htmlspecialchars($settings['receipt_header'] ?? 'Thank you for your purchase!'); ?></div>
                
                <?php if ($settings['receipt_show_date']): ?>
                <div class="date"><?= date('Y-m-d H:i:s'); ?></div>
                <?php endif; ?>
                
                <div class="items">
                    <div>
                        <span>Product 1 x 2</span>
                        <span><?= htmlspecialchars($settings['currency_symbol'] ?? '₱') ?>100.00</span>
                    </div>
                    <div>
                        <span>Product 2 x 1</span>
                        <span><?= htmlspecialchars($settings['currency_symbol'] ?? '₱') ?>50.00</span>
                    </div>
                </div>
                
                <div class="total">
                    <div>Total: <?= htmlspecialchars($settings['currency_symbol'] ?? '₱') ?>150.00</div>
                </div>
                
                <div class="message"><?= htmlspecialchars($settings['receipt_message']); ?></div>
                
                <div class="footer"><?= htmlspecialchars($settings['receipt_footer'] ?? 'Please come again!'); ?></div>
            </div>
            
            <div class="action-buttons">
                <button class="print-test" onclick="printReceipt()"><i class="fas fa-print"></i> Test Print</button>
                <button class="print-test" onclick="testPrintReceipt()" style="background: #27ae60;"><i class="fas fa-receipt"></i> Test with print_receipt.php</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab functionality
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and content
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Show corresponding content
            const tabId = button.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
    
    // Update range value displays
    document.getElementById('receipt_width').addEventListener('input', function() {
        document.getElementById('width_value').textContent = this.value;
    });
    
    document.getElementById('receipt_font_size').addEventListener('input', function() {
        document.getElementById('font_size_value').textContent = this.value;
    });
    
    // Update receipt preview in real-time
    function updateReceiptPreview() {
        const preview = document.getElementById('receipt-preview');
        const storeName = document.getElementById('store_name').value || 'Sari-Sari Store';
        const storeAddress = document.getElementById('store_address').value || '';
        const storeContact = document.getElementById('store_contact').value || '';
        const currencySymbol = document.getElementById('currency_symbol').value || '₱';
        const headerText = document.getElementById('receipt_header').value || 'Thank you for your purchase!';
        const footerText = document.getElementById('receipt_footer').value || 'Please come again!';
        const messageText = document.getElementById('receipt_message').value || 'We appreciate your business!';
        const receiptWidth = document.getElementById('receipt_width').value || 300;
        const fontSize = document.getElementById('receipt_font_size').value || 14;
        const showLogo = document.getElementById('receipt_show_logo').checked;
        const showBorder = document.getElementById('receipt_show_border').checked;
        const showDate = document.getElementById('receipt_show_date').checked;
        const logo = document.getElementById('receipt_logo').value || '🛒';
        
        // Update preview styles
        preview.style.width = receiptWidth + 'px';
        preview.style.fontSize = fontSize + 'px';
        preview.style.border = showBorder ? '1px solid #000' : '1px dashed #ccc';
        
        // Generate preview HTML
        let previewHTML = '';
        
        if (showLogo && logo) {
            previewHTML += `<div class="logo">${logo}</div>`;
        }
        
        previewHTML += `
            <div class="header">${storeName}</div>
            <div class="header">${storeAddress}</div>
            <div class="header">${storeContact}</div>
            <div class="header">${headerText}</div>
        `;
        
        if (showDate) {
            const now = new Date();
            previewHTML += `<div class="date">${now.toLocaleString()}</div>`;
        }
        
        previewHTML += `
            <div class="items">
                <div>
                    <span>Product 1 x 2</span>
                    <span>${currencySymbol}100.00</span>
                </div>
                <div>
                    <span>Product 2 x 1</span>
                    <span>${currencySymbol}50.00</span>
                </div>
            </div>
            <div class="total">
                <div>Total: ${currencySymbol}150.00</div>
            </div>
            <div class="message">${messageText}</div>
            <div class="footer">${footerText}</div>
        `;
        
        preview.innerHTML = previewHTML;
    }
    
    // Test print function
    function printReceipt() {
        const preview = document.getElementById('receipt-preview');
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print Receipt</title>
                <style>
                    body {
                        font-family: monospace;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                    }
                    .receipt {
                        ${preview.getAttribute('style')}
                        border: 1px solid #000 !important;
                    }
                    @media print {
                        @page {
                            margin: 0;
                            size: auto;
                        }
                        body {
                            height: auto;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="receipt">${preview.innerHTML}</div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 100);
                    }
                <\/script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }
    
    // Test print_receipt.php connection
    function testPrintReceipt() {
        // Create a form to submit test data to print_receipt.php
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'receipt.php';
        form.target = '_blank';
        
        // Add test data
        const testData = {
            store_name: document.getElementById('store_name').value || 'Sari-Sari Store',
            store_address: document.getElementById('store_address').value || '',
            store_contact: document.getElementById('store_contact').value || '',
            currency_symbol: document.getElementById('currency_symbol').value || '₱',
            receipt_header: document.getElementById('receipt_header').value || 'Thank you for your purchase!',
            receipt_footer: document.getElementById('receipt_footer').value || 'Please come again!',
            receipt_width: document.getElementById('receipt_width').value || 300,
            receipt_logo: document.getElementById('receipt_logo').value || '🛒',
            receipt_show_logo: document.getElementById('receipt_show_logo').checked ? '1' : '0',
            receipt_show_border: document.getElementById('receipt_show_border').checked ? '1' : '0',
            receipt_font_size: document.getElementById('receipt_font_size').value || 14,
            receipt_show_date: document.getElementById('receipt_show_date').checked ? '1' : '0',
            receipt_message: document.getElementById('receipt_message').value || 'We appreciate your business!',
            items: JSON.stringify([
                { name: 'Product 1', price: 100.00, quantity: 2 },
                { name: 'Product 2', price: 50.00, quantity: 1 }
            ])
        };
        
        for (const key in testData) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = testData[key];
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    
    // Initialize the preview
    updateReceiptPreview();
</script>
</body>
</html>