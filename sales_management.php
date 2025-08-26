<?php
include 'db.php';

// Fetch sales data
$sales = $conn->query("
    SELECT s.id AS sale_id, s.sale_date, s.payment, s.change_amt, si.quantity, si.price, p.product_name
    FROM sales s
    JOIN sale_items si ON s.id = si.sale_id
    JOIN products p ON si.product_id = p.id
    ORDER BY s.id DESC
");

$last_sale = 0;
$product_list = [];
$sale_date = '';
$totalAllSales = 0;
$salesData = [];

// Process sales data
while ($row = $sales->fetch_assoc()) {
    if ($last_sale != $row['sale_id'] && $last_sale != 0) {
        $total_per_sale = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $product_list));
        $totalAllSales += $total_per_sale;
        
        $salesData[] = [
            'id' => $last_sale,
            'date' => $sale_date,
            'products' => $product_list,
            'total' => $total_per_sale,
            'payment' => $prev_payment,
            'change' => $prev_change
        ];
        
        $product_list = [];
    }
    
    if ($last_sale != $row['sale_id']) {
        $last_sale = $row['sale_id'];
        $sale_date = date("Y-m-d h:i A", strtotime($row['sale_date']));
        $prev_payment = $row['payment'];
        $prev_change = $row['change_amt'];
    }
    
    $product_list[] = [
        'name' => $row['product_name'],
        'qty' => $row['quantity'],
        'price' => $row['price']
    ];
}

// Add the last sale
if ($last_sale != 0) {
    $total_per_sale = array_sum(array_map(fn($i) => $i['qty'] * $i['price'], $product_list));
    $totalAllSales += $total_per_sale;
    
    $salesData[] = [
        'id' => $last_sale,
        'date' => $sale_date,
        'products' => $product_list,
        'total' => $total_per_sale,
        'payment' => $prev_payment,
        'change' => $prev_change
    ];
}

