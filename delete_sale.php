<?php
include 'db.php';

// Check if id is provided
if (isset($_GET['id'])) {
    $sale_id = intval($_GET['id']);

    // Delete the sale from the database
    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param("i", $sale_id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: sales_management.php?deleted=1");
        exit();
    } else {
        $stmt->close();
        header("Location: sales_management.php?error=1");
        exit();
    }
} else {
    // No id provided
    header("Location: sales_management.php?error=1");
    exit();
}
?>
