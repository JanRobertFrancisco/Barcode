<?php
session_start();

// Always set Philippine timezone
date_default_timezone_set('Asia/Manila');

$quantities = $_POST['quantities'] ?? [];
$selectedProducts = array_filter($quantities, fn($q) => $q > 0);

if(empty($selectedProducts)) {
    header("Location: show_products.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "admin");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$receipt = [];
$total = 0;

// Update stock and prepare receipt
foreach($selectedProducts as $id => $qty) {
    $id = intval($id);
    $qtyToBuy = intval($qty);

    $res = $conn->query("SELECT product_name, price, quantity FROM products WHERE id=$id");
    $row = $res->fetch_assoc();
    if($row && $row['quantity'] >= $qtyToBuy) {
        $newStock = $row['quantity'] - $qtyToBuy;
        $stmt = $conn->prepare("UPDATE products SET quantity=? WHERE id=?");
        $stmt->bind_param("ii", $newStock, $id);
        $stmt->execute();
        $stmt->close();

        $lineTotal = $row['price'] * $qtyToBuy;
        $total += $lineTotal;

        $receipt[] = [
            'id' => $id,
            'name' => $row['product_name'],
            'price' => $row['price'],
            'qty' => $qtyToBuy,
            'total' => $lineTotal
        ];
    }
}

// Payment handling: default = total if not submitted
$paymentAmount = $_POST['paymentAmount'] ?? $total;
$paymentAmount = floatval($paymentAmount);
$changeAmount = $paymentAmount - $total;

// --- Record Sale ---
// Save Philippine time in 12-hour format
$saleDate = date("Y-m-d h:i:s A");

$stmtSale = $conn->prepare("INSERT INTO sales (sale_date, payment, change_amt) VALUES (?, ?, ?)");
$stmtSale->bind_param("sdd", $saleDate, $paymentAmount, $changeAmount);
$stmtSale->execute();
$saleId = $stmtSale->insert_id;
$stmtSale->close();

// Record each item
foreach($receipt as $item) {
    $stmtItem = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmtItem->bind_param("iiid", $saleId, $item['id'], $item['qty'], $item['price']);
    $stmtItem->execute();
    $stmtItem->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; font-family: 'Courier New', Courier, monospace; }
        .receipt-container { width: 350px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .receipt-header { text-align: center; margin-bottom: 15px; }
        .receipt-header h2 { font-weight: bold; margin-bottom: 5px; font-size: 20px; }
        .receipt-header p { margin: 0; font-size: 14px; }
        .divider { border-top: 1px dashed #000; margin: 15px 0; }
        .info-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 5px; }
        .info-row span:last-child { text-align: right; }
        .items-table { width: 100%; font-size: 14px; border-collapse: collapse; }
        .items-table th, .items-table td { text-align: left; padding: 5px 0; }
        .items-table .item-name { width: 45%; }
        .items-table .qty, .items-table .price { width: 15%; text-align: center; }
        .items-table .item-total { width: 25%; text-align: right; }
        .total-summary { text-align: right; margin-top: 15px; font-size: 16px; }
        .total-summary div { margin-bottom: 5px; }
        .total-summary span:last-child { font-weight: bold; }
        .qr-code-section { text-align: center; margin-top: 20px; }
        .qr-code-section img { width: 150px; height: 150px; }
        .footer-text { text-align: center; font-size: 14px; margin-top: 20px; line-height: 1.5; }
        .btn-container { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
        .btn-container .btn { flex: 1; font-size: 14px; }
    </style>
</head>
<body>

<div class="receipt-container" id="receipt">
    <div class="receipt-header">
        <h2>🏪MY STORE</h2>
        <p>Madridejos, Cebu</p>
        <p>Tel: 0912-345-6789</p>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>Receipt #:</span>
        <span><?= htmlspecialchars($saleId) ?></span>
    </div>
    <div class="info-row">
        <span>Date:</span>
        <span><?= date("M d, Y h:i A") ?></span>
    </div>

    <div class="divider"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="item-name">Item</th>
                <th class="qty">Qty</th>
                <th class="price">Price</th>
                <th class="item-total">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($receipt as $item): ?>
            <tr>
                <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                <td class="qty"><?= htmlspecialchars($item['qty']) ?></td>
                <td class="price">₱<?= number_format($item['price'], 2) ?></td>
                <td class="item-total">₱<?= number_format($item['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="total-summary">
        <div>Total: <span>₱<?= number_format($total, 2) ?></span></div>
        <div>Payment: <span>₱<?= number_format($paymentAmount, 2) ?></span></div>
        <div>Change: <span>₱<?= number_format($changeAmount, 2) ?></span></div>
    </div>

    <div class="divider"></div>

    <div class="qr-code-section">
            </div>

    <div class="footer-text">
        <p>Thank you for shopping!</p>
        <p>Please visit us again 🙂</p>
    </div>

    <div class="btn-container">
        <button id="printBtn" onclick="printReceipt()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
        <a id="backBtn" href="show_products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

</div>

<script>
function printReceipt() {
    document.getElementById('printBtn').style.display = 'none';
    document.getElementById('backBtn').style.display = 'none';
    window.print();
    setTimeout(() => { window.location.href = "show_products.php"; }, 500);
}
</script>
</body>
</html>