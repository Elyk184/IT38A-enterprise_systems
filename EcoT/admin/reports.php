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
</head>
<body>
    <div class="admin-header">
        <div class="admin-title">
            <h1>Reports</h1>
        </div>
        <div class="admin-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="Manage_Products.php"><i class="fas fa-box"></i> Products</a>
            <a href="Orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="Manage_Users.php"><i class="fas fa-users"></i> Users</a>
            <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <form method="GET" class="flex gap-4">
                <div>
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="border p-2 rounded">
                </div>
                <div>
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="border p-2 rounded">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Apply Filter</button>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-shopping-cart"></i>
                <div class="stat-info">
                    <h3>Total Orders</h3>
                    <p><?php echo number_format($sales_summary['total_orders']); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill-wave"></i>
                <div class="stat-info">
                    <h3>Total Sales</h3>
                    <p>₱<?php echo number_format($sales_summary['total_sales'], 2); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <div class="stat-info">
                    <h3>Average Order Value</h3>
                    <p>₱<?php echo number_format($sales_summary['average_order_value'], 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Daily Sales Chart -->
        <div class="recent-orders">
            <h2>Daily Sales</h2>
            <canvas id="dailySalesChart"></canvas>
        </div>

        <!-- Order Status Distribution -->
        <div class="recent-orders">
            <h2>Order Status Distribution</h2>
            <canvas id="statusChart"></canvas>
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