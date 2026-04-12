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
            
            createNotification($_SESSION['user_id'], $message, 'order_completed', $_POST['order_id']);

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
                       oi.id, '::', 
                       p.id, '::',
                       p.name, '::', 
                       oi.quantity, '::', 
                       oi.price, '::',
                       COALESCE(oi.received, 0), '::', 
                       COALESCE(oi.received_at, ''), '::',
                       COALESCE(p.image, '')
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

$order_count = count($orders);
$pending_count = 0;
$completed_count = 0;
$cancelled_count = 0;

foreach ($orders as $order_summary) {
    if ($order_summary['status'] === 'pending') {
        $pending_count++;
    } elseif ($order_summary['status'] === 'completed') {
        $completed_count++;
    } elseif ($order_summary['status'] === 'cancelled') {
        $cancelled_count++;
    }
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
        .orders-shell {
            width: min(1260px, 94vw);
            margin: 0 auto 48px;
        }

        .orders-container {
            max-width: 1200px;
            margin: 18px auto 0;
            padding: 0;
        }

        .orders-hero {
            margin-top: 22px;
            border: 1px solid rgba(20, 184, 166, 0.12);
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(255, 255, 255, 0.96));
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
            padding: 22px 26px;
        }

        .orders-hero h1 {
            margin: 0;
            font-size: clamp(1.35rem, 2.1vw, 2rem);
            font-weight: 800;
            color: #0f172a;
        }

        .orders-hero p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.98rem;
        }

        .orders-summary {
            margin: 16px 0 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 16px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            padding: 14px 16px;
        }

        .summary-card .label {
            display: block;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
        }

        .summary-card .value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 18px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            margin-bottom: 16px;
            padding: 18px 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }

        .order-header > div:first-child {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .order-id {
            font-weight: bold;
            color: #0f172a;
            font-size: 1.05rem;
        }

        .order-date {
            color: #64748b;
            font-size: 0.92rem;
        }

        .order-status {
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fff7ed;
            color: #c2410c;
        }

        .status-processing {
            background-color: #eff6ff;
            color: #2563eb;
        }

        .status-completed {
            background-color: #ecfdf5;
            color: #15803d;
        }

        .status-cancelled {
            background-color: #fef2f2;
            color: #b91c1c;
        }

        .order-details {
            margin-bottom: 10px;
        }

        .order-items {
            color: #64748b;
            margin-bottom: 10px;
        }

        .order-total {
            font-weight: 800;
            color: #0f766e;
            margin-top: 12px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .order-actions form {
            margin: 0;
        }

        .order-actions button {
            padding: 10px 15px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 800;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .cancel-btn {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: white;
        }

        .cancel-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
        }

        .receive-btn {
            background: linear-gradient(135deg, #22c55e, #15803d);
            color: white;
        }

        .receive-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
        }

        .tracking-info {
            background: #f8fafc;
            padding: 12px 14px;
            border-radius: 14px;
            margin-top: 12px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #334155;
        }

        .no-orders {
            text-align: center;
            padding: 48px 24px;
            color: #64748b;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 18px;
            border: 1px dashed #cbd5e1;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
        }

        .no-orders i {
            font-size: 56px;
            color: #cbd5e1;
            margin-bottom: 18px;
        }

        .order-items-list {
            margin: 14px 0;
            padding: 14px;
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .order-item {
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr) auto;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }

        .order-item-thumb {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            background: linear-gradient(135deg, #f1f5f9, #e2f8f5);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .order-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 8px;
        }

        .order-item-thumb .placeholder {
            color: #94a3b8;
            font-size: 1.2rem;
        }

        .order-item-main {
            min-width: 0;
        }

        .order-item-main strong {
            display: block;
            font-size: 0.98rem;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .order-item-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .order-item-link:hover .order-item-main strong {
            color: #0f766e;
        }

        .order-item-main span {
            color: #64748b;
            font-size: 0.9rem;
        }

        .order-item-price {
            margin-top: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .order-item-price .unit-price {
            font-weight: 800;
            color: #0f766e;
        }

        .order-item-price .subtotal-price {
            color: #64748b;
            font-size: 0.88rem;
        }

        .order-item-status-wrap {
            justify-self: end;
            text-align: right;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-status {
            font-size: 0.82rem;
            padding: 6px 10px;
            border-radius: 999px;
            font-weight: 800;
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
            background: linear-gradient(135deg, #22c55e, #15803d);
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 700;
        }

        .receive-item-btn:hover {
            filter: brightness(1.03);
        }

        .receive-item-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
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
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 800;
            transition: transform 0.2s ease, filter 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .cancel-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
        }

        .cancel-btn i {
            font-size: 0.9em;
        }

        @media (max-width: 980px) {
            .orders-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .order-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 640px) {
            .orders-shell {
                width: 94vw;
            }

            .orders-hero {
                padding: 18px;
            }

            .orders-summary {
                grid-template-columns: 1fr;
            }

            .order-card {
                padding: 16px;
            }

            .order-item {
                grid-template-columns: 60px minmax(0, 1fr);
                align-items: flex-start;
            }

            .order-item-thumb {
                width: 60px;
                height: 60px;
            }

            .order-item-status-wrap {
                grid-column: 1 / -1;
                justify-self: start;
                text-align: left;
            }

            .order-item-link {
                display: contents;
            }

            .order-actions button,
            .continue-shopping {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="search-bar" aria-label="Search products">
        <form action="dashboard.php" method="GET" role="search">
            <input type="text" name="search" placeholder="Search sustainable products...">
            <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="nav-icons">
        <a href="dashboard.php" title="Home"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="cart.php" title="Cart"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        <a href="my_orders.php" title="My Orders" class="active"><i class="fas fa-box"></i><span>Orders</span></a>
        <a href="notifications.php" title="Notifications"><i class="fas fa-bell"></i><span>Notifications</span></a>
        <a href="profile.php" title="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
        <a href="../process/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="welcome-message">
    <h1>My Orders</h1>
    <p>Track every order, review item status, and manage pending purchases in one place.</p>
</div>

<div class="orders-shell">
    <section class="orders-hero">
        <h1>Order history</h1>
        <p>See the full status of your recent purchases and take action on pending orders.</p>
    </section>

    <div class="orders-summary">
        <div class="summary-card">
            <span class="label">Total Orders</span>
            <span class="value"><?php echo number_format($order_count); ?></span>
        </div>
        <div class="summary-card">
            <span class="label">Pending</span>
            <span class="value"><?php echo number_format($pending_count); ?></span>
        </div>
        <div class="summary-card">
            <span class="label">Completed</span>
            <span class="value"><?php echo number_format($completed_count); ?></span>
        </div>
        <div class="summary-card">
            <span class="label">Cancelled</span>
            <span class="value"><?php echo number_format($cancelled_count); ?></span>
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
                        <span class="order-date"><?php 
                            try {
                                $date = new DateTime($order['created_at'], new DateTimeZone('UTC'));
                                $date->setTimezone(new DateTimeZone('Asia/Manila'));
                                $date->setDate(2025, $date->format('m'), $date->format('d'));
                                echo $date->format('F j, Y g:i A');
                            } catch (Exception $e) {
                                $date = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                $date->setDate(2025, $date->format('m'), $date->format('d'));
                                echo $date->format('F j, Y g:i A');
                            }
                        ?></span>
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
                                $parts = explode('::', $item, 8);
                                $item_id = $parts[0] ?? '';
                                $product_id = $parts[1] ?? '';
                                $name = $parts[2] ?? 'Unknown Product';
                                $quantity = $parts[3] ?? 0;
                                $unit_price = $parts[4] ?? 0;
                                $received = $parts[5] ?? 0;
                                $received_at = $parts[6] ?? '';
                                $image = $parts[7] ?? '';
                                $product_url = !empty($product_id) ? 'product_details.php?id=' . rawurlencode($product_id) : 'dashboard.php';
                                ?>
                                <div class="order-item">
                                    <a class="order-item-link" href="<?php echo $product_url; ?>" title="View <?php echo htmlspecialchars($name); ?>">
                                        <div class="order-item-thumb">
                                            <?php if (!empty($image)): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>">
                                            <?php else: ?>
                                                <span class="placeholder"><i class="fas fa-image"></i></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <a class="order-item-link" href="<?php echo $product_url; ?>" title="View <?php echo htmlspecialchars($name); ?>">
                                        <div class="order-item-main">
                                            <strong><?php echo htmlspecialchars($name); ?></strong>
                                            <span>Qty: <?php echo (int)$quantity; ?></span>
                                            <div class="order-item-price">
                                                <span class="unit-price">₱<?php echo number_format((float)$unit_price, 2); ?> each</span>
                                                <span class="subtotal-price">Subtotal: ₱<?php echo number_format((float)$unit_price * (int)$quantity, 2); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="order-item-status-wrap">
                                        <?php if ($received): ?>
                                            <span class="item-status item-received">
                                                Received on <?php 
                                                    if ($received_at && $received_at != '0000-00-00 00:00:00') {
                                                        try {
                                                            $date = new DateTime($received_at, new DateTimeZone('UTC'));
                                                            $date->setTimezone(new DateTimeZone('Asia/Manila'));
                                                            $date->setDate(2025, $date->format('m'), $date->format('d'));
                                                            echo $date->format('F j, Y g:i A');
                                                        } catch (Exception $e) {
                                                            $date = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                                            $date->setDate(2025, $date->format('m'), $date->format('d'));
                                                            echo $date->format('F j, Y g:i A');
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                ?>
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
</div>

</body>
</html> 