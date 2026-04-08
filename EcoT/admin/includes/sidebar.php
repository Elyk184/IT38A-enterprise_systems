<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

$currentFile = strtolower(basename($_SERVER['SCRIPT_NAME']));
$adminBase   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$appBase     = rtrim(dirname($adminBase), '/\\');

if (!function_exists('isActiveAny')) {
    function isActiveAny(array $files, string $currentFile): string {
        return in_array(strtolower($currentFile), array_map('strtolower', $files), true) ? 'active' : '';
    }
}
?>

<style>
:root {
    --sb-bg: #f8fcfc;
    --sb-border: #dbe7e7;
    --sb-text: #334155;
    --sb-muted: #64748b;
    --sb-hover: #ecfeff;
    --sb-active-1: #2ec4b6;
    --sb-active-2: #1fa68e;
    --sb-white: #ffffff;
}

.ecot-sidebar {
    width: 264px;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, #fbfefe 0%, var(--sb-bg) 100%);
    border-right: 1px solid var(--sb-border);
    box-shadow: 0 8px 30px rgba(15, 23, 42, .08);
    z-index: 1000;
    font-family: "Inter", "Segoe UI", Tahoma, sans-serif;
}

.ecot-sidebar .logo {
    padding: 22px 18px 14px;
    border-bottom: 1px solid var(--sb-border);
}

.ecot-sidebar .logo h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: .2px;
    color: #0f766e;
}

.ecot-sidebar .logo small {
    display: block;
    margin-top: 4px;
    color: var(--sb-muted);
    font-size: .78rem;
}

.ecot-sidebar nav {
    padding: 12px 10px;
}

.ecot-sidebar ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.ecot-sidebar li {
    margin: 6px 0;
}

.ecot-sidebar a {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: var(--sb-text);
    font-size: .94rem;
    font-weight: 600;
    padding: 10px 12px;
    border-radius: 12px;
    transition: all .18s ease;
}

.ecot-sidebar a i {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e6fffb;
    color: #0f766e;
    font-size: .85rem;
    transition: all .18s ease;
}

.ecot-sidebar a:hover {
    background: var(--sb-hover);
    color: #0f172a;
    transform: translateX(2px);
}

.ecot-sidebar a.active {
    background: linear-gradient(135deg, var(--sb-active-1), var(--sb-active-2));
    color: var(--sb-white);
    box-shadow: 0 8px 18px rgba(31, 166, 142, .30);
}

.ecot-sidebar a.active i {
    background: rgba(255, 255, 255, .22);
    color: #ffffff;
}

.ecot-sidebar .logout-link:hover {
    background: #fff1f2;
    color: #be123c;
}

.ecot-sidebar .logout-link:hover i {
    background: #ffe4e6;
    color: #be123c;
}

/* Common content wrappers */
.main-content,
.dashboard-container,
.admin-content,
.content-wrapper {
    margin-left: 264px;
}

/* Mobile */
@media (max-width: 992px) {
    .ecot-sidebar {
        width: 82px;
    }

    .ecot-sidebar .logo h2,
    .ecot-sidebar .logo small,
    .ecot-sidebar a span {
        display: none;
    }

    .ecot-sidebar a {
        justify-content: center;
        padding: 10px;
    }

    .main-content,
    .dashboard-container,
    .admin-content,
    .content-wrapper {
        margin-left: 82px;
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
                <a class="logout-link" href="<?php echo htmlspecialchars($appBase . '/process/logout.php'); ?>">
                    <i class="fas fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</div>