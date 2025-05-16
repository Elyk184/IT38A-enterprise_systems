<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the latest order for the user
try {
    $stmt = $conn->prepare("
        SELECT o.*, 
               GROUP_CONCAT(
                   CONCAT(p.name, ' (', oi.quantity, ')')
                   SEPARATOR ', '
               ) as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching order details: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .order-confirmation {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            text-align: center;
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .order-details {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .order-info {
            margin-bottom: 15px;
        }

        .order-info p {
            margin: 5px 0;
            color: #666;
        }

        .order-info strong {
            color: #333;
        }

        .order-items {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border: 1px solid #eee;
        }

        .order-total {
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .action-buttons a {
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .track-order {
            background-color: #007bff;
            color: white;
        }

        .track-order:hover {
            background-color: #0056b3;
        }

        .continue-shopping {
            background-color: #6c757d;
            color: white;
        }

        .continue-shopping:hover {
            background-color: #5a6268;
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
        <a href="my_orders.php"><i class="fas fa-box"></i></a>
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

<div class="order-confirmation">
    <div class="success-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    
    <h2>Order Placed Successfully!</h2>
    
    <?php if ($order): ?>
        <div class="order-details">
            <div class="order-info">
                <p><strong>Order Number:</strong> #<?php echo $order['id']; ?></p>
                <p><strong>Order Date:</strong> <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></p>
                <p><strong>Status:</strong> <span class="status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></p>
            </div>

            <div class="order-items">
                <h3>Order Items:</h3>
                <p><?php echo htmlspecialchars($order['items']); ?></p>
            </div>

            <div class="order-total">
                Total Amount: ₱<?php echo number_format($order['total_amount'], 2); ?>
            </div>
        </div>

        <div class="action-buttons">
            <a href="my_orders.php" class="track-order">
                <i class="fas fa-box"></i> Track Order
            </a>
            <a href="dashboard.php" class="continue-shopping">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="alert error">
            No order details found.
        </div>
        <div class="action-buttons">
            <a href="dashboard.php" class="continue-shopping">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
