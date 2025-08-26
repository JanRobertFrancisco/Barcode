<?php
include 'db.php'; // database connection

// Fetch expenses
$sql = "SELECT id, expense_name, amount, expense_date FROM expenses ORDER BY expense_date DESC";
$result = $conn->query($sql);

// Calculate total expenses
$totalSql = "SELECT SUM(amount) AS total_expenses FROM expenses";
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalExpenses = $totalRow['total_expenses'] ?? 0;

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expenses Report - Sari-Sari Store</title>
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
  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main content -->
  <div class="main">
    <h2 class="mb-4"><i class="fas fa-file-invoice-dollar"></i> Expenses Report</h2>

    <!-- Summary -->
    <div class="summary-box">
      <h4>Total Expenses: ₱<?= number_format($totalExpenses, 2) ?></h4>
    </div>

    <!-- Expenses Table -->
    <div class="card">
      <div class="card-body">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Expense Name</th>
              <th>Amount (₱)</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['expense_name']) ?></td>
                  <td>₱<?= number_format($row['amount'], 2) ?></td>
                  <td><?= date("M d, Y", strtotime($row['expense_date'])) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center text-muted">No expenses recorded yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</body>
</html>
