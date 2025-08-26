<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sales_ids'])) {
    $sales_ids = array_map('intval', $_POST['sales_ids']);
    $unique_sales_ids = array_unique($sales_ids);

    if (!empty($unique_sales_ids)) {
        $placeholders = implode(',', array_fill(0, count($unique_sales_ids), '?'));

        // Delete sale_items first to prevent FK constraint errors
        $sqlItems = "DELETE FROM sale_items WHERE sale_id IN ($placeholders)";
        $stmtItems = $conn->prepare($sqlItems);
        if ($stmtItems) {
            $types = str_repeat('i', count($unique_sales_ids));
            $stmtItems->bind_param($types, ...$unique_sales_ids);
            $stmtItems->execute();
            $stmtItems->close();
        }

        // Delete sales
        $sqlSales = "DELETE FROM sales WHERE id IN ($placeholders)";
        $stmtSales = $conn->prepare($sqlSales);
        if ($stmtSales) {
            $stmtSales->bind_param($types, ...$unique_sales_ids);
            if ($stmtSales->execute()) $_SESSION['message'] = "Selected sales records deleted successfully.";
            else $_SESSION['error'] = "Error deleting records: " . $stmtSales->error;
            $stmtSales->close();
        } else {
            $_SESSION['error'] = "Failed to prepare SQL statement.";
        }
    }
}

header("Location: sales_report.php");
exit();
?>
