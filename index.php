<?php
require_once 'db_config.php';
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get stats for dashboard
$products = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$sales_today = $conn->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
$revenue_today = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn();
$low_stock = $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 10")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sari-Sari Store Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card-counter {
            box-shadow: 2px 2px 10px #DADADA;
            margin: 5px;
            padding: 20px 10px;
            border-radius: 5px;
            transition: .3s;
        }
        .card-counter:hover {
            box-shadow: 4px 4px 20px #DADADA;
            transition: .3s;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <h2>Store Dashboard</h2>
        <div class="row">
            <div class="col-md-3">
                <div class="card-counter primary">
                    <i class="bi bi-box-seam"></i>
                    <span class="count-numbers"><?= $products ?></span>
                    <span class="count-name">Products</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-counter success">
                    <i class="bi bi-cash-stack"></i>
                    <span class="count-numbers">₱<?= number_format($revenue_today, 2) ?></span>
                    <span class="count-name">Today's Sales</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-counter info">
                    <i class="bi bi-receipt"></i>
                    <span class="count-numbers"><?= $sales_today ?></span>
                    <span class="count-name">Transactions Today</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-counter warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span class="count-numbers"><?= $low_stock ?></span>
                    <span class="count-name">Low Stock Items</span>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Sales</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("SELECT id, total_amount, sale_date FROM sales ORDER BY sale_date DESC LIMIT 5");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>
                                            <td>{$row['id']}</td>
                                            <td>₱" . number_format($row['total_amount'], 2) . "</td>
                                            <td>" . date('M d, h:i A', strtotime($row['sale_date'])) . "</td>
                                        </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Low Stock Items</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("SELECT name, price, stock_quantity FROM products WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 5");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<tr>
                                            <td>{$row['name']}</td>
                                            <td>₱" . number_format($row['price'], 2) . "</td>
                                            <td class='" . ($row['stock_quantity'] < 5 ? 'text-danger' : 'text-warning') . "'>{$row['stock_quantity']}</td>
                                        </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>