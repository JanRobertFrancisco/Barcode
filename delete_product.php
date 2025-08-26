<?php
include 'db.php';

$id = $_GET['id'];
header('Content-Type: application/json');

// Check if the product exists in sales
$result = $conn->query("SELECT COUNT(*) AS cnt FROM sale_items WHERE product_id = $id");
$row = $result->fetch_assoc();

if ($row['cnt'] > 0) {
    // Cannot delete because product exists in sales
    echo json_encode(['error_in_sales' => true]);
    exit;
}

// Safe to delete product
if ($conn->query("DELETE FROM products WHERE id = $id")) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
