<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mark notification as read if requested
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $_POST['notification_id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error marking notification as read: " . $e->getMessage();
    }
    header("Location: notifications.php");
    exit();
}

// Get notifications from database with more detailed information
try {
    $stmt = $conn->prepare("
        SELECT n.*, o.tracking_number, o.status as order_status, o.total_amount,
               GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
        FROM notifications n
        LEFT JOIN orders o ON n.order_id = o.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE n.user_id = :user_id
        GROUP BY n.id
        ORDER BY n.created_at DESC
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching notifications: " . $e->getMessage();
    $notifications = [];
}

// Get unread notification count
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = FALSE");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $unread_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    $unread_count = 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="../CSS/userdashboard.css">
    <link rel="stylesheet" href="../CSS/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        <a href="my_orders.php" title="My Orders"><i class="fas fa-box"></i><span>Orders</span></a>
        <a href="notifications.php" title="Notifications" class="notification-nav-link active">
            <i class="fas fa-bell"></i><span>Notifications</span>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" title="Profile"><i class="fas fa-user"></i><span>Profile</span></a>
        <a href="../process/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<div class="welcome-message">
    <h1>Notifications</h1>
    <p>Keep track of order updates, payment notices, and delivery progress in one place.</p>
</div>

<div class="notifications-shell">
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert error">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="notifications-container">
    <?php if (empty($notifications)): ?>
        <div class="no-notifications">
            <i class="fas fa-bell-slash"></i>
            <h3>No notifications yet</h3>
            <p>You’ll see order and delivery updates here when activity starts.</p>
            <a href="dashboard.php" class="continue-shopping">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-item-icon">
                        <?php
                        $icon = 'fa-info-circle';
                        if ($notification['type'] === 'order') {
                            $icon = 'fa-shopping-cart';
                        } elseif ($notification['type'] === 'status') {
                            $icon = 'fa-truck';
                        }
                        ?>
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-head">
                            <p class="notification-type"><?php echo ucfirst(htmlspecialchars($notification['type'])); ?></p>
                            <span class="notification-time">
                                <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </span>
                        </div>
                        <p class="notification-text"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <?php if ($notification['order_id']): ?>
                            <div class="order-details">
                                <?php if ($notification['product_names']): ?>
                                    <p class="products">Products: <?php echo htmlspecialchars($notification['product_names']); ?></p>
                                <?php endif; ?>
                                <?php if ($notification['total_amount']): ?>
                                    <p class="amount">Total: ₱<?php echo number_format($notification['total_amount'], 2); ?></p>
                                <?php endif; ?>
                                <?php if ($notification['tracking_number']): ?>
                                    <p class="tracking-info">
                                        Tracking Number: <?php echo htmlspecialchars($notification['tracking_number']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($notification['order_status']): ?>
                                    <p class="status">Status: <?php echo ucfirst(htmlspecialchars($notification['order_status'])); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                        <form method="POST" class="mark-read-form">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" name="mark_read" class="mark-read-btn">
                                <i class="fas fa-check"></i> Mark Read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</div>

</body>
</html> 