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
        :root{
            --bg: linear-gradient(180deg, #f7fffe 0%, #eefbf8 100%);
            --card: rgba(255,255,255,.92);
            --card-border: rgba(15, 118, 110, .08);
            --text: #0f172a;
            --muted: #64748b;
            --primary: #14b8a6;
            --primary-dark: #0f766e;
            --accent: #2dd4bf;
            --shadow: 0 14px 34px rgba(15, 23, 42, .08);
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 266px;
            width: calc(100% - 266px);
            padding: 26px;
            box-sizing: border-box;
        }

        .top-card,
        .welcome-card,
        .panel,
        .stat-card {
            background: var(--card);
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow);
            backdrop-filter: blur(8px);
        }

        .top-card {
            border-radius: 22px;
            padding: 24px 26px;
            margin-bottom: 22px;
            background: linear-gradient(135deg, rgba(20,184,166,.14), rgba(255,255,255,.95));
        }

        .top-card h1 {
            margin: 0 0 6px;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .top-card p,
        .welcome-card p {
            margin: 0;
            color: var(--muted);
        }

        .welcome-card {
            border-radius: 22px;
            padding: 24px;
            margin-bottom: 22px;
        }

        .welcome-card h2 {
            margin: 0 0 8px;
            font-size: 1.35rem;
            font-weight: 800;
            color: #111827;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .stat-card {
            border-radius: 20px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 38px rgba(15, 23, 42, .10);
        }

        .stat-card i {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            font-size: 1.35rem;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 10px 20px rgba(20, 184, 166, .26);
        }

        .stat-info h3 {
            margin: 0;
            font-size: 0.92rem;
            color: var(--muted);
            font-weight: 700;
        }

        .stat-info p {
            margin: 6px 0 0;
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }

        .panel {
            border-radius: 22px;
            padding: 22px;
        }

        .panel-header {
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(15, 118, 110, .08);
        }

        .panel-header h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .alert {
            margin-bottom: 16px;
            padding: 14px 18px;
            border-radius: 14px;
            font-weight: 700;
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
            font-size: 0.9rem;
            color: #475569;
            background: #f8fffe;
            border-bottom: 1px solid #e5f2ef;
        }

        tbody td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf3f2;
            color: #334155;
        }

        tbody tr:hover {
            background: #f6fffd;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 800;
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
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(20, 184, 166, .22);
        }

        .no-data {
            text-align: center;
            color: var(--muted);
            padding: 28px 0;
        }

        @media (max-width: 900px) {
            .main-content {
                margin-left: 84px;
                width: calc(100% - 84px);
                padding: 18px;
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
