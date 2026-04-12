<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_functions.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    try {
        $new_status = $_POST['status'];
        $order_id = $_POST['order_id'];
        
        $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $order_id);
        $stmt->execute();
        
        // Notify admin if order was cancelled
        if ($new_status === 'cancelled') {
            $admin_message = "Order #$order_id has been cancelled.";
            createAdminNotification($admin_message, 'cancelled_order', $order_id);
        }
        
        $_SESSION['success'] = "Order status updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
    }
    header("Location: Orders.php" . (isset($_GET['view']) ? "?view=" . $_GET['view'] : ""));
    exit();
}

// Get specific order details if view parameter is set
if (isset($_GET['view'])) {
    try {
        $stmt = $conn->prepare("
            SELECT o.*, u.name as customer_name, u.email as customer_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id
        ");
        $stmt->bindParam(':id', $_GET['view']);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Get order items
            $stmt = $conn->prepare("
                SELECT oi.*, p.name as product_name, p.price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id
            ");
            $stmt->bindParam(':order_id', $order['id']);
            $stmt->execute();
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Order not found.";
            header("Location: Orders.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching order details: " . $e->getMessage();
        header("Location: Orders.php");
        exit();
    }
} else {
    // Get all orders
    try {
        $stmt = $conn->query("
            SELECT o.*, u.name as customer_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching orders: " . $e->getMessage();
        $orders = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root{
            --bg: linear-gradient(180deg, #f7fffe 0%, #eefbf8 100%);
            --card: #fff;
            --border: rgba(15,118,110,.10);
            --text: #0f172a;
            --muted: #64748b;
            --pri: #14b8a6;
            --pri2: #0f766e;
            --shadow: 0 12px 28px rgba(15,23,42,.08);
        }

        body{ background: var(--bg); color: var(--text); }
        .admin-content{ padding: 22px; }

        .admin-header{
            margin: 0 0 14px;
            padding: 16px 20px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(20,184,166,.14), rgba(255,255,255,.96));
            box-shadow: var(--shadow);
        }
        .admin-title h1{ margin: 0; color: var(--pri2); font-weight: 800; }

        .dashboard-container{ margin: 0; padding: 0; }

        .orders-table, .order-details{
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .orders-table table, .order-items table{
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .orders-table th, .orders-table td,
        .order-items th, .order-items td{
            padding: 12px 14px;
            border-bottom: 1px solid #edf3f2;
            text-align: left;
            vertical-align: middle;
        }

        .orders-table th, .order-items th{
            background: #f8fffe;
            color: #475569;
            font-size: .84rem;
            font-weight: 800;
        }

        .orders-table tbody tr:hover,
        .order-items tbody tr:hover{ background: #f6fffd; }

        .status-badge{
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 800;
        }
        .status-badge.pending{ background:#fff7ed; color:#c2410c; }
        .status-badge.processing{ background:#eff6ff; color:#2563eb; }
        .status-badge.completed{ background:#ecfdf5; color:#047857; }
        .status-badge.cancelled{ background:#fef2f2; color:#b91c1c; }

        .view-btn{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: #fff;
            font-weight: 700;
            background: linear-gradient(135deg, var(--pri), var(--pri2));
            box-shadow: 0 8px 18px rgba(20,184,166,.22);
        }

        .order-details{ padding: 16px; }
        .order-info{
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
            gap: 12px;
            margin-bottom: 14px;
        }
        .info-group{
            background: #fbfffe;
            border: 1px solid #e6f3f1;
            border-radius: 12px;
            padding: 12px;
        }
        .info-group h4{ margin: 0 0 4px; color: var(--muted); font-size: .8rem; }
        .info-group p{ margin: 0; font-weight: 700; }

        .status-form select{
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #dbe7e5;
            border-radius: 10px;
        }

        .order-items{ margin-top: 10px; overflow-x: auto; }
        .order-items h3{
            margin: 0;
            padding: 12px 14px;
            border-bottom: 1px solid #edf3f2;
        }

        .order-total{
            margin-top: 12px;
            text-align: right;
            font-size: 1.1rem;
            font-weight: 800;
        }

        .cancel-btn{
            display: inline-block;
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: #334155;
            background: #eef2f7;
            font-weight: 700;
        }

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
            <h1><?php echo isset($_GET['view']) ? 'Order Details' : 'Manage Orders'; ?></h1>
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
        <?php if (isset($_GET['view']) && isset($order)): ?>
            <!-- Order Details View -->
            <div class="order-details">
                <div class="order-info">
                    <div class="info-group">
                        <h4>Order ID</h4>
                        <p>#<?php echo $order['id']; ?></p>
                    </div>
                    <div class="info-group">
                        <h4>Customer</h4>
                        <p><?php echo htmlspecialchars($order['customer_name']); ?></p>
                    </div>
                    <div class="info-group">
                        <h4>Email</h4>
                        <p><?php echo htmlspecialchars($order['customer_email']); ?></p>
                    </div>
                    <div class="info-group">
                        <h4>Date</h4>
                        <p><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="info-group">
                        <h4>Status</h4>
                        <form action="" method="POST" class="status-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                </div>

                <div class="order-items">
                    <h3>Order Items</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="order-total">
                    <p>Total: ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>

                <div class="form-actions">
                    <a href="Orders.php" class="cancel-btn">Back to Orders</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders List View -->
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
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;color:#64748b;padding:18px;">No orders found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
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
                                        <a href="?view=<?php echo $order['id']; ?>" class="view-btn">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
