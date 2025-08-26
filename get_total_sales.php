<?php
include 'db.php';

$totalSalesRow = $conn->query("SELECT SUM(total_amount) as total_sales FROM sales")->fetch_assoc();
$totalSales = $totalSalesRow['total_sales'] ?? 0;

echo json_encode(['total_sales' => $totalSales]);
