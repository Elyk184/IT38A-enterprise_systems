<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/notification_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

$currentFile = strtolower(basename($_SERVER['SCRIPT_NAME']));
$adminBase   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$appBase     = rtrim(dirname($adminBase), '/\\');

$admin_unread_count = getAdminUnreadNotificationCount();

if (!function_exists('isActiveAny')) {
    function isActiveAny(array $files, string $currentFile): string {
        return in_array(strtolower($currentFile), array_map('strtolower', $files), true) ? 'active' : '';
    }
}
?>

<style>
:root{
    --sb-width: 266px;
    --sb-bg: linear-gradient(180deg, #f8fffe 0%, #edfdfb 100%);
    --sb-border: rgba(13, 148, 136, .10);
    --sb-text: #0f172a;
    --sb-muted: #64748b;
    --sb-hover: rgba(45, 212, 191, .10);
    --sb-active: linear-gradient(135deg, #14b8a6 0%, #0f766e 100%);
    --sb-icon-bg: rgba(45, 212, 191, .14);
    --sb-icon-color: #0f766e;
    --sb-shadow: 0 12px 30px rgba(15, 118, 110, .10);
    --notification-badge: #ef4444;
}

.ecot-sidebar{
    width: var(--sb-width);
    min-height: 100vh;
    position: fixed;
    inset: 0 auto 0 0;
    background: var(--sb-bg);
    border-right: 1px solid var(--sb-border);
    box-shadow: var(--sb-shadow);
    z-index: 1000;
    font-family: "Inter","Segoe UI",Tahoma,sans-serif;
    display: flex;
    flex-direction: column;
}

.ecot-sidebar .logo{
    padding: 22px 18px 18px;
    border-bottom: 1px solid var(--sb-border);
    background: linear-gradient(180deg, rgba(20,184,166,.16) 0%, rgba(20,184,166,0) 100%);
}

.ecot-sidebar .logo h2{
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f766e;
    letter-spacing: .2px;
}

.ecot-sidebar .logo small{
    display: block;
    margin-top: 4px;
    color: var(--sb-muted);
    font-size: .78rem;
}

.ecot-sidebar nav{
    padding: 14px 10px;
    flex: 1;
}

.ecot-sidebar ul{
    margin: 0;
    padding: 0;
    list-style: none;
}

.ecot-sidebar li{
    margin: 6px 0;
}

.ecot-sidebar a{
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--sb-text);
    font-size: .94rem;
    font-weight: 600;
    padding: 11px 12px;
    border-radius: 12px;
    transition: all .18s ease;
    overflow: hidden;
}

.ecot-sidebar a i{
    width: 30px;
    height: 30px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--sb-icon-bg);
    color: var(--sb-icon-color);
    font-size: .88rem;
    transition: all .18s ease;
    flex: 0 0 auto;
}

.ecot-sidebar a:hover{
    background: var(--sb-hover);
    color: #0f172a;
    transform: translateX(3px);
}

.ecot-sidebar a:hover i{
    background: rgba(20, 184, 166, .22);
}

.ecot-sidebar a.active{
    background: var(--sb-active);
    color: #ffffff;
    box-shadow: 0 10px 22px rgba(15, 118, 110, .26);
}

.ecot-sidebar a.active::before{
    content: "";
    position: absolute;
    left: 0;
    top: 10px;
    bottom: 10px;
    width: 4px;
    border-radius: 0 6px 6px 0;
    background: rgba(255,255,255,.95);
}

.ecot-sidebar a.active i{
    background: rgba(255,255,255,.18);
    color: #ffffff;
}

.sidebar-notification-badge {
    background: var(--notification-badge);
    color: white;
    border-radius: 50%;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
}

.ecot-sidebar .logout-link{
    margin-top: 8px;
}

.ecot-sidebar .logout-link:hover{
    background: #fff1f2;
    color: #be123c;
}

.ecot-sidebar .logout-link:hover i{
    background: #ffe4e6;
    color: #be123c;
}

.ecot-sidebar .sidebar-footer{
    padding: 14px 18px 18px;
    border-top: 1px solid var(--sb-border);
    font-size: .78rem;
    color: var(--sb-muted);
}

/* Keep content shifted right - FIXED */
.main-content,
.admin-content,
.content-wrapper{
    margin-left: var(--sb-width);
}

@media (max-width: 992px){
    .ecot-sidebar{
        width: 84px;
    }

    .ecot-sidebar .logo h2,
    .ecot-sidebar .logo small,
    .ecot-sidebar a span,
    .ecot-sidebar .sidebar-footer{
        display: none;
    }

    .ecot-sidebar a{
        justify-content: center;
        padding: 11px;
    }

    .ecot-sidebar a.active::before{
        top: 8px;
        bottom: 8px;
    }

    .sidebar-notification-badge {
        position: absolute;
        right: -6px;
        top: 8px;
        font-size: 0.65rem;
        min-width: 16px;
        height: 16px;
    }

    .main-content,
    .admin-content,
    .content-wrapper{
        margin-left: 84px;
    }
}
</style>

<div class="sidebar ecot-sidebar">
    <div class="logo">
        <h2>EcoT Admin</h2>
        <small>Control Panel</small>
    </div>

    <nav>
        <ul>
            <li>
                <a href="<?php echo htmlspecialchars($adminBase . '/dashboard.php'); ?>"
                   class="<?php echo isActiveAny(['dashboard.php'], $currentFile); ?>">
                    <i class="fas fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="<?php echo htmlspecialchars($adminBase . '/Manage_Products.php'); ?>"
                   class="<?php echo isActiveAny(['Manage_Products.php', 'products.php'], $currentFile); ?>">
                    <i class="fas fa-box-open"></i>
                    <span>Products</span>
                </a>
            </li>

            <li>
                <a href="<?php echo htmlspecialchars($adminBase . '/Orders.php'); ?>"
                   class="<?php echo isActiveAny(['Orders.php', 'orders.php'], $currentFile); ?>">
                    <i class="fas fa-cart-shopping"></i>
                    <span>Orders</span>
                </a>
            </li>

            <li>
                <a href="<?php echo htmlspecialchars($adminBase . '/Manage_Users.php'); ?>"
                   class="<?php echo isActiveAny(['Manage_Users.php', 'users.php', 'manage_users.php'], $currentFile); ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>

            <li>
                <a href="<?php echo htmlspecialchars($adminBase . '/reports.php'); ?>"
                   class="<?php echo isActiveAny(['reports.php'], $currentFile); ?>">
                    <i class="fas fa-chart-column"></i>
                    <span>Reports</span>
                </a>
            </li>

            <li>
                <a href="<?php echo htmlspecialchars($adminBase . '/notifications.php'); ?>"
                   class="<?php echo isActiveAny(['notifications.php'], $currentFile); ?>"
                   <?php if ($admin_unread_count > 0): ?>title="<?php echo $admin_unread_count; ?> unread notifications"<?php endif; ?>>
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php if ($admin_unread_count > 0): ?>
                        <span class="sidebar-notification-badge"><?php echo $admin_unread_count > 99 ? '99+' : $admin_unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a class="logout-link" href="<?php echo htmlspecialchars($appBase . '/process/logout.php'); ?>">
                    <i class="fas fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        Signed in as Admin
    </div>
</div>
