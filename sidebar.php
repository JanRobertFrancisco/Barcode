<?php
// Get the current page file name
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sari-Sari Store</title>
    <link rel="stylesheet" href="sidebar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* --- your existing CSS --- */
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: #f4f6fa;
        }
        .sidebar-container {
            display: flex;
        }
        .sidebar {
            background: #142883;
            color: #fff;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease, left 0.3s ease;
            box-shadow: 2px 0 8px rgba(0,0,0,0.2);
            width: 200px;
        }
        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar-header {
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #094585;
        }
        .sidebar-header .brand-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #fff;
            cursor: default;
        }
        .sidebar-header i {
            font-size: 20px;
            margin-right: 8px;
        }
       /* Sidebar header */
.sidebar-header h2 {
    font-size: 18px;   /* bigger for the store name */
    font-weight: 700;  /* bold */
    letter-spacing: 0.5px;
}

/* Nav links */
.nav-link .link-text {
    font-size: 15px;   /* slightly bigger than default */
    font-weight: 500;  /* medium weight */
    letter-spacing: 0.3px;
}

/* Nav links hover / active */
.nav-link.active {
    font-weight: 600;  /* a little bolder for active page */
}

        .nav-link.active {
            background: #4361ee;
            color: #fff;
            font-weight: 500;
        }

        .nav-link.active[href="report.php"] {
            background: #4361ee !important;
            color: #fff !important;
            font-weight: 600;
        }
        .nav-link[href="report.php"]:hover {
            background: #4361ee !important;
            color: #fff !important;
        }

        .sidebar.collapsed .nav-link .link-text {
            opacity: 0;
            pointer-events: none;
        }
        .sidebar.collapsed .nav-link:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 60px;
            background: #4361ee;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 12px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }
        .sidebar.collapsed .sidebar-header h2 {
            display: none;
        }

        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid rgba(255,255,255,0.2);
            position: relative;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #ff6b6b;
            font-weight: bold;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
        }
        .logout-btn i {
            margin-right: 10px;
            font-size: 16px;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.1);
            color: #ff4b4b;
        }

        .sidebar.collapsed .logout-btn .link-text {
            display: none;
        }
        .sidebar.collapsed .logout-btn i {
            margin-right: 0;
            text-align: center;
            width: 100%;
        }
        .sidebar.collapsed .logout-btn:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 60px;
            background: #4361ee;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 12px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }

        .toggle-btn {
            cursor: pointer;
            color: #fff;
            font-size: 18px;
            background: none;
            border: none;
            transition: transform 0.3s;
        }
        .toggle-btn:hover {
            transform: rotate(90deg);
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 999;
                left: -200px;
            }
            .sidebar.show {
                left: 0;
            }
        }

        /* ✅ Submenu styles */
        .sub-menu {
            display: none;
            flex-direction: column;
            margin-left: 20px;
        }
        .sub-menu.show {
            display: flex;
        }
        .arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        .arrow.rotate {
            transform: rotate(90deg);
        }
    </style>
</head>
<body>
    <div class="sidebar-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a class="brand-link">
                    <i class="fas fa-store"></i>
                    <h2>Sari-Sari Store</h2>
                </a>
                <button class="toggle-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <hr class="divider">
            
            <div class="sidebar-content">
                <ul class="nav-links">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" data-tooltip="Dashboard">
                            <i class="fas fa-chart-bar"></i>
                            <span class="link-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products_management.php" class="nav-link <?= ($currentPage == 'products_management.php') ? 'active' : ''; ?>" data-tooltip="Products">
                            <i class="fas fa-warehouse"></i>
                            <span class="link-text">Inventory</span>
                        </a>
                    </li>
                   
                    <li class="nav-item">
                        <a href="suppliers.php" class="nav-link <?= ($currentPage == 'suppliers.php') ? 'active' : ''; ?>" data-tooltip="Suppliers">
                            <i class="fas fa-truck"></i>
                            <span class="link-text">Suppliers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="expenses.php" class="nav-link <?= ($currentPage == 'expenses.php') ? 'active' : ''; ?>" data-tooltip="Expenses">
                            <i class="fas fa-receipt"></i>
                            <span class="link-text">Expenses</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="customers.php" class="nav-link <?= ($currentPage == 'customers.php') ? 'active' : ''; ?>" data-tooltip="Customers">
                            <i class="fas fa-users"></i>
                            <span class="link-text">Customers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="sales_management.php" class="nav-link <?= ($currentPage == 'sales_management.php') ? 'active' : ''; ?>" data-tooltip="Sales">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="link-text">Sales</span>
                        </a>
                    </li>

                    <!-- ✅ Reports menu with arrow + submenu -->
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link" onclick="toggleSubMenu(this)" data-tooltip="Reports">
                            <span>
                                <i class="fas fa-chart-pie"></i>
                                <span class="link-text">Reports</span>
                            </span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </a>
                        <div class="sub-menu">
                            <a href="salesreport.php" class="nav-link <?= ($currentPage == 'salesreport.php') ? 'active' : ''; ?>">Sales Report</a>
                            <a href="expensesreport.php" class="nav-link <?= ($currentPage == 'expensesreport.php') ? 'active' : ''; ?>">Expenses Report</a>
                            <a href="inventoryreport.php" class="nav-link <?= ($currentPage == 'inventoryreport.php') ? 'active' : ''; ?>">Inventory Report</a>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a href="admin_management.php" class="nav-link <?= ($currentPage == 'admin_management.php') ? 'active' : ''; ?>" data-tooltip="Admin Management">
                            <i class="fas fa-user-shield"></i>
                            <span class="link-text">Admin Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : ''; ?>" data-tooltip="Settings">
                            <i class="fas fa-cog"></i>
                            <span class="link-text">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn" data-tooltip="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-text">Logout</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        }

        function toggleSubMenu(element) {
            const subMenu = element.nextElementSibling;
            const arrow = element.querySelector(".arrow");

            // Close other submenus
            document.querySelectorAll(".sub-menu").forEach(menu => {
                if (menu !== subMenu) {
                    menu.classList.remove("show");
                    menu.previousElementSibling.querySelector(".arrow").classList.remove("rotate");
                }
            });

            // Toggle this submenu
            subMenu.classList.toggle("show");
            arrow.classList.toggle("rotate");
        }

        // ✅ Auto-open Reports if current page is inside
        document.addEventListener("DOMContentLoaded", () => {
            const currentPage = "<?= $currentPage; ?>";
            if (["salesreport.php", "expensesreport.php", "inventoryreport.php"].includes(currentPage)) {
                const reportsMenu = document.querySelector(".sub-menu");
                const reportsLink = reportsMenu.previousElementSibling;
                reportsMenu.classList.add("show");
                reportsLink.querySelector(".arrow").classList.add("rotate");
            }
        });
    </script>
</body>
</html>
