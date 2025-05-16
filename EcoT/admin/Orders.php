<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    try {
        $stmt = $conn->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $_POST['status']);
        $stmt->bindParam(':id', $_POST['order_id']);
        $stmt->execute();
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
</head>
<body>

<div class="admin-header">
    <div class="admin-title">
        <h1><?php echo isset($_GET['view']) ? 'Order Details' : 'Manage Orders'; ?></h1>
    </div>
    <div class="admin-nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="Manage_Products.php"><i class="fas fa-box"></i> Products</a>
        <a href="Orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a>
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
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
