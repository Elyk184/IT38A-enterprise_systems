<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle order cancellation
if (isset($_POST['cancel_order']) && isset($_POST['order_id'])) {
    try {
        $conn->beginTransaction();

        // Check if order belongs to user and is in pending status
        $stmt = $conn->prepare("
            SELECT status 
            FROM orders 
            WHERE id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$_POST['order_id'], $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Update order status to cancelled
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'cancelled' 
                WHERE id = ?
            ");
            $stmt->execute([$_POST['order_id']]);

            // Restore product stock
            $stmt = $conn->prepare("
                UPDATE products p
                JOIN order_items oi ON p.id = oi.product_id
                SET p.stock = p.stock + oi.quantity
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$_POST['order_id']]);

            $conn->commit();
            $_SESSION['success'] = "Order cancelled successfully";
        } else {
            throw new Exception("Order cannot be cancelled");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error cancelling order: " . $e->getMessage();
    }
    header("Location: my_orders.php");
    exit();
}

// Handle receive order
if (isset($_POST['receive_order']) && isset($_POST['order_id'])) {
    try {
        $conn->beginTransaction();

        // Check if order belongs to user and is in pending status
        $stmt = $conn->prepare("
            SELECT o.status, o.id, 
                   GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items,
                   GROUP_CONCAT(oi.id) as item_ids
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ? AND o.user_id = ? AND o.status = 'pending'
            GROUP BY o.id
        ");
        $stmt->execute([$_POST['order_id'], $_SESSION['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Update order status to completed
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'completed' 
                WHERE id = ?
            ");
            $stmt->execute([$_POST['order_id']]);

            // Mark all items as received
            $item_ids = explode(',', $order['item_ids']);
            $stmt = $conn->prepare("
                UPDATE order_items 
                SET received = 1, received_at = CURRENT_TIMESTAMP
                WHERE id IN (" . implode(',', array_fill(0, count($item_ids), '?')) . ")
            ");
            $stmt->execute($item_ids);

            // Create notification for order completion
            $message = "Your order #" . $_POST['order_id'] . " has been completed!\n\n";
            $message .= "Items received:\n" . $order['items'] . "\n\n";
            $message .= "Thank you for your purchase!";
            
            createNotification($_SESSION['user_id'], $_POST['order_id'], $message, 'order_completed');

            $conn->commit();
            $_SESSION['success'] = "Order received successfully. All items have been marked as received.";
        } else {
            throw new Exception("Order cannot be received");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error receiving order: " . $e->getMessage();
    }
    header("Location: my_orders.php");
    exit();
}

// Get user's orders with details
try {
    $stmt = $conn->prepare("
        SELECT o.*, 
               GROUP_CONCAT(
                   CONCAT(p.name, ' (', oi.quantity, ')')
                   SEPARATOR ', '
               ) as items,
               GROUP_CONCAT(
                   CONCAT(
                       oi.id, ':', 
                       p.name, ':', 
                       oi.quantity, ':', 
                       COALESCE(oi.received, 0), ':', 
                       COALESCE(oi.received_at, '')
                   )
                   SEPARATOR '|'
               ) as item_details
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-weight: bold;
            color: #333;
        }

        .order-date {
            color: #666;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .order-details {
            margin-bottom: 15px;
        }

        .order-items {
            color: #666;
            margin-bottom: 10px;
        }

        .order-total {
            font-weight: bold;
            color: #2ecc71;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .order-actions form {
            margin: 0;
        }

        .order-actions button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        .receive-btn {
            background-color: #28a745;
            color: white;
        }

        .receive-btn:hover {
            background-color: #218838;
        }

        .tracking-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-orders i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .order-items-list {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-status {
            font-size: 0.9rem;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .item-received {
            background-color: #d4edda;
            color: #155724;
        }

        .item-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .receive-item-btn {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .receive-item-btn:hover {
            background-color: #218838;
        }

        .receive-item-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .receive-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .receive-btn:hover {
            background-color: #218838;
        }

        .receive-btn i {
            font-size: 0.9em;
        }

        .cancel-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .cancel-btn:hover {
            background-color: #c82333;
        }

        .cancel-btn i {
            font-size: 0.9em;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="search-bar">
        <form action="dashboard.php" method="GET">
            <input type="text" name="search" placeholder="Search products...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="nav-icons">
        <a href="dashboard.php"><i class="fas fa-home"></i></a>
        <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
        <a href="notifications.php"><i class="fas fa-bell"></i></a>
        <a href="profile.php"><i class="fas fa-user"></i></a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="welcome-message">
    Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
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

<div class="orders-container">
    <h2>My Orders</h2>
    
    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <i class="fas fa-shopping-bag"></i>
            <p>You haven't placed any orders yet.</p>
            <a href="dashboard.php" class="continue-shopping">Start Shopping</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span class="order-id">Order #<?php echo $order['id']; ?></span>
                        <span class="order-date"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <span class="order-status status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                
                <div class="order-details">
                    <div class="order-items-list">
                        <?php
                        if (!empty($order['item_details'])) {
                            $item_details = explode('|', $order['item_details']);
                            foreach ($item_details as $item) {
                                $parts = explode(':', $item);
                                $item_id = $parts[0] ?? '';
                                $name = $parts[1] ?? 'Unknown Product';
                                $quantity = $parts[2] ?? 0;
                                $received = $parts[3] ?? 0;
                                $received_at = $parts[4] ?? '';
                                ?>
                                <div class="order-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($name); ?></strong>
                                        <span>(Qty: <?php echo (int)$quantity; ?>)</span>
                                    </div>
                                    <div>
                                        <?php if ($received): ?>
                                            <span class="item-status item-received">
                                                Received on <?php echo $received_at ? date('M d, Y', strtotime($received_at)) : 'N/A'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="item-status item-pending">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="order-item">No items found in this order.</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="order-total">
                        Total: ₱<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                    
                    <?php if ($order['tracking_number']): ?>
                        <div class="tracking-info">
                            <strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="order-actions">
                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="receive_order" class="receive-btn">
                                    <i class="fas fa-check"></i> Receive Order
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="cancel_order" class="cancel-btn">
                                    <i class="fas fa-times"></i> Cancel Order
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html> 