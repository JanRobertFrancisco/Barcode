<?php
include 'db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main {
            margin-left: 230px; /* Match sidebar width */
            padding: 20px;
        }
        .table thead th {
            background-color: #f8f9fa;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        /* Sidebar wrapper */
        .sidebar {
            width: 230px;
            float: left;
        }

        /* Print styles */
        @media print {
            .sidebar,
            a.btn, /* hide print button */
            .actions-column { /* hide Actions column */
                display: none !important;
            }
            .main {
                margin-left: 0 !important;
            }
            table {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="main">
        <h2>📄 Products Report</h2>
        <a href="#" onclick="window.print()" class="btn btn-primary mb-3">🖨 Print Report</a>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price (₱)</th>
                    <th>Stock</th>
                    <th>Total Value (₱)</th>
                    <th class="actions-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM products ORDER BY id ASC");
                $grandTotal = 0;
                while ($row = $result->fetch_assoc()):
                    $totalValue = $row['price'] * $row['quantity'];
                    $grandTotal += $totalValue;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td>₱<?= number_format($row['price'], 2) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td>₱<?= number_format($totalValue, 2) ?></td>
                        <td class="actions-column">
                            <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">✏ Edit</a>
                            <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">🗑 Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                    <td><strong>₱<?= number_format($grandTotal, 2) ?></strong></td>
                    <td class="actions-column"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
