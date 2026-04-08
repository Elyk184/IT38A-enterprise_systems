<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

try {
    // Get total sales for the period
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_sales,
            AVG(total_amount) as average_order_value
        FROM orders 
        WHERE created_at BETWEEN ? AND ? 
        AND status != 'cancelled'
    ");
    $stmt->execute([$start_date, $end_date]);
    $sales_summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get sales by product
    $stmt = $conn->prepare("
        SELECT 
            p.name,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY p.id
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $product_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order status distribution
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM orders
        WHERE created_at BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get daily sales for the period
    $stmt = $conn->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as order_count,
            SUM(total_amount) as daily_sales
        FROM orders
        WHERE created_at BETWEEN ? AND ?
        AND status != 'cancelled'
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching reports: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root{
            --rp-bg: linear-gradient(180deg, #f7fffe 0%, #eefbf8 100%);
            --rp-card: #ffffff;
            --rp-border: rgba(15,118,110,.10);
            --rp-text: #0f172a;
            --rp-muted: #64748b;
            --rp-primary: #14b8a6;
            --rp-primary-dark: #0f766e;
            --rp-shadow: 0 12px 28px rgba(15,23,42,.08);
        }

        body{ background: var(--rp-bg); }

        .admin-content{ padding: 22px; }
        .admin-header{
            margin: 0 0 14px;
            padding: 16px 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(20,184,166,.14), rgba(255,255,255,.96));
            border: 1px solid var(--rp-border);
            box-shadow: var(--rp-shadow);
        }
        .admin-title h1{
            margin: 0;
            color: var(--rp-primary-dark);
            font-size: 1.6rem;
            font-weight: 800;
        }

        .dashboard-container{ margin: 0; padding: 0; }

        .date-filter{
            background: var(--rp-card);
            border: 1px solid var(--rp-border);
            border-radius: 16px;
            padding: 14px;
            box-shadow: var(--rp-shadow);
            margin-bottom: 14px;
        }
        .date-filter-form{
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: end;
        }
        .date-field{ min-width: 200px; }
        .date-field label{
            display: block;
            font-size: .85rem;
            color: var(--rp-muted);
            margin-bottom: 6px;
            font-weight: 700;
        }
        .date-field input{
            width: 100%;
            border: 1px solid #dbe7e5;
            border-radius: 10px;
            padding: 9px 10px;
        }
        .filter-btn{
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--rp-primary), var(--rp-primary-dark));
            cursor: pointer;
        }

        .stats-grid{ gap: 14px; margin-bottom: 14px; }
        .stat-card{
            background: var(--rp-card);
            border: 1px solid var(--rp-border);
            border-radius: 14px;
            box-shadow: var(--rp-shadow);
        }

        .recent-orders{
            background: var(--rp-card);
            border: 1px solid var(--rp-border);
            border-radius: 16px;
            box-shadow: var(--rp-shadow);
            padding: 14px;
            margin-bottom: 14px;
        }
        .recent-orders h2{
            margin: 0 0 10px;
            color: var(--rp-text);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .chart-wrap{
            position: relative;
            min-height: 320px;
        }

        .orders-table{
            border: 1px solid #edf3f2;
            border-radius: 12px;
            overflow: hidden;
        }
        .orders-table table{ width: 100%; border-collapse: collapse; }
        .orders-table th, .orders-table td{
            padding: 11px 12px;
            border-bottom: 1px solid #edf3f2;
            text-align: left;
        }
        .orders-table th{
            background: #f8fffe;
            color: #475569;
            font-size: .84rem;
            font-weight: 800;
        }
        .orders-table tbody tr:hover{ background: #f6fffd; }

        @media (max-width: 992px){
            .admin-content{ padding: 16px; }
            .orders-table{ overflow-x: auto; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="admin-header">
            <div class="admin-title">
                <h1>Reports</h1>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-container">
            <!-- Date Filter -->
            <div class="date-filter">
                <form method="GET" class="date-filter-form">
                    <div class="date-field">
                        <label for="start_date">Start Date</label>
                        <input id="start_date" type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="date-field">
                        <label for="end_date">End Date</label>
                        <input id="end_date" type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <button type="submit" class="filter-btn">Apply Filter</button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p><?php echo number_format($sales_summary['total_orders'] ?? 0); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-money-bill-wave"></i>
                    <div class="stat-info">
                        <h3>Total Sales</h3>
                        <p>₱<?php echo number_format($sales_summary['total_sales'] ?? 0, 2); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-chart-line"></i>
                    <div class="stat-info">
                        <h3>Average Order Value</h3>
                        <p>₱<?php echo number_format($sales_summary['average_order_value'] ?? 0, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Daily Sales Chart -->
            <div class="recent-orders">
                <h2>Daily Sales</h2>
                <div class="chart-wrap">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="recent-orders">
                <h2>Order Status Distribution</h2>
                <div class="chart-wrap">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Product Sales Table -->
            <div class="recent-orders">
                <h2>Product Sales</h2>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product_sales as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo number_format($product['total_quantity']); ?></td>
                                <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Daily Sales Chart
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_sales, 'date')); ?>,
                datasets: [{
                    label: 'Daily Sales',
                    data: <?php echo json_encode(array_column($daily_sales, 'daily_sales')); ?>,
                    borderColor: '#42c7d9',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($status_distribution, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($status_distribution, 'count')); ?>,
                    backgroundColor: [
                        '#fff3cd',
                        '#cce5ff',
                        '#d4edda',
                        '#f8d7da'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>