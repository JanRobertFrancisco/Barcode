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
    header("Location: supplier.php");
    exit();
}

$supplier_id = intval($_GET['id']);

// Prepare delete statement
$stmt = $conn->prepare("DELETE FROM supplier WHERE id = ?");
$stmt->bind_param("i", $supplier_id);

if ($stmt->execute()) {
    $stmt->close();
    // Redirect back to suppliers list after deletion
    header("Location: supplier.php?msg=deleted");
    exit();
} else {
    $stmt->close();
    // Redirect back with error message
    header("Location: supplier.php?error=delete_failed");
    exit();
}
?>