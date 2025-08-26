<?php
include 'db.php';

// Handle add buyer
if (isset($_POST['add_buyer'])) {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    if (!empty($fullname)) {
        $conn->query("INSERT INTO buyers (fullname) VALUES ('$fullname')");
        header("Location: buyer.php?added=1");
        exit();
    }
}

// Handle delete buyer
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM buyers WHERE id=$id");
    header("Location: buyer.php?deleted=1");
    exit();
}

// Get all buyers
$result = $conn->query("SELECT * FROM buyers ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Buyers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main {
            margin-left: 230px; 
            padding: 20px;
        }
        .alert-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 350px;
            z-index: 1055;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <h2>🧑 Buyers Management</h2>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show alert-box" role="alert" id="alertBox">
                ✅ Buyer added successfully!
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-warning alert-dismissible fade show alert-box" role="alert" id="alertBox">
                🗑 Buyer deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Add Buyer Form -->
        <form method="POST" class="mb-4">
            <div class="input-group">
                <input type="text" name="fullname" class="form-control" placeholder="Enter buyer name" required>
                <button type="submit" name="add_buyer" class="btn btn-primary">➕ Add Buyer</button>
            </div>
        </form>

        <!-- Buyers Table -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Buyer Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                        <td>
                            <a href="buyer.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm">🗑 Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS & Auto-close alert -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => {
            let alertBox = document.getElementById("alertBox");
            if (alertBox) {
                alertBox.classList.remove("show");
                setTimeout(() => alertBox.remove(), 500);
            }
        }, 2000);
    </script>
</body>
</html>
