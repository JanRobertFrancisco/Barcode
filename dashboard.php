<?php
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --card-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 10px 35px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --border-radius: 16px;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fb 0%, #eef2f6 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--gray-700);
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 24px;
            transition: var(--transition);
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-200);
            background: #fff;
            padding: 20px 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .page-title {
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            font-size: 1.75rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-title i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        
        .dashboard-card {
            border-radius: var(--border-radius);
            padding: 28px 24px;
            color: #fff;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px);
            border: none;
        }
        
        .dashboard-card.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.2) 100%);
            z-index: 1;
        }
        
        .dashboard-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: rgba(255, 255, 255, 0.4);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        
        .dashboard-card:hover::after {
            transform: scaleX(1);
        }
        
        .dashboard-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--card-shadow-hover);
        }
        
        .card-icon {
            font-size: 32px;
            margin-bottom: 16px;
            opacity: 0.9;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .dashboard-card:hover .card-icon {
            transform: scale(1.2) rotate(5deg);
        }
        
        .card-value {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .dashboard-card:hover .card-value {
            transform: scale(1.1);
        }
        
        .card-label {
            font-size: 15px;
            opacity: 0.9;
            font-weight: 500;
            position: relative;
            z-index: 2;
            letter-spacing: 0.5px;
        }
        
        .card-bg-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .card-bg-info { background: linear-gradient(135deg, var(--info), var(--primary)); }
        .card-bg-success { background: linear-gradient(135deg, #2b9348, #55a630); }
        .card-bg-warning { background: linear-gradient(135deg, #f48c06, #dc2f02); }
        .card-bg-danger { background: linear-gradient(135deg, var(--danger), #9d0208); }
        .card-bg-dark { background: linear-gradient(135deg, #343a40, #212529); }
        .card-bg-secondary { background: linear-gradient(135deg, #6c757d, #495057); }
        .card-bg-purple { background: linear-gradient(135deg, #7209b7, #560bad); }
        
        .chart-container {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--card-shadow);
            height: 100%;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.8s ease, transform 0.8s ease;
            border: 1px solid var(--gray-200);
        }
        
        .chart-container.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .chart-title {
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--dark);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .chart-title i {
            color: var(--primary);
            font-size: 18px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        
        @media (max-width: 768px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .summary-section {
            margin-bottom: 28px;
        }
        
        .section-title {
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark);
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-200);
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.8s ease, transform 0.8s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title.animated {
            opacity: 1;
            transform: translateX(0);
        }
        
        .section-title i {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 22px;
        }
        
        .bg-light-primary {
            background: rgba(67, 97, 238, 0.12) !important;
            color: var(--primary) !important;
        }
        
        .bg-light-warning {
            background: rgba(247, 37, 133, 0.12) !important;
            color: var(--warning) !important;
        }
        
        .bg-light-success {
            background: rgba(76, 201, 240, 0.12) !important;
            color: var(--success) !important;
        }
        
        .bg-light-danger {
            background: rgba(230, 57, 70, 0.12) !important;
            color: var(--danger) !important;
        }
        
        /* Animation delays for staggered effects */
        .dashboard-card:nth-child(1) { transition-delay: 0.1s; }
        .dashboard-card:nth-child(2) { transition-delay: 0.2s; }
        .dashboard-card:nth-child(3) { transition-delay: 0.3s; }
        .dashboard-card:nth-child(4) { transition-delay: 0.4s; }
        .dashboard-card:nth-child(5) { transition-delay: 0.5s; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
        
        /* Badge styles */
        .trend-badge {
            font-size: 11px;
            padding: 3px 7px;
            border-radius: 20px;
            margin-left: 6px;
            font-weight: 600;
        }
        
        /* Custom animations */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Sparkline style */
        .sparkline {
            display: inline-block;
            width: 70px;
            height: 18px;
            margin-left: 8px;
        }
        
        /* Welcome message */
        .welcome-message {
            font-size: 14px;
            color: var(--gray-600);
            margin-top: 6px;
        }
        
        /* Chart size adjustments */
        .chart-canvas-container {
            height: 250px;
            position: relative;
        }
        
        /* Smaller dashboard cards */
        .small-card {
            padding: 20px 16px;
        }
        
        .small-card .card-icon {
            font-size: 26px;
            margin-bottom: 12px;
        }
        
        .small-card .card-value {
            font-size: 26px;
        }
        
        .small-card .card-label {
            font-size: 13px;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title"><i class="bi bi-speedometer2"></i>Dashboard Overview</h1>
    </div>

    <?php
    // ======================
    // QUERIES
    // ======================

    // Products
    $totalProducts = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    $lowStock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity > 0 AND quantity < 3")->fetch_assoc()['count'];
    $outOfStock = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= 0")->fetch_assoc()['count'];
    $grandTotalProducts = $conn->query("SELECT SUM(quantity) as total FROM products")->fetch_assoc()['total'] ?? 0;
    $totalValue = $conn->query("SELECT SUM(quantity * price) as total FROM products")->fetch_assoc()['total'] ?? 0;

    // Sales & Items
    $totalProductsSold = $conn->query("SELECT SUM(quantity) as total FROM sale_items")->fetch_assoc()['total'] ?? 0;
    $grandTotal = $conn->query("SELECT SUM(si.quantity * si.price) as total 
                                FROM sale_items si 
                                JOIN sales s ON si.sale_id = s.id")->fetch_assoc()['total'] ?? 0;

    $todayTotal = $conn->query("SELECT SUM(si.quantity * si.price) as total 
                                FROM sale_items si 
                                JOIN sales s ON si.sale_id = s.id 
                                WHERE DATE(s.sale_date) = CURDATE()")->fetch_assoc()['total'] ?? 0;

    $weekTotal = $conn->query("SELECT SUM(si.quantity * si.price) as total 
                               FROM sale_items si 
                               JOIN sales s ON si.sale_id = s.id 
                               WHERE s.sale_date >= CURDATE() - INTERVAL 7 DAY")->fetch_assoc()['total'] ?? 0;

    $monthTotal = $conn->query("SELECT SUM(si.quantity * si.price) as total 
                                FROM sale_items si 
                                JOIN sales s ON si.sale_id = s.id 
                                WHERE MONTH(s.sale_date) = MONTH(CURDATE()) 
                                AND YEAR(s.sale_date) = YEAR(CURDATE())")->fetch_assoc()['total'] ?? 0;
    
    // NEW: Get sales data for the last 7 days for the trend chart
    $salesTrendData = [];
    $salesTrendLabels = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $salesTrendLabels[] = date('D', strtotime($date));
        
        $salesQuery = $conn->query("SELECT COALESCE(SUM(si.quantity * si.price), 0) as total 
                                    FROM sale_items si 
                                    JOIN sales s ON si.sale_id = s.id 
                                    WHERE DATE(s.sale_date) = '$date'");
        
        $salesData = $salesQuery->fetch_assoc();
        $salesTrendData[] = (float)$salesData['total'];
    }
    
    // NEW: Get inventory distribution data for the pie chart
    $inventoryDistribution = $conn->query("
        SELECT 
            SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN quantity > 0 AND quantity < 5 THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN quantity >= 5 THEN 1 ELSE 0 END) as in_stock
        FROM products
    ")->fetch_assoc();
    
    // NEW: Calculate percentage changes for key metrics
    $yesterdayTotal = $conn->query("SELECT COALESCE(SUM(si.quantity * si.price), 0) as total 
                                    FROM sale_items si 
                                    JOIN sales s ON si.sale_id = s.id 
                                    WHERE DATE(s.sale_date) = CURDATE() - INTERVAL 1 DAY")->fetch_assoc()['total'];
    
    $lastWeekTotal = $conn->query("SELECT COALESCE(SUM(si.quantity * si.price), 0) as total 
                                   FROM sale_items si 
                                   JOIN sales s ON si.sale_id = s.id 
                                   WHERE s.sale_date BETWEEN CURDATE() - INTERVAL 14 DAY AND CURDATE() - INTERVAL 7 DAY")->fetch_assoc()['total'];
    
    $lastMonthTotal = $conn->query("SELECT COALESCE(SUM(si.quantity * si.price), 0) as total 
                                    FROM sale_items si 
                                    JOIN sales s ON si.sale_id = s.id 
                                    WHERE MONTH(s.sale_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) 
                                    AND YEAR(s.sale_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)")->fetch_assoc()['total'];
    
    // Calculate percentage changes
    $dailyChange = $yesterdayTotal > 0 ? (($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100 : 0;
    $weeklyChange = $lastWeekTotal > 0 ? (($weekTotal - $lastWeekTotal) / $lastWeekTotal) * 100 : 0;
    $monthlyChange = $lastMonthTotal > 0 ? (($monthTotal - $lastMonthTotal) / $lastMonthTotal) * 100 : 0;
    ?>

    <div class="summary-section">
        <h2 class="section-title"><i class="bi bi-box-seam"></i>Inventory Summary</h2>
        <div class="stats-grid">
            <div class="dashboard-card card-bg-primary small-card">
                <div class="card-icon"><i class="bi bi-box-seam"></i></div>
                <div class="card-value"><?= $totalProducts ?></div>
                <div class="card-label">Total Products</div>
            </div>
            
            <div class="dashboard-card card-bg-info small-card">
                <div class="card-icon"><i class="bi bi-boxes"></i></div>
                <div class="card-value"><?= $grandTotalProducts ?></div>
                <div class="card-label">Grand Total Products</div>
            </div>
            
            <div class="dashboard-card card-bg-success small-card">
                <div class="card-icon"><i class="bi bi-currency-exchange"></i></div>
                <div class="card-value">₱<?= number_format($totalValue, 2) ?></div>
                <div class="card-label">Total Stock Value</div>
            </div>
            
            <div class="dashboard-card card-bg-warning small-card">
                <div class="card-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="card-value"><?= $lowStock ?></div>
                <div class="card-label">Low Stock</div>
            </div>
            
            <div class="dashboard-card card-bg-danger small-card">
                <div class="card-icon"><i class="bi bi-x-circle"></i></div>
                <div class="card-value"><?= $outOfStock ?></div>
                <div class="card-label">Out of Stock</div>
            </div>
        </div>
    </div>

    <div class="summary-section">
        <h2 class="section-title"><i class="bi bi-graph-up"></i>Sales Performance</h2>
        <div class="stats-grid">
            <div class="dashboard-card card-bg-dark small-card">
                <div class="card-icon"><i class="bi bi-cart-check"></i></div>
                <div class="card-value"><?= $totalProductsSold ?></div>
                <div class="card-label">Total Products Sold</div>
            </div>
            
            <div class="dashboard-card card-bg-purple small-card">
                <div class="card-icon"><i class="bi bi-graph-up"></i></div>
                <div class="card-value">₱<?= number_format($grandTotal, 2) ?></div>
                <div class="card-label">Grand Sales Total</div>
            </div>
            
            <div class="dashboard-card card-bg-primary small-card">
                <div class="card-icon"><i class="bi bi-sun"></i></div>
                <div class="card-value">₱<?= number_format($todayTotal, 2) ?></div>
                <div class="card-label">Today Sales
                    <?php if ($dailyChange != 0): ?>
                        <span class="trend-badge <?= $dailyChange > 0 ? 'bg-light-success' : 'bg-light-danger' ?>">
                            <?= $dailyChange > 0 ? '↑' : '↓' ?> <?= number_format(abs($dailyChange), 1) ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-card card-bg-warning small-card">
                <div class="card-icon"><i class="bi bi-calendar-week"></i></div>
                <div class="card-value">₱<?= number_format($weekTotal, 2) ?></div>
                <div class="card-label">Weekly Sales
                    <?php if ($weeklyChange != 0): ?>
                        <span class="trend-badge <?= $weeklyChange > 0 ? 'bg-light-success' : 'bg-light-danger' ?>">
                            <?= $weeklyChange > 0 ? '↑' : '↓' ?> <?= number_format(abs($weeklyChange), 1) ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-card card-bg-secondary small-card">
                <div class="card-icon"><i class="bi bi-calendar-month"></i></div>
                <div class="card-value">₱<?= number_format($monthTotal, 2) ?></div>
                <div class="card-label">Monthly Sales
                    <?php if ($monthlyChange != 0): ?>
                        <span class="trend-badge <?= $monthlyChange > 0 ? 'bg-light-success' : 'bg-light-danger' ?>">
                            <?= $monthlyChange > 0 ? '↑' : '↓' ?> <?= number_format(abs($monthlyChange), 1) ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-container">
            <h5 class="chart-title"><i class="bi bi-pie-chart"></i>Inventory Status Distribution</h5>
            <div class="chart-canvas-container">
                <canvas id="inventoryPieChart"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <h5 class="chart-title"><i class="bi bi-bar-chart"></i>Sales Trend (Last 7 Days)</h5>
            <div class="chart-canvas-container">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Initialize elements with animation classes
    document.addEventListener('DOMContentLoaded', function() {
        // Animate dashboard cards
        document.querySelectorAll('.dashboard-card').forEach(card => {
            card.classList.add('animated');
        });
        
        // Animate section titles
        document.querySelectorAll('.section-title').forEach(title => {
            title.classList.add('animated');
        });
        
        // Animate chart containers with a slight delay
        setTimeout(() => {
            document.querySelectorAll('.chart-container').forEach(chart => {
                chart.classList.add('animated');
            });
        }, 300);
    });

    // Inventory Status Pie Chart
    const pieCtx = document.getElementById('inventoryPieChart').getContext('2d');
    const pieChart = new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: [
                'In Stock (≥5 units)',
                'Low Stock (1-4 units)',
                'Out of Stock'
            ],
            datasets: [{
                data: [
                    <?= $inventoryDistribution['in_stock'] ?>,
                    <?= $inventoryDistribution['low_stock'] ?>,
                    <?= $inventoryDistribution['out_of_stock'] ?>
                ],
                backgroundColor: [
                    '#2b9348',
                    '#f48c06',
                    '#e63946'
                ],
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 2000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        font: { size: 11 },
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Sales Trend Chart
    const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
    const trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($salesTrendLabels) ?>,
            datasets: [{
                label: 'Daily Sales (₱)',
                data: <?= json_encode($salesTrendData) ?>,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        },
                        font: {
                            size: 10
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>