<?php
session_start();
include 'db.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .main {
            margin-left: 250px; /* sidebar space */
            padding: 20px;
        }
        .table thead th {
            background-color: #f8f9fa;
        }

        /* Hide receipt header/footer in screen */
        .receipt-header,
        .receipt-footer {
            display: none;
        }

        /* Print Styles */
       @media print {
    body {
        font-family: "Courier New", monospace;
        font-size: 12px;
        margin: 0;
        padding: 0;
    }

    .main {
        margin: 0 !important;      /* Remove sidebar offset */
        padding: 0 !important;     /* Remove container padding */
        width: 100%;               /* Use full printable area */
    }

    .btn, a, #sidebar, h2, .card {
        display: none !important;  /* Hide all non-receipt elements */
    }

    .receipt-header,
    .receipt-footer {
        display: block !important;
        text-align: center;
        margin-bottom: 5px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
        font-size: 12px;
    }

    th, td {
        padding: 2px 0;
        text-align: left;
        border-bottom: 1px dashed #000;
    }

    td.text-end {
        text-align: right;
    }

    .grand-total {
        border-top: 2px solid #000;
        font-weight: bold;
    }

    hr {
        border: none;
        border-top: 1px dashed #000;
        margin: 3px 0;
    }

    .footer-note {
        font-size: 10px;
        margin-top: 5px;
    }

    @page {
        size: auto;   /* Use full page width */
        margin: 0;    /* Remove default margins */
    }
}

    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="container-fluid">
            <h2 class="mb-4">💵 Sales Report</h2>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form id="deleteForm" action="delete_sales.php" method="POST" style="display:inline;">
                        <button type="submit" class="btn btn-danger" id="deleteButton">
                            <i class="fas fa-trash-alt"></i> Delete Selected
                        </button>
                    </form>
                    <a href="#" onclick="window.print()" class="btn btn-success">
                        <i class="fas fa-print"></i> Print Sales Report
                    </a>
                </div>
            </div>

            <!-- Receipt Header -->
            <div class="receipt-header">
                <div class="store-name">🏪 Sari-Sari Store</div>
                <div class="store-address">123 Main St, Barangay, City</div>
                <div>Date: <?= date("Y-m-d H:i:s") ?></div>
                <div>Cashier: Admin</div>
                <hr>
            </div>

            <!-- Sales Table -->
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT s.id AS sale_id, si.product_id, si.quantity, si.price, p.product_name 
                            FROM sales s
                            JOIN sale_items si ON s.id = si.sale_id
                            JOIN products p ON si.product_id = p.id
                            ORDER BY s.id ASC";
                    $result = $conn->query($sql);

                    $grandTotal = 0;
                    $currentSaleId = null;

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $total = $row['quantity'] * $row['price'];
                            $grandTotal += $total;

                            $invoiceDisplay = ($row['sale_id'] !== $currentSaleId) ? "#" . $row['sale_id'] : "";
                            $currentSaleId = $row['sale_id'];
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="sales_ids[]" value="<?= htmlspecialchars($row['sale_id']) ?>">
                            </td>
                            <td><?= $invoiceDisplay ?> <?= htmlspecialchars($row['product_name']) ?></td>
                            <td class="text-end"><?= htmlspecialchars($row['quantity']) ?></td>
                            <td class="text-end">₱<?= number_format($row['price'], 2) ?></td>
                            <td class="text-end">₱<?= number_format($total, 2) ?></td>
                        </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>No sales records found.</td></tr>";
                    }
                    ?>
                    <tr>
                        <td colspan="4" class="text-end grand-total">GRAND TOTAL</td>
                        <td class="text-end grand-total">₱<?= number_format($grandTotal, 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Receipt Footer -->
            <div class="receipt-footer">
                <hr>
                <div>Thank you for shopping!</div>
                <div class="footer-note">Visit us again 🛒 | Promo: Buy 2 Get 1 Free</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const checkAll = document.getElementById("checkAll");
            const checkboxes = document.querySelectorAll("input[name='sales_ids[]']");
            const deleteButton = document.getElementById("deleteButton");
            const deleteForm = document.getElementById("deleteForm");

            checkAll.addEventListener("change", function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            deleteButton.addEventListener("click", function(event) {
                event.preventDefault();

                let selected = false;
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) selected = true;
                });

                if (!selected) {
                    alert("Please select at least one sales record to delete.");
                } else {
                    if (confirm("Are you sure you want to delete the selected sales records? This action cannot be undone.")) {
                        deleteForm.submit();
                    }
                }
            });
        });
    </script>
</body>
</html>
