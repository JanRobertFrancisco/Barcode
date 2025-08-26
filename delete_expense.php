<?php
session_start();
include 'db.php';

// Security check – only logged-in users
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Check if 'id' is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: expenses.php");
    exit();
}

$expense_id = intval($_GET['id']); // renamed for clarity

// Prepare delete statement
$stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?"); // Make sure table name is correct
$stmt->bind_param("i", $expense_id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: expenses.php?msg=deleted");
    exit();
} else {
    $stmt->close();
    header("Location: expenses.php?error=delete_failed");
    exit();
}
?>
