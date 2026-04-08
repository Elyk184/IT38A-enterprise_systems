<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// Get status filter from URL parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    try {
        $userId = (int)($_POST['user_id'] ?? 0);

        // prevent deleting self
        if ($userId === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = "You cannot delete your own account.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role != 'admin' AND id != :current_user");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':current_user', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "User deleted successfully.";
            } else {
                $_SESSION['error'] = "Unable to delete user (admin or invalid user).";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }

    header("Location: Manage_Users.php" . ($status_filter !== 'all' ? "?status=$status_filter" : ""));
    exit();
}

// Handle user activation/deactivation
if (isset($_POST['toggle_active'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET active = NOT active WHERE id = :id AND id != :current_user");
        $stmt->bindParam(':id', $_POST['user_id']);
        $stmt->bindParam(':current_user', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "User status updated successfully.";
        } else {
            $_SESSION['error'] = "Unable to update user status.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating user status: " . $e->getMessage();
    }
    header("Location: Manage_Users.php" . ($status_filter !== 'all' ? "?status=$status_filter" : ""));
    exit();
}

// Handle user role update
if (isset($_POST['update_role'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET role = :role WHERE id = :id AND id != :current_user");
        $stmt->bindParam(':role', $_POST['role']);
        $stmt->bindParam(':id', $_POST['user_id']);
        $stmt->bindParam(':current_user', $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "User role updated successfully.";
        } else {
            $_SESSION['error'] = "Unable to update user role.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating user role: " . $e->getMessage();
    }
    header("Location: Manage_Users.php" . ($status_filter !== 'all' ? "?status=$status_filter" : ""));
    exit();
}

// Get all users with status filter
try {
    // First, check if active column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'active'");
    $columnExists = $checkColumn->rowCount() > 0;

    if (!$columnExists) {
        // Add active column if it doesn't exist
        $conn->exec("ALTER TABLE users ADD COLUMN active BOOLEAN DEFAULT TRUE");
    }

    $query = "
        SELECT u.id, u.name, u.email, u.role, u.active, u.created_at,
               COUNT(DISTINCT o.id) as total_orders,
               SUM(o.total_amount) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
    ";
    
    if ($status_filter !== 'all') {
        $query .= " WHERE u.active = " . ($status_filter === 'active' ? 'TRUE' : 'FALSE');
    }
    
    $query .= " GROUP BY u.id, u.name, u.email, u.role, u.active, u.created_at ORDER BY u.created_at DESC";
    
    $stmt = $conn->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching users: " . $e->getMessage();
    $users = [];
}

// Get user counts
try {
    $stmt = $conn->query("SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN active = TRUE THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN active = FALSE THEN 1 ELSE 0 END) as inactive_users
        FROM users WHERE role != 'admin'");
    $user_counts = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user_counts = ['total_users' => 0, 'active_users' => 0, 'inactive_users' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-header">
        <div class="admin-title">
            <h1>Manage Users</h1>
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

        

        <div class="user-stats">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <p><?php echo $user_counts['total_users']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-check"></i>
                <div class="stat-info">
                    <h3>Active Users</h3>
                    <p><?php echo $user_counts['active_users']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-times"></i>
                <div class="stat-info">
                    <h3>Inactive Users</h3>
                    <p><?php echo $user_counts['inactive_users']; ?></p>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <form method="GET" class="status-filter">
                <label for="status">Filter by Status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Users</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Users</option>
                </select>
            </form>
        </div>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="<?php echo $user['active'] ? '' : 'inactive-user'; ?>">
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" onchange="this.form.submit()" class="role-select">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                <?php else: ?>
                                    <span class="role-badge admin">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="toggle_active" class="status-btn <?php echo $user['active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                        <input type="hidden" name="toggle_active" value="1">
                                    </form>
                                <?php else: ?>
                                    <span class="status-badge active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['total_orders']; ?></td>
                            <td>₱<?php echo number_format($user['total_spent'] ?? 0, 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
:root{
    --ui-bg: linear-gradient(180deg, #f7fffe 0%, #eefbf8 100%);
    --card: #ffffff;
    --border: rgba(15,118,110,.10);
    --text: #0f172a;
    --muted: #64748b;
    --primary: #14b8a6;
    --primary-dark: #0f766e;
    --success-bg: #ecfdf5;
    --success-text: #047857;
    --danger-bg: #fef2f2;
    --danger-text: #b91c1c;
    --shadow: 0 12px 30px rgba(15, 23, 42, .08);
}

body{
    background: var(--ui-bg);
}

.admin-content{
    padding: 22px;
}

.admin-header{
    margin: 0 0 14px;
    padding: 16px 20px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(20,184,166,.14), rgba(255,255,255,.96));
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.admin-title h1{
    margin: 0;
    color: var(--primary-dark);
    font-size: 1.6rem;
    font-weight: 800;
}

.dashboard-container{
    margin: 0;
    padding: 0;
}

.user-stats{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}

.stat-card{
    background: var(--card);
    padding: 16px;
    border-radius: 14px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-card i{
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}

.stat-info h3{
    margin: 0;
    font-size: .82rem;
    color: var(--muted);
    font-weight: 700;
}

.stat-info p{
    margin: 2px 0 0;
    font-size: 1.7rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1.1;
}

.filter-section{
    margin: 0 0 14px;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 12px 14px;
    box-shadow: var(--shadow);
}

.status-filter{
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.status-filter label{
    font-weight: 700;
    color: #334155;
    font-size: .9rem;
}

.status-filter select,
.role-select{
    padding: 8px 10px;
    border: 1px solid #dbe7e5;
    border-radius: 10px;
    background: #fff;
    color: #334155;
    font-size: .88rem;
}

.users-table{
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.users-table table{
    width: 100%;
    border-collapse: collapse;
    min-width: 980px;
}

.users-table th,
.users-table td{
    padding: 12px 12px;
    border-bottom: 1px solid #edf3f2;
    text-align: left;
    vertical-align: middle;
}

.users-table th{
    background: #f8fffe;
    color: #475569;
    font-size: .82rem;
    font-weight: 800;
}

.users-table td{
    color: #334155;
    font-size: .9rem;
}

.users-table tbody tr:hover{
    background: #f6fffd;
}

.role-badge{
    padding: 6px 10px;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 800;
}

.role-badge.admin{
    background: #e0f2fe;
    color: #0369a1;
}

.status-btn{
    padding: 6px 10px;
    border: none;
    border-radius: 999px;
    cursor: pointer;
    font-size: .78rem;
    font-weight: 800;
}

.status-btn.active{
    background: var(--success-bg);
    color: var(--success-text);
}

.status-btn.inactive{
    background: var(--danger-bg);
    color: var(--danger-text);
}

.inline-form{
    display: inline;
}

.inactive-user{
    background-color: #fafafa;
    color: #64748b;
}

.status-badge{
    padding: 6px 10px;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 800;
}

.status-badge.active{
    background: var(--success-bg);
    color: var(--success-text);
}

.alert{
    border-radius: 12px;
    padding: 12px 14px;
    margin-bottom: 12px;
    font-weight: 700;
}

.alert.success{
    background: var(--success-bg);
    color: var(--success-text);
    border: 1px solid #a7f3d0;
}

.alert.error{
    background: var(--danger-bg);
    color: var(--danger-text);
    border: 1px solid #fecaca;
}

@media (max-width: 992px){
    .admin-content{ padding: 16px; }
    .users-table{ overflow-x: auto; }
}
</style>

</body>
</html>