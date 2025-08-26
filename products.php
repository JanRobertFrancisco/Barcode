<?php
include 'db.php';

// Dashboard totals
$total_products = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];
$total_stock = $conn->query("SELECT SUM(quantity) AS stock FROM products")->fetch_assoc()['stock'];
$total_value = $conn->query("SELECT SUM(price * quantity) AS value FROM products")->fetch_assoc()['value'];

// Product list
$sql = "SELECT * FROM products";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Products Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">

    <!-- Dashboard Section -->
    <h2 class="mb-4">📊 Dashboard</h2>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">📦 Total Products</h5>
                    <p class="card-text fs-4"><?= $total_products ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">📊 Total Stock</h5>
                    <p class="card-text fs-4"><?= $total_stock ?: 0 ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">💰 Inventory Value</h5>
                    <p class="card-text fs-4">₱<?= number_format($total_value ?: 0, 2) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Product List Section -->
    <h2 class="mb-4">📦 Products Management</h2>
    <a href="add_product.php" class="btn btn-primary mb-3">➕ Add New Product</a>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price (₱)</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['product_name'] ?></td>
                    <td>₱<?= number_format($row['price'], 2) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td>
                        <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">✏ Edit</a>
                        <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php if (isset($_GET['error_in_sales'])): ?>
    <div class="alert alert-warning alert-dismissible fade show alert-box" role="alert" id="alertBox">
        ⚠ Cannot delete product! It is included in sales records.
    </div>
<?php endif; ?>
