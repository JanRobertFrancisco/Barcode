<?php
date_default_timezone_set("Asia/Manila");
include 'db.php';

$sale_id = intval($_GET['sale_id'] ?? 0);

if ($sale_id <= 0) {
    die("Invalid Sale ID");
}

// Fetch store settings
$settingsQuery = $conn->query("
    SELECT s.*, u.id AS admin_id, u.username, u.password
    FROM settings s
    JOIN users u ON s.admin_user_id = u.id
    LIMIT 1
");
$settings = $settingsQuery->fetch_assoc();

// Set default values if settings don't exist
$defaultSettings = [
    'receipt_width' => 300,
    'receipt_logo' => '🛒',
    'receipt_show_logo' => 1,
    'receipt_show_border' => 0,
    'receipt_font_size' => 14,
    'receipt_show_tax' => 1,
    'receipt_show_date' => 1,
    'receipt_message' => 'We appreciate your business!',
    'currency_symbol' => '₱',
    'tax_rate' => 0,
    'receipt_header' => 'Thank you for your purchase!',
    'receipt_footer' => 'Please come again!',
    'store_name' => 'Sari-Sari Store',
    'store_address' => '',
    'store_contact' => ''
];

foreach ($defaultSettings as $key => $defaultValue) {
    if (!isset($settings[$key])) {
        $settings[$key] = $defaultValue;
    }
}

// Fetch sale details
$stmt = $conn->prepare("SELECT * FROM sales WHERE id=?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sale) {
    die("Sale not found.");
}

// Fetch sale items
$stmt = $conn->prepare("SELECT si.*, p.product_name 
                        FROM sale_items si 
                        JOIN products p ON si.product_id=p.id 
                        WHERE si.sale_id=?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Calculate totals
$subtotal = 0;
while ($row = $items->fetch_assoc()) {
    $subtotal += $row['price'] * $row['quantity'];
}

$taxRate = floatval($settings['tax_rate']);
$tax = $subtotal * ($taxRate / 100);
$total = $subtotal + $tax;

// Reset pointer for items
$items->data_seek(0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?php echo $sale_id; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: monospace; 
            background: #f9f9f9; 
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin: 0;
        }
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .receipt { 
            width: <?php echo $settings['receipt_width']; ?>px; 
            padding: 15px; 
            background: #fff; 
            <?php echo $settings['receipt_show_border'] ? 'border: 1px solid #000;' : 'border: 1px dashed #ccc;'; ?>
            font-size: <?php echo $settings['receipt_font_size']; ?>px;
            display: flex;
            flex-direction: column;
        }
        .center { 
            text-align: center; 
        }
        .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .logo {
            text-align: center;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
        }
        .items {
            margin: 10px 0;
            width: 100%;
        }
        .items div {
            display: flex;
            justify-content: space-between;
        }
        .total {
            border-top: 1px solid #ccc;
            padding-top: 5px;
            margin-top: 10px;
            font-weight: bold;
        }
        .date {
            text-align: center;
            margin-top: 5px;
            font-size: 12px;
        }
        .message {
            text-align: center;
            margin-top: 10px;
            font-style: italic;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            width: <?php echo $settings['receipt_width']; ?>px;
        }
        
        .print-btn, .back-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        
        .print-btn {
            background: #142883;
            color: white;
            box-shadow: 0 3px 10px rgba(20, 40, 131, 0.2);
        }
        
        .print-btn:hover {
            background: #1d3ab3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(20, 40, 131, 0.3);
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            box-shadow: 0 3px 10px rgba(108, 117, 125, 0.2);
        }
        
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        /* Alert styles */
        .alert-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .alert-box {
            background: #fff;
            padding: 20px 30px;
            border-radius: 10px;
            text-align: center;
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 300px;
        }
        .ok-btn, .cancel-btn {
            margin-top: 15px;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .ok-btn {
            background: #142883;
            color: white;
            margin-right: 10px;
        }
        .ok-btn:hover {
            background: #1d3ab3;
        }
        .cancel-btn {
            background: #6c757d;
            color: white;
        }
        .cancel-btn:hover {
            background: #5a6268;
        }

        /* PRINT STYLES */
        @media print {
            body { 
                background: #fff;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                height: auto;
            }
            .container {
                display: block;
            }
            .receipt { 
                width: <?php echo $settings['receipt_width']; ?>px; 
                border: 1px solid #000;
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
            .action-buttons, .alert-overlay {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="receipt">
        <?php if ($settings['receipt_show_logo']): ?>
        <div class="logo"><?php echo htmlspecialchars($settings['receipt_logo']); ?></div>
        <?php endif; ?>
        
        <div class="header"><?php echo htmlspecialchars($settings['store_name']); ?></div>
        <?php if (!empty($settings['store_address'])): ?>
        <div class="header"><?php echo htmlspecialchars($settings['store_address']); ?></div>
        <?php endif; ?>
        <?php if (!empty($settings['store_contact'])): ?>
        <div class="header"><?php echo htmlspecialchars($settings['store_contact']); ?></div>
        <?php endif; ?>
        
        <div class="header"><?php echo htmlspecialchars($settings['receipt_header']); ?></div>
        
        <?php if ($settings['receipt_show_date']): ?>
        <div class="date"><?php echo date("Y-m-d h:i A", strtotime($sale['sale_date'])); ?></div>
        <?php endif; ?>
        
        <div class="items">
            <?php while ($row = $items->fetch_assoc()): ?>
            <div>
                <span><?php echo htmlspecialchars($row['product_name']); ?> x <?php echo $row['quantity']; ?></span>
                <span><?php echo htmlspecialchars($settings['currency_symbol']); ?><?php echo number_format($row['price'] * $row['quantity'], 2); ?></span>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="total">
            <div>Subtotal: <?php echo htmlspecialchars($settings['currency_symbol']); ?><?php echo number_format($subtotal, 2); ?></div>
            <?php if ($settings['receipt_show_tax'] && $taxRate > 0): ?>
            <div>Tax (<?php echo $taxRate; ?>%): <?php echo htmlspecialchars($settings['currency_symbol']); ?><?php echo number_format($tax, 2); ?></div>
            <?php endif; ?>
            <div>Total: <?php echo htmlspecialchars($settings['currency_symbol']); ?><?php echo number_format($total, 2); ?></div>
            <div>Payment: <?php echo htmlspecialchars($settings['currency_symbol']); ?><?php echo number_format($sale['payment'], 2); ?></div>
            <div>Change: <?php echo htmlspecialchars($settings['currency_symbol']); ?><?php echo number_format($sale['change_amt'], 2); ?></div>
        </div>
        
        <div class="message"><?php echo htmlspecialchars($settings['receipt_message']); ?></div>
        
        <div class="footer"><?php echo htmlspecialchars($settings['receipt_footer']); ?></div>
    </div>

    <div class="action-buttons">
        <button onclick="window.print();" class="print-btn">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button onclick="showAlert()" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Sales
        </button>
    </div>
</div>

<button onclick="window.location.href='show_products.php'" 
        style="padding:10px 20px; background:#007BFF; color:#fff; border:none; border-radius:5px; cursor:pointer; margin-top:20px;">
    ⬅ Back to Products
</button>

<!-- Custom Centered Alert - Initially hidden -->
<div id="customAlert" class="alert-overlay">
    <div class="alert-box">
        <p>Going back to Sales Management...</p>
        <button onclick="goBack()" class="ok-btn">OK</button>
        <button onclick="closeAlert()" class="cancel-btn">Cancel</button>
    </div>
</div>

<script>
function showAlert() {
    document.getElementById("customAlert").style.display = "flex";
}

function closeAlert() {
    document.getElementById("customAlert").style.display = "none";
}

function goBack() {
    window.location.href = "sales_management.php";
}
</script>

</body>
</html>