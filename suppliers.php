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

// Check if a delete action was requested via GET
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Perform the deletion
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['alert_message'] = "Supplier deleted successfully!";
        $_SESSION['alert_type'] = "success";
    } else {
        $_SESSION['alert_message'] = "Error deleting supplier: " . $conn->error;
        $_SESSION['alert_type'] = "error";
    }
    
    $stmt->close();
    
    // Redirect to avoid form resubmission
    header("Location: suppliers.php");
    exit();
}

// Fetch suppliers
$result = $conn->query("SELECT id, supplier_name, contact_person, phone, address FROM suppliers ORDER BY supplier_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Sari-Sari Store</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css"> <!-- sidebar styles -->
    <style>
        :root {
            --primary: #142883;
            --primary-hover: #1d3ab3;
            --danger: #dc3545;
            --danger-hover: #bb2d3b;
            --success: #198754;
            --warning: #ffc107;
            --info: #0dcaf0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border: #dee2e6;
            --table-hover: rgba(20, 40, 131, 0.05);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        
        .page-header {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 5px;
            padding: 5px 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }
        
        .search-box input {
            border: none;
            outline: none;
            padding: 8px;
            width: 250px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-danger:hover {
            background: var(--danger-hover);
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 13px;
        }
        
        .table-container {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: var(--table-hover);
        }
        
        .action-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .empty-row {
            text-align: center;
            padding: 30px;
            color: var(--gray);
        }
        
        .empty-row i {
            font-size: 50px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(25, 135, 84, 0.15);
            color: var(--success);
        }
        
        /* Alert styles */
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out forwards;
            opacity: 0;
            transform: translateX(100%);
        }
        
        .alert.hide {
            animation: slideOut 0.3s ease-in forwards;
        }
        
        .alert-success {
            background-color: var(--success);
            border-left: 5px solid rgba(0,0,0,0.2);
        }
        
        .alert-error {
            background-color: var(--danger);
            border-left: 5px solid rgba(0,0,0,0.2);
        }
        
        .alert-warning {
            background-color: var(--warning);
            color: var(--dark);
            border-left: 5px solid rgba(0,0,0,0.2);
        }
        
        .alert-info {
            background-color: var(--info);
            color: var(--dark);
            border-left: 5px solid rgba(0,0,0,0.2);
        }
        
        .alert-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            font-size: 18px;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        
        .alert-close:hover {
            opacity: 1;
        }
        
        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .search-box input {
                width: 180px;
            }
            
            .alert-container {
                max-width: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .page-header {
                font-size: 20px;
            }
            
            th, td {
                padding: 10px;
            }
            
            .action-cell {
                flex-direction: column;
            }
            
            .alert-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
        
        /* Animation for table rows */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        tbody tr {
            animation: fadeIn 0.4s ease-out;
        }
        
        tbody tr:nth-child(odd) {
            animation-delay: 0.05s;
        }
        
        tbody tr:nth-child(even) {
            animation-delay: 0.1s;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?> <!-- Sidebar stays consistent -->

    <div class="main-content">
        <h1 class="page-header"><i class="fas fa-truck"></i> Suppliers Management</h1>

        <div class="actions">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search suppliers..." onkeyup="searchTable()">
            </div>
            <button class="btn" onclick="location.href='add_supplier.php'">
                <i class="fas fa-plus"></i> Add New Supplier
            </button>
        </div>

        <div class="table-container">
            <table id="suppliersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id']; ?></td>
                                <td><?= htmlspecialchars($row['supplier_name']); ?></td>
                                <td><?= htmlspecialchars($row['contact_person']); ?></td>
                                <td><?= htmlspecialchars($row['phone']); ?></td>
                                <td><?= htmlspecialchars($row['address']); ?></td>
                                <td class="action-cell">
                                    <button class="btn btn-sm" onclick="location.href='edit_supplier.php?id=<?= $row['id']; ?>'">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-row">
                                <i class="fas fa-inbox"></i>
                                <h3>No Suppliers Found</h3>
                                <p>Add your first supplier by clicking the "Add New Supplier" button</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alert Container -->
    <div class="alert-container" id="alertContainer"></div>

    <script>
        // Search function for the table
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toLowerCase();
            const table = document.getElementById("suppliersTable");
            const tr = table.getElementsByTagName("tr");
            
            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName("td");
                let found = false;
                
                for (let j = 0; j < td.length - 1; j++) { // Skip actions column
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? "" : "none";
            }
        }
        
        // Confirm delete function
        function confirmDelete(id) {
       
                // Redirect to this page with delete parameter
                window.location.href = 'suppliers.php?delete_id=' + id;
            }
        
        
        // Show alert function
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();
            
            const alertEl = document.createElement('div');
            alertEl.className = `alert alert-${type}`;
            alertEl.id = alertId;
            
            // Set icon based on alert type
            let icon = 'info-circle';
            if (type === 'success') icon = 'check-circle';
            if (type === 'error') icon = 'exclamation-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            
            alertEl.innerHTML = `
                <div class="alert-content">
                    <i class="fas fa-${icon}"></i>
                    <span>${message}</span>
                </div>
                <button class="alert-close" onclick="closeAlert('${alertId}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            alertContainer.appendChild(alertEl);
            
            // Auto close after 5 seconds
            setTimeout(() => {
                closeAlert(alertId);
            }, 5000);
        }
        
        // Close alert function
        function closeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.add('hide');
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }
        }
        
        // Add animation to table rows on page load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
            });
            
            // Check for URL parameters to show alerts
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                showAlert(urlParams.get('success'), 'success');
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            if (urlParams.has('error')) {
                showAlert(urlParams.get('error'), 'error');
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
    
    <?php
    // Check for session messages and display them
    if (isset($_SESSION['alert_message'])) {
        $message = $_SESSION['alert_message'];
        $type = $_SESSION['alert_type'] ?? 'info';
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_type']);
        echo "<script>showAlert('$message', '$type');</script>";
    }
    ?>
</body>
</html>