<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Handle new customer form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO customers (name, contact, address, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $name, $contact, $address);
    $stmt->execute();
    $stmt->close();

    header("Location: customers.php"); // refresh page
    exit();
}

// Fetch customers
$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Sari-Sari Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            background: #f4f4f4;
        }

        .main-content {
            margin-left: 200px;
            padding: 20px;
            flex-grow: 1;
        }

        h1 {
            margin-bottom: 20px;
        }

        .customer-form {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .customer-form input, .customer-form button {
            padding: 10px;
            margin: 5px;
        }
        .customer-form button {
            background: #142883;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        .customer-form button:hover {
            background: #1d3ab3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background: #142883;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1><i class="fas fa-users"></i> Customers</h1>

        <!-- Add Customer Form -->
        <div class="customer-form">
            <form method="post">
                <input type="text" name="name" placeholder="Customer Name" required>
                <input type="text" name="contact" placeholder="Contact Number" required>
                <input type="text" name="address" placeholder="Address" required>
                <button type="submit" name="add_customer"><i class="fas fa-plus"></i> Add Customer</button>
            </form>
        </div>

        <!-- Customers Table -->
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Created At</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['name']); ?></td>
                <td><?= htmlspecialchars($row['contact']); ?></td>
                <td><?= htmlspecialchars($row['address']); ?></td>
                <td><?= $row['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
