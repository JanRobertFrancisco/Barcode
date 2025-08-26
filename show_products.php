<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "admin");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all products, including their barcodes
$products_result = $conn->query("SELECT * FROM products ORDER BY id DESC");
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Product Management System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<style>
:root {
    --primary: #4361ee;
    --secondary: #3f37c9;
    --success: #4cc9f0;
    --light: #f8f9fa;
    --dark: #212529;
    --danger: #e63946;
    --warning: #fca311;
    --info: #4895ef;
    --gray: #6c757d;
    --light-gray: #e9ecef;
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    padding-bottom: 30px;
}

.header {
    background: var(--primary);
    color: white;
    padding: 15px 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

.logo {
    font-weight: 700;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
}

.logo i {
    margin-right: 10px;
    font-size: 2rem;
}

.products-section {
    max-width: 1200px;
    margin: 0 auto;
    background: #fff;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.section-title {
    color: var(--primary);
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-gray);
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
    font-size: 1.8rem;
}

.search-container {
    position: relative;
    margin-bottom: 20px;
}

.search-container i {
    position: absolute;
    left: 15px;
    top: 12px;
    color: var(--gray);
}

#searchInput {
    padding-left: 40px;
    border-radius: 50px;
    border: 1px solid var(--light-gray);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

#scanBtn {
    border-radius: 50px;
    padding: 10px 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

#reader {
    width: 100%;
    max-width: 400px;
    margin: 15px auto;
    border: 1px solid var(--light-gray);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.table-responsive {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

#productsTable {
    margin-bottom: 0;
}

#productsTable thead th {
    background: var(--primary);
    color: white;
    font-weight: 600;
    border: none;
    padding: 15px 12px;
    text-align: center;
    vertical-align: middle;
}

#productsTable tbody td {
    padding: 12px;
    vertical-align: middle;
    text-align: center;
}

.quantity-input {
    width: 80px;
    margin: 0 auto;
    text-align: center;
    border-radius: 8px;
    border: 1px solid var(--light-gray);
    transition: all 0.3s;
}

.quantity-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
}

.product-name-cell {
    cursor: pointer;
    font-weight: 600;
    color: var(--dark);
    transition: all 0.2s;
    text-align: left;
    padding-left: 20px !important;
}

.product-name-cell:hover {
    color: var(--primary);
    transform: translateX(5px);
}

.price-cell {
    font-weight: 600;
    color: var(--primary);
}

.stock-cell {
    font-weight: 500;
}

.stock-low {
    color: var(--warning);
    font-weight: 600;
}

.stock-out {
    color: var(--danger);
    font-weight: 700;
}

tr.selected td {
    background-color: rgba(76, 201, 240, 0.1) !important;
}

.summary-card {
    background: var(--light);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-top: 20px;
}

.summary-title {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.payment-container {
    position: relative;
    margin: 15px 0;
}

.payment-container i {
    position: absolute;
    left: 15px;
    top: 12px;
    color: var(--gray);
    z-index: 5;
}

#paymentAmount {
    padding-left: 40px;
    border-radius: 50px;
    border: 2px solid var(--light-gray);
    transition: all 0.3s;
}

#paymentAmount:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
}

.amount-display {
    background: white;
    padding: 12px 20px;
    border-radius: 50px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin: 10px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.amount-label {
    color: var(--gray);
}

.amount-value {
    color: var(--primary);
    font-size: 1.3rem;
}

.change-positive {
    color: var(--success) !important;
}

.change-negative {
    color: var(--danger) !important;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 25px;
}

.btn-primary {
    background: var(--primary);
    border: none;
    border-radius: 50px;
    padding: 12px 25px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: var(--secondary);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-success {
    background: var(--success);
    border: none;
    border-radius: 50px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-success:hover {
    background: #3aafd9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-secondary {
    border-radius: 50px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

#alertBox, #confirmBox {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 25px 30px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    z-index: 9999;
    display: none;
    font-weight: bold;
    text-align: center;
    min-width: 300px;
    backdrop-filter: blur(10px);
}

#alertBox {
    background: rgba(230, 57, 70, 0.9);
    color: white;
    border: none;
}

#confirmBox {
    background: rgba(255, 255, 255, 0.95);
    color: var(--dark);
    border: 2px solid var(--primary);
}

#confirmBox button {
    border-radius: 50px;
    padding: 8px 20px;
    margin: 5px;
    font-weight: 600;
}

.badge {
    font-size: 0.7rem;
    padding: 5px 10px;
    border-radius: 50px;
    margin-left: 8px;
}

.badge-success {
    background: var(--success);
}

.badge-warning {
    background: var(--warning);
}

.badge-danger {
    background: var(--danger);
}

.stock-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.stock-progress {
    width: 80%;
    height: 6px;
    background: var(--light-gray);
    border-radius: 3px;
    overflow: hidden;
}

.stock-progress-bar {
    height: 100%;
    background: var(--success);
    border-radius: 3px;
}

