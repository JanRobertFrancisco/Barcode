<?php
// Include your database connection file
include 'db.php';

// Initialize variables
$message = "";
$message_type = "";
$can_add_product = true; // Flag to allow insertion

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form data
    $product_name = trim($conn->real_escape_string($_POST['product_name'] ?? ''));
    $price = trim($conn->real_escape_string($_POST['price'] ?? ''));
    $quantity = trim($conn->real_escape_string($_POST['quantity'] ?? ''));
    $scanned_code = trim($conn->real_escape_string($_POST['product_id'] ?? ''));

    // Remove non-alphanumeric characters from barcode
    $scanned_code = preg_replace("/[^A-Za-z0-9]/", "", $scanned_code);

    // Barcode validation logic
    if (empty($scanned_code)) {
        // Generate a unique numeric barcode
        $scanned_code = time() . rand(1000, 9999);
    } else {
        // Check if the scanned barcode already exists
        $check = $conn->prepare("SELECT id FROM products WHERE barcode = ?");
        $check->bind_param("s", $scanned_code);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "❌ Error: The barcode you scanned already exists. Please scan a different barcode or leave blank to auto-generate.";
            $message_type = "danger";
            $can_add_product = false;
        }
        $check->close();
    }

    // Insert the new product into the database only if allowed
    if ($can_add_product) {
        $stmt = $conn->prepare("INSERT INTO products (product_name, price, quantity, barcode) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdis", $product_name, $price, $quantity, $scanned_code);

        if ($stmt->execute()) {
            $message = "✅ Product added successfully! Barcode: $scanned_code";
            $message_type = "success";
        } else {
            $message = "❌ Error: " . $stmt->error;
            $message_type = "danger";
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
    <title>Add Product - Inventory Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: var(--transition);
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .page-title {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px 25px;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #444;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
            transform: translateY(-2px);
        }
        
        .scanner-container {
            background: #f9fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 2px dashed #e4e7ed;
        }
        
        .scanner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .scanner-title {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border: 1px solid #e4e7ed;
            border-radius: 8px;
            overflow: hidden;
        }
        
        #qr-reader__dashboard_section_csr {
            padding: 15px;
        }
        
        .alert-box {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 300px;
            z-index: 1055;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-radius: 10px;
            border: none;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .form-icon {
            color: var(--primary);
            font-size: 18px;
            width: 24px;
        }
        
        .info-text {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: var(--gray);
            font-size: 14px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .divider::before {
            margin-right: .5em;
        }
        
        .divider::after {
            margin-left: .5em;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-plus-circle me-2"></i>Add New Product</h1>
            <a href="products_management.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show alert-box" role="alert" id="msgBox">
                <div class="d-flex align-items-center">
                    <i class="fas <?= $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                    <div><?= $message ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-info-circle form-icon"></i>
                        Product Information
                    </div>
                    <div class="card-body">
                        <form id="addProductForm" method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" name="product_name" class="form-control" required placeholder="Enter product name">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price (₱)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity" class="form-control" required placeholder="Enter quantity">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Barcode (Optional)</label>
                                    <input type="text" id="product_id" name="product_id" class="form-control" placeholder="Scan or enter barcode">
                                    <div class="info-text">Leave blank to auto-generate a barcode</div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Add Product
                                </button>
                                <button type="reset" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-qrcode form-icon"></i>
                        Barcode Scanner
                    </div>
                    <div class="card-body">
                        <p class="info-text">Scan a barcode to automatically populate the barcode field</p>
                        
                        <div class="scanner-container text-center">
                            <div id="qr-reader"></div>
                        </div>
                        
                        <div class="divider">OR</div>
                        
                        <div class="text-center">
                            <button class="btn btn-outline-primary" id="manual-barcode-btn">
                                <i class="fas fa-keyboard me-2"></i>Enter Barcode Manually
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-lightbulb form-icon"></i>
                        Quick Tips
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Use descriptive product names for easy searching
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Ensure barcodes are unique for each product
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Scan barcodes in well-lit areas for best results
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const msgBox = document.getElementById('msgBox');
            if (msgBox) {
                setTimeout(() => { 
                    const alert = bootstrap.Alert.getOrCreateInstance(msgBox);
                    alert.close();
                }, 5000);
            }

            // Manual barcode entry button
            document.getElementById('manual-barcode-btn').addEventListener('click', () => {
                document.getElementById('product_id').focus();
            });

            // Initialize barcode scanner optimized for small/blurry barcodes
            const html5QrcodeScanner = new Html5Qrcode("qr-reader");

            html5QrcodeScanner.start(
                { facingMode: "environment" },
                {
                    fps: 20, // High frame rate for faster detection
                    qrbox: { width: 250, height: 250 }, // Adjusted scanning area
                    experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                    rememberLastUsedCamera: true
                },
                (decodedText, decodedResult) => {
                    const input = document.getElementById('product_id');
                    input.value = decodedText.replace(/[^A-Za-z0-9]/g, ""); // Clean barcode input
                    input.focus();
                    
                    // Show brief success feedback
                    input.classList.add('is-valid');
                    setTimeout(() => input.classList.remove('is-valid'), 2000);
                    
                    console.log("Barcode detected:", decodedText);
                },
                (errorMessage) => {
                    // Keep scanning, ignore minor errors
                    console.warn(`Scan error: ${errorMessage}`);
                }
            ).catch((err) => {
                document.getElementById('qr-reader').innerHTML = 
                    '<div class="text-center p-4"><i class="fas fa-camera-slash fa-2x text-muted mb-3"></i><p class="text-muted">Camera not available or permission denied.</p></div>';
                console.warn(err);
            });
        });
    </script>
</body>
</html>