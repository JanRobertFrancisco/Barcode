<?php
include 'db.php'; // database connection

// Get product statistics with corrected SQL syntax
$totalProducts = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$lowStockProducts = $conn->query("SELECT COUNT(*) as low FROM products WHERE quantity > 0 AND quantity < 3")->fetch_assoc()['low'];
$outOfStockProducts = $conn->query("SELECT COUNT(*) as `out` FROM products WHERE quantity <= 0")->fetch_assoc()['out'];

// Pagination setup
$results_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$offset = ($current_page - 1) * $results_per_page;

// Get total number of pages
$number_of_pages = ceil($totalProducts / $results_per_page);

// Get products for current page
$result = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT $offset, $results_per_page");
$hasProducts = $result->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management | Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #4361ee;
        --primary-light: #e8eeff;
        --primary-gradient: linear-gradient(135deg, #4361ee 0%, #4361ee 100%);
        --secondary: #858796;
        --success: #22bf4cff;
        --success-light: #d6f8eb;
        --danger: #e74a3b;
        --danger-light: #fbe7e5;
        --warning: #f6c23e;
        --warning-light: #fef7e3;
        --info: #36b9cc;
        --light: #f8f9fc;
        --dark: #2e3a59;
        --sidebar-width: 250px;
        --card-shadow: 0 4px 20px rgba(0,0,0,0.08);
        --transition: all 0.3s ease;
        --border-radius: 12px;
        --table-header-bg: #f8f9fc;
        --table-border: #eaecf4;
    }

    body {
        background-color: #f5f7fb;
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        color: #6e707e;
    }

    .main-content {
        margin-left: var(--sidebar-width);
        padding: 30px;
        transition: var(--transition);
        background-color: #f5f7fb;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--table-border);
    }

    .page-header h2 {
        font-weight: 700;
        color: var(--dark);
        margin: 0;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        margin-bottom: 30px;
        transition: var(--transition);
        overflow: hidden;
        background: #ffffff;
    }

    .card:hover {
        box-shadow: 0 10px 30px rgba(78, 115, 223, 0.15);
        transform: translateY(-2px);
    }

    .card-header {
        background: var(--primary-gradient);
        border-bottom: 1px solid var(--table-border);
        padding: 20px;
        font-weight: 600;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
    }

    .table-container {
        overflow-x: auto;
        border-radius: 0 0 var(--border-radius) var(--border-radius);
    }

    table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    th {
        font-weight: 600;
        color: var(--dark);
        background-color: var(--table-header-bg);
        padding: 16px 20px !important;
        border-top: none;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid var(--table-border);
    }

    td {
        padding: 18px 20px !important;
        vertical-align: middle !important;
        border-top: 1px solid var(--table-border);
    }

    tr {
        transition: var(--transition);
        background: white;
    }

    tr:hover {
        background-color: var(--primary-light);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(78, 115, 223, 0.1);
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 10px 18px;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
    }

    .btn-primary {
        background: var(--primary-gradient);
        box-shadow: 0 4px 10px rgba(44, 85, 211, 0.81);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2543baff 0%, #6a6fd8 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(42, 83, 207, 0.98);
    }

    .btn-success {
        background: linear-gradient(135deg, #1cc88a 0%, #2ae0a0 100%);
        box-shadow: 0 4px 10px rgba(28, 200, 138, 0.25);
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #19b47c 0%, #25cb92 100%);
        box-shadow: 0 6px 15px rgba(28, 200, 138, 0.35);
    }

    .btn-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #f8ce68 100%);
        box-shadow: 0 4px 10px rgba(246, 194, 62, 0.25);
        color: #2e3a59;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, #e4b236 0%, #f0c456 100%);
        box-shadow: 0 6px 15px rgba(246, 194, 62, 0.35);
        color: #2e3a59;
    }

    .btn-danger {
        background: linear-gradient(135deg, #e74a3b 0%, #eb685b 100%);
        box-shadow: 0 4px 10px rgba(231, 74, 59, 0.25);
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #d44335 0%, #e25a4d 100%);
        box-shadow: 0 6px 15px rgba(231, 74, 59, 0.35);
    }

    .btn-sm {
        padding: 8px 12px;
        font-size: 13px;
    }

    .badge {
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
    }

    .bg-success {
        background: linear-gradient(135deg, #1cc88a 0%, #2ae0a0 100%) !important;
    }

    .bg-danger {
        background: linear-gradient(135deg, #e74a3b 0%, #eb685b 100%) !important;
    }

    .bg-dark {
        background: linear-gradient(135deg, #5a5c69 0%, #6f727e 100%) !important;
    }

    .barcode-img {
        height: 50px;
        border: 1px solid #eee;
        background: #fff;
        padding: 5px;
        border-radius: 6px;
        transition: var(--transition);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .barcode-img:hover {
        transform: scale(1.8);
        z-index: 5;
        position: relative;
        box-shadow: 0 5px 15px rgba(78, 115, 223, 0.2);
    }

    .search-container {
        position: relative;
        flex-grow: 1;
        margin-right: 15px;
        max-width: 400px;
    }

    .search-container i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--secondary);
        z-index: 5;
    }

    .search-container input {
        padding-left: 45px;
        border-radius: 30px;
        height: 46px;
        border: 1px solid var(--table-border);
        transition: var(--transition);
        background: white;
    }

    .search-container input:focus {
        box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.15);
        border-color: var(--primary);
    }

    .filter-btn {
        border-radius: 30px;
        margin-right: 8px;
        min-width: 110px;
        padding: 10px 15px;
        border: 1px solid var(--table-border);
        background: white;
        transition: var(--transition);
        color: var(--secondary);
    }

    .filter-btn.active, .filter-btn:hover {
        background: var(--primary-gradient);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 4px 10px rgba(78, 115, 223, 0.25);
    }

    .action-buttons .btn {
        margin-right: 8px;
        border-radius: 8px;
        width: 36px;
        height: 36px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .alert-box {
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        z-index: 1055;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        border: none;
        border-radius: 10px;
        animation: slideIn 0.5s ease;
        border-left: 4px solid;
    }

    .alert-success {
        background-color: var(--success-light);
        color: #0f6848;
        border-left-color: var(--success) !important;
    }

    .alert-danger {
        background-color: var(--danger-light);
        color: #b12a2a;
        border-left-color: var(--danger) !important;
    }

    @keyframes slideIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--secondary);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .empty-state p {
        font-size: 1.1rem;
        margin-bottom: 25px;
    }

    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-top: 1px solid var(--table-border);
        background: var(--table-header-bg);
    }

    .pagination-info {
        color: var(--secondary);
        font-size: 0.9rem;
    }

    .pagination .page-link {
        border-radius: 8px;
        margin: 0 5px;
        border: 1px solid var(--table-border);
        color: var(--dark);
    }

    .pagination .page-item.active .page-link {
        background: var(--primary-gradient);
        border-color: var(--primary);
    }

    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        
        .filter-buttons {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 10px;
            margin-top: 15px;
        }

        .search-container {
            max-width: 100%;
        }
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            top: 20px;
            left: 20px;
        }
    }

    .tooltip-inner {
        border-radius: 6px;
        padding: 6px 12px;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        border-radius: var(--border-radius);
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h2><i class="fas fa-boxes me-2"></i>Products Management</h2>
        <a href="add_product.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i> Add Product
        </a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show alert-box" role="alert" id="alertBox">
            <i class="fas fa-check-circle me-2"></i> Product updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show alert-box" role="alert" id="alertBox">
            <i class="fas fa-check-circle me-2"></i> Product deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-box" role="alert" id="alertBox">
            <i class="fas fa-exclamation-circle me-2"></i> Failed to delete product. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list me-2"></i>Product List</h3>
            <button class="btn btn-success" onclick="printAllBarcodes()">
                <i class="fas fa-print me-2"></i> Print All Barcodes
            </button>
        </div>
        <div class="card-body position-relative">
            <div class="loading-overlay" id="tableLoading">
                <div class="spinner"></div>
            </div>
            
            <div class="d-flex mb-4 flex-wrap align-items-center">
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search" class="form-control" placeholder="Search products by name..." onkeyup="searchProducts()">
                </div>
                <div class="filter-buttons d-flex">
                    <button class="btn filter-btn active" onclick="filterProducts('all')" id="filter-all">
                        <i class="fas fa-cubes me-2"></i> All
                    </button>
                    <button class="btn filter-btn" onclick="filterProducts('low')" id="filter-low">
                        <i class="fas fa-exclamation-triangle me-2"></i> Low Stock
                    </button>
                    <button class="btn filter-btn" onclick="filterProducts('out')" id="filter-out">
                        <i class="fas fa-times-circle me-2"></i> Out of Stock
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table class="table table-hover" id="products_table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price (₱)</th>
                            <th>Stock</th>
                            <th>Barcode</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($hasProducts): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr id="product-<?= $row['id'] ?>" data-quantity="<?= $row['quantity'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="fas fa-box text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($row['product_name']) ?></div>
                                                <?php if(!empty($row['description'])): ?>
                                                    <div class="small text-muted"><?= substr(htmlspecialchars($row['description']), 0, 50) ?>...</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-bold">₱<?= number_format($row['price'], 2) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="fw-semibold me-2"><?= $row['quantity'] ?></span>
                                            <?php if($row['quantity'] <= 0): ?>
                                                <span class="badge bg-dark">Out of Stock</span>
                                            <?php elseif($row['quantity'] > 0 && $row['quantity'] < 3): ?>
                                                <span class="badge bg-danger">Low Stock</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">In Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if(!empty($row['barcode'])): ?>
                                            <img src="https://barcode.tec-it.com/barcode.ashx?data=<?= urlencode($row['barcode']) ?>&code=Code128" 
                                                class="barcode-img" alt="Barcode" id="barcode-<?= $row['id'] ?>"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Click to enlarge">
                                        <?php else: ?>
                                            <span class="text-muted">No barcode</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?= $row['id'] ?>)" data-bs-toggle="tooltip" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php if(!empty($row['barcode'])): ?>
                                            <button class="btn btn-success btn-sm" onclick="printBarcode('<?= $row['id'] ?>', '<?= htmlspecialchars($row['product_name']) ?>')" data-bs-toggle="tooltip" title="Print Barcode">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <h4>No products found</h4>
                                        <p>Get started by adding your first product to the inventory</p>
                                        <a href="add_product.php" class="btn btn-primary">
                                            <i class="fas fa-plus-circle me-2"></i> Add Product
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($hasProducts): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?= $result->num_rows ?> of <?= $totalProducts ?> products
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>">Previous</a>
                        </li>
                        
                        <?php for($page = 1; $page <= $number_of_pages; $page++): ?>
                            <li class="page-item <?= $page == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $page ?>"><?= $page ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $current_page >= $number_of_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Show loading state
    function showLoading() {
        document.getElementById('tableLoading').classList.add('active');
    }

    // Hide loading state
    function hideLoading() {
        document.getElementById('tableLoading').classList.remove('active');
    }

    // Print single barcode
    function printBarcode(id, name) {
        const barcodeImg = document.getElementById("barcode-" + id).src;
        const w = window.open('', '_blank', 'height=400,width=600');
        w.document.write('<html><head><title>Print Barcode - ' + name + '</title>');
        w.document.write('<style>body { text-align: center; font-family: Arial, sans-serif; }</style>');
        w.document.write('</head><body>');
        w.document.write('<h3>' + name + '</h3>');
        w.document.write('<img src="' + barcodeImg + '" style="margin:20px; border:1px solid #000; padding:10px; max-width:100%;">');
        w.document.write('</body></html>');
        w.document.close();
        w.print();
    }

    // Print all barcodes
    function printAllBarcodes() {
        const rows = document.querySelectorAll("#products_table tbody tr");
        let content = "<html><head><title>Print All Barcodes</title>";
        content += "<style>body { font-family: Arial, sans-serif; } .barcode-print { display: inline-block; text-align: center; margin: 10px; padding: 10px; border: 1px solid #000; }</style>";
        content += "</head><body><div style='text-align:center;'>";

        rows.forEach(row => {
            const nameCell = row.querySelector("td:first-child");
            const barcodeImg = row.querySelector("td:nth-child(4) img");
            if (barcodeImg) {
                content += "<div class='barcode-print'>";
                content += "<h4>" + nameCell.innerText + "</h4>";
                content += "<img src='" + barcodeImg.src + "' style='max-width:100%;'>";
                content += "</div>";
            }
        });

        content += "</div></body></html>";
        const w = window.open('', '_blank', 'height=800,width=1000');
        w.document.write(content);
        w.document.close();
        w.print();
    }

    // Search functionality
    function searchProducts() {
        showLoading();
        const filter = document.getElementById('search').value.toLowerCase();
        const trs = document.getElementById('products_table').getElementsByTagName('tr');
        
        setTimeout(() => {
            for (let i = 1; i < trs.length; i++) {
                const nameTd = trs[i].getElementsByTagName('td')[0];
                if (nameTd) {
                    trs[i].style.display = nameTd.innerText.toLowerCase().includes(filter) ? '' : 'none';
                }
            }
            hideLoading();
        }, 300);
    }

    // Filter functionality
    function filterProducts(type) {
        showLoading();
        
        // Update button states first
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById('filter-' + type).classList.add('active');
        
        const trs = document.getElementById('products_table').getElementsByTagName('tr');
        
        setTimeout(() => {
            let visibleCount = 0;
            
            for (let i = 1; i < trs.length; i++) {
                const qty = parseInt(trs[i].getAttribute('data-quantity'));
                if (type === 'all') {
                    trs[i].style.display = '';
                    visibleCount++;
                } else if (type === 'low') {
                    trs[i].style.display = (qty > 0 && qty < 3) ? '' : 'none';
                    if (qty > 0 && qty < 3) visibleCount++;
                } else if (type === 'out') {
                    trs[i].style.display = (qty <= 0) ? '' : 'none';
                    if (qty <= 0) visibleCount++;
                }
            }
            
            // Update pagination info
            const paginationInfo = document.querySelector('.pagination-info');
            if (paginationInfo) {
                paginationInfo.textContent = `Showing ${visibleCount} products`;
            }
            
            hideLoading();
        }, 300);
    }

    // Delete functionality
    function deleteProduct(id) {
        if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            return;
        }

        showLoading();
        fetch('delete_product.php?id=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('product-' + id).remove();
                    showAlert('✅ Product deleted successfully!', 'success');
                    
                    // Update statistics after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    hideLoading();
                    showAlert('❌ Failed to delete product.', 'danger');
                }
            })
            .catch(error => {
                hideLoading();
                showAlert('❌ An error occurred while deleting the product.', 'danger');
                console.error(error);
            });
    }

    // Show alert function
    function showAlert(message, type) {
        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${type} alert-dismissible fade show alert-box`;
        alertBox.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i> 
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertBox);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertBox.remove();
        }, 5000);
    }
</script>
</body>
</html>