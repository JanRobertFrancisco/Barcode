<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['products']) && isset($_POST['paymentAmount'])) {
    $products = $_POST['products'];
    $payment = (float)$_POST['paymentAmount'];
    $grandTotal = 0;

    // Calculate Grand Total
    foreach ($products as $p) {
        $grandTotal += $p['quantity'] * $p['price'];
    }

    if ($payment < $grandTotal) {
        echo "<p>Payment is less than the Grand Total! <a href='sales_management.php'>Go back</a></p>";
        exit;
    }

    // Insert sales and update stock
    foreach ($products as $id => $p) {
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $stmt->bind_param("ii", $p['quantity'], $id);
        $stmt->execute();
    }

    echo "<p>Sale completed successfully!<br>Grand Total: ₱".number_format($grandTotal,2)."<br>Payment: ₱".number_format($payment,2)."<br>Change: ₱".number_format($payment - $grandTotal,2)."</p>";
    echo "<p><a href='show_products.php'>Back to Products</a></p>";

    unset($_SESSION['selected_products']);
} else {
    echo "<p>No products to process. <a href='show_products.php'>Go back</a></p>";
}
?>