.footer {
    text-align: center;
    margin-top: 30px;
    color: var(--gray);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .products-section {
        padding: 15px;
        border-radius: 12px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    #productsTable thead {
        display: none;
    }
    
    #productsTable tbody tr {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    #productsTable tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        text-align: right;
        border: none;
    }
    
    #productsTable tbody td:before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--primary);
        text-align: left;
    }
    
    .product-name-cell {
        background: var(--light);
        font-weight: 700;
        padding-left: 15px !important;
    }
}
</style>
</head>
<body>

<div class="header">
    <div class="container">
        <div class="logo"><i class="fas fa-cash-register"></i> Product Management System</div>
    </div>
</div>

<div class="container products-section">
    <h4 class="section-title"><i class="fas fa-boxes"></i> Product Inventory</h4>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Search products by name or barcode...">
            </div>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-primary w-100" id="scanBtn">
                <i class="fas fa-qrcode"></i> Start Scanner
            </button>
        </div>
    </div>
    
    <div id="reader" style="display: none;"></div>

    <form method="POST" action="receipt.php" id="buyForm">
        <div class="table-responsive">
            <table id="productsTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Qty to Buy</th>
                        <th>Product Name</th>
                        <th>Price (₱)</th>
                        <th>Stock Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $row): 
                        $stockClass = '';
                        $stockBadge = '';
                        $progressWidth = ($row['quantity'] / 50) * 100; // Assuming max stock is 50 for visualization
                        if ($row['quantity'] == 0) {
                            $stockClass = 'stock-out';
                            $stockBadge = '<span class="badge badge-danger">Out of Stock</span>';
                        } elseif ($row['quantity'] < 10) {
                            $stockClass = 'stock-low';
                            $stockBadge = '<span class="badge badge-warning">Low Stock</span>';
                        } else {
                            $stockBadge = '<span class="badge badge-success">In Stock</span>';
                        }
                    ?>
                        <tr class="<?= $stockClass ?>" data-id="<?= $row['id'] ?>" data-barcode="<?= htmlspecialchars($row['barcode']) ?>">
                            <td data-label="Quantity">
                                <input type="number" name="quantities[<?= $row['id'] ?>]" value="0" min="0" max="<?= $row['quantity'] ?>" class="form-control quantity-input" data-price="<?= $row['price'] ?>" <?= ($row['quantity'] == 0) ? 'disabled' : '' ?>>
                            </td>
                            <td class="product-name-cell" data-label="Product">
                                <?= htmlspecialchars($row['product_name']) ?>
                                <?= $stockBadge ?>
                            </td>
                            <td class="price-cell" data-label="Price">₱<?= number_format($row['price'], 2) ?></td>
                            <td class="stock-cell" data-label="Stock">
                                <div class="stock-info">
                                    <span><?= $row['quantity'] ?> units</span>
                                    <?php if ($row['quantity'] > 0): ?>
                                    <div class="stock-progress">
                                        <div class="stock-progress-bar" style="width: <?= $progressWidth ?>%"></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="summary-card">
            <h5 class="summary-title"><i class="fas fa-receipt"></i> Order Summary</h5>
            
            <div class="amount-display">
                <span class="amount-label">Grand Total:</span>
                <span class="amount-value" id="grandTotal">₱0.00</span>
            </div>
            
            <div class="payment-container">
                <i class="fas fa-money-bill-wave"></i>
                <input type="number" name="paymentAmount" id="paymentAmount" class="form-control payment-input" placeholder="Enter Payment Amount" value="0" min="0" step="0.01">
            </div>
            
            <div class="amount-display">
                <span class="amount-label">Change:</span>
                <span class="amount-value" id="changeAmount">₱0.00</span>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Confirm Purchase
                </button>
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </form>


</div>

<div id="alertBox"></div>

<div id="confirmBox">
    <i class="fas fa-question-circle fa-2x mb-3" style="color: var(--primary);"></i>
    <p>Are you sure you want to purchase the selected items?</p>
    <div class="mt-3">
        <button id="confirmYes" class="btn btn-success">Yes, Confirm</button>
        <button id="confirmNo" class="btn btn-secondary">Cancel</button>
    </div>
</div>

<script>
// JavaScript code remains exactly the same as in the original
// Only CSS and HTML structure have been modified

const quantityInputs = document.querySelectorAll('.quantity-input');
const grandTotalElem = document.getElementById('grandTotal');
const paymentInput = document.getElementById('paymentAmount');
const changeElem = document.getElementById('changeAmount');
const buyForm = document.getElementById('buyForm');
const alertBox = document.getElementById('alertBox');
const confirmBox = document.getElementById('confirmBox');
const confirmYes = document.getElementById('confirmYes');
const confirmNo = document.getElementById('confirmNo');

const productsData = {};
document.querySelectorAll('#productsTable tbody tr').forEach(row => {
    const id = row.dataset.id;
    const barcode = row.dataset.barcode;
    productsData[id] = {
        row: row,
        input: row.querySelector('.quantity-input'),
        name: row.querySelector('.product-name-cell').textContent.trim(),
        price: parseFloat(row.querySelector('.quantity-input').dataset.price),
        stock: parseInt(row.querySelector('.quantity-input').max),
        barcode: barcode
    };
});

