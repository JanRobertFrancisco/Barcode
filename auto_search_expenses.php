<?php
// Start session
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    exit("Unauthorized");
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