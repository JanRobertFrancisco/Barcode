<?php
date_default_timezone_set("Asia/Manila"); // Philippine time
include 'db.php';
$error = "";
$success = "";
$sale_id = 0; // store last sale id

// Handle form submission
$cart_data = json_decode($_POST['cart_data'] ?? '[]', true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $payment = floatval($_POST['payment']);

    if (empty($product_ids)) {
        $error = "Please add at least one product.";
    } else {
        $total = 0;
        foreach ($product_ids as $i => $pid) {
            $stmt = $conn->prepare("SELECT price, quantity, product_name FROM products WHERE id=?");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($quantities[$i] > $p['quantity']) {
                $error = "Not enough stock for product " . htmlspecialchars($p['product_name']);
                break;
            }
            $total += $p['price'] * $quantities[$i];
        }

        if (!$error) {
            if ($payment < $total) {
                $error = "Payment is not enough. Total: ₱" . number_format($total, 2);
            } else {
                $change = $payment - $total;
                $date = date("Y-m-d h:i:s A"); // 12-hour format

                // Insert into sales
                $stmt = $conn->prepare("INSERT INTO sales (total_payment, payment, change_amt, sale_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ddds", $total, $payment, $change, $date);
                $stmt->execute();
                $sale_id = $stmt->insert_id;
                $stmt->close();

                // Insert sale items & update stock
                foreach ($product_ids as $i => $pid) {
                    $stmt = $conn->prepare("SELECT price, quantity FROM products WHERE id=?");
                    $stmt->bind_param("i", $pid);
                    $stmt->execute();
                    $p = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $sale_id, $pid, $quantities[$i], $p['price']);
                    $stmt->execute();
                    $stmt->close();

                    $new_stock = $p['quantity'] - $quantities[$i];
                    $stmt = $conn->prepare("UPDATE products SET quantity=? WHERE id=?");
                    $stmt->bind_param("ii", $new_stock, $pid);
                    $stmt->execute();
                    $stmt->close();
                }

                // Clear cart
                $cart_data = [];

                // Redirect to print_receipt.php with the sale_id
                header("Location: print_receipt.php?sale_id=" . $sale_id);
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Products</title>
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
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main {
            margin-left: 230px; 
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eaeaea;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-success {
            background-color: #06d6a0;
            border-color: #06d6a0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
        }
        
        #search_results {
            position: absolute;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 200px;
            overflow-y: auto;
            width: 75%;
        }
        
        #search_results div {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.2s;
        }
        
        #search_results div:last-child {
            border-bottom: none;
        }
        
        #search_results div:hover {
            background: #f8f9fa;
        }
        
        #messageBox {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: #333;
            color: #fff;
            font-weight: bold;
            border-radius: 8px;
            z-index: 2000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        #reader {
            width: 250px;
            height: 200px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 10px auto;
            background: #f8f9fa;
        }
        
        .cart-item {
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            background-color: #f8f9fa;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border-radius: 12px;
        }
        
        .summary-value {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .confirmation-box {
            background-color: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 450px;
            width: 90%;
            transform: scale(0.95);
            transition: transform 0.3s ease-in-out;
        }

        .confirmation-box.active {
            transform: scale(1);
        }

        .confirmation-box h4 {
            margin-bottom: 20px;
            color: var(--dark);
        }

        .confirmation-box p {
            font-size: 1.1rem;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .confirmation-box .btn-group {
            margin-top: 25px;
        }
        
        .badge-stock {
            background-color: #e9ecef;
            color: #6c757d;
            font-weight: 500;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        
        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .scan-section {
            transition: all 0.3s ease;
            text-align: center;
        }
        
        @media (max-width: 992px) {
            .main {
                margin-left: 0;
            }
        }
        
        @media (max-width: 576px) {
            #reader {
                width: 100%;
                height: 180px;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div id="messageBox"></div>

<!-- Confirmation Box -->
<div id="confirmationOverlay" class="modal-overlay">
    <div class="confirmation-box">
        <h4><i class="fas fa-check-circle me-2 text-success"></i> Confirm Purchase</h4>
        <p><span>Total:</span> <span id="confirmTotal"></span></p>
        <p><span>Payment:</span> <span id="confirmPayment"></span></p>
        <p><span>Change:</span> <span id="confirmChange"></span></p>
        <div class="btn-group w-100">
            <button type="button" class="btn btn-success me-2" id="confirmBuyBtn">Confirm Purchase</button>
            <button type="button" class="btn btn-outline-secondary" onclick="hideConfirmationBox()">Cancel</button>
        </div>
    </div>
</div>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-cart-plus me-2"></i> Buy Products</h2>
        <div class="d-flex">
            <span class="badge bg-primary p-2 me-2"><i class="fas fa-clock me-1"></i> <span id="live-clock"><?php echo date("Y-m-d h:i:s A"); ?></span></span>
            <a href="sales_management.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-1"></i> Back to Sales</a>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <div><?php echo $error; ?></div>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success d-flex align-items-center" id="successMsg">
            <i class="fas fa-check-circle me-2"></i>
            <div><?php echo $success; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" id="buyForm" onsubmit="return saveCartBeforeSubmit(event)">
        <input type="hidden" name="cart_data" id="cart_data">

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
                <i class="fas fa-search me-2"></i> Product Search
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-7 position-relative">
                        <label class="form-label">Search by Name or Barcode</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            <input type="text" id="search" class="form-control" placeholder="Enter product name or barcode" oninput="showSearchResults()">
                        </div>
                        <div id="search_results"></div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" id="quantity" class="form-control" min="1" value="1">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary me-2 flex-fill" onclick="addFromSearch()">
                            <i class="fas fa-plus-circle me-1"></i> Add Product
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="scan-btn" onclick="startScanner()">
                            <i class="fas fa-camera me-1"></i> Scan
                        </button>
                    </div>
                </div>
                
                <div class="scan-section mt-3" id="scan-section" style="display: none;">
                    <div id="reader"></div>
                    <div class="d-flex justify-content-center mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="stopScanner()">
                            <i class="fas fa-times me-1"></i> Close Scanner
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-shopping-cart me-2"></i> Shopping Cart
                        <span class="badge bg-primary ms-2" id="cart-count">0</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40%">Product</th>
                                        <th width="15%">Qty</th>
                                        <th width="15%">Price/unit</th>
                                        <th width="20%">Total</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cart_body">
                                    <tr>
                                        <td colspan="5" class="empty-cart">
                                            <i class="fas fa-shopping-cart"></i>
                                            <p>Your cart is empty</p>
                                            <small class="text-muted">Search and add products above</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-receipt me-2"></i> Payment Summary
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Items:</span>
                            <span id="summary-items">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal:</span>
                            <span id="summary-subtotal">₱0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong id="summary-total">₱0.00</strong>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Amount (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" id="payment" name="payment" class="form-control" step="0.01" oninput="calculateChange()" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Change (₱)</label>
                            <div class="form-control bg-light" id="change-display">₱0.00</div>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100 py-3">
                            <i class="fas fa-check-circle me-2"></i> Complete Purchase
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let cart = <?php echo json_encode($cart_data ?: []); ?>;
let selectedProductId = null;
let products = [
<?php
$products = $conn->query("SELECT * FROM products ORDER BY product_name ASC");
$arr = [];
while($p = $products->fetch_assoc()){
    $arr[] = "{id:'".$p['id']."', name:'".addslashes($p['product_name'])."', price:".$p['price'].", stock:".$p['quantity'].", barcode:'".addslashes($p['barcode'])."'}";
}
echo implode(",", $arr);
?>
];

const html5QrCode = new Html5Qrcode("reader");
let isScannerActive = false;
let isScanningBlocked = false;

// Update clock in real-time
function updateClock() {
    const now = new Date();
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true 
    };
    document.getElementById('live-clock').textContent = now.toLocaleDateString('en-PH', options);
}
setInterval(updateClock, 1000);
updateClock();

function showMessage(msg, type='info'){
    const box = document.getElementById('messageBox');
    box.innerHTML = `<i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'} me-2"></i> ${msg}`;
    box.style.background = type==='error' ? '#e74c3c' : '#2ecc71';
    box.style.display = 'block';
    setTimeout(()=>{ box.style.display = 'none'; }, 3000);
}

function showSearchResults() {
    const search = document.getElementById("search").value.toLowerCase();
    const resultsDiv = document.getElementById("search_results");
    resultsDiv.innerHTML = "";
    if (!search) return;
    
    const filtered = products.filter(p => 
        p.name.toLowerCase().includes(search) || 
        (p.barcode && p.barcode.toLowerCase().includes(search))
    );
    
    if (filtered.length === 0) {
        resultsDiv.innerHTML = `<div class="text-muted p-3">No products found</div>`;
        return;
    }
    
    filtered.forEach(p => {
        const div = document.createElement("div");
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>${p.name}</div>
                <div>
                    <span class="badge badge-stock">Stock: ${p.stock}</span>
                    <span class="badge bg-light text-dark ms-1">₱${p.price.toFixed(2)}</span>
                </div>
            </div>
        `;
        div.onclick = () => {
            document.getElementById("search").value = p.name;
            selectedProductId = p.id;
            resultsDiv.innerHTML = "";
        };
        resultsDiv.appendChild(div);
    });
}

function addFromSearch() {
    if (!selectedProductId) return showMessage("Please select a product from search results.", 'error');
    const qty = parseInt(document.getElementById("quantity").value);
    if (qty <= 0) return showMessage("Enter a valid quantity", 'error');
    const product = products.find(p => p.id === selectedProductId);
    if (qty > product.stock) return showMessage("Not enough stock for " + product.name, 'error');

    let existingItem = cart.find(item => item.id === product.id);
    if (existingItem) {
        existingItem.qty += qty;
    } else {
        cart.push({id: product.id, name: product.name, qty, price: product.price});
    }

    product.stock -= qty;
    
    renderCart();
    showMessage(`${product.name} (x${qty}) added to cart!`, 'success');
    document.getElementById("search").value = "";
    document.getElementById("quantity").value = 1;
    selectedProductId = null;
}

function renderCart() {
    const tbody = document.getElementById("cart_body");
    const totalSpan = document.getElementById("summary-total");
    const subtotalSpan = document.getElementById("summary-subtotal");
    const itemsSpan = document.getElementById("summary-items");
    const cartCount = document.getElementById("cart-count");
    
    // Clear the cart display first
    tbody.innerHTML = "";
    
    if (cart.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <small class="text-muted">Search and add products above</small>
                </td>
            </tr>
        `;
        cartCount.textContent = "0";
        itemsSpan.textContent = "0";
        subtotalSpan.textContent = "₱0.00";
        totalSpan.textContent = "₱0.00";
        return;
    }
    
    // Calculate total and generate rows
    const total = cart.reduce((sum, item) => {
        const itemTotal = item.price * item.qty;
        const row = `<tr class="cart-item">
            <td>${item.name}</td>
            <td>${item.qty}</td>
            <td>₱${item.price.toFixed(2)}</td>
            <td>₱${itemTotal.toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem('${item.id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
            <input type="hidden" name="product_id[]" value="${item.id}">
            <input type="hidden" name="quantity[]" value="${item.qty}">
        </tr>`;
        tbody.innerHTML += row;
        return sum + itemTotal;
    }, 0);
    
    itemsSpan.textContent = cart.length;
    subtotalSpan.textContent = `₱${total.toFixed(2)}`;
    totalSpan.textContent = `₱${total.toFixed(2)}`;
    cartCount.textContent = cart.length;
    
    calculateChange();
}

function removeItem(productId){ 
    const removedIndex = cart.findIndex(item => item.id === productId);
    if (removedIndex !== -1) {
        const removed = cart[removedIndex];
        const product = products.find(p => p.id === removed.id);
        if(product) product.stock += removed.qty;
        cart.splice(removedIndex, 1); 
        renderCart(); 
        showMessage("Item removed from cart", 'error');
    }
}

function calculateChange(){
    const payment = parseFloat(document.getElementById("payment").value) || 0;
    const totalText = document.getElementById("summary-total").textContent;
    const total = parseFloat(totalText.replace('₱', '')) || 0;
    const change = payment - total > 0 ? payment - total : 0;
    document.getElementById("change-display").textContent = `₱${change.toFixed(2)}`;
    
    // Change color if payment is insufficient
    if (payment < total) {
        document.getElementById("change-display").style.color = '#e74c3c';
    } else {
        document.getElementById("change-display").style.color = '#212529';
    }
}

function saveCartBeforeSubmit(event){
    event.preventDefault(); // Stop the form from submitting immediately

    const totalText = document.getElementById("summary-total").textContent;
    const total = parseFloat(totalText.replace('₱', '')) || 0;
    const payment = parseFloat(document.getElementById("payment").value) || 0;

    if(cart.length === 0){ 
        showMessage("Please add at least one product.", 'error'); 
        return false; 
    }
    if(payment < total){
        showMessage(`Payment amount cannot be less than total. Total: ₱${total.toFixed(2)}`, 'error');
        return false;
    }

    // Populate the confirmation box with current values
    document.getElementById("confirmTotal").textContent = `₱${total.toFixed(2)}`;
    document.getElementById("confirmPayment").textContent = `₱${payment.toFixed(2)}`;
    document.getElementById("confirmChange").textContent = `₱${(payment - total).toFixed(2)}`;

    showConfirmationBox();
    return false; // Prevent default form submission
}

function showConfirmationBox() {
    const overlay = document.getElementById('confirmationOverlay');
    const box = overlay.querySelector('.confirmation-box');
    overlay.style.display = 'flex';
    setTimeout(() => {
        box.classList.add('active');
    }, 10);
}

function hideConfirmationBox() {
    const overlay = document.getElementById('confirmationOverlay');
    const box = overlay.querySelector('.confirmation-box');
    box.classList.remove('active');
    setTimeout(() => {
        overlay.style.display = 'none';
    }, 300);
}

// Add an event listener to the Confirm button
document.getElementById('confirmBuyBtn').addEventListener('click', () => {
    document.getElementById("cart_data").value = JSON.stringify(cart);
    document.getElementById('buyForm').submit();
});

// Barcode scanner functions
function startScanner() {
    if (isScannerActive) {
        stopScanner();
        return;
    }
    
    document.getElementById('scan-section').style.display = 'block';
    document.getElementById('scan-btn').innerHTML = '<i class="fas fa-camera me-1"></i> Stop Scan';

    const qrCodeSuccessCallback = (decodedText, decodedResult) => {
        if (isScanningBlocked) {
            return; // Do nothing if blocked
        }
        
        isScanningBlocked = true; // Block scanning
        
        const product = products.find(p => p.barcode === decodedText);
        if (product) {
            // Set the selected product and quantity
            selectedProductId = product.id;
            document.getElementById('quantity').value = 1;
            addFromSearch();
        } else {
            showMessage("Product not found for this barcode.", 'error');
        }
        
        // Unblock scanning after 3 seconds
        setTimeout(() => {
            isScanningBlocked = false;
        }, 3000);
    };

    const config = { fps: 10, qrbox: { width: 200, height: 150 } };
    html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback)
        .then(() => {
            isScannerActive = true;
            showMessage("Scanner started. Point to a barcode to scan.", 'info');
        })
        .catch(err => {
            showMessage("Error starting scanner. Make sure your device has a camera and grant permission.", 'error');
            document.getElementById('scan-section').style.display = 'none';
            document.getElementById('scan-btn').innerHTML = '<i class="fas fa-camera me-1"></i> Scan';
        });
}

function stopScanner() {
    if (!isScannerActive) return;
    
    html5QrCode.stop().then(ignore => {
        isScannerActive = false;
        document.getElementById('scan-btn').innerHTML = '<i class="fas fa-camera me-1"></i> Scan';
        document.getElementById('scan-section').style.display = 'none';
        showMessage("Scanner stopped.", 'info');
    }).catch(err => {
        console.error("Failed to stop scanner: " + err);
    });
}

// Initialize page
renderCart();
</script>
</body>
</html>