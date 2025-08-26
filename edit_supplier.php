<?php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Get supplier ID from GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: suppliers.php");
    exit();
}

$supplier_id = intval($_GET['id']);

// Initialize variables
$error = '';
$success = '';
$supplier_name = '';
$contact_person = '';
$phone = '';
$address = '';

// Fetch existing supplier data
$stmt = $conn->prepare("SELECT supplier_name, contact_person, phone, address FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$stmt->bind_result($supplier_name, $contact_person, $phone, $address);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_name_new = trim($_POST['supplier_name']);
    $contact_person_new = trim($_POST['contact_person']);
    $phone_new = trim($_POST['phone']);
    $address_new = trim($_POST['address']);

    if (empty($supplier_name_new)) {
        $error = "Supplier name is required!";
    } else {
        // Check for duplicate supplier name (excluding current ID)
        $stmt_check = $conn->prepare("SELECT id FROM suppliers WHERE supplier_name = ? AND id != ?");
        $stmt_check->bind_param("si", $supplier_name_new, $supplier_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "This supplier name already exists!";
        } else {
            // Update supplier
            $stmt_update = $conn->prepare("UPDATE suppliers SET supplier_name = ?, contact_person = ?, phone = ?, address = ? WHERE id = ?");
            $stmt_update->bind_param("ssssi", $supplier_name_new, $contact_person_new, $phone_new, $address_new, $supplier_id);

            if ($stmt_update->execute()) {
                $success = "Supplier updated successfully!";
                // Update variables for form
                $supplier_name = $supplier_name_new;
                $contact_person = $contact_person_new;
                $phone = $phone_new;
                $address = $address_new;
            } else {
                $error = "Error updating supplier: " . $conn->error;
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier - Sari-Sari Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <style>
        :root {
            --primary: #142883;
            --primary-hover: #1d3ab3;
            --danger: #dc3545;
            --success: #198754;
            --gray: #6c757d;
            --border: #dee2e6;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fb; color: #333; }
        .main-content { margin-left: 250px; padding: 20px; transition: margin-left 0.3s; }
        .page-header { font-size: 24px; font-weight: 600; margin-bottom: 20px; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .card { background: #fff; border-radius: 10px; box-shadow: var(--card-shadow); padding: 25px; max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 5px; font-size: 16px; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(20,40,131,0.1); }
        .btn { padding: 12px 20px; background: var(--primary); color: white; border: none; border-radius: 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 500; }
        .btn:hover { background: var(--primary-hover); transform: translateY(-2px); }
        .btn-back { background: var(--gray); margin-right: 10px; }
        .btn-back:hover { background: #5a6268; }
        .alert { padding: 12px 15px; border-radius: 5px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; justify-content: center; text-align: center; width: 100%; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-container { display: flex; justify-content: center; margin-bottom: 20px; }
        .alert-inner { max-width: 800px; width: 100%; text-align: center; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-col { flex: 1; }
        @media (max-width: 992px) { .main-content { margin-left: 0; padding: 15px; } }
        @media (max-width: 768px) { .form-row { flex-direction: column; gap: 0; } .card { padding: 20px; } }
        @media (max-width: 576px) { .page-header { font-size: 20px; } .btn-text { display: none; } .alert { flex-direction: column; text-align: center; padding: 15px 10px; } }
    </style>
    <script>
        // Auto-redirect after 2 seconds if success
        window.onload = function() {
            const successBox = document.getElementById('success-alert');
            if(successBox) {
                setTimeout(() => {
                    window.location.href = 'suppliers.php';
                }, 2000);
            }
        }
    </script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1 class="page-header"><i class="fas fa-edit"></i> Edit Supplier</h1>

        <!-- Centered Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert-container">
                <div class="alert alert-error alert-inner">
                    <i class="fas fa-exclamation-circle"></i> <?= $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Centered Success Alert -->
        <?php if (!empty($success)): ?>
            <div class="alert-container">
                <div class="alert alert-success alert-inner" id="success-alert">
                    <i class="fas fa-check-circle"></i> <?= $success; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $supplier_id); ?>">
                <div class="form-group">
                    <label for="supplier_name">Supplier Name *</label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="<?= htmlspecialchars($supplier_name); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="contact_person">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?= htmlspecialchars($contact_person); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($phone); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($address); ?></textarea>
                </div>

                <div class="form-group">
                    <button type="button" class="btn btn-back" onclick="location.href='suppliers.php'">
                        <i class="fas fa-arrow-left"></i> <span class="btn-text">Back to Suppliers</span>
                    </button>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> <span class="btn-text">Update Supplier</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
