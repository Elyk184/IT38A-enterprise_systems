<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Initialize variables
$total_products = 0;
$total_orders = 0;
$total_users = 0;
$recent_orders = [];

// Get statistics
try {
    // Total products
    $stmt = $conn->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();

    // Total orders
    $stmt = $conn->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn();

    // Total users
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $total_users = $stmt->fetchColumn();

    // Recent orders
    $stmt = $conn->query("SELECT o.*, u.name as customer_name 
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC 
                         LIMIT 5");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #f4fbfb 0%, #eef8f5 100%);
            color: #1f2937;
        }

        .admin-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 18px 28px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(66, 199, 217, 0.12);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .admin-title h1 {
            margin: 0;
            font-size: 1.6rem;
            color: #2d7d46;
        }

        .admin-subtitle {
            margin-top: 6px;
            font-size: 0.92rem;
            color: #6b7280;
        }

        .admin-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .admin-nav a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            text-decoration: none;
            color: #2563eb;
            background: #f8fafc;
            border: 1px solid transparent;
            transition: 0.2s ease;
            font-weight: 600;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: #42c7d9;
            color: #fff;
            box-shadow: 0 10px 20px rgba(66, 199, 217, 0.18);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 28px auto;
            padding: 0 20px 36px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #ffffff 0%, #f0fffb 100%);
            border: 1px solid rgba(66, 199, 217, 0.12);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .welcome-card h2 {
            margin: 0 0 8px;
            font-size: 1.4rem;
            color: #111827;
        }

        .welcome-card p {
            margin: 0;
            color: #6b7280;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .stat-card {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid rgba(66, 199, 217, 0.10);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.08);
        }

        .stat-card i {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 1.4rem;
            color: #fff;
            background: linear-gradient(135deg, #42c7d9, #2d7d46);
        }

        .stat-info h3 {
            margin: 0;
            font-size: 0.95rem;
            color: #6b7280;
            font-weight: 600;
        }

        .stat-info p {
            margin: 6px 0 0;
            font-size: 2rem;
            font-weight: 800;
            color: #111827;
        }

        .panel {
            background: #fff;
            border-radius: 20px;
            padding: 22px;
            border: 1px solid rgba(66, 199, 217, 0.10);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #111827;
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            padding: 28px 0;
        }

        .orders-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 780px;
        }

        thead th {
            text-align: left;
            padding: 14px 12px;
            font-size: 0.92rem;
            color: #374151;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        tbody td {
            padding: 14px 12px;
            border-bottom: 1px solid #eef2f7;
            color: #374151;
        }

        tbody tr:hover {
            background: #f9fffe;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .status-badge.pending { background: #fff7ed; color: #c2410c; }
        .status-badge.processing { background: #eff6ff; color: #2563eb; }
        .status-badge.shipped { background: #ecfeff; color: #0e7490; }
        .status-badge.completed { background: #ecfdf5; color: #047857; }
        .status-badge.cancelled { background: #fef2f2; color: #b91c1c; }

        .view-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(135deg, #42c7d9, #2d7d46);
            font-weight: 600;
            transition: 0.2s ease;
        }

        .view-btn:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .alert {
            max-width: 1200px;
            margin: 16px auto 0;
            padding: 14px 18px;
            border-radius: 12px;
            font-weight: 600;
        }

        .alert.success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .alert.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-nav {
                width: 100%;
            }

            .admin-nav a {
                flex: 1 1 auto;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="admin-header">
    <div class="admin-title">
        <h1>Admin Dashboard</h1>
        <div class="admin-subtitle">Overview of your store activity</div>
    </div>
    <div class="admin-nav">
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="Manage_Products.php"><i class="fas fa-box"></i> Products</a>
        <a href="Orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
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
    <div class="welcome-card">
        <h2>Welcome back, Admin</h2>
        <p>Manage products, orders, users, and reports from one place.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-box"></i>
            <div class="stat-info">
                <h3>Total Products</h3>
                <p><?php echo $total_products; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-shopping-cart"></i>
            <div class="stat-info">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="stat-info">
                <h3>Total Users</h3>
                <p><?php echo $total_users; ?></p>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Recent Orders</h2>
        </div>

        <?php if (empty($recent_orders)): ?>
            <p class="no-data">No orders found.</p>
        <?php else: ?>
            <div class="orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="Orders.php?view=<?php echo $order['id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
