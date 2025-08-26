<?php
include 'db.php';

if (isset($_POST['sale_ids']) && is_array($_POST['sale_ids'])) {
    $sale_ids = $_POST['sale_ids'];
    
    // Prepare IDs for query
    $ids = implode(',', array_map('intval', $sale_ids));

    // Delete sale_items first to maintain foreign key integrity
    $conn->query("DELETE FROM sale_items WHERE sale_id IN ($ids)");

    // Delete sales
    if ($conn->query("DELETE FROM sales WHERE id IN ($ids)")) {
        header("Location: sales_management.php?selected_deleted=1");
        exit;
    } else {
        header("Location: sales_management.php?error=1");
        exit;
    }
} else {
    header("Location: sales_management.php?error=1");
    exit;
}
?>
