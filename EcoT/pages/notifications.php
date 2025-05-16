<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get notifications from session
$notifications = $_SESSION['notifications'] ?? array();
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
        <a href="notifications.php"><i class="fas fa-bell"></i></a>
        <a href="profile.php"><i class="fas fa-user"></i></a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="welcome-message">
    Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!
</div>

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
                <div class="notification-item <?php echo $notification['read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-icon">
                        <i class="fas <?php echo $notification['icon']; ?>"></i>
                    </div>
                    <div class="notification-content">
                        <p class="notification-text"><?php echo htmlspecialchars($notification['message']); ?></p>
                        <span class="notification-time"><?php echo $notification['time']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html> 