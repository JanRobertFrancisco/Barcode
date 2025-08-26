<?php
// Start session
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include 'db.php';

// Initialize search variables
$search = "";
$whereClause = "";

// Check if search parameter exists
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $searchTerm = $conn->real_escape_string($search);
    $whereClause = "WHERE expense_name LIKE '%$searchTerm%' 
                    OR amount LIKE '%$searchTerm%' 
                    OR expense_date LIKE '%$searchTerm%' 
                    OR notes LIKE '%$searchTerm%'";
}

// Fetch expenses with optional search filter
$result = $conn->query("SELECT id, expense_name, amount, expense_date, notes 
                        FROM expenses 
                        $whereClause 
                        ORDER BY expense_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Sari-Sari Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css"> <!-- sidebar styles -->
    <style>
        .page-header {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #142883;
        }
        .actions {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn {
            padding: 8px 14px;
            background: #142883;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn:hover {
            background: #1d3ab3;
        }
        .btn-delete {
            background: crimson;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        th {
            background: #142883;
            color: white;
            font-size: 14px;
        }
        tr:hover {
            background: #f9f9f9;
        }
        /* Alert styles */
        .alert {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
            transition: opacity 0.5s ease;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        /* Search bar styles */
        .search-container {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px 10px;
            width: 300px;
        }
        .search-container input {
            border: none;
            outline: none;
            padding: 8px;
            width: 100%;
            font-size: 14px;
        }
        .search-container button {
            background: none;
            border: none;
            cursor: pointer;
            color: #142883;
        }
        .clear-search {
            margin-left: 10px;
            color: #142883;
            text-decoration: none;
            font-size: 14px;
        }
        .clear-search:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?> <!-- Sidebar stays consistent -->

    <div class="main-content">
        <h1 class="page-header"><i class="fas fa-money-bill-wave"></i> Expenses</h1>

        <!-- Centered Alerts -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success" id="alertBox">
                Expense deleted successfully!
            </div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
            <div class="alert alert-error" id="alertBox">
                Failed to delete expense. Please try again.
            </div>
        <?php endif; ?>

        <div class="actions">
            <button class="btn" onclick="location.href='add_expense.php'">
                <i class="fas fa-plus"></i> Add Expense
            </button>
            
            <!-- Search Form -->
            <form method="GET" action="" style="display: flex; align-items: center;">
                <div class="search-container">
                    <input type="text" name="search" placeholder="Search expenses..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                <?php if (!empty($search)): ?>
                    <a href="expenses.php" class="clear-search">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Expense Name</th>
                    <th>Amount (₱)</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id']; ?></td>
                            <td><?= htmlspecialchars($row['expense_name']); ?></td>
                            <td>₱<?= number_format($row['amount'], 2); ?></td>
                            <td><?= date("M d, Y", strtotime($row['expense_date'])); ?></td>
                            <td><?= htmlspecialchars($row['notes']); ?></td>
                            <td>
                                <button class="btn" onclick="location.href='edit_expense.php?id=<?= $row['id']; ?>'">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-delete" onclick="location.href='delete_expense.php?id=<?= $row['id']; ?>'">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">
                            <?php echo !empty($search) ? 'No expenses found matching your search.' : 'No expenses found.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Auto-hide alert after 5 seconds -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const alertBox = document.getElementById('alertBox');
            if(alertBox) {
                setTimeout(() => {
                    alertBox.style.opacity = '0';
                    setTimeout(() => alertBox.style.display = 'none', 500); // fade out smoothly
                }, 5000);
            }
        });
    </script>
</body>
</html>