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
    <div class="search-bar">
        <form action="dashboard.php" method="GET">
            <input type="text" name="search" placeholder="Search products...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="nav-icons">
        <a href="dashboard.php"><i class="fas fa-home"></i></a>
        <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
        <a href="notifications.php" class="notification-icon">
            <i class="fas fa-bell"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php"><i class="fas fa-user"></i></a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="welcome-message">
    Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert error">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="notifications-container">
    <h2>Notifications</h2>
    
    <?php if (empty($notifications)): ?>
        <div class="no-notifications">
            <i class="fas fa-bell-slash"></i>
            <p>No notifications yet</p>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-icon">
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
                        <span class="notification-time">
                            <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                        </span>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                        <form method="POST" class="mark-read-form">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" name="mark_read" class="mark-read-btn">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff4444;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.75rem;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid #eee;
    background-color: white;
    transition: background-color 0.3s;
}

.notification-item.unread {
    background-color: #f8f9fa;
}

.notification-icon {
    margin-right: 1rem;
    color: #1976d2;
    font-size: 1.25rem;
}

.notification-content {
    flex: 1;
}

.notification-text {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-weight: 500;
}

.order-details {
    background-color: #f8f9fa;
    padding: 0.5rem;
    border-radius: 4px;
    margin: 0.5rem 0;
}

.order-details p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
    color: #666;
}

.tracking-info {
    color: #1976d2;
    font-weight: 500;
}

.status {
    color: #2ecc71;
    font-weight: 500;
}

.notification-time {
    color: #999;
    font-size: 0.75rem;
}

.mark-read-form {
    margin-left: 1rem;
}

.mark-read-btn {
    background: none;
    border: none;
    color: #4caf50;
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.3s;
}

.mark-read-btn:hover {
    color: #388e3c;
}

.no-notifications {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.no-notifications i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ddd;
}

.alert {
    padding: 1rem;
    margin: 1rem;
    border-radius: 4px;
    text-align: center;
}

.alert.error {
    background-color: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}
</style>

</body>
</html> 