<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

$total_products = 0;
$total_orders = 0;
$total_users = 0;
$recent_orders = [];

try {
    $total_products = (int)$conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_orders = (int)$conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_users = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

    $stmt = $conn->query("
        SELECT o.*, u.name AS customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching dashboard data.";
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

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 24px;
            box-sizing: border-box;
        }

        .top-card,
        .welcome-card,
        .panel,
        .stat-card {
            background: #fff;
            border: 1px solid rgba(66, 199, 217, 0.10);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.05);
        }

        .top-card {
            border-radius: 20px;
            padding: 22px 24px;
            margin-bottom: 22px;
        }

        .top-card h1 {
            margin: 0 0 6px;
            font-size: 1.6rem;
            color: #2d7d46;
        }

        .top-card p,
        .welcome-card p {
            margin: 0;
            color: #6b7280;
        }

        .welcome-card {
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 22px;
        }

        .welcome-card h2 {
            margin: 0 0 8px;
            font-size: 1.4rem;
            color: #111827;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .stat-card {
            border-radius: 18px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
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
            border-radius: 20px;
            padding: 22px;
        }

        .panel-header {
            margin-bottom: 18px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #111827;
        }

        .alert {
            margin-bottom: 16px;
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
        }

        .no-data {
            text-align: center;
            color: #6b7280;
            padding: 28px 0;
        }

        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .admin-layout {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="top-card">
                <h1>Admin Dashboard</h1>
                <p>Overview of your store activity</p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

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
                                        <td>₱<?php echo number_format((float)$order['total_amount'], 2); ?></td>
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
        </main>
    </div>
</body>
</html>
