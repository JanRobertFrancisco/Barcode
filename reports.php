<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Example Report: Sales per Product
$report = $conn->query("
    SELECT p.product_name, 
           SUM(s.quantity) AS qty, 
           SUM(s.total_amount) AS total 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    GROUP BY p.product_name 
    ORDER BY total DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .main {
            margin-left: 260px;   /* space for sidebar */
            padding: 30px;        /* breathing room */
        }
        .main h2 {
            margin-bottom: 25px;  /* space below title */
        }
        .card {
            margin-bottom: 25px;  /* spacing between cards */
        }
        @media (max-width: 768px) {
            .main {
                margin-left: 0;   /* full width on mobile */
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h2>📑 Reports</h2>

        <div class="card shadow">
            <div class="card-header bg-success text-white">
                Sales by Product
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $report->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['product_name']); ?></td>
                            <td><?= $row['qty']; ?></td>
                            <td>₱<?= number_format($row['total'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
