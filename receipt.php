<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "admin");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ""; // Store any error or success message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantities'] ?? [];
    $payment = floatval($_POST['paymentAmount'] ?? 0);

    if (empty($quantities)) {
        $message = "⚠ No items selected.";
    } else {
        $subtotal = 0;
        $validItems = [];

        // Calculate totals & validate stock
        foreach ($quantities as $pid => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $res = $conn->query("SELECT * FROM products WHERE id = $pid");
                if ($res && $res->num_rows > 0) {
                    $product = $res->fetch_assoc();

                    if ($qty <= $product['quantity']) {
                        $total = $qty * $product['price'];
                        $subtotal += $total;

                        $validItems[] = [
                            'id'    => $product['id'],
                            'price' => $product['price'],
                            'qty'   => $qty,
                            'total' => $total
                        ];
                    } else {
                        $message = "⚠ Not enough stock for product: " . htmlspecialchars($product['name']);
                        break;
                    }
                }
            }
        }

        if (empty($message)) {
            if ($subtotal <= 0) {
                $message = "⚠ Invalid purchase.";
            } elseif ($payment < $subtotal) {
                $message = "⚠ Payment is less than total.";
            } else {
                $change = $payment - $subtotal;

                // Insert into sales table
                $stmt = $conn->prepare("INSERT INTO sales (sale_date, total_amount, payment, change_amt) VALUES (NOW(), ?, ?, ?)");
                $stmt->bind_param("ddd", $subtotal, $payment, $change);
                $stmt->execute();
                $sale_id = $stmt->insert_id;
                $stmt->close();

                // Insert each item into sale_items + update stock
                $itemStmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
                $stockStmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");

                foreach ($validItems as $item) {
                    // Save sale item
                    $itemStmt->bind_param("iiidd", $sale_id, $item['id'], $item['qty'], $item['price'], $item['total']);
                    $itemStmt->execute();

                    // Update stock
                    $stockStmt->bind_param("ii", $item['qty'], $item['id']);
                    $stockStmt->execute();
                }

                $itemStmt->close();
                $stockStmt->close();

                // Redirect to receipt print page
                header("Location: print_receipt.php?sale_id=" . $sale_id);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .msg { margin-bottom: 20px; color: red; font-weight: bold; }
        .btn { padding: 10px 20px; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>

<?php if (!empty($message)) : ?>
    <div class="msg"><?= $message ?></div>
<?php endif; ?>

<!-- Back button -->
<button class="btn" onclick="window.location.href='show_products.php'">⬅ Back to Products</button>

</body>
</html>