$totalSales = $conn->query("SELECT COUNT(*) as total FROM sales")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            overflow-x: hidden;
        }
        
        .main { 
            margin-left: var(--sidebar-width); 
            padding: 20px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .main {
                margin-left: 0;
                padding: 15px;
            }
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .table thead th {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 15px;
            font-weight: 600;
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: background-color 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 8px 16px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        .btn-warning {
            background-color: var(--warning);
            border-color: var(--warning);
            color: white;
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .alert-box {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
            border-radius: 8px;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-container input {
            padding-left: 40px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .highlight {
            background-color: rgba(76, 201, 240, 0.2);
            padding: 2px 4px;
            border-radius: 4px;
        }
        
        /* Print receipt styling */
        @media print {
            body * {
                visibility: hidden;
            }
            #printSection, #printSection * {
                visibility: visible;
            }
            #printSection {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: block !important;
            }
            .no-print {
                display: none !important;
            }
        }
        
        /* Custom checkbox */
        .custom-checkbox {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }
        
        /* Print-specific styles */
        .receipt {
            width: 300px;
            font-family: monospace;
            padding: 20px;
            border: 1px dashed #ccc;
            margin: 0 auto;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .receipt-totals {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="page-header">
            <h2><i class="fas fa-cash-register me-2"></i>Sales Management</h2>
            <p class="mb-0">View and manage all sales transactions</p>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show alert-box" role="alert" id="alertBox">
                <i class="fas fa-check-circle me-2"></i>Sale deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-box" role="alert" id="alertBox">
                <i class="fas fa-exclamation-circle me-2"></i>Failed to delete sale. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['selected_deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show alert-box" role="alert" id="alertBox">
                <i class="fas fa-check-circle me-2"></i>Selected sales deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['all_deleted'])): ?>
            <div class="alert alert-warning alert-dismissible fade show alert-box" role="alert" id="alertBox">
                <i class="fas fa-exclamation-triangle me-2"></i>All sales deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Actions Card -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sales Actions</h5>
                <div>
                    <span class="badge bg-primary"><?php echo $totalSales['total']; ?> Sales</span>
                </div>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <a href="add_sale.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Sale</a>
                    <button class="btn btn-danger" onclick="confirmDeleteSelected()"><i class="fas fa-trash me-2"></i>Delete Selected</button>
                    <div class="ms-auto d-flex">
                        <div class="search-container">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search" class="form-control" placeholder="Search products...">
                        </div>
                    </div>
                </div>
                
                <!-- Sales Table -->
                <form id="salesForm" action="delete_selected_sales.php" method="POST">
                    <div class="table-responsive">
                        <table class="table table-hover" id="sales_table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="custom-checkbox" id="selectAll"></th>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Products</th>
                                    <th>Quantity</th>
                                    <th>Price/unit (₱)</th>
                                    <th>Total (₱)</th>
                                    <th>Payment (₱)</th>
                                    <th>Change (₱)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesData as $sale): ?>
                                    <tr>
                                        <td><input type="checkbox" class="custom-checkbox" name="sale_ids[]" value="<?= $sale['id'] ?>"></td>
                                        <td><span class="badge bg-secondary"><?= $sale['id'] ?></span></td>
                                        <td><?= $sale['date'] ?></td>
                                        <td><?= implode('<br>', array_column($sale['products'], 'name')) ?></td>
                                        <td><?= implode('<br>', array_column($sale['products'], 'qty')) ?></td>
                                        <td>₱<?= implode('<br>₱', array_column($sale['products'], 'price')) ?></td>
                                        <td><strong>₱<?= number_format($sale['total'], 2) ?></strong></td>
                                        <td>₱<?= number_format($sale['payment'], 2) ?></td>
                                        <td>₱<?= number_format($sale['change'], 2) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_sale.php?id=<?= $sale['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                                <a href="delete_sale.php?id=<?= $sale['id'] ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                                <a href="print_receipt.php?sale_id=<?= $sale['id'] ?>" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-receipt"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="6" style="text-align:right;">Grand Total (₱):</th>
                                    <th>₱<?= number_format($totalAllSales, 2) ?></th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>
                
                <!-- Pagination -->
                <nav aria-label="Sales pagination">
                    <ul class="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Print Section (Hidden) -->
    <div id="printSection" style="display:none;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alert
        setTimeout(() => {
            let alertBox = document.getElementById("alertBox");
            if (alertBox) {
                alertBox.classList.remove("show");
                setTimeout(() => alertBox.remove(), 500);
            }
        }, 5000);

        // Real-time search with highlight
        document.getElementById('search').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('sales_table');
            const trs = table.getElementsByTagName('tr');
            
            for (let i = 1; i < trs.length; i++) {
                let productCell = trs[i].getElementsByTagName('td')[3];
                let matchFound = false;
                
                if (productCell) {
                    let productsText = productCell.textContent.toLowerCase();
                    matchFound = productsText.includes(filter);
                    
                    // Highlight matching text
                    if (filter && matchFound) {
                        const regex = new RegExp(filter, 'gi');
                        productCell.innerHTML = productCell.textContent.replace(regex, match => 
                            `<span class="highlight">${match}</span>`);
                    }
                }
                
                trs[i].style.display = matchFound ? '' : 'none';
            }
        });

        // Print sale
        function printSale(saleId) {
            const table = document.getElementById('sales_table');
            const rows = table.querySelectorAll('tbody tr');
            const printSection = document.getElementById('printSection');

            let printContent = `
                <div class="receipt">
                    <div class="receipt-header">
                        <h2 style="margin: 0; font-size: 24px;">🏪 My Store</h2>
                        <p style="margin: 5px 0; font-size: 14px;">123 Main St, City</p>
                        <p style="margin: 0; font-size: 14px;">Tel: (123) 456-7890</p>
                    </div>
                    <hr style="border-top: 1px dashed #000; margin: 10px 0;">
            `;

            let found = false;
            
            rows.forEach(row => {
                const checkbox = row.querySelector('input[name="sale_ids[]"]');
                if (checkbox && parseInt(checkbox.value) === saleId) {
                    found = true;
                    const id = row.cells[1].textContent;
                    const date = row.cells[2].textContent;
                    const products = row.cells[3].textContent.split('\n');
                    const qty = row.cells[4].textContent.split('\n');
                    const price = row.cells[5].textContent.split('\n');
                    const total = row.cells[6].textContent;
                    const payment = row.cells[7].textContent;
                    const change = row.cells[8].textContent;

                    printContent += `
                        <p style="margin: 5px 0;"><strong>Receipt #:</strong> ${id}</p>
                        <p style="margin: 5px 0;"><strong>Date:</strong> ${date}</p>
                        <hr style="border-top: 1px dashed #000; margin: 10px 0;">
                        <div class="receipt-item" style="font-weight: bold;">
                            <span>Item</span>
                            <span>Qty x Price</span>
                            <span>Total</span>
                        </div>
                    `;
                    
                    products.forEach((p, i) => {
                        if (p.trim() !== '') {
                            const itemTotal = (parseFloat(price[i].replace('₱','')) * parseInt(qty[i])).toFixed(2);
                            printContent += `
                                <div class="receipt-item">
                                    <span>${p}</span>
                                    <span>${qty[i]} x ${price[i]}</span>
                                    <span>₱${itemTotal}</span>
                                </div>
                            `;
                        }
                    });
                    
                    printContent += `
                        <hr style="border-top: 1px dashed #000; margin: 10px 0;">
                        <div class="receipt-item receipt-totals">
                            <span><strong>Total:</strong></span>
                            <span><strong>${total}</strong></span>
                        </div>
                        <div class="receipt-item">
                            <span>Payment:</span>
                            <span>${payment}</span>
                        </div>
                        <div class="receipt-item">
                            <span>Change:</span>
                            <span>${change}</span>
                        </div>
                        <hr style="border-top: 1px dashed #000; margin: 10px 0;">
                        <p style="text-align: center; margin: 15px 0 5px; font-style: italic;">Thank you for your purchase!</p>
                    `;
                }
            });
            
            if (!found) {
                showToast('Sale details not found.', 'error');
                return;
            }

            printContent += `</div>`;
            printSection.innerHTML = printContent;
            
            // Trigger print
            window.print();
        }

        // Select All
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="sale_ids[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });

        // Confirm before deleting selected sales
        function confirmDeleteSelected() {
            const form = document.getElementById('salesForm');
            const checkboxes = form.querySelectorAll('input[name="sale_ids[]"]:checked');
            
            if (checkboxes.length === 0) {
                showToast('Please select at least one sale to delete.', 'warning');
                return false;
            }
            
            if (confirm(`Are you sure you want to delete ${checkboxes.length} selected sale(s)? This action cannot be undone.`)) {
                form.submit();
            }
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            if (!document.getElementById('toastContainer')) {
                const toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
                toastContainer.style.position = 'fixed';
                toastContainer.style.bottom = '20px';
                toastContainer.style.right = '20px';
                toastContainer.style.zIndex = '1060';
                document.body.appendChild(toastContainer);
            }
            
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} alert-dismissible fade show`;
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            toast.style.minWidth = '300px';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            
            document.getElementById('toastContainer').appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }
    </script>
</body>
</html>