// Update Grand Total
function updateGrandTotal() {
    let total = 0;
    Object.values(productsData).forEach(product => {
        const qty = parseInt(product.input.value) || 0;
        total += qty * product.price;
    });
    grandTotalElem.textContent = `₱${total.toFixed(2)}`;
    updateChange();
}

// Update Change
function updateChange() {
    const total = parseFloat(grandTotalElem.textContent.replace('₱','')) || 0;
    const payment = parseFloat(paymentInput.value) || 0;
    const change = payment - total;
    changeElem.textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
    
    // Update class for color
    if (payment >= total) {
        changeElem.classList.add('change-positive');
        changeElem.classList.remove('change-negative');
    } else {
        changeElem.classList.add('change-negative');
        changeElem.classList.remove('change-positive');
    }
}

// Quantity input events
quantityInputs.forEach(input => {
    const row = input.closest('tr');
    const productNameCell = row.querySelector('.product-name-cell');

    // Input directly into quantity field (manual typing)
    input.addEventListener('input', function() {
        let val = parseInt(this.value) || 0;
        const maxStock = parseInt(this.max);
        if(val > maxStock) val = maxStock;
        this.value = val;

        if(val > 0){
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
        updateGrandTotal();
    });

    // Click product name to set quantity automatically to 1 (if 0) or toggle 0
    productNameCell.addEventListener('click', function() {
        if (!input.disabled) {
            let currentQty = parseInt(input.value) || 0;
            currentQty = (currentQty === 0) ? 1 : 0; // first click selects 1

            input.value = currentQty;
            if(currentQty > 0){
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
            updateGrandTotal();
        }
    });
});

// Payment input updates change
paymentInput.addEventListener('input', updateChange);

// Form submission check
buyForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const total = parseFloat(grandTotalElem.textContent.replace('₱','')) || 0;
    const payment = parseFloat(paymentInput.value) || 0;
    const anySelected = Array.from(quantityInputs).some(input => parseInt(input.value) > 0);

    if(!anySelected) {
        showAlert('Please select at least one product!');
        return;
    }

    if(payment < total) {
        showAlert('Payment amount is less than the Grand Total!');
        paymentInput.focus();
        return;
    }

    confirmBox.style.display = 'block';
});

function showAlert(message) {
    alertBox.textContent = message;
    alertBox.style.display = 'block';
    setTimeout(() => { alertBox.style.display = 'none'; }, 2000);
}

// Confirmation buttons
confirmYes.addEventListener('click', () => {
    confirmBox.style.display = 'none';
    buyForm.submit();
});
confirmNo.addEventListener('click', () => {
    confirmBox.style.display = 'none';
});

// Press Enter to click Yes when confirm box visible
document.addEventListener('keydown', function(e) {
    if(confirmBox.style.display === 'block' && e.key === 'Enter') {
        e.preventDefault();
        confirmYes.click();
    }
});

// Initial calculation
updateGrandTotal();

// Search filter
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(row => {
        const productName = row.querySelector('.product-name-cell').textContent.toLowerCase();
        const barcode = row.dataset.barcode.toLowerCase();
        row.style.display = (productName.includes(filter) || barcode.includes(filter)) ? '' : 'none';
    });
});

// --- Barcode Scanner Logic ---
const scanBtn = document.getElementById('scanBtn');
const readerDiv = document.getElementById('reader');
const html5QrCode = new Html5Qrcode("reader");
let isScanning = false;
let isScanBlocked = false;

function startScanner() {
    isScanning = true;
    readerDiv.style.display = 'block';
    scanBtn.innerHTML = '<i class="fas fa-stop-circle"></i> Stop Scanner';
    scanBtn.classList.add('btn-danger');

    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decodedText, decodedResult) => {
            if (isScanBlocked) return;
            isScanBlocked = true;

            const product = Object.values(productsData).find(p => p.barcode === decodedText);
            
            if (product) {
                let currentQty = parseInt(product.input.value) || 0;
                const newQty = currentQty + 1;
                
                if (newQty <= product.stock) {
                    product.input.value = newQty;
                    product.row.classList.add('selected');
                    updateGrandTotal();
                    showAlert(`✅ Added 1 x ${product.name}`);
                } else {
                    showAlert(`❌ Not enough stock for ${product.name}`);
                }
            } else {
                showAlert(`Product not found for barcode: ${decodedText}`);
            }

            // Unblock scanning after 2 seconds
            setTimeout(() => {
                isScanBlocked = false;
            }, 2000);
        },
        (errorMessage) => {
            // Error, do nothing
        }
    ).catch((err) => {
        showAlert("Error starting scanner. Check camera permissions.");
        console.error(err);
        stopScanner();
    });
}

function stopScanner() {
    if (isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
            readerDiv.style.display = 'none';
            scanBtn.innerHTML = '<i class="fas fa-qrcode"></i> Start Scanner';
            scanBtn.classList.remove('btn-danger');
        }).catch(err => {
            console.error("Failed to stop scanner:", err);
        });
    }
}

scanBtn.addEventListener('click', () => {
    if (isScanning) {
        stopScanner();
    } else {
        startScanner();
    }
});
</script>

</body>
</html>