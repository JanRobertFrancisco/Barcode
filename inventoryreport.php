<?php
include 'db.php'; // database connection

// Fetch inventory
$sql = "SELECT id, product_name, price, quantity FROM products ORDER BY product_name ASC";
$result = $conn->query($sql);

// Calculate total inventory value
$totalSql = "SELECT SUM(price * quantity) AS total_value FROM products";
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalValue = $totalRow['total_value'] ?? 0;

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory Report - Sari-Sari Store</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6fa; font-family: 'Poppins', sans-serif; }
    .main { margin-left: 200px; padding: 20px; }
    .table thead th { background: #142883; color: #fff; }
    .summary-box {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .summary-box h4 { margin: 0; font-weight: 600; color: #142883; }
    .low-stock { background: #ffe5e5; color: #b30000; font-weight: 600; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main content -->
  <div class="main">
    <h2 class="mb-4"><i class="fas fa-boxes"></i> Inventory Report</h2>

    <!-- Summary -->
    <div class="summary-box">
      <h4>Total Inventory Value: ₱<?= number_format($totalValue, 2) ?></h4>
    </div>

    <!-- Inventory Table -->
    <div class="card">
      <div class="card-body">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Product Name</th>
              <th>Price (₱)</th>
              <th>Quantity</th>
              <th>Total Value (₱)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): 
                $rowValue = $row['price'] * $row['quantity'];
              ?>
                <tr class="<?= ($row['quantity'] <= 5) ? 'low-stock' : '' ?>">
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['product_name']) ?></td>
                  <td>₱<?= number_format($row['price'], 2) ?></td>
                  <td><?= $row['quantity'] ?></td>
                  <td>₱<?= number_format($rowValue, 2) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No products found in inventory.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</body>
</html>
