<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$expense_name = '';
$amount = '';
$date = date('Y-m-d'); // Default to today
$notes = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expense_name = trim($_POST['expense_name']);
    $amount = trim($_POST['amount']);
    $date = trim($_POST['date']);
    $notes = trim($_POST['notes']);

    if (empty($expense_name)) {
        $error = "Expense name is required.";
    } elseif (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $error = "Please enter a valid amount.";
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO expenses (expense_name, amount, expense_date, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $expense_name, $amount, $date, $notes);

        if ($stmt->execute()) {
            $success = "Expense added successfully!";
            // Redirect to expenses.php after 2 seconds
            header("Refresh:2; url=expenses.php");
            // Clear form fields
            $expense_name = $amount = $notes = '';
            $date = date('Y-m-d');
        } else {
            $error = "Error adding expense: " . $conn->error;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Expense</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="sidebar.css">
<style>
    .main-content { margin-left: 250px; padding: 20px; transition: margin-left 0.3s; }
    .alert-container { display: flex; justify-content: center; margin-bottom: 20px; }
    .alert-inner { max-width: 600px; width: 100%; text-align: center; }
    @media (max-width: 992px) { .main-content { margin-left: 0; } }
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2><i class="fas fa-plus-circle"></i> Add New Expense</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-container">
            <div class="alert alert-danger alert-inner"><?= $error ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert-container">
            <div class="alert alert-success alert-inner"><?= $success ?></div>
        </div>
    <?php endif; ?>

    <div class="card p-4">
        <form method="POST" action="add_expense.php">
            <div class="mb-3">
                <label for="expense_name" class="form-label">Expense Name *</label>
                <input type="text" class="form-control" id="expense_name" name="expense_name" value="<?= htmlspecialchars($expense_name) ?>" required>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Amount (₱) *</label>
                <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?= htmlspecialchars($amount) ?>" required>
            </div>

            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date) ?>">
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Expense</button>
            <a href="expenses.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Expenses</a>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
</body>
</html>
