<?php
session_start();
include 'db.php';

// Fetch product safely
$product = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $barcode = preg_replace("/[^A-Za-z0-9]/", "", trim($_POST['barcode']));

    // Check duplicate barcode
    $stmt_check = $conn->prepare("SELECT id FROM products WHERE barcode = ? AND id != ?");
    $stmt_check->bind_param("si", $barcode, $id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $error = "❌ Error: This barcode is already used by another product.";
    } else {
        $stmt_check->close();
        $stmt = $conn->prepare("UPDATE products SET product_name=?, price=?, quantity=?, barcode=? WHERE id=?");
        $stmt->bind_param("sdssi", $name, $price, $stock, $barcode, $id);

        if ($stmt->execute()) {
            $stmt->close();
            // Redirect with success
            header("Location: products_management.php?updated=true");
            exit();
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css"> <!-- Include sidebar CSS -->
    <style>
        .main-content { margin-left: 250px; padding: 20px; transition: margin-left 0.3s; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?> <!-- Include sidebar here -->

    <div class="main-content">
        <h2>✏ Edit Product</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($product): ?>
        <form method="POST" action="edit_product.php?id=<?= $product['id'] ?>">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price (₱)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?= $product['price'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Stock Quantity</label>
                <input type="number" class="form-control" id="stock" name="stock" value="<?= $product['quantity'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="barcode" class="form-label">Barcode</label>
                <input type="text" class="form-control" id="barcode" name="barcode" value="<?= htmlspecialchars($product['barcode']) ?>">
            </div>
            <button type="submit" class="btn btn-warning">Update Product</button>
            <a href="products_management.php" class="btn btn-secondary">Cancel</a>
        </form>
        <?php else: ?>
            <p class="text-danger">Product not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
