<?php
session_start();
require_once '../config/db.php';
require_once '../includes/notification_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Mark notification as read if requested
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $_POST['notification_id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $_SESSION['success'] = "Notification marked as read.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error marking notification as read: " . $e->getMessage();
    }
    header("Location: notifications.php");
    exit();
}

// Get admin notifications (current logged-in admin only)
try {
    $stmt = $conn->prepare("
        SELECT n.*, o.tracking_number, o.status as order_status, o.total_amount, u.name as customer_name
        FROM notifications n
        LEFT JOIN orders o ON n.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE n.user_id = :user_id
        ORDER BY n.created_at DESC
    ");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching notifications: " . $e->getMessage();
    $notifications = [];
}

// Get unread count
$unread_count = getAdminUnreadNotificationCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Notifications</title>
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
            --notif-unread: #fef2f2;
            --notif-read: #f8fafc;
        }

        body{ background: var(--bg); color: var(--text); }

        .admin-content{ padding: 22px; }

        .admin-header{
            margin: 0 0 22px;
            padding: 20px 24px;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(20,184,166,.16), rgba(255,255,255,.98));
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .admin-header i { font-size: 1.8rem; color: var(--pri2); }

        .admin-title h1{ margin: 0; color: var(--pri2); font-weight: 800; font-size: 1.6rem; }
        .admin-subtitle { margin: 4px 0 0; color: var(--muted); font-size: .95rem; }

        .notifications-container {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .notif-stats {
            padding: 20px 24px;
            border-bottom: 1px solid #e6f3f1;
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number { font-size: 2rem; font-weight: 800; color: var(--pri2); }
        .stat-label { font-size: .85rem; color: var(--muted); margin-top: 2px; }

        .notifications-list {
            max-height: 70vh;
            overflow-y: auto;
        }

        .notification-item {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f4;
            transition: all .2s ease;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .notification-item:hover { background: #f8fffe; }
        .notification-item.unread { background: var(--notif-unread); }
        .notification-item.read { background: var(--notif-read); }

        .notif-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .notif-icon.new-order { background: rgba(34,197,94,.15); color: #10b981; }
        .notif-icon.cancelled-order { background: rgba(239,68,68,.15); color: #ef4444; }
        .notif-icon.default { background: rgba(20,184,166,.15); color: var(--pri2); }

        .notif-content h4 { margin: 0 0 4px; font-weight: 700; font-size: 1rem; }
        .notif-content p { margin: 0 0 8px; color: var(--text); line-height: 1.5; }
        .notif-meta { font-size: .82rem; color: var(--muted); }

        .notif-actions {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .mark-read-btn, .view-order-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all .18s ease;
        }

        .mark-read-btn {
            background: var(--pri);
            color: white;
        }

        .mark-read-btn:hover { background: var(--pri2); transform: translateY(-1px); }

        .view-order-btn {
            background: transparent;
            color: var(--pri);
            border: 1px solid rgba(20,184,166,.3);
        }

        .view-order-btn:hover { background: rgba(20,184,166,.08); }

        .no-notifications {
            text-align: center;
            padding: 60px 40px;
            color: var(--muted);
        }

        .no-notifications i { font-size: 4rem; opacity: .4; margin-bottom: 20px; display: block; }

        @media (max-width: 992px){
            .admin-content{ padding: 16px; }
            .notification-item { flex-direction: column; gap: 12px; text-align: center; }
            .notif-actions { flex-direction: row; justify-content: center; margin-left: 0; }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-header">
        <i class="fas fa-bell"></i>
        <div>
            <h1>Notifications</h1>
            <p class="admin-subtitle"><?php echo $unread_count; ?> unread • <?php echo count($notifications); ?> total</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="notifications-container">
        <div class="notif-stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $unread_count; ?></div>
                <div class="stat-label">Unread</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($notifications); ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <h3>No notifications</h3>
                <p>Stay updated on new orders and cancellations here.</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $messageLower = strtolower((string)($notification['message'] ?? ''));
                    $isCancelled = strpos($messageLower, 'cancel') !== false;
                    $isNewOrder = strpos($messageLower, 'new order') !== false || $notification['type'] === 'order';
                    $iconClass = $isCancelled ? 'cancelled-order' : ($isNewOrder ? 'new-order' : 'default');
                    $iconName = $isCancelled ? 'fa-times-circle' : ($isNewOrder ? 'fa-shopping-bag' : 'fa-info-circle');
                    ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                        <div class="notif-icon <?php echo $iconClass; ?>">
                            <i class="fas <?php echo $iconName; ?>"></i>
                        </div>
                        <div class="notif-content">
                            <h4><?php echo htmlspecialchars($notification['message']); ?></h4>
                            <p class="notif-meta">
                                <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                <?php if ($notification['order_id']): ?>
                                    • Order #<?php echo $notification['order_id']; ?>
                                    <?php if ($notification['customer_name']): ?>by <?php echo htmlspecialchars($notification['customer_name']); ?><?php endif; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="notif-actions">
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="mark-read-btn">Mark Read</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($notification['order_id']): ?>
                                <a href="Orders.php?view=<?php echo $notification['order_id']; ?>" class="view-order-btn">
                                    <i class="fas fa-eye"></i> View Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
