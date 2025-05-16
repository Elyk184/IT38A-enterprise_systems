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
        // Check if user is inactive
        $stmt = $conn->prepare("SELECT active FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_POST['user_id']);
        $stmt->execute();
        $isActive = $stmt->fetchColumn();

        if ($isActive) {
            $_SESSION['error'] = "Cannot delete active users. Deactivate the user first.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
            $stmt->bindParam(':id', $_POST['user_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "User deleted successfully.";
            } else {
                $_SESSION['error'] = "Unable to delete user.";
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

<div class="admin-header">
    <div class="admin-title">
        <h1>Manage Users</h1>
    </div>
    <div class="admin-nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="Manage_Products.php"><i class="fas fa-box"></i> Products</a>
        <a href="Orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="Manage_Users.php" class="active"><i class="fas fa-users"></i> Users</a>
        <a href="../process/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                    <th>Actions</th>
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
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <?php if (!$user['active']): ?>
                                    <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this inactive user?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.user-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card i {
    font-size: 2rem;
    color: #1976d2;
}

.stat-info h3 {
    margin: 0;
    font-size: 0.875rem;
    color: #666;
}

.stat-info p {
    margin: 0.25rem 0 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
}

.filter-section {
    margin-bottom: 1.5rem;
}

.status-filter {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-filter label {
    font-weight: 500;
    color: #333;
}

.status-filter select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    cursor: pointer;
}

.role-select {
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    cursor: pointer;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.role-badge.admin {
    background-color: #e3f2fd;
    color: #1976d2;
}

.status-btn {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color 0.3s;
}

.status-btn.active {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.status-btn.inactive {
    background-color: #ffebee;
    color: #c62828;
}

.inline-form {
    display: inline;
}

.delete-form {
    display: inline;
}

.delete-btn {
    background-color: #ffebee;
    color: #d32f2f;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.delete-btn:hover {
    background-color: #ffcdd2;
}

.inactive-user {
    background-color: #f5f5f5;
    color: #757575;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.active {
    background-color: #e8f5e9;
    color: #2e7d32;
}
</style>

</body>
</html> 